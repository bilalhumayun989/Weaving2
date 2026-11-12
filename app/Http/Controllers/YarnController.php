<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, YarnPurchase};
use App\Services\{LedgerService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YarnController extends Controller
{
    public function index(LedgerService $ledger)
    {
        $suppliers=Party::whereIn('type',['supplier','both'])->orderBy('name')->get()->map(function($party) use($ledger){
            $party->balances=$ledger->balances($party); return $party;
        });
        return view('yarn.index', [
            'purchases' => YarnPurchase::with(['factory','party'])->latest('purchased_on')->paginate(30),
            'factories' => Factory::orderBy('name')->get(), 'parties' => Party::whereIn('type',['supplier','both'])->orderBy('name')->get(),
            'suppliers'=>$suppliers,
        ]);
    }
    public function storePurchase(Request $request, LedgerService $ledger)
    {
        $d = $request->validate([
            'factory_id'=>'required|exists:factories,id','party_id'=>'required|exists:parties,id',
            'purchased_on'=>'required|date','invoice_no'=>'nullable|string|max:100',
            'yarn_description'=>'required|string|max:255','bags'=>'required|numeric|gt:0',
            'rate_per_bag'=>'required|numeric|min:0','notes'=>'nullable|string|max:1000',
            'cones_per_bag'=>'nullable|integer|min:1|max:200',
        ]);
        $d['total'] = round((float)$d['bags'] * (float)$d['rate_per_bag'], 2);
        DB::transaction(function () use ($d, $ledger) {
            $p = YarnPurchase::create($d);
            AuditService::record('yarn_purchase.created',$p,['bags'=>$p->bags,'total'=>$p->total]);
            if ($p->total > 0) $ledger->post($p->party_id, $p->factory_id, 'payable', $p->purchased_on,
                'Yarn purchase: '.$p->yarn_description, 'yarn_purchase', $p->id, 0, (float)$p->total);
        });
        return back()->with('success','Yarn purchase recorded and supplier payable updated.');
    }
}
