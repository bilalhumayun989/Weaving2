<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Contract, FabricQuality, CheckingReport, Thaan};
use App\Services\{ReferenceService,AuditService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FabricController extends Controller
{
    public function index()
    {
        return view('fabric.index', [
            'checks' => CheckingReport::with(['factory','quality','contract','thaans.factory'])->withCount('thaans')->latest('checked_on')->paginate(25),
            'factories' => Factory::orderBy('name')->get(), 'qualities' => FabricQuality::orderBy('name')->get(),
            'contracts' => Contract::with('party')->where('status','active')->orderByDesc('id')->get(),
        ]);
    }
    public function stock(Request $request)
    {
        $query = Thaan::with(['factory','quality','contract','checkingReport'])->where('status','available');
        if ($request->filled('factory_id')) $query->where('factory_id', $request->integer('factory_id'));
        return view('fabric.stock', ['thaans' => $query->latest()->paginate(50), 'factories'=>Factory::all()]);
    }
    public function storeChecking(Request $request)
    {
        $d = $request->validate([
            'fabric_quality_id'=>'required|exists:fabric_qualities,id',
            'contract_id'=>'nullable|exists:contracts,id','entry_type'=>'required|in:job_work,own_stock,own_contract',
            'checked_on'=>'required|date','factory_rows'=>'required|array|min:1|max:20',
            'factory_rows.*.factory_id'=>'required|integer|distinct|exists:factories,id',
            'factory_rows.*.metres'=>'required|numeric|gt:0|max:99999999',
            'notes'=>'nullable|string|max:1500',
        ]);
        $contract = !empty($d['contract_id']) ? Contract::findOrFail($d['contract_id']) : null;
        if ($d['entry_type'] === 'own_stock' && $contract) throw ValidationException::withMessages(['contract_id'=>'Own stock must not have a contract.']);
        if ($d['entry_type'] !== 'own_stock' && !$contract) throw ValidationException::withMessages(['contract_id'=>'Select a contract for this entry type.']);
        if ($contract && $contract->status !== 'active') {
            throw ValidationException::withMessages(['contract_id'=>'The selected contract is not active. Choose an active contract.']);
        }
        if ($contract && (int)$contract->fabric_quality_id !== (int)$d['fabric_quality_id']) {
            throw ValidationException::withMessages(['fabric_quality_id'=>'Choose the fabric quality assigned to the selected contract.']);
        }
        // Check only factories that actually have rows. An All Factories contract
        // covers any subset; it does not require a row for every factory.
        if ($contract && $contract->factory_scope !== 'all' && collect($d['factory_rows'])->contains(fn($row)=>(int)$contract->factory_id !== (int)$row['factory_id'])) {
            throw ValidationException::withMessages(['factory_rows'=>'This single-factory contract only accepts stock from its assigned factory. For multiple factories, choose an All Factories contract.']);
        }
        if ($contract && (($d['entry_type']==='job_work' && $contract->type!=='job_work') || ($d['entry_type']==='own_contract' && $contract->type!=='fabric_rate')))
            throw ValidationException::withMessages(['entry_type'=>'Production ownership/type does not match the selected contract.']);
        $rows=array_map(fn($row)=>['factory_id'=>(int)$row['factory_id'],'metres'=>round((float)$row['metres'],2)],$d['factory_rows']);
        DB::transaction(function () use ($d,$rows) {
            $reference=ReferenceService::make('CHK');
            $primaryFactoryId=$rows[0]['factory_id'];
            $report = CheckingReport::create([
                'reference'=>$reference, 'checked_on'=>$d['checked_on'],
                'factory_id'=>$primaryFactoryId,'fabric_quality_id'=>$d['fabric_quality_id'],
                'contract_id'=>$d['contract_id'] ?? null,'entry_type'=>$d['entry_type'], 'notes'=>$d['notes'] ?? null,
            ]);
            AuditService::record('checking.created',$report,['factory_breakdown'=>$rows]);
            foreach ($rows as $index=>$row) $report->thaans()->create([
                'factory_id'=>$row['factory_id'],'fabric_quality_id'=>$report->fabric_quality_id,
                'contract_id'=>$report->contract_id,'ownership'=>$report->entry_type==='job_work'?'job_work':'own',
                'thaan_no'=>'LOT-'.$reference.'-'.str_pad((string)($index+1),2,'0',STR_PAD_LEFT),'metres'=>$row['metres'], 'status'=>'available',
            ]);
        });
        return back()->with('success', 'Factory totals recorded separately; the combined metre total is shown in the report. Stock lots are available for dispatch.');
    }
}
