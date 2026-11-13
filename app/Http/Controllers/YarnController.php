<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, YarnPurchase, YarnReceipt, Contract, YarnQuality};
use App\Services\{LedgerService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YarnController extends Controller
{
    public function index()
    {
        $stockPurchases=YarnPurchase::with(['factory','yarnQuality','contract.party'])->whereNull('voided_at')->get();
        $customerReceipts=YarnReceipt::with(['factory','yarnQuality','contract.party','contract.factory'])
            ->latest('received_on')->get();
        $stockEntries=collect();
        foreach($stockPurchases as $purchase) {
            $stockEntries->push([
                'quality_key'=>$purchase->yarn_quality_id ?: 'legacy-'.$purchase->yarn_description,
                'quality'=>$purchase->yarnQuality?->name ?? $purchase->yarn_description,
                'count'=>$purchase->yarnQuality?->count,
                'owner'=>'Factory', 'factory_scope'=>$purchase->factory_scope ?? 'single',
                'factory_id'=>$purchase->factory_id,
                'factory'=>$purchase->factory_scope==='all'?'All Factories':$purchase->factory?->name,
                'yarn_type'=>$purchase->yarn_type, 'bags'=>(float)$purchase->bags,
                'contract_id'=>$purchase->contract_id, 'contract'=>$purchase->contract?->reference,
                'party'=>$purchase->contract?->party?->name,
            ]);
        }
        foreach($customerReceipts as $receipt) {
            $scope=$receipt->factory_scope ?? (($receipt->contract?->factory_scope==='all')?'all':'single');
            $stockEntries->push([
                'quality_key'=>'customer-supplied',
                'quality'=>'Customer-supplied yarn',
                'count'=>null,
                'owner'=>'Customer / Party', 'factory_scope'=>$scope,
                'factory_id'=>$receipt->factory_id,
                'factory'=>$scope==='all'?'All Factories':($receipt->factory?->name ?? $receipt->contract?->factory?->name),
                'yarn_type'=>$receipt->yarn_type, 'bags'=>(float)$receipt->bags,
                'contract_id'=>$receipt->contract_id, 'contract'=>$receipt->contract?->reference,
                'party'=>$receipt->contract?->party?->name,
            ]);
        }
        $stockByQuality=$stockEntries->groupBy(fn($entry)=>implode('|',[
            $entry['quality_key'],$entry['owner'],$entry['factory_scope'],
            $entry['factory_scope']==='all'?'all':$entry['factory_id'],
        ]))->map(function ($items) {
            $first=$items->first();
            return [
                'quality'=>$first['quality'],'count'=>$first['count'],'owner'=>$first['owner'],'factory'=>$first['factory'],
                'warp'=>(float)$items->where('yarn_type','warp')->sum('bags'),
                'weft'=>(float)$items->where('yarn_type','weft')->sum('bags'),
                'contract'=>(float)$items->whereNotNull('contract_id')->sum('bags'),
                'general'=>(float)$items->whereNull('contract_id')->sum('bags'),
            ];
        })->values();
        $contractStock=$stockEntries->whereNotNull('contract_id')->groupBy(fn($entry)=>implode('|',[
            $entry['contract_id'],$entry['quality_key'],$entry['owner'],$entry['factory_scope'],
            $entry['factory_scope']==='all'?'all':$entry['factory_id'],
        ]))->map(function ($items) {
            $first=$items->first();
            return [
                'contract_id'=>$first['contract_id'],'contract'=>$first['contract'],'party'=>$first['party'],
                'quality'=>$first['quality'],'count'=>$first['count'],'owner'=>$first['owner'],'factory'=>$first['factory'],
                'warp'=>(float)$items->where('yarn_type','warp')->sum('bags'),
                'weft'=>(float)$items->where('yarn_type','weft')->sum('bags'),
            ];
        })->values();
        return view('yarn.index', [
            'purchases' => YarnPurchase::with(['factory','party','contract','yarnQuality'])->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')->latest('purchased_on')->paginate(30),
            'factories' => Factory::orderBy('name')->get(), 'parties' => Party::whereIn('type',['supplier','both'])->orderBy('name')->get(),
            'contracts' => Contract::with(['party','factory'])
                ->withSum(['receipts as customer_warp_received'=>fn($q)=>$q->where('yarn_type','warp')],'bags')
                ->withSum(['receipts as customer_weft_received'=>fn($q)=>$q->where('yarn_type','weft')],'bags')
                ->withSum(['yarnPurchases as factory_warp_received'=>fn($q)=>$q->where('yarn_type','warp')->whereNull('voided_at')],'bags')
                ->withSum(['yarnPurchases as factory_weft_received'=>fn($q)=>$q->where('yarn_type','weft')->whereNull('voided_at')],'bags')
                ->orderByDesc('id')->get(),
            'customerReceipts' => $customerReceipts,
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
            'factory_id'=>'required|string','party_id'=>'nullable|exists:parties,id',
            'contract_id'=>'nullable|exists:contracts,id','yarn_type'=>'required|in:warp,weft',
            'purchased_on'=>'required|date','invoice_no'=>'nullable|string|max:150',
            'yarn_quality_id'=>'nullable|exists:yarn_qualities,id','bags'=>'required|numeric|gt:0',
            'rate_per_lb'=>'nullable|numeric|min:0','notes'=>'nullable|string|max:1000',
            'cones_per_bag'=>'nullable|integer|min:1|max:200',
            'payment_terms_days'=>'nullable|integer|min:0|max:365',
            'payment_terms_note'=>'nullable|string|max:255',
        ]);
        $scope=$d['factory_id']==='all'?'all':'single';
        if($scope==='single') Factory::findOrFail($d['factory_id']);
        else $d['factory_id']=Factory::orderBy('id')->value('id') ?? throw \Illuminate\Validation\ValidationException::withMessages(['factory_id'=>'Create at least one factory before using All Factories.']);
        $d['factory_scope']=$scope;
        $contract=isset($d['contract_id'])?Contract::findOrFail($d['contract_id']):null;
        $quality=isset($d['yarn_quality_id'])?YarnQuality::findOrFail($d['yarn_quality_id']):null;
        $d['yarn_description']=$quality ? trim($quality->name.($quality->count ? ' · '.$quality->count : '')) : null;
        if ($contract && ($contract->type!=='job_work' || $contract->status!=='active' || ($contract->factory_scope!=='all' && (int)$contract->factory_id!==(int)$d['factory_id']))) {
            return back()->withErrors(['contract_id'=>'Choose an active Job Work contract for the selected factory.'])->withInput();
        }
        if ($contract && $contract->factory_scope==='all' && $scope==='single') {
            $d['factory_scope']='single';
        } elseif ($contract && $contract->factory_scope==='all') {
            $d['factory_scope']='all';
        }
        if ($contract?->yarn_supplier==='customer') {
            $receipt=DB::transaction(function () use ($d,$contract) {
                $locked=Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();
                if ($locked->type!=='job_work' || $locked->status!=='active' || $locked->yarn_supplier!=='customer') {
                    throw \Illuminate\Validation\ValidationException::withMessages(['contract_id'=>'This contract no longer accepts customer yarn receipts.']);
                }
                $required=(float)($locked->parta_snapshot['results'][$d['yarn_type'].'_bags'] ?? 0);
                $received=(float)$locked->receipts()->where('yarn_type',$d['yarn_type'])->sum('bags');
                $remaining=max(0,round($required-$received,2));
                if ((float)$d['bags']>$remaining) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['bags'=>'Only '.number_format($remaining,2).' '.$d['yarn_type'].' bags remain for this contract.']);
                }
                $saved=$locked->receipts()->create([
                    'received_on'=>$d['purchased_on'],'yarn_type'=>$d['yarn_type'],'bags'=>$d['bags'],
                    'reference'=>$d['invoice_no']??null,'notes'=>$d['notes']??null,
                    'cones_per_bag'=>$d['cones_per_bag']??null,'yarn_quality_id'=>null,
                    'factory_id'=>$d['factory_scope']==='all'?null:$d['factory_id'],
                    'factory_scope'=>$locked->factory_scope==='all'?'all':'single','rate_per_lb'=>null,
                    'rate_per_bag'=>null,
                ]);
                AuditService::record('bags.received',$saved,[
                    'contract'=>$locked->reference,'bags'=>$saved->bags,'yarn_type'=>$saved->yarn_type,
                    'owner'=>'customer',
                ]);
                return $saved;
            });
            return back()->with('success',number_format((float)$receipt->bags,2).' customer-supplied '.ucfirst($receipt->yarn_type).' bags recorded on '.$contract->reference.'. No supplier payable was created.');
        }

        if (empty($d['party_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['party_id'=>'Choose a supplier for a purchase that is not customer-supplied.']);
        }
        if (empty($d['yarn_quality_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['yarn_quality_id'=>'Choose a yarn quality for this supplier purchase.']);
        }
        $supplier=Party::findOrFail($d['party_id']);
        if ($supplier->type==='customer') return back()->withErrors(['party_id'=>'Choose a supplier account.'])->withInput();
        if (!isset($d['rate_per_lb'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['rate_per_lb'=>'Enter the supplier rate per pound.']);
        }
        $d['rate_per_bag']=round((float)$d['rate_per_lb']*100,4);
        unset($d['rate_per_lb'],$d['cones_per_bag']);
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
            unset($d['rate_per_lb'],$d['cones_per_bag']);
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
