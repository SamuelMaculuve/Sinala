<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\Mask;
use App\Support\ReportColumns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['Administrador da Organização', 'Operador'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        config(['services.mobile_payments.url' => 'https://pay.example.test/charge', 'services.mobile_payments.token' => 'secret-token']);
    }

    private function plan(array $attributes = []): Plan
    {
        return Plan::create($attributes + ['name' => 'Pacote', 'slug' => Str::random(8), 'price_mzn' => 1500, 'event_limit' => 5, 'participant_limit' => 2, 'duration_days' => 30, 'user_limit' => 3, 'storage_mb' => 500]);
    }

    private function organization(?Plan $plan = null): array
    {
        $plan ??= $this->plan(['price_mzn' => 0]);
        $org = Organization::create(['uuid' => Str::uuid(), 'name' => 'Org', 'slug' => Str::random(8), 'responsible_name' => 'R', 'email' => Str::random(5).'@example.com']);
        Subscription::create(['organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => 'active', 'starts_at' => today()]);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('Administrador da Organização');

        return [$org->fresh(), $user];
    }

    private function event(Organization $org): Event
    {
        return Event::create(['uuid' => Str::uuid(), 'organization_id' => $org->id, 'name' => 'Formação', 'type' => 'training', 'status' => 'scheduled', 'location' => 'Maputo', 'starts_on' => today(), 'ends_on' => today(), 'public_code' => Str::upper(Str::random(10))]);
    }

    public function test_participant_limit_per_event_is_enforced(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);

        $this->actingAs($user)->post(route('participants.store', $event), ['full_name' => 'Ana', 'phone' => '841111111'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('participants.store', $event), ['full_name' => 'Bruno', 'phone' => '842222222'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('participants.store', $event), ['full_name' => 'Carla', 'phone' => '843333333'])->assertSessionHasErrors('participant');

        $this->assertSame(2, $event->participants()->count());
    }

    public function test_admin_override_raises_participant_limit(): void
    {
        [$org] = $this->organization();
        $org->subscription->update(['participant_limit' => 500]);

        $this->assertSame(500, app(SubscriptionService::class)->participantLimit($org));
    }

    public function test_expired_subscription_redirects_event_creation_to_billing(): void
    {
        [$org, $user] = $this->organization();
        $org->subscription->update(['expires_at' => today()->subDay()]);

        $this->actingAs($user)->get(route('events.create'))->assertRedirect(route('organization.billing'));
    }

    public function test_successful_mobile_payment_activates_package(): void
    {
        Http::fake(['pay.example.test/*' => Http::response(['success' => true, 'transaction_id' => 'TX123'])]);
        [$org, $user] = $this->organization();
        $paid = $this->plan(['name' => 'Profissional', 'event_limit' => 20, 'participant_limit' => 100, 'price_mzn' => 3500]);

        $this->actingAs($user)->post(route('organization.billing.checkout', $paid), ['phone' => '+258 84 123 4567'])
            ->assertRedirect(route('organization.billing'))->assertSessionHasNoErrors();

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret-token') && $request['amount'] == 3500 && $request['phone_number'] === '841234567');
        $subscription = $org->fresh()->subscription;
        $this->assertSame($paid->id, $subscription->plan_id);
        $this->assertSame(today()->addDays(30)->toDateString(), $subscription->expires_at->toDateString());
        $this->assertDatabaseHas('subscription_payments', ['organization_id' => $org->id, 'status' => 'paid', 'provider_reference' => 'TX123', 'subscription_id' => $subscription->id]);
        $this->assertSame(1, $org->subscriptions()->where('status', 'active')->count());
    }

    public function test_failed_mobile_payment_keeps_current_package(): void
    {
        Http::fake(['pay.example.test/*' => Http::response(['detail' => 'Saldo insuficiente'], 422)]);
        [$org, $user] = $this->organization();
        $current = $org->subscription->plan_id;
        $paid = $this->plan();

        $this->actingAs($user)->post(route('organization.billing.checkout', $paid), ['phone' => '851234567'])->assertSessionHasErrors('payment');

        $this->assertSame($current, $org->fresh()->subscription->plan_id);
        $this->assertDatabaseHas('subscription_payments', ['status' => 'failed', 'error_message' => 'Saldo insuficiente']);
    }

    public function test_phone_must_start_with_84_or_85_and_have_nine_digits(): void
    {
        Http::fake();
        [, $user] = $this->organization();
        $paid = $this->plan();

        foreach (['821234567', '84123456', '8412345678'] as $phone) {
            $this->actingAs($user)->post(route('organization.billing.checkout', $paid), ['phone' => $phone])->assertSessionHasErrors('phone');
        }
        Http::assertNothingSent();
    }

    public function test_super_admin_can_confirm_pending_payment(): void
    {
        [$org] = $this->organization();
        $paid = $this->plan(['name' => 'Anual', 'duration_days' => 365]);
        $payment = SubscriptionPayment::create(['uuid' => Str::uuid(), 'organization_id' => $org->id, 'plan_id' => $paid->id, 'amount_mzn' => 1500, 'phone' => '841234567', 'status' => 'pending']);
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($admin)->post(route('admin.payments.confirm', $payment))->assertSessionHasNoErrors();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame($paid->id, $org->fresh()->subscription->plan_id);
    }

    public function test_super_admin_pages_render_and_are_protected(): void
    {
        [$org, $user] = $this->organization();
        $admin = User::factory()->create(['is_super_admin' => true]);

        foreach ([route('admin.dashboard'), route('admin.organizations.index'), route('admin.organizations.show', $org), route('admin.payments.index'), route('admin.plans.index')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_suspended_organization_loses_access(): void
    {
        [$org, $user] = $this->organization();
        $admin = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($admin)->put(route('admin.organizations.status', $org), ['status' => 'suspended', 'suspended_reason' => 'Pagamento em atraso']);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_admin_can_assign_custom_package(): void
    {
        [$org] = $this->organization();
        $plan = $this->plan(['is_public' => false]);
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($admin)->post(route('admin.organizations.assign-plan', $org), ['plan_id' => $plan->id, 'event_limit' => 40, 'participant_limit' => 250])->assertSessionHasNoErrors();

        $usage = app(SubscriptionService::class)->usage($org->fresh());
        $this->assertSame(40, $usage['limit']);
        $this->assertSame(250, $usage['participant_limit']);
    }

    public function test_tables_mask_contacts_and_billing_page_renders(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);
        $participant = Participant::create(['uuid' => Str::uuid(), 'organization_id' => $org->id, 'full_name' => 'Ana Sitoe', 'phone' => '841234567']);
        $event->participants()->attach($participant);

        $this->actingAs($user)->get(route('organization.participants'))->assertOk()->assertDontSee('841234567')->assertSee('84•••••67');
        $this->actingAs($user)->get(route('events.show', $event))->assertOk()->assertDontSee('841234567');
        $this->actingAs($user)->get(route('organization.billing'))->assertOk();
    }

    public function test_report_columns_follow_organization_choice(): void
    {
        $participant = new Participant(['full_name' => 'Ana', 'phone' => '841234567', 'company' => 'CIES', 'sex' => 'female']);

        $this->assertSame(['sex', 'company', 'phone'], array_keys(ReportColumns::for([], 'attendance')));
        $this->assertSame(['company'], array_keys(ReportColumns::for(['report_columns' => ['attendance' => ['company']]], 'attendance')));
        $this->assertSame('84•••••67', ReportColumns::value($participant, 'phone', true));
        $this->assertSame('Feminino', ReportColumns::value($participant, 'sex', true));
        $this->assertSame('an•••@mail.com', Mask::email('ana@mail.com'));
    }

    public function test_document_settings_save_report_columns(): void
    {
        [$org, $user] = $this->organization();

        $this->actingAs($user)->put(route('organization.documents.update'), [
            'report_columns' => ['attendance' => ['company', 'position'], 'payment' => ['phone']],
            'mask_sensitive_in_reports' => '1',
        ])->assertSessionHasNoErrors();

        $settings = $org->fresh()->report_settings;
        $this->assertSame(['company', 'position'], $settings['report_columns']['attendance']);
        $this->assertSame(['phone'], $settings['report_columns']['payment']);
        $this->assertTrue($settings['mask_sensitive_in_reports']);
    }
}
