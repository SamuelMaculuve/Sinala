<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: safe to run on the production SQLite database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $t) {
            // Maximum participants (signatures) per event. Null = unlimited.
            $t->unsignedInteger('participant_limit')->nullable()->after('event_limit');
            // How long a purchased package stays valid. Null = no expiry.
            $t->unsignedInteger('duration_days')->nullable()->after('participant_limit');
            $t->string('description', 500)->nullable()->after('slug');
            // Visible to clients on the subscription page.
            $t->boolean('is_public')->default(true)->after('active');
            $t->unsignedInteger('sort_order')->default(0)->after('is_public');
        });

        Schema::table('subscriptions', function (Blueprint $t) {
            // Per-organization overrides set by the platform admin. Null = use the plan value.
            $t->unsignedInteger('event_limit')->nullable()->after('plan_id');
            $t->unsignedInteger('participant_limit')->nullable()->after('event_limit');
            $t->text('notes')->nullable()->after('transaction_reference');
        });

        Schema::table('organizations', function (Blueprint $t) {
            $t->string('status')->default('active')->after('report_settings');
            $t->string('suspended_reason', 500)->nullable()->after('status');
            $t->text('admin_notes')->nullable()->after('suspended_reason');
        });

        Schema::create('subscription_payments', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('amount_mzn');
            $t->string('phone', 20);
            $t->string('method')->default('mobile');
            $t->string('status')->default('pending');
            $t->string('provider_reference')->nullable();
            $t->string('error_message', 500)->nullable();
            $t->json('provider_response')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['organization_id', 'status']);
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::table('organizations', fn (Blueprint $t) => $t->dropColumn(['status', 'suspended_reason', 'admin_notes']));
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn(['event_limit', 'participant_limit', 'notes']));
        Schema::table('plans', fn (Blueprint $t) => $t->dropColumn(['participant_limit', 'duration_days', 'description', 'is_public', 'sort_order']));
    }
};
