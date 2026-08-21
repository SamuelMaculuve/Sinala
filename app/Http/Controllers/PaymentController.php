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

    public function selectEvent(Request $request)
    {
        $organization = $request->user()->organization;
        $events = $organization->events()->withCount('participants')->orderByDesc('starts_on')->paginate(12);

        return view('payments.select-event', compact('events'));
    }

    public function create(Request $request, Event $event)
    {
        $this->authorize('managePayments', $event);
        $participants = $event->participants()->orderBy('full_name')->get();
        $presentIds = $event->attendanceRecords()->where('type', 'check_in')->pluck('participant_id')->unique();

        return view('payments.create', compact('event', 'participants', 'presentIds'));
    }

    public function storeList(Request $request, Event $event)
    {
        $this->authorize('managePayments', $event);
        $data = $request->validate(['name'=>'required|max:180','type'=>'required|max:100','description'=>'nullable|max:1000','default_amount'=>'required|numeric|min:0','currency'=>'required|in:MZN,USD,EUR,ZAR','payment_date'=>'required|date','cost_center'=>'nullable|max:100','participant_ids'=>'required|array|min:1','participant_ids.*'=>'integer']);

        // Apenas participantes efectivamente marcados como presentes (seleccionados nesta lista) são elegíveis.
        $participantIds = $event->participants()->whereIn('participants.id', $data['participant_ids'])->pluck('participants.id')->unique();
        abort_if($participantIds->count() !== collect($data['participant_ids'])->unique()->count(), 422, 'Um ou mais participantes não pertencem a este evento.');

        $list = DB::transaction(function () use ($event, $data, $participantIds) {
            $list = $event->paymentLists()->create(collect($data)->except('participant_ids')->toArray() + ['uuid' => (string) Str::uuid()]);
            $participantIds->each(fn ($id) => $list->payments()->create([
                'uuid' => (string) Str::uuid(),
                'participant_id' => $id,
                'amount' => $data['default_amount'],
                'status' => 'pending',
            ]));
            return $list;
        });

        return redirect()->route('payments.lists.show', $list)->with('success', 'Lista criada com os participantes marcados como presentes. Cada pagamento requer assinatura.');
    }

    public function destroyList(PaymentList $paymentList)
    {
        $paymentList->load('event');
        $event = $paymentList->event;
        $this->authorize('managePayments', $event);
        abort_if($paymentList->payments()->where('status', 'paid')->exists(), 422, 'Não é possível apagar uma lista com pagamentos já confirmados.');

        $paymentList->delete();

        return redirect()->route('events.show', $event)->with('success', 'Lista de pagamento removida. Pode criar uma nova a qualquer momento.');
    }

    public function updateAmount(Request $request, ParticipantPayment $payment)
    {
        $payment->load('paymentList.event', 'participant');
        $event = $payment->paymentList->event;
        $this->authorize('managePayments', $event);
        abort_if($payment->status === 'paid', 422, 'Não é possível alterar o valor de um pagamento já confirmado.');

        $data = $request->validate(['amount' => 'required|numeric|min:0']);
        $payment->update(['amount' => $data['amount']]);

        return back()->with('success', 'Valor de '.$payment->participant->full_name.' actualizado.');
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
