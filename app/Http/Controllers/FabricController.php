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
            'checks' => CheckingReport::with(['factory','quality','contract'])->withCount('thaans')->latest('checked_on')->paginate(25),
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
            'factory_id'=>'required|exists:factories,id','fabric_quality_id'=>'required|exists:fabric_qualities,id',
            'contract_id'=>'nullable|exists:contracts,id','entry_type'=>'required|in:job_work,own_stock,own_contract',
            'checked_on'=>'required|date','thaans'=>'required|string|max:20000',
            'notes'=>'nullable|string|max:1500',
        ]);
        $contract = !empty($d['contract_id']) ? Contract::findOrFail($d['contract_id']) : null;
        if ($d['entry_type'] === 'own_stock' && $contract) throw ValidationException::withMessages(['contract_id'=>'Own stock must not have a contract.']);
        if ($d['entry_type'] !== 'own_stock' && !$contract) throw ValidationException::withMessages(['contract_id'=>'Select a contract for this entry type.']);
        if ($contract && ((int)$contract->factory_id !== (int)$d['factory_id'] || (int)$contract->fabric_quality_id !== (int)$d['fabric_quality_id'] || $contract->status !== 'active'))
            throw ValidationException::withMessages(['contract_id'=>'The contract must be active and match the selected factory and quality.']);
        if ($contract && (($d['entry_type']==='job_work' && $contract->type!=='job_work') || ($d['entry_type']==='own_contract' && $contract->type!=='fabric_rate')))
            throw ValidationException::withMessages(['entry_type'=>'Production ownership/type does not match the selected contract.']);
        $lines = preg_split('/\r\n|\r|\n/', trim($d['thaans']));
        if (count($lines) > 150) throw ValidationException::withMessages(['thaans'=>'Maximum 150 Thaans per checking report.']);
        $rows = []; $seen = [];
        foreach ($lines as $line) {
            $bits = array_map('trim', explode(',', $line));
            if (count($bits)!==2 || $bits[0]==='' || strlen($bits[0])>100 || !is_numeric($bits[1]) || (float)$bits[1]<=0)
                throw ValidationException::withMessages(['thaans'=>'Use one Thaan per line: Thaan Number, Metres (e.g., A001, 68.5).']);
            $number = $bits[0];
            if (isset($seen[$number])) throw ValidationException::withMessages(['thaans'=>'Repeated Thaan number: '.$number]);
            $seen[$number] = true;
            $metres = round((float)$bits[1], 2);
            if ($metres <= 0 || $metres > 99999999) throw ValidationException::withMessages(['thaans'=>'Invalid Thaan length.']);
            $rows[] = ['thaan_no'=>$number,'metres'=>$metres];
        }
        $existing=Thaan::where('factory_id',$d['factory_id'])
            ->whereIn('thaan_no',array_column($rows,'thaan_no'))->pluck('thaan_no');
        if($existing->isNotEmpty()) throw ValidationException::withMessages([
            'thaans'=>'Already registered in this factory: '.$existing->implode(', ').'. Correct the entry before saving.',
        ]);
        DB::transaction(function () use ($d,$rows) {
            $report = CheckingReport::create([
                'reference'=>ReferenceService::make('CHK'), 'checked_on'=>$d['checked_on'],
                'factory_id'=>$d['factory_id'],'fabric_quality_id'=>$d['fabric_quality_id'],
                'contract_id'=>$d['contract_id'] ?? null,'entry_type'=>$d['entry_type'], 'notes'=>$d['notes'] ?? null,
            ]);
            AuditService::record('checking.created',$report,['thaans'=>count($rows)]);
            foreach ($rows as $row) $report->thaans()->create([
                'factory_id'=>$report->factory_id,'fabric_quality_id'=>$report->fabric_quality_id,
                'contract_id'=>$report->contract_id,'ownership'=>$report->entry_type==='job_work'?'job_work':'own',
                'thaan_no'=>$row['thaan_no'],'metres'=>$row['metres'], 'status'=>'available',
            ]);
        });
        return back()->with('success', 'Checked fabric recorded; Thaans are available for dispatch. No production entry required.');
    }
}
