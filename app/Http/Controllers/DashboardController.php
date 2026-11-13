<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, Contract, Thaan, KachiParchi, YarnPurchase, Bill};
use App\Services\LedgerService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(LedgerService $ledger)
    {
        $parties=Party::all(); $balances=$parties->map(fn ($p)=>$ledger->balances($p));
        $today=Carbon::today();
        $dueYarnPurchases=YarnPurchase::with(['party','factory'])->withSum(['payments as linked_paid_total'=>fn($q)=>$q->whereNull('voided_at')],'amount')
            ->whereNotNull('payment_due_on')->whereNull('voided_at')->whereDate('payment_due_on','<=',$today->copy()->addDays(7))
            ->orderBy('payment_due_on')->get()->map(function($purchase) use ($today) {
                $purchase->outstanding_amount=max(0,round((float)$purchase->total-(float)($purchase->linked_paid_total??0),2));
                $due=Carbon::parse($purchase->payment_due_on)->startOfDay();
                $purchase->is_overdue=$due->lt($today);
                $purchase->days_remaining=$due->gte($today)?(int)$today->diffInDays($due):0;
                $purchase->days_overdue=$due->lt($today)?(int)$due->diffInDays($today):0;
                return $purchase;
            })->filter(fn($purchase)=>$purchase->outstanding_amount>0)->values();
        return view('dashboard', [
            'factoryCount'=>Factory::count(), 'activeContracts'=>Contract::where('status','active')->count(),
            'availableThaans'=>Thaan::where('status','available')->count(),
            'availableMetres'=>Thaan::where('status','available')->sum('metres'),
            'awaitingPakki'=>KachiParchi::where('status','dispatched')->count(),
            'pendingReturns'=>KachiParchi::where('status','pending_returns')->count(),
            'receivables'=>$balances->sum(fn ($b)=>max(0,$b['receivable'])),
            'payables'=>$balances->sum(fn ($b)=>max(0,$b['payable'])),
            'recentBills'=>Bill::with(['factory','party'])->latest('billed_on')->limit(7)->get(),
            'dueYarnPurchases'=>$dueYarnPurchases,
        ]);
    }
}
