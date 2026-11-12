<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Contract, YarnPurchase, Bill, Thaan, Party};
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ReportsController extends Controller
{
    public function index()
    {
        $factories=Factory::all()->map(function ($factory) {
            $factory->yarn_total=YarnPurchase::where('factory_id',$factory->id)->whereNull('voided_at')->sum('total');
            $factory->sales_total=Bill::where('factory_id',$factory->id)->where('type','fabric_sale')->whereIn('billing_status',['provisional','final'])->sum('total');
            $factory->conversion_total=Bill::where('factory_id',$factory->id)->where('type','conversion')->whereIn('billing_status',['provisional','final'])->sum('total');
            $factory->available_metres=Thaan::where('factory_id',$factory->id)->where('status','available')->sum('metres');
            return $factory;
        });
        $contracts=Contract::where('type','job_work')->where('yarn_supplier','customer')->with(['party','factory','quality'])->withSum('receipts','bags')->get();
        return view('reports.index', compact('factories','contracts'));
    }

    public function sales(Request $request)
    {
        $this->validateSalesFilters($request);
        $base=$this->salesQuery($request);
        $summary=(clone $base)->selectRaw("SUM(CASE WHEN billing_status = 'provisional' THEN total ELSE 0 END) as provisional_total")
            ->selectRaw("SUM(CASE WHEN billing_status = 'final' THEN total ELSE 0 END) as final_total")
            ->selectRaw("SUM(CASE WHEN billing_status = 'void' THEN total ELSE 0 END) as void_total")
            ->selectRaw("SUM(CASE WHEN billing_status IN ('provisional','final') THEN total ELSE 0 END) as receivable_sales_total")
            ->selectRaw("SUM(CASE WHEN billing_status IN ('provisional','final') THEN accepted_metres ELSE 0 END) as receivable_metres")
            ->first();
        return view('reports.sales',[
            'sales'=>(clone $base)->with(['party','factory','contract'])->orderByDesc('billed_on')->orderByDesc('id')->paginate(50)->withQueryString(),
            'summary'=>$summary,
            'factories'=>Factory::orderBy('name')->get(),'parties'=>Party::whereIn('type',['customer','both'])->orderBy('name')->get(),
            'contracts'=>Contract::orderByDesc('id')->get(),'filters'=>$request->only(['date_from','date_to','factory_id','party_id','contract_id','type','status']),
        ]);
    }

    public function contractLedger(Request $request)
    {
        $this->validateSalesFilters($request);
        $base=$this->salesQuery($request);
        $contractSales=$this->contractSales($base);
        $sales=(clone $base)->whereNotNull('contract_id')->with(['party','factory','contract'])->orderBy('contract_id')->orderByDesc('billed_on')->orderByDesc('id')->paginate(50)->withQueryString();
        return view('reports.contracts',[
            'contractSales'=>$contractSales,'sales'=>$sales,
            'factories'=>Factory::orderBy('name')->get(),'parties'=>Party::whereIn('type',['customer','both'])->orderBy('name')->get(),
            'contracts'=>Contract::orderByDesc('id')->get(),'filters'=>$request->only(['date_from','date_to','factory_id','party_id','contract_id','type','status']),
        ]);
    }

    private function validateSalesFilters(Request $request): void
    {
        $request->validate([
            'date_from'=>'nullable|date','date_to'=>'nullable|date|after_or_equal:date_from',
            'factory_id'=>'nullable|integer|exists:factories,id','party_id'=>'nullable|integer|exists:parties,id',
            'contract_id'=>'nullable|integer|exists:contracts,id',
            'type'=>'nullable|in:conversion,fabric_sale','status'=>'nullable|in:provisional,final,void',
        ]);
    }

    private function contractSales(Builder $base)
    {
        $rows=(clone $base)->whereNotNull('contract_id')->select('contract_id')
            ->selectRaw("SUM(CASE WHEN billing_status = 'provisional' THEN total ELSE 0 END) as provisional_total")
            ->selectRaw("SUM(CASE WHEN billing_status = 'final' THEN total ELSE 0 END) as final_total")
            ->selectRaw("SUM(CASE WHEN billing_status = 'void' THEN total ELSE 0 END) as void_total")
            ->selectRaw("SUM(CASE WHEN billing_status IN ('provisional','final') THEN total ELSE 0 END) as receivable_total")
            ->groupBy('contract_id')->orderBy('contract_id')->get();
        $contracts=Contract::with(['party','factory'])->whereIn('id',$rows->pluck('contract_id'))->get()->keyBy('id');
        return $rows->each(fn($row)=>$row->setRelation('contract',$contracts->get($row->contract_id)));
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
