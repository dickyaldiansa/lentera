<?php

namespace App\Services;

use App\Models\{Application, ApplicationCoordinationTask, User, WorkflowAction, WorkflowInstance, WorkflowStep, WorkflowTask, WorkflowTemplate};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkflowEngine
{
    public function startOrResume(Model $workflowable, string $module = 'diklat'): ?WorkflowInstance
    {
        return DB::transaction(function () use ($workflowable, $module) {
            $existing = WorkflowInstance::where('workflowable_type', $workflowable->getMorphClass())
                ->where('workflowable_id', $workflowable->getKey())->first();

            if ($existing) {
                if ($existing->status === 'revision_required') {
                    $step = $existing->currentStep;
                    if($step?->behavior()==='ethics' && $workflowable instanceof Application){
                        $ethics=$workflowable->ethicsApplication;
                        $ethics?->update(['title'=>$workflowable->title,'institution_id'=>$workflowable->institution_id,'start_date'=>$workflowable->start_date,'end_date'=>$workflowable->end_date,'status'=>'administrative_verification']);
                        $ethics?->reviews()->update(['status'=>'assigned','completed_at'=>null,'recommendation'=>null,'scores'=>null]);
                    }
                    $existing->update(['status' => 'active', 'completed_at' => null]);
                    if ($step && ! $existing->tasks()->where('workflow_step_id', $step->id)->where('status', 'pending')->exists()) {
                        $this->createTask($existing, $step);
                    }
                    $this->updateWorkflowableStatus($workflowable, 'waiting_approval', 'Perbaikan dikirim kembali ke alur persetujuan.');
                }
                return $existing->fresh(['currentStep', 'tasks']);
            }

            $activityTypeId = $workflowable->activity_type_id ?? null;
            $template = $activityTypeId
                ? WorkflowTemplate::where('module', $module)->where('activity_type_id', $activityTypeId)->where('active', true)->first()
                : WorkflowTemplate::where('module', $module)->whereNull('activity_type_id')->where('active', true)->first();
            $template ??= WorkflowTemplate::where('module', $module)->whereNull('activity_type_id')->where('active', true)->first();
            $version = $template?->versions()->where('status', 'published')->latest('version_number')->first();
            if (! $version) return null;

            $steps = $version->steps->filter(fn ($step) => $this->matchesConditions($workflowable, $step));
            $firstStep = $steps->first();
            if (! $firstStep) return null;

            $instance = WorkflowInstance::create([
                'workflow_version_id' => $version->id,
                'workflowable_type' => $workflowable->getMorphClass(),
                'workflowable_id' => $workflowable->getKey(),
                'current_step_id' => $firstStep->id,
                'status' => 'active',
                'started_at' => now(),
            ]);
            $this->createTask($instance, $firstStep);
            $this->updateWorkflowableStatus($workflowable, 'waiting_approval', 'Workflow persetujuan dimulai: '.$template->name.'.');
            AuditService::record('workflow', 'started', $workflowable, [], ['workflow_instance_id' => $instance->id], $template->name);

            return $instance->fresh(['currentStep', 'tasks']);
        });
    }

    public function act(WorkflowTask $task, User $actor, string $action, ?string $note = null, ?User $nextAssignee = null, ?float $invoiceAmount = null, bool $automatic = false): WorkflowInstance
    {
        abort_unless(($automatic && ($task->step->conditions['managed']??false) && $task->step->behavior()==='finish') || $this->canAct($actor, $task), 403, 'Tugas persetujuan ini tidak ditugaskan kepada Anda.');
        abort_unless($task->status === 'pending', 422, 'Tugas ini sudah ditindaklanjuti.');
        abort_unless(in_array($action, ['approve', 'revision', 'reject', 'pending'], true), 422);
        abort_unless($action !== 'pending' || ($task->step->assignee_value === 'executive-approver' && !($task->step->conditions['managed']??false)) || ($task->step->conditions['allow_pending']??false), 422, 'Tahap ini tidak mengizinkan pending.');
        abort_unless($nextAssignee === null || ($task->step->behavior() === 'disposition' && $action === 'approve'), 422, 'Penerima hanya dipilih pada tahap disposisi.');
        abort_if($action === 'revision' && ! $task->step->allow_revision, 422, 'Tahap ini tidak mengizinkan permintaan perbaikan.');
        abort_if($action === 'reject' && ! $task->step->allow_reject, 422, 'Tahap ini tidak mengizinkan penolakan.');
        abort_if(in_array($action,['revision','reject','pending']) && blank($note),422,'Catatan wajib diisi.');
        if($action==='approve' && $task->step->behavior()==='disposition'){
            abort_unless($nextAssignee && $this->dispositionRecipients($task)->contains('id',$nextAssignee->id),422,'Pilih penerima disposisi yang sesuai tahap berikutnya.');
        }

        return DB::transaction(function () use ($task, $actor, $action, $note, $nextAssignee, $invoiceAmount) {
            $task = WorkflowTask::lockForUpdate()->with(['step', 'instance.version.steps'])->findOrFail($task->id);
            abort_unless($task->status === 'pending', 422, 'Tugas ini sudah ditindaklanjuti.');
            $instance = $task->instance;
            abort_unless($instance->status === 'active' && $instance->current_step_id === $task->workflow_step_id, 422, 'Tahap ini belum aktif.');
            $application = $instance->workflowable;
            if($action==='approve' && $task->step->behavior()==='ethics'){
                abort_unless($application instanceof Application && $application->ethicsApplication?->clearance,422,'Telaah etik harus selesai dan Ethical Clearance harus diterbitkan terlebih dahulu.');
            }
            if($action==='approve' && ($task->step->conditions['managed']??false) && in_array($task->step->behavior(),['coordination','handover'])){
                $coordination=$application->coordinationTasks()->latest('id')->first();
                abort_unless($coordination && ($task->step->behavior()==='handover'?$coordination->status==='handover_completed':in_array($coordination->status,['scheduled','handover_completed'])),422,'Selesaikan tindakan melalui halaman koordinasi KAMKORDIK.');
            }
            if ($task->step->conditions['payment_gate'] ?? false) {
                abort_unless($action === 'approve' && $application->invoice?->status === 'paid'
                    && $application->invoice->payments()->where('status', 'verified')->exists(), 422,
                    'Pembayaran harus diverifikasi melalui pemeriksaan bukti pembayaran.');
            }
            if ($action === 'approve' && ($task->step->conditions['creates_invoice'] ?? false)) {
                abort_unless($invoiceAmount !== null && $invoiceAmount > 0, 422, 'Diklat harus menetapkan nominal tagihan terlebih dahulu.');
                abort_if($application->invoice()->exists(), 422, 'Tagihan sudah diterbitkan.');
                $application->invoice()->create([
                    'number' => app(NumberGenerator::class)->next('invoice'), 'subtotal' => $invoiceAmount,
                    'total' => $invoiceAmount, 'status' => 'unpaid', 'due_date' => now()->addDays(7),
                    'items' => [['description' => 'Biaya '.$application->activityType->name, 'amount' => $invoiceAmount]],
                ]);
            }
            $status = match ($action) {'approve' => 'approved', 'revision' => 'revision_required', 'pending' => 'pending', default => 'rejected'};
            $task->update(['status' => $status, 'acted_by' => $actor->id, 'acted_at' => now(), 'note' => $note]);

            $roleName = $actor->roles->firstWhere('slug', $task->assignee_role_slug)?->name
                ?? $actor->roles->pluck('name')->join(', ');
            WorkflowAction::create([
                'workflow_task_id' => $task->id,
                'user_id' => $actor->id,
                'action' => $action,
                'actor_name' => $actor->name,
                'actor_position' => $actor->position ?: $roleName,
                'note' => $note,
                'metadata' => ['ip' => request()?->ip(), 'step' => $task->step->name],
                'acted_at' => now(),
            ]);

            if ($action === 'pending') {
                $task->update(['note' => $note]);
                AuditService::record('workflow', 'pending', $task->instance->workflowable, [], ['step' => $task->step->name, 'actor' => $actor->name], $note);
                return $task->instance->fresh(['currentStep', 'tasks.step', 'tasks.actions']);
            }

            $instance = $task->instance;
            $workflowable = $instance->workflowable;
            if ($action === 'approve' && $workflowable instanceof Application && $task->step->assignee_value === 'kamkordik-admin' && str_contains($task->step->name, 'Persamaan Persepsi')) {
                ApplicationCoordinationTask::firstOrCreate(
                    ['application_id' => $workflowable->id, 'recipient_role_slug' => 'kamkordik-admin'],
                    ['sender_id' => $actor->id, 'recipient_id' => $actor->id, 'note' => 'Persamaan persepsi disetujui. Lengkapi jadwal, PIC, instruksi, dan hasil koordinasi.']
                );
            }
            if ($action === 'revision' || ($action === 'reject' && ($task->step->conditions['reject_behavior']??'reject')==='revision')) {
                $target=$instance->version->steps->firstWhere('position',(int)($task->step->conditions['revision_target']??$task->step->position))??$task->step;
                $instance->update(['status' => 'revision_required','current_step_id'=>$target->id]);
                $this->updateWorkflowableStatus($workflowable, 'revision_required', $note ?: 'Perbaikan diminta pada tahap '.$task->step->name.'.');
            } elseif ($action === 'reject') {
                $instance->update(['status' => 'rejected', 'completed_at' => now()]);
                $this->updateWorkflowableStatus($workflowable, 'rejected', $note ?: 'Ditolak pada tahap '.$task->step->name.'.');
            } else {
                $next = $instance->version->steps
                    ->filter(fn ($step) => $step->position > $task->step->position && $this->matchesConditions($workflowable, $step))
                    ->sortBy('position')->first();
                if ($next) {
                    $instance->update(['current_step_id' => $next->id, 'status' => 'active']);
                    if(($next->conditions['managed']??false) && $next->behavior()==='finish' && $workflowable instanceof Application){
                        $terminal=$this->createTask($instance,$next);
                        AuditService::record('workflow',$action,$workflowable,[],['step'=>$task->step->name,'actor'=>$actor->name],$note);
                        return $this->act($terminal,$actor,'approve','Seluruh tahap layanan telah selesai.',null,null,true);
                    }
                    if ($next->name === 'Selesai' && $workflowable instanceof Application) {
                        $instance->update(['current_step_id' => null, 'status' => 'approved', 'completed_at' => now()]);
                        $completed = (bool)($next->conditions['completes_activity'] ?? false);
                        if ($completed) {
                            $terminalTask = $this->createTask($instance, $next);
                            $terminalTask->update(['status' => 'approved', 'acted_by' => $actor->id, 'acted_at' => now(), 'note' => 'Selesai otomatis setelah serah terima KAMKORDIK.']);
                            WorkflowAction::create([
                                'workflow_task_id' => $terminalTask->id, 'user_id' => $actor->id, 'action' => 'approve',
                                'actor_name' => $actor->name, 'actor_position' => $actor->position ?: $roleName,
                                'note' => 'Selesai otomatis setelah serah terima KAMKORDIK.', 'acted_at' => now(),
                            ]);
                        }
                        $this->updateWorkflowableStatus($workflowable, $completed ? 'completed' : 'approved', 'Serah terima selesai; seluruh tahapan workflow telah diselesaikan.');
                    } else {
                        $this->createTask($instance, $next, $nextAssignee);
                        if ($next->name === 'KAMKORDIK: Persamaan Persepsi' && $workflowable instanceof Application) {
                            ApplicationCoordinationTask::firstOrCreate(
                                ['application_id' => $workflowable->id, 'recipient_role_slug' => 'kamkordik-admin'],
                                ['sender_id' => $actor->id, 'recipient_id' => $actor->id, 'note' => 'Lengkapi jadwal, PIC, instruksi, dan hasil persamaan persepsi.']
                            );
                        }
                        $recipientLabel = $nextAssignee ? ' '.$nextAssignee->position.' '.$nextAssignee->name : '';
                        $this->updateWorkflowableStatus($workflowable, ($next->conditions['payment_gate'] ?? false) ? 'waiting_payment' : 'waiting_approval', $task->step->name.' disetujui; menunggu '.$next->name.$recipientLabel.'.');
                    }
                } else {
                    $instance->update(['current_step_id' => null, 'status' => 'approved', 'completed_at' => now()]);
                    if(($task->step->conditions['managed']??false) && $task->step->behavior()==='finish' && $workflowable instanceof Application){
                        $this->finishService($workflowable,$task->step);
                    }else $this->updateWorkflowableStatus($workflowable, 'approved', 'Seluruh tahapan workflow telah disetujui.');
                }
            }
            AuditService::record('workflow', $action, $workflowable, [], ['step' => $task->step->name, 'actor' => $actor->name], $note);

            return $instance->fresh(['currentStep', 'tasks.step', 'tasks.actions']);
        });
    }

    public function canAct(User $user, WorkflowTask $task): bool
    {
        if($user->hasRole('super-admin') && $task->step->behavior()==='payment')return true;
        if ($task->assignee_user_id === $user->id) return true;
        if ($task->assignee_role_slug && $user->hasRole($task->assignee_role_slug)) return true;
        if (! $task->assignee_user_id) return false;
        return DB::table('workflow_delegations')->where('from_user_id', $task->assignee_user_id)
            ->where('to_user_id', $user->id)->where('active', true)
            ->where('starts_at', '<=', now())->where('ends_at', '>=', now())->exists();
    }

    private function createTask(WorkflowInstance $instance, WorkflowStep $step, ?User $assignee = null): WorkflowTask
    {
        $assignment = $assignee
            ? ['assignee_user_id' => $assignee->id, 'assignee_role_slug' => null]
            : ($step->assignee_type === 'user'
            ? ['assignee_user_id' => (int) $step->assignee_value, 'assignee_role_slug' => null]
            : ['assignee_user_id' => null, 'assignee_role_slug' => $step->assignee_value]);
        $task=$instance->tasks()->create($assignment + [
            'workflow_step_id' => $step->id,
            'status' => 'pending',
            'due_at' => $step->sla_hours ? now()->addHours($step->sla_hours) : null,
        ]);
        $application=$instance->workflowable;
        if($application instanceof Application && ($step->conditions['managed']??false)){
            if($step->behavior()==='ethics') {
                $ethics=$application->ethicsApplication()->firstOrCreate([], [
                'number'=>app(NumberGenerator::class)->next('ethics'),'applicant_id'=>$application->applicant_id,'institution_id'=>$application->institution_id,
                'title'=>$application->title,'principal_investigator'=>$application->person_in_charge?:$application->applicant->name,
                'start_date'=>$application->start_date,'end_date'=>$application->end_date,'status'=>'administrative_verification','submitted_at'=>now(),
                ]);
                if(!$ethics->clearance && !$ethics->wasRecentlyCreated){
                    $ethics->update(['title'=>$application->title,'institution_id'=>$application->institution_id,'start_date'=>$application->start_date,'end_date'=>$application->end_date,'status'=>'administrative_verification']);
                    $ethics->reviews()->update(['status'=>'assigned','completed_at'=>null,'recommendation'=>null,'scores'=>null]);
                }
            }
            if($step->behavior()==='coordination'){
                $recipient=User::where('active',true)->whereHas('roles',fn($q)=>$q->where('slug','kamkordik-admin'))->firstOrFail();
                $application->coordinationTasks()->firstOrCreate(['recipient_role_slug'=>'kamkordik-admin'],['sender_id'=>auth()->id()??$application->applicant_id,'recipient_id'=>$recipient->id,'note'=>'Lengkapi persamaan persepsi sesuai alur layanan.']);
            }
            if($step->behavior()!=='finish'){
                $recipients=$assignee?collect([$assignee]):($step->assignee_type==='user'?User::whereKey($step->assignee_value)->get():User::where('active',true)->whereHas('roles',fn($q)=>$q->where('slug',$step->assignee_value))->get());
                foreach($recipients as $recipient)$recipient->notify(new \App\Notifications\ApplicationProgressNotification($application,'Tugas '.$step->name.' menunggu tindakan Anda.',route('approvals.show',$task)));
            }
        }
        return $task;
    }

    public function dispositionRecipients(WorkflowTask $task)
    {
        if(!($task->step->conditions['managed']??false))return User::where('active',true)->whereHas('roles',fn($q)=>$q->whereIn('slug',['director','deputy-director']))->with('roles')->get();
        $next=$task->instance->version->steps->first(fn($step)=>$step->position>$task->step->position && $this->matchesConditions($task->instance->workflowable,$step));
        if(!$next)return collect();
        return User::where('active',true)->when($next->assignee_type==='user',fn($q)=>$q->whereKey($next->assignee_value),fn($q)=>$q->whereHas('roles',fn($r)=>$r->where('slug',$next->assignee_value)))->with('roles')->get();
    }

    private function finishService(Application $application,WorkflowStep $step): void
    {
        $outcome=$step->conditions['outcome']??'information';
        if($outcome==='permit'){
            $application->letters()->create(['number'=>app(NumberGenerator::class)->next('letter'),'type'=>'permit','subject'=>'Izin '.$application->title,
                'content'=>$step->conditions['letter_content'],'issued_at'=>today(),'verification_token'=>\Illuminate\Support\Str::random(48),'status'=>'issued']);
            \App\Models\Activity::updateOrCreate(['application_id'=>$application->id],['title'=>$application->title,'start_at'=>$application->start_date->copy()->setTime(8,0),'end_at'=>$application->end_date->copy()->setTime(16,0),'location'=>$application->department?->name,'status'=>'scheduled']);
        }
        $this->updateWorkflowableStatus($application,$outcome==='permit'?'permit_issued':'completed',match($outcome){'permit'=>'Permohonan disetujui. Surat izin telah diterbitkan.','certificate'=>'Proses selesai. Peserta siap diproses pada menu Sertifikat.',default=>'Permohonan selesai. Silakan mengikuti informasi pelaksanaan dari rumah sakit.'});
    }

    public function requestEthicsRevision(\App\Models\EthicsApplication $ethics,User $actor,string $note):void
    {
        $application=$ethics->application;
        $instance=$application->workflowInstance;
        $task=$instance->tasks()->where('workflow_step_id',$instance->current_step_id)->where('status','pending')->firstOrFail();
        $task->update(['status'=>'revision_required','acted_by'=>$actor->id,'acted_at'=>now(),'note'=>$note]);
        WorkflowAction::create(['workflow_task_id'=>$task->id,'user_id'=>$actor->id,'action'=>'revision','actor_name'=>$actor->name,'actor_position'=>$actor->position,'note'=>$note,'acted_at'=>now()]);
        $instance->update(['status'=>'revision_required']);
        $this->updateWorkflowableStatus($application,'revision_required',$note);
        AuditService::record('workflow','ethics_revision',$application,[],['reviewer'=>$actor->id],$note);
    }

    private function matchesConditions(Model $workflowable, WorkflowStep $step): bool
    {
        $condition = $step->conditions['type'] ?? 'always';
        $type = $workflowable->relationLoaded('activityType') ? $workflowable->activityType : ($workflowable->activityType ?? null);
        return match ($condition) {
            'paid' => (bool) $type?->is_paid,
            'requires_ethics' => (bool) $type?->requires_ethics,
            'requires_kamkordik' => (bool) $type?->requires_kamkordik,
            default => true,
        };
    }

    private function updateWorkflowableStatus(Model $model, string $status, string $note): void
    {
        $from = $model->status;
        if ($from === $status) return;
        $model->update(['status' => $status]);
        if ($model instanceof Application) {
            $model->histories()->create(['from_status' => $from, 'to_status' => $status, 'note' => $note, 'changed_by' => auth()->id()]);
            if ($model->usesPaymentGate() || ($model->workflowInstance?->version->steps->first()?->conditions['managed']??false)) $model->applicant->notify(new \App\Notifications\ApplicationProgressNotification($model, $note));
        }
    }
}
