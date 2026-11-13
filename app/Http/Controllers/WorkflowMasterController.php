<?php

namespace App\Http\Controllers;

use App\Models\{ActivityType, Role, User, WorkflowTemplate};
use App\Services\{AuditService, WorkflowConfiguration};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkflowMasterController extends Controller
{
    public function index()
    {
        return view('workflows.master-index', [
            'templates'=>WorkflowTemplate::with(['activityType','latestVersion.steps','publishedVersion.steps'])->latest()->get(),
            'activityTypes'=>ActivityType::where('active',true)->orderBy('name')->get(),
        ]);
    }
    public function create(Request $request) { return $this->form(new WorkflowTemplate(['activity_type_id'=>$request->integer('activity_type_id') ?: null])); }
    public function edit(WorkflowTemplate $workflowTemplate) { return $this->form($workflowTemplate); }
    public function store(Request $request, WorkflowConfiguration $configuration) { return $this->save($request,new WorkflowTemplate,$configuration); }
    public function update(Request $request, WorkflowTemplate $workflowTemplate, WorkflowConfiguration $configuration) { return $this->save($request,$workflowTemplate,$configuration); }

    private function save(Request $request, WorkflowTemplate $template, WorkflowConfiguration $configuration)
    {
        $data=$request->validate([
            'name'=>'required|string|max:255','module'=>['required',Rule::in(['diklat','ethics','kamkordik'])],
            'activity_type_id'=>'nullable|exists:activity_types,id','description'=>'nullable|string|max:5000',
            'active'=>'nullable|boolean','intent'=>['nullable',Rule::in(['draft','publish'])],
            'document_requirements'=>'nullable|string|max:10000',
            'steps'=>'required|array|min:1|max:40','steps.*.name'=>'required|string|max:255',
            'steps.*.source_step_id'=>'nullable|integer','steps.*.action_type'=>['required',Rule::in(['verification','review','approval','finance','signature'])],
            'steps.*.assignee'=>['required','regex:/^(role|user):[A-Za-z0-9_-]+$/'],
            'steps.*.sla_hours'=>'nullable|integer|min:1|max:8760',
            'steps.*.condition'=>['required',Rule::in(['always','paid','requires_ethics','requires_kamkordik'])],
            'steps.*.behavior'=>['nullable',Rule::in(array_keys(WorkflowConfiguration::BEHAVIORS))],
            'steps.*.allow_revision'=>'nullable|boolean','steps.*.allow_reject'=>'nullable|boolean','steps.*.allow_pending'=>'nullable|boolean',
            'steps.*.revision_target'=>'nullable|integer|min:1|max:40',
            'steps.*.reject_behavior'=>['nullable',Rule::in(['reject','revision'])],
            'steps.*.outcome'=>['nullable',Rule::in(['information','permit','certificate'])],
            'steps.*.letter_content'=>'nullable|string|max:10000',
        ]);
        $publishing=($data['intent']??'publish')==='publish';
        DB::transaction(function()use($request,$template,$configuration,$data,$publishing){
            if($template->exists) $template=WorkflowTemplate::lockForUpdate()->findOrFail($template->id);
            if($template->exists && $template->versions()->where('status','published')->exists()
                && ($template->module!==$data['module'] || (string)$template->activity_type_id!==(string)($data['activity_type_id']??''))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['activity_type_id'=>'Kategori dan modul alur yang sudah diterbitkan tidak dapat dipindahkan. Buat alur terpisah.']);
            }
            $duplicate=WorkflowTemplate::where('module',$data['module'])->where('activity_type_id',$data['activity_type_id']??null)->when($template->exists,fn($q)=>$q->whereKeyNot($template->id))->exists();
            if($duplicate && (isset($data['intent']) || $template->exists)) throw \Illuminate\Validation\ValidationException::withMessages(['activity_type_id'=>'Kategori ini sudah memiliki pengaturan alur. Edit alur yang tersedia.']);
            $steps=$configuration->prepare($data,$template->exists?$template:null,$publishing);
            if(!$template->exists || $publishing) {
                $template->fill(['name'=>$data['name'],'module'=>$data['module'],'activity_type_id'=>$data['activity_type_id']??null,
                    'description'=>$data['description']??null,'active'=>$publishing?$request->boolean('active'):false,'created_by'=>$template->created_by??$request->user()->id])->save();
            }
            $version=$template->versions()->create(['version_number'=>(int)$template->versions()->max('version_number')+1,
                'status'=>$publishing?'published':'draft','published_at'=>$publishing?now():null,'created_by'=>$request->user()->id]);
            foreach($steps as $i=>$step){
                [$kind,$value]=explode(':',$step['assignee'],2);
                $settings=$step['settings'];
                if($i===0) $settings['editor']=['name'=>$data['name'],'description'=>$data['description']??'', 'active'=>$request->boolean('active')];
                $version->steps()->create(['position'=>$i+1,'name'=>$step['name'],'action_type'=>$step['action_type'],
                    'assignee_type'=>$kind,'assignee_value'=>$value,'sla_hours'=>$step['sla_hours']?:null,
                    'allow_revision'=>$step['allow_revision'],'allow_reject'=>$step['allow_reject'],'conditions'=>$settings]);
            }
            AuditService::record('master.workflow',$publishing?'publish_version':'save_draft',$template,[],['version'=>$version->version_number,'status'=>$version->status]);
        });
        return redirect()->route('workflow-master.index')->with('success',$publishing?'Versi alur diterbitkan. Pengajuan berjalan tetap memakai versi sebelumnya.':'Draf alur disimpan. Alur aktif belum berubah.');
    }

    private function form(WorkflowTemplate $template)
    {
        $version=$template->exists?$template->latestVersion:null;
        return view('workflows.master-form',[
            'template'=>$template,'version'=>$version,'activityTypes'=>ActivityType::where('active',true)->orderBy('name')->get(),
            'roles'=>Role::where('scope','internal')->orderBy('name')->get(),
            'users'=>User::where('active',true)->whereHas('roles',fn($q)=>$q->where('scope','internal'))->orderBy('name')->get(),
            'behaviors'=>WorkflowConfiguration::BEHAVIORS,
        ]);
    }
}
