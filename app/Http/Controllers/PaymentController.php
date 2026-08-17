<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ParticipantPayment;
use App\Models\PaymentList;
use App\Models\PaymentSignature;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function showList(Request $request, PaymentList $paymentList)
    {
        $paymentList->load('event.organization');
        $this->authorize('view', $paymentList->event);
        $query = $paymentList->payments()->with(['participant', 'signature']);
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('search'), fn ($q) => $q->whereHas('participant', fn ($participant) => $participant->where('full_name', 'like', '%'.$request->string('search')->trim().'%')));
        $perPage = in_array($request->integer('per_page'), [10, 20, 50, 100], true) ? $request->integer('per_page') : 20;
        $payments = $query->orderBy('id')->paginate($perPage)->withQueryString();
        $stats = [
            'count' => $paymentList->payments()->count(),
            'confirmed' => $paymentList->payments()->where('status', 'paid')->count(),
            'expected' => $paymentList->payments()->sum('amount'),
            'paid' => $paymentList->payments()->where('status', 'paid')->sum('amount'),
        ];

        return view('payments.show', compact('paymentList', 'payments', 'stats'));
    }

    public function storeList(Request $request, Event $event)
    {
        $this->authorize('managePayments', $event);
        $data = $request->validate(['name'=>'required|max:180','type'=>'required|max:100','description'=>'nullable|max:1000','default_amount'=>'required|numeric|min:0','currency'=>'required|in:MZN,USD,EUR,ZAR','payment_date'=>'required|date','cost_center'=>'nullable|max:100']);

        $list = DB::transaction(function () use ($event, $data) {
            $list = $event->paymentLists()->create($data + ['uuid' => (string) Str::uuid()]);
            // A lista de presença do evento é a fonte única de participantes elegíveis.
            $event->participants()->each(fn ($participant) => $list->payments()->create([
                'uuid' => (string) Str::uuid(),
                'participant_id' => $participant->id,
                'amount' => $data['default_amount'],
                'status' => 'pending',
            ]));
            return $list;
        });

        return redirect()->route('payments.lists.show', $list)->with('success', 'Lista criada com os participantes da lista de presença. Cada pagamento requer assinatura.');
    }

    public function confirm(Request $request, ParticipantPayment $payment, SignatureService $signatures)
    {
        $payment->load('paymentList.event');
        $event = $payment->paymentList->event;
        $this->authorize('managePayments', $event);
        if ($payment->status === 'paid') return back()->withErrors(['payment' => 'Este participante já confirmou o recebimento em '.$payment->paid_at->format('d/m/Y H:i').'.']);

        $data = $request->validate(['signature' => 'required|string']);
        $file = $signatures->store($data['signature'], $event->organization_id, 'payments');
        DB::transaction(function () use ($payment, $file, $request) {
            $payment->update(['status'=>'paid','paid_at'=>now(),'confirmed_by'=>$request->user()->id]);
            PaymentSignature::create(['uuid'=>(string) Str::uuid(),'participant_payment_id'=>$payment->id,'path'=>$file['path'],'sha256'=>$file['sha256'],'ip_address'=>$request->ip()]);
        });

        return back()->with('success', 'Pagamento confirmado com assinatura.');
    }
}
