<?php
namespace App\Http\Controllers;
use App\Models\{Party, Thaan, YarnPurchase, Bill,Factory};
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
class ExportController extends Controller
{
    private function csv(string $filename,array $header,iterable $rows)
    {
        return response()->streamDownload(function () use($header,$rows) {
            $out=fopen('php://output','w');
            // UTF-8 BOM for Excel including Urdu-party text support.
            fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,$header);
            foreach($rows as $row) {
                // Avoid spreadsheet formula injection from party names or descriptions.
                $safe=array_map(function ($v) {
                    $s=(string)($v??'');
                    return is_numeric($s) ? $s : (preg_match('/^\s*[=+@\-\t\r]/u',$s) ? "'".$s : $s);
                },$row);
                fputcsv($out,$safe);
            }
            fclose($out);
        },$filename,['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function ledger(Party $party,LedgerService $service)
    {
        $rows=[];
        foreach(['receivable','payable'] as $account) {
            $timeline=$service->timeline($party,$account);
            $rows[]=[$account,'','Opening balance','','','',$timeline['opening']];
            foreach($timeline['rows'] as $r) {
                $e=$r['entry']; $rows[]=[$account,$e->entry_on,$e->description,$e->factory?->name,$e->debit,$e->credit,$r['balance']];
            }
        }
        return $this->csv('ledger-party-'.$party->id.'.csv',['Account','Date','Transaction','Factory','Debit','Credit','Running balance'],$rows);
    }
    public function stock(Request $request)
    {
        $q=Thaan::with('factory','quality','contract')->where('status','available');
        if($request->filled('factory_id')) $q->where('factory_id',$request->integer('factory_id'));
        return $this->csv('available-fabric-stock.csv',['Factory','Quality','Thaan','Metres','Ownership','Contract'],
            $q->orderBy('factory_id')->orderBy('thaan_no')->get()->map(fn($t)=>[$t->factory->name,$t->quality->name,$t->thaan_no,$t->metres,$t->ownership,$t->contract?->reference]));
    }
    public function transactions(Request $request)
    {
        $kind=$request->query('kind','bills');
        if($kind==='purchases') {
            return $this->csv('yarn-purchases.csv',['Date','Factory','Supplier','Invoice','Yarn','Bags','Rate','Total','Contract','Payment Terms (days)','Due Date','Broker / Terms Note','Status'],
                YarnPurchase::with(['factory','party','contract','yarnQuality'])->orderBy('purchased_on')->get()->map(fn($p)=>[$p->purchased_on,$p->factory_scope==='all'?'All Factories':$p->factory->name,$p->party->name,$p->invoice_no,$p->yarnQuality?->name??$p->yarn_description,$p->bags,$p->rate_per_bag,$p->total,$p->contract?->reference,$p->payment_terms_days,$p->payment_due_on,$p->payment_terms_note,$p->voided_at?'Reversed':'Active']));
        }
        return $this->csv('fabric-sales-conversion-bills.csv',['Date','Bill','Factory','Party','Type','Status','Metres','Rate','Total'],
            Bill::with('factory','party')->orderBy('billed_on')->get()->map(fn($b)=>[$b->billed_on,$b->reference,$b->factory->name,$b->party->name,$b->type,$b->billing_status,$b->accepted_metres,$b->rate,$b->total]));
    }

    public function sales(Request $request)
    {
        $request->validate([
            'date_from'=>'nullable|date','date_to'=>'nullable|date|after_or_equal:date_from',
            'factory_id'=>'nullable|integer|exists:factories,id','party_id'=>'nullable|integer|exists:parties,id',
            'contract_id'=>'nullable|integer|exists:contracts,id',
            'type'=>'nullable|in:conversion,fabric_sale','status'=>'nullable|in:provisional,final,void',
        ]);
        $rows=$this->salesQuery($request)->with(['factory','party','contract.quality'])->orderBy('billed_on')->orderBy('id')->get();
        return $this->csv('sales-ledger-report.csv',[
            'Date','Bill','Status','Party','Factory','Contract','Quality','Sale Type','Metres','Rate Basis','Rate','Effective Rate / Metre','Sale / Receivable Amount (PKR)',
        ],$rows->map(function($bill) {
            $factoryYarn=$bill->type==='conversion' && $bill->contract?->yarn_supplier==='factory';
            $perMetre=$bill->type==='conversion' && !$factoryYarn?(float)$bill->picks*(float)$bill->rate:(float)$bill->rate;
            $basis=$bill->type==='conversion' && !$factoryYarn?'Per pick':'Per metre';
            return [
                $bill->billed_on,$bill->reference,$bill->billing_status,$bill->party->name,$bill->factory->name,
                $bill->contract?->reference,$bill->contract?->quality?->name,$bill->type,$bill->accepted_metres,
                $basis,$bill->rate,$perMetre,$bill->billing_status==='void'?0:$bill->total,
            ];
        }));
    }

    private function salesQuery(Request $request): Builder
    {
        return Bill::query()
            ->when($request->filled('date_from'),fn($q)=>$q->whereDate('billed_on','>=',$request->input('date_from')))
            ->when($request->filled('date_to'),fn($q)=>$q->whereDate('billed_on','<=',$request->input('date_to')))
            ->when($request->filled('factory_id'),fn($q)=>$q->where('factory_id',$request->integer('factory_id')))
            ->when($request->filled('party_id'),fn($q)=>$q->where('party_id',$request->integer('party_id')))
            ->when($request->filled('contract_id'),fn($q)=>$q->where('contract_id',$request->integer('contract_id')))
            ->when($request->filled('type'),fn($q)=>$q->where('type',$request->input('type')))
            ->when($request->filled('status'),fn($q)=>$q->where('billing_status',$request->input('status')));
    }
}
