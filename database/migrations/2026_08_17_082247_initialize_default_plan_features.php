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
        $features=[
            'free'=>['attendance','signatures','pdf','payments'],
            'professional'=>['attendance','signatures','pdf','excel','payments','check_out','multi_day','qr_code','imports','advanced_reports','priority_support'],
            'organization'=>['attendance','signatures','pdf','excel','payments','check_out','multi_day','qr_code','imports','advanced_reports','projects','donors','audit','custom_documents','priority_support'],
        ];
        foreach($features as $slug=>$items){DB::table('plans')->where('slug',$slug)->where(fn($q)=>$q->whereNull('features')->orWhere('features','[]'))->update(['features'=>json_encode($items),'updated_at'=>now()]);}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não revertemos configurações que podem ter sido editadas pelo administrador.
    }
};
