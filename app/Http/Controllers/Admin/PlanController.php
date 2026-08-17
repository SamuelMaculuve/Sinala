<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plans=Plan::orderBy('price_mzn')->get(); return view('admin.plans.index',compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.plans.form',['plan'=>new Plan]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $plan=Plan::create($this->validated($request)); return redirect()->route('admin.plans.edit',$plan)->with('success','Plano criado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Plan $plan)
    {
        return redirect()->route('admin.plans.edit',$plan);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plan $plan)
    {
        return view('admin.plans.form',compact('plan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request,$plan)); return back()->with('success','Características do plano actualizadas.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan)
    {
        abort_if($plan->subscriptions()->exists(),422,'Não é possível eliminar um plano com subscrições.'); $plan->delete(); return redirect()->route('admin.plans.index')->with('success','Plano eliminado.');
    }

    private function validated(Request $request,?Plan $plan=null): array
    {
        $data=$request->validate(['name'=>'required|max:100','slug'=>'required|alpha_dash|max:100|unique:plans,slug,'.($plan?->id ?? 'NULL'),'price_mzn'=>'required|integer|min:0','event_limit'=>'required|integer|min:1','user_limit'=>'required|integer|min:1','storage_mb'=>'required|integer|min:1','monthly_event_limit'=>'nullable|boolean','active'=>'nullable|boolean','features'=>'nullable|array','features.*'=>'string|in:attendance,signatures,pdf,excel,payments,check_out,multi_day,qr_code,imports,advanced_reports,projects,donors,audit,custom_documents,priority_support']);
        $data['monthly_event_limit']=$request->boolean('monthly_event_limit'); $data['active']=$request->boolean('active'); $data['features']=array_values($data['features']??[]); return $data;
    }
}
