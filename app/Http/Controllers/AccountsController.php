<?php
namespace App\Http\Controllers;

use App\Models\{Party, Factory, Payment,Bill};
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
        return view('accounts.show', [
            'party'=>$party,
            'receivable'=>$ledger->timeline($party,'receivable'),
            'payable'=>$ledger->timeline($party,'payable'),
            'factories'=>Factory::orderBy('name')->get(),
            'payments'=>Payment::where('party_id',$party->id)->latest('id')->limit(30)->get(),
            'purchases'=>\App\Models\YarnPurchase::with('factory')->where('party_id',$party->id)->latest('purchased_on')->limit(30)->get(),
            'bills'=>Bill::with(['factory','contract'])->where('party_id',$party->id)->latest('billed_on')->limit(30)->get(),
        ]);
    }
    public function storePayment(Request $request,LedgerService $ledger)
    {
        $d=$request->validate([
            'party_id'=>'required|exists:parties,id','factory_id'=>'nullable|exists:factories,id',
            'kind'=>'required|in:received,paid','paid_on'=>'required|date','amount'=>'required|numeric|gt:0',
            'reference'=>'nullable|string|max:150','notes'=>'nullable|string|max:1000',
        ]);
        $party=Party::findOrFail($d['party_id']);
        if (($d['kind']==='received' && $party->type==='supplier') || ($d['kind']==='paid' && $party->type==='customer')) {
            return back()->withErrors(['kind'=>'Payment type does not match this party account.'])->withInput();
        }
        DB::transaction(function () use ($d,$ledger) {
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
