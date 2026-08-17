<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plans')->where('slug','free')->update(['event_limit'=>10,'user_limit'=>3,'updated_at'=>now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->where('slug','free')->update(['event_limit'=>15,'user_limit'=>1,'updated_at'=>now()]);
    }
};
