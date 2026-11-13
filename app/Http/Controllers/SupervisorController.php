<?php

namespace App\Http\Controllers;

use App\Models\{ClinicalSupervisor,Department,Role,User};
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupervisorController extends Controller
{
    public function index(Request $request){$items=ClinicalSupervisor::with(['department','user'])->when($request->q,fn($q,$term)=>$q->where('name','like','%'.$term.'%'))->orderBy('name')->paginate(15)->withQueryString();return view('kamkordik.supervisors',['supervisors'=>$items]);}
    public function create(){return $this->form(new ClinicalSupervisor);}
    public function store(Request $request){$supervisor=ClinicalSupervisor::create($this->validated($request));$this->ensureRole($supervisor);AuditService::record('kamkordik','supervisor.create',$supervisor,[],$supervisor->toArray());return redirect()->route('supervisors.index')->with('success','Pembimbing klinis berhasil ditambahkan.');}
    public function edit(ClinicalSupervisor $supervisor){return $this->form($supervisor);}
    public function update(Request $request,ClinicalSupervisor $supervisor){$old=$supervisor->toArray();$supervisor->update($this->validated($request,$supervisor));$this->ensureRole($supervisor);AuditService::record('kamkordik','supervisor.update',$supervisor,$old,$supervisor->fresh()->toArray());return redirect()->route('supervisors.index')->with('success','Pembimbing klinis berhasil diperbarui.');}
    public function status(ClinicalSupervisor $supervisor){$old=$supervisor->toArray();$supervisor->update(['active'=>!$supervisor->active]);AuditService::record('kamkordik','supervisor.status',$supervisor,$old,$supervisor->fresh()->toArray());return back()->with('success','Status pembimbing berhasil diperbarui.');}
    private function form(ClinicalSupervisor $supervisor){return view('kamkordik.supervisor-form',['supervisor'=>$supervisor,'departments'=>Department::where('active',1)->orderBy('name')->get(),'users'=>User::where('active',1)->orderBy('name')->get()]);}
    private function validated(Request $request,?ClinicalSupervisor $supervisor=null): array{return $request->validate(['user_id'=>['nullable','exists:users,id',Rule::unique('clinical_supervisors','user_id')->ignore($supervisor)],'department_id'=>'required|exists:departments,id','name'=>'required|max:255','professional_number'=>'nullable|max:100','specialization'=>'nullable|max:150','active'=>'required|boolean']);}
    private function ensureRole(ClinicalSupervisor $supervisor): void {if($supervisor->user_id && $role=Role::where('slug','clinical-supervisor')->first())$supervisor->user->roles()->syncWithoutDetaching([$role->id]);}
}
