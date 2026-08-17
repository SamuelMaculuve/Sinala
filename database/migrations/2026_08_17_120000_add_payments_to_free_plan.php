<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plan = DB::table('plans')->where('slug', 'free')->first();

        if (! $plan) {
            return;
        }

        $features = json_decode($plan->features ?: '[]', true) ?: [];
        $features = array_values(array_unique([...$features, 'attendance', 'payments']));

        DB::table('plans')->where('id', $plan->id)->update([
            'features' => json_encode($features),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Não removemos funcionalidades que possam ter sido configuradas pelo administrador.
    }
};
