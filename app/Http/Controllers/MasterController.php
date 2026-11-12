<?php
namespace App\Http\Controllers;

use App\Models\{Factory, Party, FabricQuality};
use Illuminate\Http\Request;
use App\Services\AuditService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterController extends Controller
{
    public function index()
    {
        return view('setup.index', [
            'factories' => Factory::orderBy('name')->get(),
            'parties' => Party::orderBy('name')->get(),
            'qualities' => FabricQuality::orderBy('name')->get(),
        ]);
    }
    public function storeFactory(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:150|unique:factories,name']);
         $factory=Factory::create($data);
        AuditService::record('factory.created',$factory);
        return redirect()->route('setup.index')->with('success', 'Factory added.');
    }
    public function storeParty(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:parties,name',
            'type' => 'required|in:customer,supplier,both',
            'phone' => 'nullable|string|max:60', 'address' => 'nullable|string|max:1000',
            'opening_receivable' => 'nullable|numeric|min:0', 'opening_payable' => 'nullable|numeric|min:0',
        ]);
         $party=Party::create($data);
        AuditService::record('party.created',$party);
        return redirect()->route('setup.index')->with('success', 'Party added.');
    }
    public function storeQuality(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:fabric_qualities,name',
            'picks' => 'nullable|numeric|gt:0|max:10000',
            'warp_count' => 'nullable|string|max:80', 'weft_count' => 'nullable|string|max:80',
            'width' => 'nullable|string|max:80', 'notes' => 'nullable|string|max:2000',
        ]);
         $quality=FabricQuality::create($data);
        AuditService::record('fabric_quality.created',$quality);
        if($request->expectsJson()) return response()->json(['quality'=>$quality],201);
        return redirect()->route('setup.index')->with('success', 'Fabric quality added.');
    }
    private function record(string $kind,int $id)
    {
        return match ($kind) {
            'factory'=>Factory::findOrFail($id),
            'party'=>Party::findOrFail($id),
            'quality'=>FabricQuality::findOrFail($id),
            default=>abort(404),
        };
    }
    public function edit(string $kind,int $id)
    {
        AuditService::adminOnly();
        return view('setup.edit',['kind'=>$kind,'record'=>$this->record($kind,$id)]);
    }
    public function update(Request $request,string $kind,int $id)
    {
        AuditService::adminOnly();
        $record=$this->record($kind,$id);
        if($kind==='factory') {
            $d=$request->validate(['name'=>['required','string','max:150',Rule::unique('factories','name')->ignore($id)]]);
        } elseif($kind==='party') {
            $d=$request->validate([
                'name'=>['required','string','max:150',Rule::unique('parties','name')->ignore($id)],
                'type'=>'required|in:customer,supplier,both','phone'=>'nullable|string|max:60','address'=>'nullable|string|max:1000',
            ]);
        } else {
            $d=$request->validate([
                'name'=>['required','string','max:150',Rule::unique('fabric_qualities','name')->ignore($id)],
                'picks'=>'nullable|numeric|gt:0|max:10000','warp_count'=>'nullable|string|max:80',
                'weft_count'=>'nullable|string|max:80','width'=>'nullable|string|max:80','notes'=>'nullable|string|max:2000',
            ]);
        }
        $before=$record->only(array_keys($d));
        $record->update($d);
        AuditService::record('master.updated',$record,['before'=>$before,'after'=>$d]);
        return redirect()->route('setup.index')->with('success','Master record updated. Existing contract rates and pick snapshots remain unchanged.');
    }

}
