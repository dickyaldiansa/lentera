<?php
namespace App\Http\Controllers;
use App\Models\{Institution,Role,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function login(){return view('auth.login');}
 public function authenticate(Request $r){$data=$r->validate(['email'=>'required|email','password'=>'required']); if(Auth::attempt($data,$r->boolean('remember'))){$r->session()->regenerate();return redirect()->intended('/dashboard');} return back()->withErrors(['email'=>'Email atau kata sandi tidak sesuai.'])->onlyInput('email');}
 public function register(){return view('auth.register',['institutions'=>Institution::orderBy('name')->get()]);}
 public function store(Request $r){$data=$r->validate(['nik'=>'required|unique:users','name'=>'required','email'=>'required|email|unique:users','phone'=>'required','password'=>'required|min:8|confirmed','institution_name'=>'required|string|max:255','position'=>'nullable']);$institutionName=trim($data['institution_name']);$institution=Institution::whereRaw('lower(name) = lower(?)',[$institutionName])->first()??Institution::create(['name'=>$institutionName,'type'=>'Institusi Pemohon','cooperation_status'=>'pending']);unset($data['institution_name']);$data['institution_id']=$institution->id;$user=User::create($data);$user->roles()->attach(Role::where('slug','applicant')->value('id'));Auth::login($user);return redirect('/dashboard')->with('success','Akun berhasil dibuat.');}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
}
