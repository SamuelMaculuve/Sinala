<?php

namespace Tests\Feature;

use App\Models\{AttendanceRecord,Event,Organization,Participant,ParticipantPayment,PaymentList,Plan,Subscription,User};
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Database\Seeders\SinalaSeeder;
use Tests\TestCase;

class SaaSCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['Administrador da Organização','Gestor de Eventos','Operador'] as $role) Role::firstOrCreate(['name'=>$role,'guard_name'=>'web']);
    }

    private function organization(string $name='Organização A', int $limit=15): array
    {
        $plan=Plan::create(['name'=>'Teste','slug'=>Str::slug($name).Str::random(3),'event_limit'=>$limit,'user_limit'=>5,'storage_mb'=>500]);
        $org=Organization::create(['uuid'=>Str::uuid(),'name'=>$name,'slug'=>Str::slug($name).Str::random(3),'responsible_name'=>'Responsável','email'=>Str::random(5).'@example.com']);
        Subscription::create(['organization_id'=>$org->id,'plan_id'=>$plan->id,'status'=>'active','starts_at'=>today()]);
        $user=User::factory()->create(['organization_id'=>$org->id]); $user->assignRole('Administrador da Organização');
        return [$org,$user];
    }

    private function event(Organization $org, string $name='Evento'): Event
    {
        return Event::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'name'=>$name,'type'=>'training','status'=>'scheduled','location'=>'Maputo','starts_on'=>today(),'ends_on'=>today(),'public_code'=>Str::upper(Str::random(10))]);
    }

    public function test_organization_cannot_view_another_organizations_event(): void
    {
        [$a,$userA]=$this->organization(); [$b]=$this->organization('Organização B'); $event=$this->event($b);
        $this->actingAs($userA)->get(route('events.show',$event))->assertForbidden();
    }

    public function test_free_plan_cannot_create_eleventh_event(): void
    {
        [$org]=$this->organization(limit:10); for($i=1;$i<=10;$i++) $this->event($org,"Evento $i");
        $usage = app(SubscriptionService::class)->usage($org);
        $this->assertFalse($usage['can_create']);
        $this->assertSame(10, $usage['used']);
        $this->assertSame(10, $usage['limit']);
        $this->assertSame(0, $usage['remaining']);
    }

    public function test_free_plan_includes_attendance_and_payments(): void
    {
        $this->seed(SinalaSeeder::class);
        $plan = Plan::where('slug', 'free')->firstOrFail();

        $this->assertContains('attendance', $plan->features);
        $this->assertContains('payments', $plan->features);
    }

    public function test_paid_payment_cannot_be_confirmed_twice(): void
    {
        [$org,$user]=$this->organization(); $event=$this->event($org); $participant=Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'João Manuel']);
        $list=PaymentList::create(['uuid'=>Str::uuid(),'event_id'=>$event->id,'name'=>'Transporte','type'=>'transport','default_amount'=>1500,'currency'=>'MZN','payment_date'=>today()]);
        $payment=ParticipantPayment::create(['uuid'=>Str::uuid(),'payment_list_id'=>$list->id,'participant_id'=>$participant->id,'amount'=>1500,'status'=>'paid','paid_at'=>now(),'confirmed_by'=>$user->id]);
        $this->actingAs($user)->post(route('payments.confirm',$payment),['signature'=>'ignored'])->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payment_signatures',0);
    }

    public function test_payment_list_uses_only_attendance_list_and_requires_signature(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);
        $eligible = Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Participante elegível']);
        Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Fora da lista']);
        $event->participants()->attach($eligible->id, ['status' => 'pending']);

        $this->actingAs($user)->post(route('payments.lists.store', $event), [
            'name'=>'Subsídio de transporte','type'=>'Transporte','default_amount'=>1500,'currency'=>'MZN','payment_date'=>today()->format('Y-m-d'),
        ])->assertRedirect();

        $list = PaymentList::firstOrFail();
        $this->assertCount(1, $list->payments);
        $this->assertSame($eligible->id, $list->payments->first()->participant_id);
        $this->get(route('payments.lists.show', $list))->assertOk()->assertSee('Confirmar e assinar')->assertSee('Participante elegível')->assertDontSee('Fora da lista');

        $payment = $list->payments->first();
        $this->post(route('payments.confirm', $payment), ['signature'=>''])->assertSessionHasErrors('signature');
        $this->post(route('payments.confirm', $payment), ['signature'=>'data:image/png;base64,'.base64_encode(str_repeat('a', 101))])->assertSessionHas('success');
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('payment_signatures', ['participant_payment_id' => $payment->id]);
    }

    public function test_attendance_signature_marks_participant_present_and_duplicate_submit_keeps_status(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);
        $day = $event->days()->create(['date' => today()]);
        $participant = Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Maria Presente']);
        $event->participants()->attach($participant->id, ['status' => 'pending']);

        $payload = [
            'participant_id' => $participant->id,
            'event_day_id' => $day->id,
            'type' => 'check_in',
            'signature' => 'data:image/png;base64,'.base64_encode(str_repeat('a', 101)),
        ];

        $this->actingAs($user)
            ->post(route('attendance.store', $event), $payload)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'event_day_id' => $day->id,
            'participant_id' => $participant->id,
            'type' => 'check_in',
            'status' => 'present',
        ]);
        $this->assertSame('present', $event->participants()->whereKey($participant->id)->firstOrFail()->pivot->status);

        $this->post(route('attendance.store', $event), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('attendance');

        $this->assertSame(1, AttendanceRecord::count());
        $this->assertSame('present', $event->participants()->whereKey($participant->id)->firstOrFail()->pivot->status);
    }

    public function test_duplicate_attendance_can_be_updated_when_user_confirms_replacement(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);
        $day = $event->days()->create(['date' => today()]);
        $participant = Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Pessoa Actualizada']);
        $event->participants()->attach($participant->id, ['status' => 'pending']);

        $payload = [
            'participant_id' => $participant->id,
            'event_day_id' => $day->id,
            'type' => 'check_in',
            'signature' => 'data:image/png;base64,'.base64_encode(str_repeat('a', 101)),
        ];

        $this->actingAs($user)
            ->post(route('attendance.store', $event), $payload)
            ->assertSessionHas('success');

        $record = AttendanceRecord::firstOrFail();
        $firstRecordedAt = $record->recorded_at;

        $this->post(route('attendance.store', $event), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('attendance');

        $this->post(route('attendance.store', $event), $payload + ['replace_existing' => '1'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Presença actualizada com sucesso.');

        $this->assertSame(1, AttendanceRecord::count());
        $this->assertDatabaseHas('attendance_signatures', ['attendance_record_id' => $record->id]);
        $this->assertTrue($record->fresh()->recorded_at->greaterThanOrEqualTo($firstRecordedAt));
        $this->assertSame('present', $event->participants()->whereKey($participant->id)->firstOrFail()->pivot->status);
    }

    public function test_event_can_sync_participant_statuses_from_existing_attendance_records(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);
        $day = $event->days()->create(['date' => today()]);
        $participant = Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Pessoa Corrigida']);
        $event->participants()->attach($participant->id, ['status' => 'pending']);

        AttendanceRecord::create([
            'uuid' => Str::uuid(),
            'event_id' => $event->id,
            'event_day_id' => $day->id,
            'participant_id' => $participant->id,
            'type' => 'check_in',
            'status' => 'present',
            'recorded_at' => now(),
            'recorded_by' => $user->id,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($user)
            ->post(route('events.sync-attendance-statuses', $event))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('present', $event->participants()->whereKey($participant->id)->firstOrFail()->pivot->status);
    }

    public function test_authenticated_dashboard_and_event_pages_render(): void
    {
        [$org,$user]=$this->organization(); $event=$this->event($org);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Eventos recentes')->assertSee('brand/sinala-logo-transparent.png');
        $this->get(route('events.index'))->assertOk()->assertSee($event->name);
        $this->get(route('events.show',$event))->assertOk()->assertSee('Participantes');
    }

    public function test_organization_navigation_modules_render(): void
    {
        [$org, $user] = $this->organization();
        $this->event($org);

        $this->actingAs($user)->get(route('organization.participants'))->assertOk()->assertSee('Pessoas registadas');
        $this->get(route('organization.attendance'))->assertOk()->assertSee('Registos de presença');
        $this->get(route('organization.payments'))->assertOk()->assertSee('Pagamentos e subsídios');
        $this->get(route('organization.reports'))->assertOk()->assertSee('Resumo dos eventos');
    }

    public function test_participants_and_payments_can_be_filtered_by_event_server_side(): void
    {
        [$org, $user] = $this->organization();
        $eventA = $this->event($org, 'Evento Alfa');
        $eventB = $this->event($org, 'Evento Beta');
        $participantA = Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Pessoa Alfa']);
        $participantB = Participant::create(['uuid'=>Str::uuid(),'organization_id'=>$org->id,'full_name'=>'Pessoa Beta']);
        $eventA->participants()->attach($participantA->id, ['status'=>'pending']);
        $eventB->participants()->attach($participantB->id, ['status'=>'pending']);
        $listA = PaymentList::create(['uuid'=>Str::uuid(),'event_id'=>$eventA->id,'name'=>'Lista Alfa','type'=>'Transporte','default_amount'=>100,'currency'=>'MZN','payment_date'=>today()]);
        $listB = PaymentList::create(['uuid'=>Str::uuid(),'event_id'=>$eventB->id,'name'=>'Lista Beta','type'=>'Transporte','default_amount'=>100,'currency'=>'MZN','payment_date'=>today()]);
        ParticipantPayment::create(['uuid'=>Str::uuid(),'payment_list_id'=>$listA->id,'participant_id'=>$participantA->id,'amount'=>100]);
        ParticipantPayment::create(['uuid'=>Str::uuid(),'payment_list_id'=>$listB->id,'participant_id'=>$participantB->id,'amount'=>100]);

        $this->actingAs($user)->get(route('organization.participants', ['event'=>$eventA->id, 'per_page'=>10]))->assertOk()->assertSee('Pessoa Alfa')->assertDontSee('Pessoa Beta');
        $this->get(route('organization.payments', ['event'=>$eventA->id, 'per_page'=>10]))->assertOk()->assertSee('Lista Alfa')->assertDontSee('Lista Beta');
    }

    public function test_event_documents_can_be_exported_as_pdf(): void
    {
        [$org, $user] = $this->organization();
        $event = $this->event($org);
        $event->days()->create(['date' => today()]);
        $list = PaymentList::create(['uuid'=>Str::uuid(),'event_id'=>$event->id,'name'=>'Transporte','type'=>'Subsídio de transporte','default_amount'=>1500,'currency'=>'MZN','payment_date'=>today()]);

        $this->actingAs($user)->get(route('exports.attendance', $event))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('exports.payment', $list))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_organization_admin_can_configure_its_document_header(): void
    {
        [$org, $admin] = $this->organization();
        $signatory = User::factory()->create(['organization_id' => $org->id]);
        $signatory->assignRole('Gestor de Eventos');

        $this->actingAs($admin)->put(route('organization.documents.update'), [
            'header_title' => 'Cabeçalho da Organização A',
            'header_subtitle' => 'Programa de formação',
            'project_name' => 'Projecto Inclusivo',
            'funding_reference' => 'REF-2026',
            'footer_note' => 'Documento emitido electronicamente.',
            'signatory_user_ids' => [$admin->id, $signatory->id],
        ])->assertRedirect()->assertSessionHas('success');

        $settings = $org->fresh()->report_settings;
        $this->assertSame('Cabeçalho da Organização A', $settings['header_title']);
        $this->assertSame([$admin->id, $signatory->id], $settings['signatory_user_ids']);
    }

    public function test_only_super_admin_can_configure_plan_features(): void
    {
        [$org,$ordinary]=$this->organization(); $plan=$org->subscription->plan;
        $this->actingAs($ordinary)->get(route('admin.plans.index'))->assertForbidden();
        $admin=User::factory()->create(['organization_id'=>null,'is_super_admin'=>true]);
        $this->actingAs($admin)->put(route('admin.plans.update',$plan),['name'=>'Plano Actualizado','slug'=>$plan->slug,'price_mzn'=>4200,'event_limit'=>30,'user_limit'=>8,'storage_mb'=>20480,'monthly_event_limit'=>1,'active'=>1,'features'=>['attendance','payments','pdf']])->assertRedirect();
        $plan->refresh(); $this->assertSame(4200,$plan->price_mzn); $this->assertSame(['attendance','payments','pdf'],$plan->features);
    }

    public function test_initial_cies_organization_and_accounts_are_created(): void
    {
        Role::query()->delete();
        $this->seed(SinalaSeeder::class);
        $cies=Organization::where('slug','cies')->firstOrFail();
        $this->assertSame('CIES - Centro Informazione e Educazione allo Sviluppo',$cies->name);
        $this->assertSame(2,$cies->users()->count());
        $this->assertSame('organization-headers/cies-header.png',$cies->report_settings['header_banner_path']);
        $this->assertSame(3,User::where('is_super_admin',true)->count());
        $this->assertTrue(User::where('email','samuelmaculuve8@gmail.com')->firstOrFail()->hasRole('Super Administrador'));
        $this->assertSame($cies->id,User::where('email','digit.coordination@cies.it')->value('organization_id'));
    }

    public function test_login_destination_is_always_selected_by_account_type(): void
    {
        $this->seed(SinalaSeeder::class);
        $this->withSession(['url.intended'=>route('admin.plans.index')])->post(route('login'),['email'=>'digit.coordination@cies.it','password'=>'Cies@2026!'])->assertRedirect(route('dashboard'));
        $this->post(route('logout'));
        $this->withSession(['url.intended'=>route('dashboard')])->post(route('login'),['email'=>'samuelmaculuve8@gmail.com','password'=>'Admin@2026!'])->assertRedirect(route('admin.plans.index'));
        $this->get(route('dashboard'))->assertRedirect(route('admin.plans.index'));
    }
}
