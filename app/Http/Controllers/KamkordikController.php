<?php

namespace App\Http\Controllers;

use App\Models\{ApplicationCoordinationTask,ClinicalRotation,ClinicalSupervisor,Department,EducationProgram,Institution,Logbook,Student};
use App\Notifications\KamkordikScheduleNotification;
use App\Services\{AuditService,NumberGenerator,WorkflowEngine};
use Illuminate\Http\Request;

class KamkordikController extends Controller
{
    public function index(Request $request)
    {
        $students=Student::with(['institution','department','program','rotations']);
        $this->scopeStudents($request,$students);
        $studentIds=(clone $students)->pluck('students.id');
        return view('kamkordik.index',['students'=>$students->latest()->paginate(12),'pending'=>Logbook::whereIn('student_id',$studentIds)->where('status','submitted')->count()]);
    }

    public function coordinations(Request $request)
    {
        $status=$request->get('status','active');
        $filters=[
            'active'=>['pending','needs_information','scheduled','needs_revision'],
            'completed'=>['handover_completed','completed'],
            'not_ready'=>['not_ready'],
        ];
        abort_unless(array_key_exists($status,$filters),404);
        $roleSlugs=$request->user()->roles->pluck('slug');
        $base=ApplicationCoordinationTask::where(function ($query) use ($request, $roleSlugs) {
            $query->where('recipient_id', $request->user()->id)
                ->orWhereIn('recipient_role_slug', $roleSlugs);
        });
        $tasks=(clone $base)->with(['application.applicant','application.activityType','sender'])
            ->whereIn('status',$filters[$status])->latest()->paginate(15)->withQueryString();
        $counts=[
            'active'=>(clone $base)->whereIn('status',$filters['active'])->count(),
            'completed'=>(clone $base)->whereIn('status',$filters['completed'])->count(),
            'not_ready'=>(clone $base)->whereIn('status',$filters['not_ready'])->count(),
        ];
        return view('kamkordik.coordinations',compact('tasks','status','counts'));
    }

    public function showCoordination(Request $request, ApplicationCoordinationTask $coordination)
    {
        $roleSlugs = $request->user()->roles->pluck('slug');
        abort_unless($coordination->recipient_id === $request->user()->id || ($coordination->recipient_role_slug && $roleSlugs->contains($coordination->recipient_role_slug)) || $request->user()->hasRole('super-admin'), 403);
        $coordination->load(['application.applicant', 'application.activityType', 'sender']);
        return view('kamkordik.coordination-detail', compact('coordination'));
    }

    public function respondCoordination(Request $request, ApplicationCoordinationTask $coordination, WorkflowEngine $workflow)
    {
        abort_unless($coordination->recipient_id===$request->user()->id || ($coordination->recipient_role_slug && $request->user()->hasRole($coordination->recipient_role_slug)) || $request->user()->hasRole('super-admin'),403);
        abort_unless(!in_array($coordination->status,['handover_completed','completed'],true),422,'Koordinasi ini sudah dinyatakan selesai.');
        $data=$request->validate([
            'status'=>'required|in:needs_information,scheduled,handover_completed,not_ready',
            'institution_contact'=>'nullable|string|max:255',
            'institution_contact_phone'=>'nullable|string|max:50',
            'scheduled_at'=>'nullable|date',
            'location_or_meeting_link'=>'nullable|string|max:500',
            'hospital_pic'=>'nullable|string|max:255',
            'applicant_instruction'=>'nullable|string|max:2000',
            'response'=>'required|string|max:2000',
            'notify_applicant'=>'nullable|boolean',
        ]);
        $data['notify_applicant']=$request->boolean('notify_applicant');
        if ($coordination->application->usesPaymentGate() || ($coordination->application->workflowInstance?->currentStep?->conditions['managed']??false)) {
            $instance = $coordination->application->workflowInstance;
            abort_unless((!$coordination->application->usesPaymentGate() || $coordination->application->invoice?->status === 'paid') && $instance->status === 'active'
                && $instance->currentStep?->assignee_value === 'kamkordik-admin', 422, 'Tahap KAMKORDIK belum aktif.');
            if ($data['status'] === 'handover_completed') {
                $handover = $instance->tasks()->where('workflow_step_id', $instance->current_step_id)->where('status','pending')->first();
                abort_unless($handover && $handover->step->behavior() === 'handover', 422, 'Selesaikan persamaan persepsi sebelum serah terima.');
                abort_unless($workflow->canAct($request->user(), $handover), 403);
            }
        }
        $data['responded_at']=now();
        if($data['status']==='handover_completed') $data['handover_completed_at']=now();
        $old=$coordination->toArray();
        $coordination->update($data);
        if ($data['status'] === 'scheduled') {
            $perceptionTask = $coordination->application->workflowInstance?->tasks()
                ->where('status', 'pending')
                ->whereHas('step', fn ($query) => $query->where('name', 'KAMKORDIK: Persamaan Persepsi')->orWhere('conditions->behavior','coordination'))
                ->with('step')
                ->first();
            if ($perceptionTask && $workflow->canAct($request->user(), $perceptionTask)) {
                $workflow->act($perceptionTask, $request->user(), 'approve', 'Persamaan persepsi telah selesai dan jadwal telah ditetapkan.');
                if(($perceptionTask->step->conditions['managed']??false) && $perceptionTask->instance->fresh()->currentStep?->behavior()!=='handover') $coordination->update(['status'=>'completed']);
            }
        }
        if ($data['status'] === 'handover_completed') {
            $handoverTask = $coordination->application->workflowInstance?->tasks()
                ->where('status', 'pending')
                ->whereHas('step', fn ($query) => $query->where('name', 'KAMKORDIK: Serah Terima')->orWhere('conditions->behavior','handover'))
                ->with('step')
                ->first();
            if ($handoverTask && $workflow->canAct($request->user(), $handoverTask)) {
                $workflow->act($handoverTask, $request->user(), 'approve', 'Serah terima mahasiswa praktik/magang telah dicatat melalui koordinasi KAMKORDIK.');
            }
        }
        $labels=['needs_information'=>'Memerlukan data/kontak tambahan','scheduled'=>'Jadwal persamaan persepsi ditetapkan','handover_completed'=>'Serah terima terlaksana — siap penempatan','not_ready'=>'Belum siap ditempatkan'];
        $note='Koordinasi KAMKORDIK: '.$labels[$data['status']].'. '.$data['response'];
        $coordination->application->histories()->create(['from_status'=>$coordination->application->status,'to_status'=>$coordination->application->status,'note'=>$note,'changed_by'=>$request->user()->id]);
        $coordination->load('application.applicant');
        if($coordination->notify_applicant) $coordination->application->applicant->notify(new KamkordikScheduleNotification($coordination));
        AuditService::record('kamkordik','coordination.respond',$coordination,$old,$coordination->fresh()->toArray(),$data['response']);
        return back()->with('success','Pembaruan koordinasi berhasil disimpan.'.($coordination->notify_applicant?' Pemohon telah diberi notifikasi.':''));
    }

    public function createStudent(){return $this->studentForm(new Student);}

    public function storeStudent(Request $request,NumberGenerator $numbers)
    {
        $data=$this->validateStudent($request);$data['number']=$numbers->next('student');
        $student=Student::create($data);AuditService::record('kamkordik','student.create',$student,[],$student->toArray());
        return redirect()->route('kamkordik.index')->with('success','Peserta didik berhasil ditambahkan.');
    }

    public function editStudent(Student $student){return $this->studentForm($student);}

    public function updateStudent(Request $request,Student $student)
    {
        $old=$student->toArray();$student->update($this->validateStudent($request,true));
        AuditService::record('kamkordik','student.update',$student,$old,$student->fresh()->toArray());
        return redirect()->route('kamkordik.index')->with('success','Data peserta berhasil diperbarui.');
    }

    public function statusStudent(Request $request,Student $student)
    {
        $data=$request->validate(['status'=>'required|in:active,inactive,completed,dropped']);$old=$student->toArray();$student->update($data);
        AuditService::record('kamkordik','student.status',$student,$old,$student->fresh()->toArray());
        return back()->with('success','Status peserta berhasil diperbarui.');
    }

    public function rotations(Request $request)
    {
        $rotations=ClinicalRotation::with(['student','department','supervisor']);$this->scopeRotations($request,$rotations);
        return view('kamkordik.rotations',['rotations'=>$rotations->latest()->paginate(15),'students'=>Student::where('status','active')->orderBy('name')->get(),'departments'=>Department::where('active',1)->orderBy('name')->get(),'supervisors'=>ClinicalSupervisor::where('active',1)->orderBy('name')->get()]);
    }

    public function storeRotation(Request $request)
    {
        $data=$this->validateRotation($request);if($response=$this->overlapResponse($data))return $response;
        $rotation=ClinicalRotation::create($data);AuditService::record('kamkordik','rotation.create',$rotation,[],$rotation->toArray());
        return redirect()->route('rotations.index')->with('success','Rotasi berhasil dijadwalkan.');
    }

    public function editRotation(ClinicalRotation $rotation)
    {
        return view('kamkordik.rotation-form',['rotation'=>$rotation,'students'=>Student::where('status','active')->orWhere('id',$rotation->student_id)->orderBy('name')->get(),'departments'=>Department::where('active',1)->orWhere('id',$rotation->department_id)->orderBy('name')->get(),'supervisors'=>ClinicalSupervisor::where('active',1)->orWhere('id',$rotation->clinical_supervisor_id)->orderBy('name')->get()]);
    }

    public function updateRotation(Request $request,ClinicalRotation $rotation)
    {
        $data=$this->validateRotation($request,true);if($response=$this->overlapResponse($data,$rotation))return $response;
        $old=$rotation->toArray();$rotation->update($data);AuditService::record('kamkordik','rotation.update',$rotation,$old,$rotation->fresh()->toArray());
        return redirect()->route('rotations.index')->with('success','Jadwal rotasi berhasil diperbarui.');
    }

    public function cancelRotation(ClinicalRotation $rotation)
    {
        $old=$rotation->toArray();$rotation->update(['status'=>'cancelled']);AuditService::record('kamkordik','rotation.cancel',$rotation,$old,$rotation->fresh()->toArray());
        return back()->with('success','Rotasi berhasil dibatalkan tanpa menghapus riwayat.');
    }

    public function logbooks(Request $request)
    {
        $query=Logbook::with(['student','rotation.department']);$rotations=ClinicalRotation::with('student')->whereIn('status',['scheduled','running']);$this->scopeLogbooks($request,$query);$this->scopeRotations($request,$rotations);
        return view('kamkordik.logbooks',['logbooks'=>$query->latest('activity_date')->paginate(15),'rotations'=>$rotations->get()]);
    }

    public function storeLogbook(Request $request)
    {
        $data=$request->validate(['clinical_rotation_id'=>'required|exists:clinical_rotations,id','activity_date'=>'required|date','room'=>'nullable','activity_type'=>'required','anonymous_case_number'=>'nullable','age_group'=>'nullable','patient_gender'=>'nullable|in:L,P','diagnosis'=>'nullable','procedure'=>'nullable','student_role'=>'nullable','note'=>'nullable']);
        $rotation=ClinicalRotation::with('student')->findOrFail($data['clinical_rotation_id']);abort_unless($rotation->student->user_id===$request->user()->id,403,'Anda hanya dapat mengisi logbook milik sendiri.');$data['student_id']=$rotation->student_id;$data['clinical_supervisor_id']=$rotation->clinical_supervisor_id;
        $logbook=Logbook::create($data);AuditService::record('kamkordik','logbook.create',$logbook,[],$logbook->toArray());return back()->with('success','Logbook disimpan.');
    }

    public function verifyLogbook(Request $request,Logbook $logbook)
    {
        if($request->user()->hasRole('clinical-supervisor'))abort_unless($logbook->rotation?->supervisor?->user_id===$request->user()->id,403,'Logbook ini bukan penugasan Anda.');
        $data=$request->validate(['status'=>'required|in:verified,rejected','supervisor_note'=>'nullable']);$old=$logbook->toArray();$logbook->update($data+['verified_at'=>now()]);AuditService::record('kamkordik','logbook.verify',$logbook,$old,$logbook->fresh()->toArray());return back()->with('success','Logbook telah ditinjau.');
    }

    private function studentForm(Student $student){return view('kamkordik.student-form',['student'=>$student,'institutions'=>Institution::orderBy('name')->get(),'departments'=>Department::where('active',true)->orWhere('id',$student->department_id)->orderBy('name')->get(),'programs'=>EducationProgram::where('active',1)->orWhere('id',$student->education_program_id)->orderBy('name')->get()]);}
    private function validateStudent(Request $request,bool $updating=false): array{return $request->validate(['name'=>'required|max:255','nim'=>'nullable|max:100','institution_id'=>'required|exists:institutions,id','department_id'=>'nullable|exists:departments,id','education_program_id'=>'required|exists:education_programs,id','email'=>'nullable|email|max:255','phone'=>'nullable|max:50','gender'=>'nullable|in:L,P','period_start'=>'required|date','period_end'=>'required|date|after_or_equal:period_start']+($updating?['status'=>'required|in:active,inactive,completed,dropped']:[]));}
    private function validateRotation(Request $request,bool $updating=false): array{return $request->validate(['student_id'=>'required|exists:students,id','department_id'=>'required|exists:departments,id','clinical_supervisor_id'=>'nullable|exists:clinical_supervisors,id','stage'=>'required|max:255','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date']+($updating?['status'=>'required|in:scheduled,running,completed,cancelled']:[]));}
    private function overlapResponse(array $data,?ClinicalRotation $current=null){$query=ClinicalRotation::where('student_id',$data['student_id'])->where('status','!=','cancelled')->where('start_date','<=',$data['end_date'])->where('end_date','>=',$data['start_date']);if($current)$query->where('id','!=',$current->id);return $query->exists()?back()->withErrors(['start_date'=>'Periode rotasi bentrok dengan rotasi lain.'])->withInput():null;}
    private function scopeStudents(Request $request,$query): void {if($request->user()->hasRole('student'))$query->where('user_id',$request->user()->id);elseif($request->user()->hasRole('clinical-supervisor'))$query->whereHas('rotations.supervisor',fn($q)=>$q->where('user_id',$request->user()->id));}
    private function scopeRotations(Request $request,$query): void {if($request->user()->hasRole('student'))$query->whereHas('student',fn($q)=>$q->where('user_id',$request->user()->id));elseif($request->user()->hasRole('clinical-supervisor'))$query->whereHas('supervisor',fn($q)=>$q->where('user_id',$request->user()->id));}
    private function scopeLogbooks(Request $request,$query): void {if($request->user()->hasRole('student'))$query->whereHas('student',fn($q)=>$q->where('user_id',$request->user()->id));elseif($request->user()->hasRole('clinical-supervisor'))$query->whereHas('rotation.supervisor',fn($q)=>$q->where('user_id',$request->user()->id));}
}
