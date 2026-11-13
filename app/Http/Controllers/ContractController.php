<?php
namespace App\Http\Controllers;

use App\Models\{Contract, Factory, Party, FabricQuality, YarnReceipt};
use App\Services\ReferenceService;
use App\Services\PartaCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\AuditService;
use App\Models\{Thaan,KachiParchi};
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['factory','party','quality'])->withSum('receipts', 'bags')->withSum(['yarnPurchases as active_yarn_bags'=>fn($query)=>$query->whereNull('voided_at')], 'bags')->latest()->paginate(30);
        return view('contracts.index', compact('contracts'));
    }
    public function create()
    {
        return view('contracts.create', [
            'factories' => Factory::orderBy('name')->get(), 'parties' => Party::whereIn('type',['customer','both'])->orderBy('name')->get(),
            'qualities' => FabricQuality::orderBy('name')->get(),
            'qualityDefaults' => FabricQuality::all()->mapWithKeys(fn($q)=>[(string)$q->id=>[
                'picks'=>$q->picks, 'warp_count'=>$q->warp_count,
                'weft_count'=>$q->weft_count, 'width'=>$q->width,
            ]])->all(),
        ]);
    }
    public function store(Request $request)
    {
        $d = $request->validate([
            'factory_id'=>'required|string', 'party_id'=>'required|exists:parties,id',
            'fabric_quality_id'=>'required|exists:fabric_qualities,id',
            'type'=>'required|in:job_work,fabric_rate', 'contract_date'=>'required|date',
            'target_metres'=>'nullable|numeric|gt:0', 'rate_per_pick'=>'nullable|numeric|min:0',
            'rate_per_metre'=>'nullable|numeric|min:0',
            'parta_reed'=>'nullable|numeric|gt:0',
            'parta_warp_count'=>'nullable|numeric|gt:0',
            'parta_weft_count'=>'nullable|numeric|gt:0',
            'parta_width'=>'nullable|numeric|gt:0',
            'parta_warp_yarn_rate'=>'nullable|numeric|min:0',
            'parta_weft_yarn_rate'=>'nullable|numeric|min:0',
            'notes'=>'nullable|string|max:2500', 'parta_details'=>'nullable|string|max:10000',
            'yarn_supplier'=>'nullable|in:customer,factory',
        ]);
        $factoryScope=$d['factory_id']==='all'?'all':'single';
        if ($factoryScope==='single') Factory::findOrFail($d['factory_id']);
        else $d['factory_id']=Factory::orderBy('id')->value('id') ?? throw ValidationException::withMessages(['factory_id'=>'Create at least one factory before using All Factories.']);
        $d['factory_scope']=$factoryScope;
        if (Party::findOrFail($d['party_id'])->type==='supplier') {
            throw ValidationException::withMessages(['party_id'=>'Contracts can only be assigned to a customer or a customer/supplier party.']);
        }
        if ($d['type'] === 'job_work') {
            // Older clients that predate the explicit selector retain the former customer-supplied default.
            $provider=$d['yarn_supplier']??'customer';
            $d['yarn_supplier']=$provider;
            $quality = FabricQuality::findOrFail($d['fabric_quality_id']);
            if (!$quality->picks || (float)$quality->picks <= 0) {
                throw ValidationException::withMessages(['fabric_quality_id'=>'Set a positive pick count on the chosen fabric quality first.']);
            }
            $job = $request->validate([
                'target_metres'=>'required|numeric|gt:0',
                'rate_per_pick'=>'required|numeric|min:0',
                'parta_reed'=>'required|numeric|gt:0',
                'parta_warp_count'=>'required|numeric|gt:0',
                'parta_weft_count'=>'required|numeric|gt:0',
                'parta_width'=>'required|numeric|gt:0',
            ]);
            if ($provider === 'factory') {
                $request->validate([
                    'parta_warp_yarn_rate'=>'required|numeric|min:0',
                    'parta_weft_yarn_rate'=>'required|numeric|min:0',
                ]);
            }
            $snap=PartaCalculator::calculate([
                'quantity_metres'=>$job['target_metres'],
                'reed'=>$job['parta_reed'], 'picks'=>$quality->picks,
                'warp_count'=>$job['parta_warp_count'], 'weft_count'=>$job['parta_weft_count'],
                'width'=>$job['parta_width'], 'rate_per_pick'=>$job['rate_per_pick'],
                'warp_yarn_rate'=>$d['parta_warp_yarn_rate']??null,
                'weft_yarn_rate'=>$d['parta_weft_yarn_rate']??null,
            ]);
            // Never trust client-side bag calculations or a manually submitted required_bags field.
            $d['parta_snapshot']=$snap;
            $d['required_bags']=$snap['results']['total_required_bags'];
            $d['picks_at_contract']=$quality->picks;
            $d['rate_per_metre']=null;
        } else {
            $d['yarn_supplier']='factory';
            if (!isset($d['rate_per_metre'])) {
                throw ValidationException::withMessages(['rate_per_metre'=>'Fabric Rate contract requires an agreed rate per metre.']);
            }
            $d['rate_per_pick']=null;
            $d['required_bags']=null;
            $d['picks_at_contract']=null;
            $d['parta_snapshot']=null;
        }
        unset($d['parta_reed'],$d['parta_warp_count'],$d['parta_weft_count'],$d['parta_width'],
              $d['parta_warp_yarn_rate'],$d['parta_weft_yarn_rate']);
        $d['reference'] = ReferenceService::make('CTR');
        $contract = Contract::create($d);
        AuditService::record('contract.created',$contract,['type'=>$contract->type]);
        return redirect()->route('contracts.show', $contract)->with('success', 'Contract created.');
    }
    public function show(Contract $contract)
    {
        $contract->load(['factory','party','quality']);
        $salesBills=$contract->bills()->with('factory')->orderByDesc('billed_on')->orderByDesc('id')->get();
        $provisionalSales=(float)$salesBills->where('billing_status','provisional')->sum('total');
        $finalSales=(float)$salesBills->where('billing_status','final')->sum('total');
        $voidSales=(float)$salesBills->where('billing_status','void')->sum('total');
        $receipts = $contract->receipts()->latest('received_on')->get();
        $purchases = $contract->yarnPurchases()->with('party')->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')->whereNull('voided_at')->latest('purchased_on')->get();
        $warpRequired=(float)($contract->parta_snapshot['results']['warp_bags'] ?? 0);
        $weftRequired=(float)($contract->parta_snapshot['results']['weft_bags'] ?? 0);
        $warpReceived=(float)$receipts->where('yarn_type','warp')->sum('bags')+(float)$purchases->where('yarn_type','warp')->sum('bags');
        $weftReceived=(float)$receipts->where('yarn_type','weft')->sum('bags')+(float)$purchases->where('yarn_type','weft')->sum('bags');
        $received=$warpReceived+$weftReceived+(float)$receipts->whereNull('yarn_type')->sum('bags');
        $remaining=max(0,round((float)$contract->required_bags-$received,2));
        $warpRemaining=max(0,round($warpRequired-$warpReceived,2));
        $weftRemaining=max(0,round($weftRequired-$weftReceived,2));
        $suppliers=Party::whereIn('type',['supplier','both'])->orderBy('name')->get();
        $factories=Factory::orderBy('name')->get();
        return view('contracts.show', compact('contract','receipts','purchases','received','remaining','warpRequired','weftRequired','warpReceived','weftReceived','warpRemaining','weftRemaining','suppliers','factories','salesBills','provisionalSales','finalSales','voidSales'));
    }
    public function receiveBags(Request $request, Contract $contract)
    {
        if ($contract->type !== 'job_work' || $contract->status !== 'active') abort(422, 'Bag receipts are only allowed on active job-work contracts.');
        if ($contract->yarn_supplier !== 'customer') {
            throw ValidationException::withMessages(['bags'=>'This contract uses factory-supplied yarn. Customer bag receipts are not allowed.']);
        }
        $d = $request->validate(['received_on'=>'required|date','yarn_type'=>'required|in:warp,weft','bags'=>'required|numeric|gt:0|decimal:0,2',
            'reference'=>'nullable|string|max:150','notes'=>'nullable|string|max:1000',
            'cones_per_bag'=>'nullable|integer|min:1|max:200']);
        $receipt=DB::transaction(function () use ($contract,$d) {
            $locked=Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if ($locked->type!=='job_work' || $locked->status!=='active' || $locked->yarn_supplier!=='customer') {
                throw ValidationException::withMessages(['bags'=>'Customer bag receipts are not allowed for this contract.']);
            }
            $parta=$locked->parta_snapshot['results'] ?? [];
            $required=(float)($parta[$d['yarn_type'].'_bags'] ?? 0);
            $alreadyType=(float)$locked->receipts()->where('yarn_type',$d['yarn_type'])->sum('bags');
            $alreadyTotal=(float)$locked->receipts()->sum('bags');
            $typeRemaining=max(0,round($required-$alreadyType,2));
            $totalRemaining=max(0,round((float)$locked->required_bags-$alreadyTotal,2));
            if ((float)$d['bags']>$typeRemaining || (float)$d['bags']>$totalRemaining) {
                throw ValidationException::withMessages(['bags'=>'Only '.number_format(min($typeRemaining,$totalRemaining),2).' bags remain for this yarn type and contract.']);
            }
            $saved=$locked->receipts()->create($d);
            AuditService::record('bags.received',$saved,['contract'=>$locked->reference,'bags'=>$saved->bags]);
            return $saved;
        });
        return back()->with('success', ucfirst($d['yarn_type']).' yarn bags received from the customer.');
    }
    public function close(Request $request, Contract $contract)
    {
        AuditService::adminOnly();
        $d=$request->validate(['closed_on'=>'required|date','close_reason'=>'required|string|min:8|max:1200']);
        DB::transaction(function () use ($contract,$d) {
            $c=Contract::whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if($c->status!=='active') throw ValidationException::withMessages(['close_reason'=>'Contract already closed.']);
            if(Thaan::where('contract_id',$c->id)->whereIn('status',['available','dispatched'])->exists() ||
               KachiParchi::where('contract_id',$c->id)->whereNull('voided_at')->where('status','!=','settled')->exists())
                throw ValidationException::withMessages(['close_reason'=>'Contract has available/dispatched fabric or unresolved parchis. Settle these before closure.']);
            $c->update(['status'=>'closed','closed_on'=>$d['closed_on'],'close_reason'=>$d['close_reason']]);
            AuditService::record('contract.closed',$c,['reason'=>$d['close_reason']]);
        });
        return back()->with('success','Contract closed and retained for history.');
    }

}
