<?php

namespace Database\Seeders;

use App\Models\{Organization,Plan,Subscription,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SinalaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach(['Super Administrador','Administrador da Organização','Gestor de Eventos','Operador','Visualizador/Auditor','Coordenador Geral','Coordenadora de campo'] as $role) Role::firstOrCreate(['name'=>$role,'guard_name'=>'web']);
        $plans=[
            ['name'=>'Free','slug'=>'free','price_mzn'=>0,'event_limit'=>10,'user_limit'=>3,'storage_mb'=>500,'monthly_event_limit'=>false,'features'=>['attendance','signatures','pdf','payments']],
            ['name'=>'Profissional','slug'=>'professional','price_mzn'=>3500,'event_limit'=>20,'user_limit'=>5,'storage_mb'=>10240,'monthly_event_limit'=>true,'features'=>['attendance','signatures','pdf','excel','payments','check_out','multi_day','qr_code','imports','advanced_reports','priority_support']],
            ['name'=>'Organização','slug'=>'organization','price_mzn'=>7500,'event_limit'=>100,'user_limit'=>20,'storage_mb'=>51200,'monthly_event_limit'=>true,'features'=>['attendance','signatures','pdf','excel','payments','check_out','multi_day','qr_code','imports','advanced_reports','projects','donors','audit','custom_documents','priority_support']],
        ];
        foreach($plans as $plan) Plan::firstOrCreate(['slug'=>$plan['slug']],$plan);
        $org=Organization::where('slug','cies')->first() ?? Organization::where('slug','fundacao-horizonte')->first();
        if($org){$org->update(['name'=>'CIES - Centro Informazione e Educazione allo Sviluppo','slug'=>'cies','responsible_name'=>'João Gomes','email'=>'digit.coordination@cies.it','country'=>'Moçambique']);}
        else{$org=Organization::create(['uuid'=>Str::uuid(),'name'=>'CIES - Centro Informazione e Educazione allo Sviluppo','slug'=>'cies','responsible_name'=>'João Gomes','email'=>'digit.coordination@cies.it','country'=>'Moçambique']);}
        $ciesHeaderSource=public_path('document-assets/cies-header.png');
        $ciesHeaderPath='organization-headers/cies-header.png';
        if(file_exists($ciesHeaderSource)){
            // Força sempre a imagem do repositório, substituindo qualquer cópia antiga ou parcial no storage.
            Storage::disk('local')->put($ciesHeaderPath,file_get_contents($ciesHeaderSource));
            $reportSettings=$org->report_settings??[];
            $reportSettings['header_banner_path']=$ciesHeaderPath;
            $org->update(['report_settings'=>$reportSettings]);
        }
        Subscription::updateOrCreate(['organization_id'=>$org->id],['plan_id'=>Plan::where('slug','organization')->value('id'),'status'=>'active','starts_at'=>today(),'expires_at'=>today()->addYear(),'amount_mzn'=>7500]);

        $organizationUsers=[
            ['name'=>'Leodemila Zacarias','email'=>'leodemila.zacarias@gmail.com','role'=>'Coordenadora de campo'],
            ['name'=>'João Gomes','email'=>'digit.coordination@cies.it','role'=>'Coordenador Geral'],
        ];
        foreach($organizationUsers as $data){$role=$data['role'];unset($data['role']);$user=User::firstOrCreate(['email'=>$data['email']],$data+['organization_id'=>$org->id,'password'=>Hash::make('Cies@2026!')]);$user->update(['organization_id'=>$org->id,'is_super_admin'=>false]);$user->syncRoles([$role]);}

        $administrators=[
            ['name'=>'Samuel Maculuve','email'=>'samuelmaculuve8@gmail.com'],
            ['name'=>'K. Massango','email'=>'kmassango1@gmail.com'],
            ['name'=>'Edmilson Saiete','email'=>'edmilsonsaiete6@gmail.com'],
        ];
        foreach($administrators as $data){$admin=User::firstOrCreate(['email'=>$data['email']],$data+['password'=>Hash::make('Admin@2026!'),'is_super_admin'=>true]);$admin->update(['organization_id'=>null,'is_super_admin'=>true]);$admin->syncRoles(['Super Administrador']);}

        User::whereIn('email',['demo@sinala.co.mz','admin@sinala.co.mz'])->delete();
    }
}
