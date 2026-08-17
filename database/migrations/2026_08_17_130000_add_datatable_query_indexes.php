<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', fn (Blueprint $table) => $table->index(['event_id', 'recorded_at'], 'attendance_event_recorded_idx'));
        Schema::table('participant_payments', fn (Blueprint $table) => $table->index(['status', 'paid_at'], 'payments_status_paid_idx'));
        Schema::table('payment_lists', fn (Blueprint $table) => $table->index(['event_id', 'payment_date'], 'payment_lists_event_date_idx'));
    }

    public function down(): void
    {
        Schema::table('attendance_records', fn (Blueprint $table) => $table->dropIndex('attendance_event_recorded_idx'));
        Schema::table('participant_payments', fn (Blueprint $table) => $table->dropIndex('payments_status_paid_idx'));
        Schema::table('payment_lists', fn (Blueprint $table) => $table->dropIndex('payment_lists_event_date_idx'));
    }
};
