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
        $plans=Plan::withCount(['subscriptions as active_subscriptions_count'=>fn($q)=>$q->whereIn('status',['active','trial'])])->orderBy('sort_order')->orderBy('price_mzn')->get(); return view('admin.plans.index',compact('plans'));
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
        $data=$request->validate([
            'name'=>'required|max:100',
            'slug'=>'required|alpha_dash|max:100|unique:plans,slug,'.($plan?->id ?? 'NULL'),
            'description'=>'nullable|max:500',
            'price_mzn'=>'required|integer|min:0',
            'event_limit'=>'required|integer|min:1',
            'participant_limit'=>'nullable|integer|min:1',
            'duration_days'=>'nullable|integer|min:1|max:3650',
            'user_limit'=>'required|integer|min:1',
            'storage_mb'=>'required|integer|min:1',
            'sort_order'=>'nullable|integer|min:0',
            'monthly_event_limit'=>'nullable|boolean',
            'active'=>'nullable|boolean',
            'is_public'=>'nullable|boolean',
            'features'=>'nullable|array',
            'features.*'=>'string|in:'.implode(',',array_keys(Plan::FEATURES)),
        ]);
        $data['monthly_event_limit']=$request->boolean('monthly_event_limit');
        $data['active']=$request->boolean('active');
        $data['is_public']=$request->boolean('is_public');
        $data['sort_order']=(int)($data['sort_order']??0);
        $data['participant_limit']=$data['participant_limit']??null;
        $data['duration_days']=$data['duration_days']??null;
        $data['features']=array_values($data['features']??[]);

        return $data;
    }
}
