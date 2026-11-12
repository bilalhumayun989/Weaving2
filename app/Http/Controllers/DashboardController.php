<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, Contract, Thaan, KachiParchi, YarnPurchase, Bill};
use App\Services\LedgerService;

class DashboardController extends Controller
{
    public function index(LedgerService $ledger)
    {
        $parties=Party::all(); $balances=$parties->map(fn ($p)=>$ledger->balances($p));
        return view('dashboard', [
            'factoryCount'=>Factory::count(), 'activeContracts'=>Contract::where('status','active')->count(),
            'availableThaans'=>Thaan::where('status','available')->count(),
            'availableMetres'=>Thaan::where('status','available')->sum('metres'),
            'awaitingPakki'=>KachiParchi::where('status','dispatched')->count(),
            'pendingReturns'=>KachiParchi::where('status','pending_returns')->count(),
            'receivables'=>$balances->sum(fn ($b)=>max(0,$b['receivable'])),
            'payables'=>$balances->sum(fn ($b)=>max(0,$b['payable'])),
            'recentBills'=>Bill::with(['factory','party'])->latest('billed_on')->limit(7)->get(),
        ]);
    }
}
