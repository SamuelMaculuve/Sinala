<?php

namespace App\Http\Controllers;

use App\Models\{Organization,Plan,Subscription,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB,Hash};
use Illuminate\Support\Str;

class AuthController extends Controller {
    public function showLogin(){return view('auth.login');}
    public function login(Request $r){$data=$r->validate(['email'=>'required|email','password'=>'required']); if(!Auth::attempt($data,$r->boolean('remember'))) return back()->withErrors(['email'=>'Credenciais inválidas.'])->onlyInput('email'); $r->session()->regenerate(); $r->session()->forget('url.intended'); return $r->user()->is_super_admin ? redirect()->route('admin.plans.index') : redirect()->route('dashboard');}
    public function showRegister(){return view('auth.register');}
    public function register(Request $r){$data=$r->validate(['organization_name'=>'required|max:150','nuit'=>'nullable|max:30','province'=>'nullable|max:80','district'=>'nullable|max:80','country'=>'required|max:80','responsible_name'=>'required|max:150','email'=>'required|email|unique:users','phone'=>'nullable|max:30','password'=>'required|min:8|confirmed']); $user=DB::transaction(function()use($data){$org=Organization::create(['uuid'=>Str::uuid(),'name'=>$data['organization_name'],'slug'=>Str::slug($data['organization_name']).'-'.Str::lower(Str::random(5)),'nuit'=>$data['nuit']??null,'province'=>$data['province']??null,'district'=>$data['district']??null,'country'=>$data['country'],'responsible_name'=>$data['responsible_name'],'email'=>$data['email'],'phone'=>$data['phone']??null]); $plan=Plan::where('slug','free')->firstOrFail(); Subscription::create(['organization_id'=>$org->id,'plan_id'=>$plan->id,'status'=>'active','starts_at'=>today(),'amount_mzn'=>0]); $user=User::create(['organization_id'=>$org->id,'name'=>$data['responsible_name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'password'=>Hash::make($data['password'])]); $user->assignRole('Administrador da Organização'); return $user;}); Auth::login($user); return redirect('/dashboard');}
    public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
}
