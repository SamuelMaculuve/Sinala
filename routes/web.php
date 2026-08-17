<?php

use App\Http\Controllers\{AttendanceController,AuthController,DashboardController,DocumentSettingsController,EventController,ExportController,OrganizationModuleController,ParticipantController,PaymentController};
use App\Http\Controllers\Admin\PlanController;
use App\Support\PublicSeo;
use Illuminate\Support\Facades\Route;

Route::get('/',fn()=>view('landing',['structuredData'=>PublicSeo::structuredData()]))->name('home');
Route::get('/robots.txt',fn()=>response("User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /dashboard\nDisallow: /events\nDisallow: /entrar\nDisallow: /registar\n\nUser-agent: OAI-SearchBot\nAllow: /\nDisallow: /admin/\nDisallow: /dashboard\nDisallow: /events\n\nUser-agent: GPTBot\nAllow: /\nDisallow: /admin/\nDisallow: /dashboard\nDisallow: /events\n\nUser-agent: ChatGPT-User\nAllow: /\n\nSitemap: ".url('/sitemap.xml')."\n",200,['Content-Type'=>'text/plain; charset=UTF-8']));
Route::get('/sitemap.xml',fn()=>response('<?xml version="1.0" encoding="UTF-8"?>'.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>'.e(url('/')).'</loc><lastmod>'.now()->toDateString().'</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url></urlset>',200,['Content-Type'=>'application/xml; charset=UTF-8']));
Route::get('/llms.txt',fn()=>response(view('llms'),200,['Content-Type'=>'text/plain; charset=UTF-8']));
Route::middleware('guest')->group(function(){Route::get('/entrar',[AuthController::class,'showLogin'])->name('login');Route::post('/entrar',[AuthController::class,'login']);Route::get('/registar',[AuthController::class,'showRegister'])->name('register');Route::post('/registar',[AuthController::class,'register']);});
Route::middleware('auth')->group(function(){
 Route::post('/sair',[AuthController::class,'logout'])->name('logout');
 Route::middleware('organization')->group(function(){Route::get('/dashboard',DashboardController::class)->name('dashboard');
  Route::get('/participants',[OrganizationModuleController::class,'participants'])->name('organization.participants');
  Route::get('/attendance',[OrganizationModuleController::class,'attendance'])->name('organization.attendance');
  Route::get('/payments',[OrganizationModuleController::class,'payments'])->name('organization.payments');
  Route::get('/reports',[OrganizationModuleController::class,'reports'])->name('organization.reports');
  Route::get('/settings/documents',[DocumentSettingsController::class,'edit'])->name('organization.documents.edit');
  Route::put('/settings/documents',[DocumentSettingsController::class,'update'])->name('organization.documents.update');
  Route::get('/settings/documents/logo',[DocumentSettingsController::class,'logo'])->name('organization.documents.logo');
  Route::get('/settings/documents/header',[DocumentSettingsController::class,'headerBanner'])->name('organization.documents.header');
  Route::resource('events',EventController::class); Route::post('/events/{event}/participants',[ParticipantController::class,'store'])->name('participants.store'); Route::delete('/events/{event}/participants/{participant}',[ParticipantController::class,'destroy'])->name('participants.destroy');
  Route::get('/events/{event}/kiosk',[AttendanceController::class,'kiosk'])->name('attendance.kiosk'); Route::post('/events/{event}/attendance',[AttendanceController::class,'store'])->name('attendance.store');
  Route::get('/events/{event}/exports/attendance.pdf',[ExportController::class,'attendance'])->name('exports.attendance');
  Route::get('/payment-lists/{paymentList}/export.pdf',[ExportController::class,'payment'])->name('exports.payment');
  Route::get('/payment-lists/{paymentList}',[PaymentController::class,'showList'])->name('payments.lists.show');
  Route::post('/events/{event}/payment-lists',[PaymentController::class,'storeList'])->name('payments.lists.store'); Route::post('/payments/{payment}/confirm',[PaymentController::class,'confirm'])->name('payments.confirm');});
});
Route::middleware(['auth','super.admin'])->prefix('admin')->name('admin.')->group(function(){Route::resource('plans',PlanController::class);});
