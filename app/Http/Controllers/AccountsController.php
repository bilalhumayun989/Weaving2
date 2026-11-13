<?php
namespace App\Http\Controllers;

use App\Models\{Party, Factory, Payment,Bill,YarnPurchase};
use App\Services\{LedgerService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    public function index(LedgerService $ledger)
    {
        $parties=Party::whereIn('type',['customer','both'])->withCount(['ledgerEntries','contracts','purchases','bills','payments'])->orderBy('name')->get()->map(function ($p) use ($ledger) {
            $p->balances=$ledger->balances($p);
            $p->has_account_activity=$p->ledger_entries_count+$p->purchases_count+$p->bills_count+$p->payments_count>0
                || (float)$p->opening_receivable>0 || (float)$p->opening_payable>0;
            return $p;
        });
        $suppliers=Party::whereIn('type',['supplier','both'])->withCount(['purchases','payments'])->orderBy('name')->get()->map(function($p) use($ledger){
            $p->balances=$ledger->balances($p); return $p;
        });
        return view('accounts.index', [
            'parties'=>$parties,
            'receivables'=>$parties->sum(fn ($p)=>max(0,$p->balances['receivable'])),
            'payables'=>$suppliers->sum(fn ($p)=>max(0,$p->balances['payable'])),
            'suppliers'=>$suppliers,
            'factories'=>Factory::orderBy('name')->get(),
            'withoutAccounts'=>$parties->where('has_account_activity',false)->count(),
        ]);
    }
    public function bills(Request $request)
    {
        $q=Bill::with(['party','factory','contract','kachi'])->orderByDesc('billed_on')->orderByDesc('id');
        if($request->filled('party_id')) $q->where('party_id',$request->integer('party_id'));
        if($request->filled('factory_id')) $q->where('factory_id',$request->integer('factory_id'));
        if(in_array($request->query('type'),['fabric_sale','conversion'],true)) $q->where('type',$request->query('type'));
        return view('accounts.bills',[
            'bills'=>$q->paginate(40)->withQueryString(),
            'factories'=>Factory::orderBy('name')->get(),
            'parties'=>Party::orderBy('name')->get(),
        ]);
    }
    public function show(Party $party,LedgerService $ledger)
    {
        $openYarnPurchases=YarnPurchase::with('factory')->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')
            ->where('party_id',$party->id)->whereNull('voided_at')->latest('purchased_on')->get()
            ->map(function($purchase){$purchase->outstanding_amount=max(0,round((float)$purchase->total-(float)($purchase->linked_paid_total??0),2));return $purchase;})
            ->where('outstanding_amount','>',0)->values();
        return view('accounts.show', [
            'party'=>$party,
            'receivable'=>$ledger->timeline($party,'receivable'),
            'payable'=>$ledger->timeline($party,'payable'),
            'factories'=>Factory::orderBy('name')->get(),
            'payments'=>Payment::with('yarnPurchase')->where('party_id',$party->id)->latest('id')->limit(30)->get(),
            'openYarnPurchases'=>$openYarnPurchases,
            'purchases'=>\App\Models\YarnPurchase::with(['factory','contract'])->where('party_id',$party->id)->latest('purchased_on')->limit(30)->get(),
            'bills'=>Bill::with(['factory','contract'])->where('party_id',$party->id)->latest('billed_on')->limit(30)->get(),
        ]);
    }
    public function storePayment(Request $request,LedgerService $ledger)
    {
        $d=$request->validate([
            'party_id'=>'required|exists:parties,id','factory_id'=>'nullable|exists:factories,id',
            'kind'=>'required|in:received,paid','paid_on'=>'required|date','amount'=>'required|numeric|gt:0',
            'yarn_purchase_id'=>'nullable|integer|exists:yarn_purchases,id',
            'reference'=>'nullable|string|max:150','notes'=>'nullable|string|max:1000',
        ]);
        $party=Party::findOrFail($d['party_id']);
        if (($d['kind']==='received' && $party->type==='supplier') || ($d['kind']==='paid' && $party->type==='customer')) {
            return back()->withErrors(['kind'=>'Payment type does not match this party account.'])->withInput();
        }
        if(!empty($d['yarn_purchase_id'])) {
            if($d['kind']!=='paid') return back()->withErrors(['yarn_purchase_id'=>'A yarn purchase can only be linked to a supplier payment.'])->withInput();
            $purchase=YarnPurchase::findOrFail($d['yarn_purchase_id']);
            if((int)$purchase->party_id!==(int)$party->id || $purchase->voided_at) return back()->withErrors(['yarn_purchase_id'=>'Choose an active purchase belonging to this supplier.'])->withInput();
            if(!empty($d['factory_id']) && (int)$d['factory_id']!==(int)$purchase->factory_id) return back()->withErrors(['factory_id'=>'The factory must match the selected yarn purchase.'])->withInput();
            $d['factory_id']=$purchase->factory_id;
            $d['yarn_purchase_id']=$purchase->id;
        }
        DB::transaction(function () use ($d,$ledger) {
            if(!empty($d['yarn_purchase_id'])) {
                $purchase=YarnPurchase::whereKey($d['yarn_purchase_id'])->lockForUpdate()->firstOrFail();
                if($purchase->voided_at || (int)$purchase->party_id!==(int)$d['party_id']) throw \Illuminate\Validation\ValidationException::withMessages(['yarn_purchase_id'=>'This purchase is no longer available for payment.']);
                $paid=(float)$purchase->payments()->whereNull('voided_at')->sum('amount');
                $remaining=max(0,round((float)$purchase->total-$paid,2));
                if((float)$d['amount']>$remaining) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'Only Rs '.number_format($remaining,2).' remains on this purchase.']);
            }
            $payment=Payment::create($d);
            AuditService::record('payment.created',$payment,['kind'=>$payment->kind,'amount'=>$payment->amount]);
            if ($payment->kind==='received') $ledger->post($payment->party_id,$payment->factory_id,'receivable',$payment->paid_on,
                'Payment received'.($payment->reference?' — '.$payment->reference:''),'payment',$payment->id,0,(float)$payment->amount);
            else $ledger->post($payment->party_id,$payment->factory_id,'payable',$payment->paid_on,
                'Payment paid'.($payment->reference?' — '.$payment->reference:''),'payment',$payment->id,(float)$payment->amount,0);
        });
        return redirect(route('accounts.show',$party))->with('success','Payment recorded and running balance updated.');
    }

    public function storeParty(Request $request)
    {
        $data=$request->validate([
            'name'=>'required|string|max:150|unique:parties,name',
            'type'=>$request->input('return_to')==='contract'?'required|in:customer':'required|in:customer,both,supplier',
            'phone'=>'nullable|string|max:60','address'=>'nullable|string|max:1000',
            'opening_receivable'=>'nullable|numeric|min:0','opening_payable'=>'nullable|numeric|min:0',
        ]);
        $party=Party::create($data);
        AuditService::record('party.created',$party);
        if($request->expectsJson()) return response()->json(['party'=>$party],201);
        $redirect=match($request->input('return_to')) {
            'accounts'=>route('accounts.index'), 'contract'=>route('contracts.create'), default=>route('setup.index'),
        };
        return redirect($redirect)->with('success','Party added and ready to use.');
    }
}
