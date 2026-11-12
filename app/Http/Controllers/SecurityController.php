<?php
namespace App\Http\Controllers;
use App\Models\{AuditLog, User};
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
class SecurityController extends Controller
{
    public function index()
    {
        AuditService::adminOnly();
        return view('security.index', [
            'users'=>User::orderBy('name')->get(),
            'logs'=>AuditLog::with('user')->latest('id')->paginate(70),
        ]);
    }
    public function createUser(Request $request)
    {
        AuditService::adminOnly();
        $d=$request->validate([
            'name'=>'required|string|max:100', 'email'=>'required|email|max:255|unique:users,email',
            'role'=>'required|in:admin,operator', 'password'=>['required','confirmed',Password::min(12)->letters()->numbers()],
        ]);
        $u=new User();
        $u->name=$d['name']; $u->email=$d['email']; $u->role=$d['role'];
        $u->password=Hash::make($d['password']); $u->save();
        AuditService::record('user.created',$u,['role'=>$u->role]);
        return back()->with('success','User account created.');
    }
    public function passwordForm() { return view('security.password'); }
    public function changePassword(Request $request)
    {
        $d=$request->validate([
            'current_password'=>'required|current_password',
            'password'=>['required','confirmed',Password::min(12)->letters()->numbers()],
        ]);
        $u=$request->user(); $u->password=Hash::make($d['password']); $u->save();
        AuditService::record('password.changed',$u);
        return back()->with('success','Password updated.');
    }
}
