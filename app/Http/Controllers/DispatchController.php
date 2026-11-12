<?php
namespace App\Http\Controllers;

use App\Models\{KachiParchi, KachiItem, PakkiParchi, Thaan, Contract, Factory, Party, Bill};
use App\Services\{LedgerService, ReferenceService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispatchController extends Controller
{
    public function index()
    {
        return view('dispatch.index', [
            'kachis'=>KachiParchi::with(['factory','party','contract','pakki','bill'])->latest('dispatched_on')->paginate(40),
            'factories'=>Factory::orderBy('name')->get(),
            'parties'=>Party::orderBy('name')->get(),
            'contracts'=>Contract::with('party')->where('status','active')->orderByDesc('id')->get(),
            'thaans'=>Thaan::with(['quality','factory'])->where('status','available')->orderBy('factory_id')->orderBy('thaan_no')->get(),
        ]);
    }
    public function storeKachi(Request $request)
    {
        $d=$request->validate([
            'factory_id'=>'required|exists:factories,id','party_id'=>'required|exists:parties,id',
            'contract_id'=>'nullable|exists:contracts,id','kind'=>'required|in:job_work,fabric_sale',
            'rate_per_metre'=>'nullable|numeric|min:0','dispatched_on'=>'required|date',
            'thaan_ids'=>'required|array|min:1','thaan_ids.*'=>'required|integer|distinct|exists:thaans,id',
        ]);
        $contract=!empty($d['contract_id'])?Contract::findOrFail($d['contract_id']):null;
        if ($d['kind']==='job_work' && (!$contract || $contract->type!=='job_work'))
            throw ValidationException::withMessages(['contract_id'=>'Job Work Kachi requires a job-work contract.']);
        if ($d['kind']==='fabric_sale' && $contract && $contract->type!=='fabric_rate')
            throw ValidationException::withMessages(['contract_id'=>'Own fabric sale requires a fabric-rate contract, or no contract for a stock sale.']);
        if ($d['kind']==='fabric_sale' && !$contract && !isset($d['rate_per_metre']))
            throw ValidationException::withMessages(['rate_per_metre'=>'For-stock sales require a selling rate per metre.']);
        if ($contract && (!$contract->coversFactory((int)$d['factory_id']) || (int)$contract->party_id!==(int)$d['party_id'] || $contract->status!=='active'))
            throw ValidationException::withMessages(['contract_id'=>'Contract must be active and match the selected buyer and factory.']);

        DB::transaction(function () use ($d,$contract) {
            $items=Thaan::whereIn('id',$d['thaan_ids'])->lockForUpdate()->get();
            if ($items->count()!==count($d['thaan_ids'])) throw ValidationException::withMessages(['thaan_ids'=>'Some Thaans were not found.']);
            $qualityId=$items->first()->fabric_quality_id;
            if ($contract && (int)$contract->fabric_quality_id !== (int)$qualityId)
                throw ValidationException::withMessages(['thaan_ids'=>'Selected fabric quality must match the contract.']);
            foreach ($items as $thaan) {
                if ($thaan->status!=='available' || (int)$thaan->factory_id!==(int)$d['factory_id'] || (int)$thaan->fabric_quality_id!==(int)$qualityId ||
                    ($d['kind']==='job_work' && ($thaan->ownership!=='job_work' || (int)$thaan->contract_id!==(int)$contract->id)) ||
                    ($d['kind']==='fabric_sale' && ($thaan->ownership!=='own' ||
                        (!$contract && $thaan->contract_id !== null) ||
                        ($contract && $thaan->contract_id !== null && (int)$thaan->contract_id !== (int)$contract->id)))) {
                    throw ValidationException::withMessages(['thaan_ids'=>'All selected Thaans must be available, the same quality, from the selected factory and correct contract/ownership.']);
                }
            }
            $kachi=KachiParchi::create([
                'reference'=>ReferenceService::make('KCH'), 'dispatched_on'=>$d['dispatched_on'],
                'factory_id'=>$d['factory_id'], 'party_id'=>$d['party_id'],
                'contract_id'=>$contract?->id,'kind'=>$d['kind'],
                'rate_per_metre'=> $d['kind']==='fabric_sale' ? ($contract?->rate_per_metre ?? $d['rate_per_metre']):null,
            ]);
            AuditService::record('kachi.created',$kachi,['thaans'=>$items->count()]);
            foreach ($items as $item) {
                $kachi->items()->create(['thaan_id'=>$item->id,'metres'=>$item->metres]);
                $item->update(['status'=>'dispatched']);
            }
        });
        return back()->with('success','Kachi Parchi created; Thaans marked dispatched.');
    }
    public function show(KachiParchi $kachi)
    {
        $kachi->load(['party','factory','contract.quality','items.thaan','pakki','bill']);
        return view('dispatch.show',compact('kachi'));
    }
    public function storePakki(Request $request,KachiParchi $kachi,LedgerService $ledger)
    {
        $d=$request->validate(['accepted_on'=>'required|date','accepted_metres'=>'required|numeric|min:0',
            'rejected_metres'=>'required|numeric|min:0','accepted_thaan_count'=>'required|integer|min:0']);
        DB::transaction(function () use ($d,$kachi,$ledger) {
            $kachi=KachiParchi::whereKey($kachi->id)->lockForUpdate()->firstOrFail();
            if ($kachi->voided_at || $kachi->pakki()->exists()) throw ValidationException::withMessages(['accepted_metres'=>'Pakki already recorded for this Kachi.']);
            $items=$kachi->items()->lockForUpdate()->get();
            $total=round((float)$items->sum('metres'),2);
            if (abs(round((float)$d['accepted_metres']+(float)$d['rejected_metres'],2)-$total)>0.009)
                throw ValidationException::withMessages(['accepted_metres'=>'Accepted + rejected metres must equal Kachi dispatched metres ('.$total.').']);
            if ((int)$d['accepted_thaan_count']>$items->count()) throw ValidationException::withMessages(['accepted_thaan_count'=>'Accepted Thaan count exceeds dispatched count.']);
            if (((float)$d['rejected_metres']===0.0 && (int)$d['accepted_thaan_count']!==$items->count()) ||
                ((float)$d['rejected_metres']>0.0 && (int)$d['accepted_thaan_count']===$items->count()))
                throw ValidationException::withMessages(['accepted_thaan_count'=>'Accepted count must agree with complete-Thaan rejection.']);
            if (((float)$d['accepted_metres'] > 0 && (int)$d['accepted_thaan_count']===0) || (round((float)$d['accepted_metres'],2)==0.0 && (int)$d['accepted_thaan_count']>0))
                throw ValidationException::withMessages(['accepted_thaan_count'=>'Zero accepted metres must have zero accepted Thaans.']);
            $kachi->pakki()->create($d);
            if ($kachi->kind==='job_work') {
                $contract=$kachi->contract()->firstOrFail();
                if ($contract->yarn_supplier==='factory') {
                    $estimatedRate=$contract->parta_snapshot['results']['estimated_fabric_rate_per_metre'] ?? null;
                    if ($estimatedRate===null && (float)$d['accepted_metres']>0) {
                        throw ValidationException::withMessages(['accepted_metres'=>'This factory-yarn contract has no valid estimated fabric rate. Update the contract yarn rates before creating the Pakki bill.']);
                    }
                    $rate=(float)$estimatedRate;
                    $picks=null;
                    $amount=round((float)$d['accepted_metres']*$rate,2);
                } else {
                    $picks=(float)$contract->picks_at_contract;
                    $rate=(float)$contract->rate_per_pick;
                    $amount=round((float)$d['accepted_metres']*$picks*$rate,2);
                }
                $type='conversion';
            } else {
                $picks=null;
                $rate=(float)$kachi->rate_per_metre;
                $type='fabric_sale';
                $amount=round((float)$d['accepted_metres']*$rate,2);
            }
            $bill=Bill::create([
                'reference'=>ReferenceService::make('BIL'),'kachi_parchi_id'=>$kachi->id,
                'factory_id'=>$kachi->factory_id,'party_id'=>$kachi->party_id,'contract_id'=>$kachi->contract_id,
                'type'=>$type,'billed_on'=>$d['accepted_on'],'accepted_metres'=>$d['accepted_metres'],
                'picks'=>$picks,'rate'=>$rate,'total'=>$amount,
            ]);
            AuditService::record('pakki.accepted',$kachi->pakki,['bill'=>$bill->reference,'accepted_metres'=>$d['accepted_metres'],'rejected_metres'=>$d['rejected_metres']]);
            if ($amount>0) $ledger->post($bill->party_id,$bill->factory_id,'receivable',$bill->billed_on,
                ($type==='conversion'?($contract->yarn_supplier==='factory'?'Job Work bill (factory yarn) ':'Job Work conversion bill '):'Fabric sale ').$bill->reference,'bill',$bill->id,$amount,0);
            if (round((float)$d['rejected_metres'],2)>0) $kachi->update(['status'=>'pending_returns']);
            else {
                foreach ($items as $item) {
                    $item->update(['state'=>'accepted']);
                    $item->thaan()->update(['status'=>'accepted']);
                }
                $kachi->update(['status'=>'settled']);
            }
        });
        return redirect()->route('dispatch.show',$kachi)->with('success','Pakki recorded, bill generated from accepted metres, and customer receivable updated.');
    }
    public function storeReturns(Request $request,KachiParchi $kachi)
    {
        $d=$request->validate(['thaan_ids'=>'required|array|min:1','thaan_ids.*'=>'required|integer|distinct']);
        DB::transaction(function () use ($kachi,$d) {
            $kachi=KachiParchi::whereKey($kachi->id)->lockForUpdate()->firstOrFail();
            if ($kachi->status!=='pending_returns' || !$kachi->pakki) throw ValidationException::withMessages(['thaan_ids'=>'This Kachi has no outstanding complete-Thaan returns.']);
            $items=$kachi->items()->with('thaan')->lockForUpdate()->get();
            $returnItems=$items->whereIn('thaan_id',$d['thaan_ids']);
            $expectedCount=$items->count()-(int)$kachi->pakki->accepted_thaan_count;
            $sum=round((float)$returnItems->sum('metres'),2);
            if ($returnItems->count()!==count($d['thaan_ids']) || $returnItems->count()!==$expectedCount || abs($sum-(float)$kachi->pakki->rejected_metres)>0.009)
                throw ValidationException::withMessages(['thaan_ids'=>'Selected complete Thaans must match the rejected Thaan count ('.$expectedCount.') and original rejected lengths ('.$kachi->pakki->rejected_metres.' m).']);
            foreach ($items as $item) {
                if ($returnItems->contains('id',$item->id)) {
                    $item->update(['state'=>'returned']); $item->thaan->update(['status'=>'available']);
                } else {
                    $item->update(['state'=>'accepted']); $item->thaan->update(['status'=>'accepted']);
                }
            }
            $kachi->update(['status'=>'settled']);
            AuditService::record('rejected_thaans.returned',$kachi,['thaans'=>$returnItems->count(),'metres'=>$sum]);
        });
        return back()->with('success','Returned Thaans recorded at original lengths. Available stock updated; redispatch uses a new Kachi.');
    }
}
