<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, YarnPurchase, Contract};
use App\Services\{LedgerService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YarnController extends Controller
{
    public function index()
    {
        return view('yarn.index', [
            'purchases' => YarnPurchase::with(['factory','party','contract'])->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')->latest('purchased_on')->paginate(30),
            'factories' => Factory::orderBy('name')->get(), 'parties' => Party::whereIn('type',['supplier','both'])->orderBy('name')->get(),
            'contracts' => Contract::where('type','job_work')->where('yarn_supplier','factory')->where('status','active')->with('party')->orderByDesc('id')->get(),
        ]);
    }
    public function storePurchase(Request $request, LedgerService $ledger)
    {
        $d = $request->validate([
            'factory_id'=>'required|exists:factories,id','party_id'=>'required|exists:parties,id',
            'contract_id'=>'nullable|exists:contracts,id','yarn_type'=>'required|in:warp,weft',
            'purchased_on'=>'required|date','invoice_no'=>'nullable|string|max:100',
            'yarn_description'=>'required|string|max:255','bags'=>'required|numeric|gt:0',
            'rate_per_bag'=>'required|numeric|min:0','notes'=>'nullable|string|max:1000',
            'payment_terms_days'=>'nullable|integer|min:0|max:365',
            'payment_terms_note'=>'nullable|string|max:255',
        ]);
        $contract=isset($d['contract_id'])?Contract::findOrFail($d['contract_id']):null;
        $supplier=Party::findOrFail($d['party_id']);
        if ($supplier->type==='customer') return back()->withErrors(['party_id'=>'Choose a supplier account.'])->withInput();
        if ($contract && ($contract->type!=='job_work' || $contract->yarn_supplier!=='factory' || $contract->status!=='active' || !$contract->coversFactory((int)$d['factory_id']))) {
            return back()->withErrors(['contract_id'=>'Choose an active factory-yarn Job Work contract for this factory.'])->withInput();
        }
        $d['total'] = round((float)$d['bags'] * (float)$d['rate_per_bag'], 2);
        $d['payment_due_on']=isset($d['payment_terms_days'])
            ? \Illuminate\Support\Carbon::parse($d['purchased_on'])->addDays((int)$d['payment_terms_days'])->toDateString()
            : null;
        DB::transaction(function () use ($d, $ledger, $contract) {
            if ($contract) {
                $locked=Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();
                $required=(float)($locked->parta_snapshot['results'][$d['yarn_type'].'_bags'] ?? 0);
                $received=(float)$locked->yarnPurchases()->where('yarn_type',$d['yarn_type'])->whereNull('voided_at')->sum('bags');
                $remaining=max(0,round($required-$received,2));
                if ((float)$d['bags']>$remaining) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['bags'=>'Only '.number_format($remaining,2).' '.$d['yarn_type'].' bags remain to meet this contract requirement.']);
                }
            }
            $p = YarnPurchase::create($d);
            AuditService::record('yarn_purchase.created',$p,['bags'=>$p->bags,'total'=>$p->total,'payment_terms_days'=>$p->payment_terms_days,'payment_due_on'=>$p->payment_due_on]);
            if ($p->total > 0) $ledger->post($p->party_id, $p->factory_id, 'payable', $p->purchased_on,
                'Yarn purchase: '.$p->yarn_description, 'yarn_purchase', $p->id, 0, (float)$p->total);
        });
        return $contract
            ? redirect()->route('contracts.show',$contract)->with('success','Supplier yarn purchase recorded against this contract and supplier payable updated.')
            : back()->with('success','Yarn purchase recorded and supplier payable updated.');
    }
}
