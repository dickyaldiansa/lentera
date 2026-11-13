<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $query=Institution::query()->when($request->q,fn($q,$term)=>$q->where('name','like','%'.$term.'%'))
            ->when($request->status,fn($q,$status)=>$q->where('cooperation_status',$status));
        return view('directory.institutions',['institutions'=>$query->orderBy('name')->paginate(15)->withQueryString()]);
    }

    public function create(){return view('directory.institution-form',['institution'=>new Institution]);}

    public function store(Request $request)
    {
        $institution=Institution::create($this->validated($request));
        AuditService::record('master','institution.create',$institution,[],$institution->toArray());
        return redirect()->route('institutions')->with('success','Institusi berhasil ditambahkan.');
    }

    public function edit(Institution $institution){return view('directory.institution-form',compact('institution'));}

    public function update(Request $request,Institution $institution)
    {
        $old=$institution->toArray();$institution->update($this->validated($request,$institution));
        AuditService::record('master','institution.update',$institution,$old,$institution->fresh()->toArray());
        return redirect()->route('institutions')->with('success','Institusi berhasil diperbarui.');
    }

    public function status(Request $request,Institution $institution)
    {
        $data=$request->validate(['cooperation_status'=>'required|in:pending,active,inactive,expired']);$old=$institution->toArray();$institution->update($data);
        AuditService::record('master','institution.status',$institution,$old,$institution->fresh()->toArray());
        return back()->with('success','Status institusi berhasil diperbarui.');
    }

    private function validated(Request $request,?Institution $institution=null): array
    {
        return $request->validate([
            'name'=>['required','string','max:255',Rule::unique('institutions','name')->ignore($institution)],'type'=>'nullable|max:100','faculty'=>'nullable|max:150','study_program'=>'nullable|max:150','address'=>'nullable|string','pic_name'=>'nullable|max:150','email'=>'nullable|email|max:150','phone'=>'nullable|max:50','cooperation_status'=>'required|in:pending,active,inactive,expired','mou_number'=>'nullable|max:100','mou_start'=>'nullable|date','mou_end'=>'nullable|date|after_or_equal:mou_start',
        ]);
    }
}
