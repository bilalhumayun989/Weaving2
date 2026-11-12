<?php
namespace App\Http\Controllers;
use App\Models\{Party, Thaan, YarnPurchase, Bill,Factory};
use App\Services\LedgerService;
use Illuminate\Http\Request;
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
            return $this->csv('yarn-purchases.csv',['Date','Factory','Supplier','Invoice','Yarn','Bags','Rate','Total','Status'],
                YarnPurchase::with('factory','party')->orderBy('purchased_on')->get()->map(fn($p)=>[$p->purchased_on,$p->factory->name,$p->party->name,$p->invoice_no,$p->yarn_description,$p->bags,$p->rate_per_bag,$p->total,$p->voided_at?'Reversed':'Active']));
        }
        return $this->csv('fabric-sales-conversion-bills.csv',['Date','Bill','Factory','Party','Type','Status','Metres','Rate','Total'],
            Bill::with('factory','party')->orderBy('billed_on')->get()->map(fn($b)=>[$b->billed_on,$b->reference,$b->factory->name,$b->party->name,$b->type,$b->billing_status,$b->accepted_metres,$b->rate,$b->total]));
    }
}
