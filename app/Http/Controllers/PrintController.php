<?php
namespace App\Http\Controllers;
use App\Models\{Bill, KachiParchi, YarnPurchase, Party};
use App\Services\LedgerService;
class PrintController extends Controller
{
    public function kachi(KachiParchi $kachi)
    {
        $kachi->load(['factory','party','contract','items.thaan','pakki','bill']);
        return view('print.kachi',['kachi'=>$kachi,'printTitle'=>'Kachi Parchi']);
    }
    public function pakki(KachiParchi $kachi)
    {
        $kachi->load(['factory','party','contract','items.thaan','pakki','bill']);
        abort_unless($kachi->pakki && !$kachi->voided_at,404);
        return view('print.pakki',['kachi'=>$kachi,'printTitle'=>'Pakki Parchi']);
    }
    public function bill(Bill $bill)
    {
        $bill->load(['factory','party','contract.quality','kachi.items.thaan','kachi.pakki']);
        return view('print.bill',['bill'=>$bill,'printTitle'=>'Bill / Invoice']);
    }
    public function purchase(YarnPurchase $purchase)
    {
        $purchase->load(['factory','party']);
        return view('print.purchase',['purchase'=>$purchase,'printTitle'=>'Yarn Purchase']);
    }
    public function ledger(Party $party,LedgerService $service)
    {
        return view('print.ledger',['party'=>$party,'receivable'=>$service->timeline($party,'receivable'),
            'payable'=>$service->timeline($party,'payable'),'printTitle'=>'Party Account Statement']);
    }
}
