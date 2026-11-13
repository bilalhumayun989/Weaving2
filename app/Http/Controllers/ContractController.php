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
            'print_details.sales_tax_registration_no'=>'nullable|string|max:100',
            'print_details.broker'=>'nullable|string|max:150',
            'print_details.ntn'=>'nullable|string|max:100',
            'print_details.grey_demand_no'=>'nullable|string|max:100',
            'print_details.weave_quality'=>'nullable|string|max:200',
            'print_details.invoice_quality'=>'nullable|string|max:200',
            'print_details.specification'=>'nullable|string|max:2000',
            'print_details.hs_code_description'=>'nullable|string|max:500',
            'print_details.last_delivery_date'=>'nullable|date',
            'print_details.broker_commission_percent'=>'nullable|numeric|min:0|max:100',
            'print_details.planned_looms'=>'nullable|integer|min:0|max:100000',
            'print_details.weight_per_metre_kg'=>'nullable|numeric|min:0|max:1000',
            'print_details.warp_component'=>'nullable|string|max:255',
            'print_details.weft_component'=>'nullable|string|max:255',
            'print_details.selvedge'=>'nullable|string|max:150',
            'print_details.weave'=>'nullable|string|max:150',
            'print_details.count_margin'=>'nullable|string|max:150',
            'print_details.loom_type'=>'nullable|string|max:150',
            'print_details.stripe_size'=>'nullable|string|max:150',
            'print_details.process'=>'nullable|string|max:200',
            'print_details.payment_terms'=>'nullable|string|max:500',
            'print_details.packing'=>'nullable|string|max:500',
            'print_details.special_remarks'=>'nullable|string|max:2500',
            'print_details.terms_conditions'=>'nullable|string|max:5000',
            'delivery_schedule'=>'nullable|array|max:30',
            'delivery_schedule.*.date'=>'nullable|date',
            'delivery_schedule.*.quantity'=>'nullable|numeric|gt:0',
            'delivery_schedule.*.uom'=>'nullable|string|max:20',
            'delivery_schedule.*.location'=>'nullable|string|max:150',
        ]);
        $schedule=[];
        foreach ($d['delivery_schedule'] ?? [] as $i=>$row) {
            $hasValues=collect($row)->contains(fn($value)=>$value!==null && trim((string)$value)!=='');
            if (!$hasValues) continue;
            if (empty($row['date']) || empty($row['quantity'])) {
                throw ValidationException::withMessages(["delivery_schedule.$i.date"=>'Each delivery schedule row needs both a date and quantity.']);
            }
            $schedule[]=[
                'date'=>$row['date'],'quantity'=>(float)$row['quantity'],
                'uom'=>trim($row['uom']??'M') ?: 'M','location'=>trim($row['location']??''),
            ];
        }
        $printDetails=$d['print_details']??[];
        $printDetails['delivery_schedule']=$schedule;
        $d['print_details']=$printDetails;
        unset($d['delivery_schedule']);
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
