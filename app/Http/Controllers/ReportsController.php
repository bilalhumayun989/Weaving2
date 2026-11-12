<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Contract, YarnPurchase, Bill, Thaan};

class ReportsController extends Controller
{
    public function index()
    {
        $factories=Factory::all()->map(function ($factory) {
            $factory->yarn_total=YarnPurchase::where('factory_id',$factory->id)->whereNull('voided_at')->sum('total');
            $factory->sales_total=Bill::where('factory_id',$factory->id)->where('type','fabric_sale')->sum('total');
            $factory->conversion_total=Bill::where('factory_id',$factory->id)->where('type','conversion')->sum('total');
            $factory->available_metres=Thaan::where('factory_id',$factory->id)->where('status','available')->sum('metres');
            return $factory;
        });
        $contracts=Contract::where('type','job_work')->where('yarn_supplier','customer')->with(['party','factory','quality'])->withSum('receipts','bags')->get();
        return view('reports.index', compact('factories','contracts'));
    }
}
