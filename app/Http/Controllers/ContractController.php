<?php
namespace App\Http\Controllers;

use App\Models\{Contract, Factory, Party, FabricQuality, YarnReceipt, YarnQuality};
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
        $receipts = $contract->receipts()->with(['yarnQuality','factory'])->latest('received_on')->get();
        $purchases = $contract->yarnPurchases()->with(['party','yarnQuality'])->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')->whereNull('voided_at')->latest('purchased_on')->get();
        $warpRequired=(float)($contract->parta_snapshot['results']['warp_bags'] ?? 0);
        $weftRequired=(float)($contract->parta_snapshot['results']['weft_bags'] ?? 0);
        $customerYarn=$contract->yarn_supplier==='customer';
        $warpReceived=$customerYarn?(float)$receipts->where('yarn_type','warp')->sum('bags'):(float)$purchases->where('yarn_type','warp')->sum('bags');
        $weftReceived=$customerYarn?(float)$receipts->where('yarn_type','weft')->sum('bags'):(float)$purchases->where('yarn_type','weft')->sum('bags');
        $received=$warpReceived+$weftReceived+(float)$receipts->whereNull('yarn_type')->sum('bags');
        $remaining=max(0,round((float)$contract->required_bags-$received,2));
        $warpRemaining=max(0,round($warpRequired-$warpReceived,2));
        $weftRemaining=max(0,round($weftRequired-$weftReceived,2));
        $suppliers=Party::whereIn('type',['supplier','both'])->orderBy('name')->get();
        $factories=Factory::orderBy('name')->get();
        $yarnQualities=YarnQuality::orderBy('name')->get();
        return view('contracts.show', compact('contract','receipts','purchases','received','remaining','warpRequired','weftRequired','warpReceived','weftReceived','warpRemaining','weftRemaining','suppliers','factories','yarnQualities','salesBills','provisionalSales','finalSales','voidSales'));
    }
    public function receiveBags(Request $request, Contract $contract)
    {
        abort(410, 'Bag receipt entry has moved to Yarn Purchases. Select the contract there to record Warp or Weft bags.');
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
