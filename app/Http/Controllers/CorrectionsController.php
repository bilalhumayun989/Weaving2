<?php
namespace App\Http\Controllers;
use App\Models\{KachiParchi, KachiItem, Payment, YarnPurchase, Thaan,CheckingReport};
use App\Services\{AuditService,LedgerService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CorrectionsController extends Controller
{
    public function voidPayment(Request $request,Payment $payment,LedgerService $ledger)
    {
        AuditService::adminOnly();
        $d=$request->validate(['reason'=>'required|string|min:8|max:1000']);
        DB::transaction(function () use ($payment,$d,$ledger) {
            $p=Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if($p->voided_at) throw ValidationException::withMessages(['reason'=>'Payment already reversed.']);
            if($p->kind==='received') $ledger->post($p->party_id,$p->factory_id,'receivable',today()->toDateString(),
                'Payment reversal: '.($p->reference?:'#'.$p->id),'payment_void',$p->id,(float)$p->amount,0);
            else $ledger->post($p->party_id,$p->factory_id,'payable',today()->toDateString(),
                'Supplier payment reversal: '.($p->reference?:'#'.$p->id),'payment_void',$p->id,0,(float)$p->amount);
            $p->update(['voided_at'=>now(),'void_reason'=>$d['reason']]);
            AuditService::record('payment.reversed',$p,['reason'=>$d['reason'],'amount'=>$p->amount]);
        });
        return back()->with('success','Payment reversed. Original record and reversal remain in the ledger.');
    }
    public function voidPurchase(Request $request,YarnPurchase $purchase,LedgerService $ledger)
    {
        AuditService::adminOnly();
        $d=$request->validate(['reason'=>'required|string|min:8|max:1000']);
        DB::transaction(function () use ($purchase,$d,$ledger) {
            $p=YarnPurchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            if($p->voided_at) throw ValidationException::withMessages(['reason'=>'Purchase already reversed.']);
            if($p->payments()->whereNull('voided_at')->exists()) throw ValidationException::withMessages(['reason'=>'Reverse all payments linked to this purchase before reversing the purchase.']);
            if((float)$p->total>0) $ledger->post($p->party_id,$p->factory_scope==='all'?null:$p->factory_id,'payable',today()->toDateString(),
                'Yarn purchase reversal: '.($p->invoice_no?:'#'.$p->id),'yarn_purchase_void',$p->id,(float)$p->total,0);
            $p->update(['voided_at'=>now(),'void_reason'=>$d['reason']]);
            AuditService::record('purchase.reversed',$p,['reason'=>$d['reason'],'total'=>$p->total]);
        });
        return back()->with('success','Purchase and its payable reversed.');
    }
    public function voidKachi(Request $request,KachiParchi $kachi,LedgerService $ledger)
    {
        AuditService::adminOnly();
        $d=$request->validate(['reason'=>'required|string|min:8|max:1000']);
        DB::transaction(function () use ($kachi,$d,$ledger) {
            $k=KachiParchi::whereKey($kachi->id)->lockForUpdate()->firstOrFail();
            if($k->voided_at || $k->pakki()->exists()) throw ValidationException::withMessages(['reason'=>'Only a Kachi without Pakki can be cancelled once.']);
            $items=$k->items()->lockForUpdate()->get();
            $thaans=Thaan::whereIn('id',$items->pluck('thaan_id'))->lockForUpdate()->get();
            if($thaans->contains(fn ($t)=>$t->status!=='dispatched'))
                throw ValidationException::withMessages(['reason'=>'Thaan status changed; cancellation blocked to protect stock.']);
            foreach($thaans as $thaan) $thaan->update(['status'=>'available']);
            $bill=$k->bill()->lockForUpdate()->first();
            if ($bill && $bill->billing_status==='provisional') {
                $amount=(float)$bill->total;
                if ($amount>0) $ledger->post($bill->party_id,$bill->factory_id,'receivable',today()->toDateString(),
                    'Provisional bill reversal: '.$bill->reference,'kachi_void',$k->id,0,$amount);
                $bill->update(['billing_status'=>'void']);
                AuditService::record('bill.provisional_reversed',$bill,['kachi'=>$k->reference,'amount'=>$amount,'reason'=>$d['reason']]);
            }
            $k->update(['voided_at'=>now(),'void_reason'=>$d['reason']]);
            AuditService::record('kachi.cancelled',$k,['reason'=>$d['reason']]);
        });
        return redirect()->route('dispatch.index')->with('success','Unaccepted Kachi cancelled. Thaans returned to available stock.');
    }
    public function voidChecking(Request $request,CheckingReport $checking)
    {
        AuditService::adminOnly();
        $d=$request->validate(['reason'=>'required|string|min:8|max:1000']);
        DB::transaction(function () use ($checking,$d) {
            $r=CheckingReport::whereKey($checking->id)->lockForUpdate()->firstOrFail();
            if($r->voided_at) throw ValidationException::withMessages(['reason'=>'Checking report already cancelled.']);
            $thaans=$r->thaans()->lockForUpdate()->get();
            if($thaans->contains(fn($t)=>$t->status!=='available') ||
               KachiItem::whereIn('thaan_id',$thaans->pluck('id'))->exists())
                throw ValidationException::withMessages(['reason'=>'Cannot cancel: one or more Thaans were dispatched or accepted.']);
            // No kachi references exist. Removing only unsent, never-dispatched stock is safe.
            $r->thaans()->delete();
            $r->update(['voided_at'=>now(),'void_reason'=>$d['reason']]);
            AuditService::record('checking.cancelled',$r,['reason'=>$d['reason'],'removed_unsent_thaans'=>$thaans->count()]);
        });
        return back()->with('success','Unsent checking report cancelled. Re-enter it with corrected values.');
    }

}
