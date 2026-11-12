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
        $parties=Party::orderBy('name')->get()->map(function ($p) use ($ledger) {
            $p->balances=$ledger->balances($p); return $p;
        });
        return view('accounts.index', [
            'parties'=>$parties,
            'receivables'=>$parties->sum(fn ($p)=>max(0,$p->balances['receivable'])),
            'payables'=>$parties->sum(fn ($p)=>max(0,$p->balances['payable'])),
            'factories'=>Factory::orderBy('name')->get(),
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
        ]);
    }
    public function storePayment(Request $request,LedgerService $ledger)
    {
        $d=$request->validate([
            'party_id'=>'required|exists:parties,id','factory_id'=>'nullable|exists:factories,id',
            'kind'=>'required|in:received,paid','paid_on'=>'required|date','amount'=>'required|numeric|gt:0',
            'reference'=>'nullable|string|max:150','notes'=>'nullable|string|max:1000',
        ]);
        DB::transaction(function () use ($d,$ledger) {
            $payment=Payment::create($d);
            AuditService::record('payment.created',$payment,['kind'=>$payment->kind,'amount'=>$payment->amount]);
            if ($payment->kind==='received') $ledger->post($payment->party_id,$payment->factory_id,'receivable',$payment->paid_on,
                'Payment received'.($payment->reference?' — '.$payment->reference:''),'payment',$payment->id,0,(float)$payment->amount);
            else $ledger->post($payment->party_id,$payment->factory_id,'payable',$payment->paid_on,
                'Payment paid'.($payment->reference?' — '.$payment->reference:''),'payment',$payment->id,(float)$payment->amount,0);
        });
        return redirect()->route('accounts.show',$d['party_id'])->with('success','Payment recorded and running balance updated.');
    }
}
