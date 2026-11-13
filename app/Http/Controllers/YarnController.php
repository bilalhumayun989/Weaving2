<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, YarnPurchase, Contract, YarnQuality};
use App\Services\{LedgerService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YarnController extends Controller
{
    public function index()
    {
        $stockPurchases=YarnPurchase::with(['factory','yarnQuality','contract.party'])->whereNull('voided_at')->get();
        $stockByQuality=$stockPurchases->groupBy(fn($purchase)=>implode('|',[
            $purchase->yarn_quality_id ?: 'legacy-'.$purchase->yarn_description,
            $purchase->factory_scope ?? 'single',
            $purchase->factory_scope==='all' ? 'all' : $purchase->factory_id,
        ]))->map(function ($items) {
            $first=$items->first();
            return [
                'quality'=>$first->yarnQuality?->name ?? $first->yarn_description,
                'count'=>$first->yarnQuality?->count,
                'factory'=>$first->factory_scope==='all' ? 'All Factories' : $first->factory?->name,
                'warp'=>(float)$items->where('yarn_type','warp')->sum('bags'),
                'weft'=>(float)$items->where('yarn_type','weft')->sum('bags'),
                'contract'=>(float)$items->whereNotNull('contract_id')->sum('bags'),
                'general'=>(float)$items->whereNull('contract_id')->sum('bags'),
            ];
        })->values();
        $contractStock=$stockPurchases->whereNotNull('contract_id')->groupBy(fn($purchase)=>implode('|',[
            $purchase->contract_id,
            $purchase->yarn_quality_id ?: 'legacy-'.$purchase->yarn_description,
            $purchase->factory_scope ?? 'single',
            $purchase->factory_scope==='all' ? 'all' : $purchase->factory_id,
        ]))->map(function ($items) {
            $first=$items->first();
            return [
                'contract_id'=>$first->contract_id,
                'contract'=>$first->contract?->reference,
                'party'=>$first->contract?->party?->name,
                'quality'=>$first->yarnQuality?->name ?? $first->yarn_description,
                'count'=>$first->yarnQuality?->count,
                'factory'=>$first->factory_scope==='all' ? 'All Factories' : $first->factory?->name,
                'warp'=>(float)$items->where('yarn_type','warp')->sum('bags'),
                'weft'=>(float)$items->where('yarn_type','weft')->sum('bags'),
            ];
        })->values();
        return view('yarn.index', [
            'purchases' => YarnPurchase::with(['factory','party','contract','yarnQuality'])->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')->latest('purchased_on')->paginate(30),
            'factories' => Factory::orderBy('name')->get(), 'parties' => Party::whereIn('type',['supplier','both'])->orderBy('name')->get(),
            'contracts' => Contract::with(['party','factory'])->orderByDesc('id')->get(),
            'yarnQualities' => YarnQuality::orderBy('name')->get(),
            'stockByQuality' => $stockByQuality,
            'contractStock' => $contractStock,
        ]);
    }

    public function storeQuality(Request $request)
    {
        $data=$request->validate([
            'name'=>'required|string|max:120|unique:yarn_qualities,name',
            'count'=>'nullable|string|max:80',
            'description'=>'nullable|string|max:255',
        ]);
        $quality=YarnQuality::create($data);
        AuditService::record('yarn_quality.created',$quality);
        return response()->json(['yarn_quality'=>$quality],201);
    }

    public function storePurchase(Request $request, LedgerService $ledger)
    {
        $d = $request->validate([
            'factory_id'=>'required|string','party_id'=>'required|exists:parties,id',
            'contract_id'=>'nullable|exists:contracts,id','yarn_type'=>'required|in:warp,weft',
            'purchased_on'=>'required|date','invoice_no'=>'nullable|string|max:100',
            'yarn_quality_id'=>'required|exists:yarn_qualities,id','bags'=>'required|numeric|gt:0',
            'rate_per_bag'=>'required|numeric|min:0','notes'=>'nullable|string|max:1000',
            'payment_terms_days'=>'nullable|integer|min:0|max:365',
            'payment_terms_note'=>'nullable|string|max:255',
        ]);
        $scope=$d['factory_id']==='all'?'all':'single';
        if($scope==='single') Factory::findOrFail($d['factory_id']);
        else $d['factory_id']=Factory::orderBy('id')->value('id') ?? throw \Illuminate\Validation\ValidationException::withMessages(['factory_id'=>'Create at least one factory before using All Factories.']);
        $d['factory_scope']=$scope;
        $contract=isset($d['contract_id'])?Contract::findOrFail($d['contract_id']):null;
        $supplier=Party::findOrFail($d['party_id']);
        $quality=YarnQuality::findOrFail($d['yarn_quality_id']);
        $d['yarn_description']=trim($quality->name.($quality->count ? ' · '.$quality->count : ''));
        if ($supplier->type==='customer') return back()->withErrors(['party_id'=>'Choose a supplier account.'])->withInput();
        if ($contract && ($contract->type!=='job_work' || $contract->status!=='active' || ($scope==='all' && $contract->factory_scope!=='all') || !$contract->coversFactory((int)$d['factory_id']))) {
            return back()->withErrors(['contract_id'=>'Choose an active Job Work contract that covers the selected factory scope.'])->withInput();
        }
        $d['total'] = round((float)$d['bags'] * (float)$d['rate_per_bag'], 2);
        $d['payment_due_on']=isset($d['payment_terms_days'])
            ? \Illuminate\Support\Carbon::parse($d['purchased_on'])->addDays((int)$d['payment_terms_days'])->toDateString()
            : null;
        DB::transaction(function () use ($d, $ledger, $contract) {
            if ($contract) {
                $locked=Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();
                if ($locked->yarn_supplier==='factory') {
                    $required=(float)($locked->parta_snapshot['results'][$d['yarn_type'].'_bags'] ?? 0);
                    $received=(float)$locked->yarnPurchases()->where('yarn_type',$d['yarn_type'])->whereNull('voided_at')->sum('bags');
                    $remaining=max(0,round($required-$received,2));
                    if ((float)$d['bags']>$remaining) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['bags'=>'Only '.number_format($remaining,2).' '.$d['yarn_type'].' bags remain to meet this contract requirement.']);
                    }
                }
            }
            $p = YarnPurchase::create($d);
            AuditService::record('yarn_purchase.created',$p,['bags'=>$p->bags,'total'=>$p->total,'payment_terms_days'=>$p->payment_terms_days,'payment_due_on'=>$p->payment_due_on]);
            if ($p->total > 0) $ledger->post($p->party_id, $p->factory_scope==='all' ? null : $p->factory_id, 'payable', $p->purchased_on,
                'Yarn purchase: '.$p->yarn_description, 'yarn_purchase', $p->id, 0, (float)$p->total);
        });
        return $contract
            ? redirect()->route('contracts.show',$contract)->with('success','Supplier yarn purchase recorded against this contract and supplier payable updated.')
            : back()->with('success','Yarn purchase recorded and supplier payable updated.');
    }
}
