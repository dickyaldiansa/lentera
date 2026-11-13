<?php

namespace App\Http\Controllers;

use App\Models\{ApplicationCoordinationTask, User, WorkflowAction, WorkflowTask};
use App\Services\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $base = WorkflowTask::query()->forUser($request->user());
        $stats = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'revision' => (clone $base)->where('status', 'revision_required')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
        ];
        $status = $request->get('status', 'pending');
        $history = (clone $base)->with(['step', 'instance.workflowable'])->whereNotNull('acted_at')->latest('acted_at')->limit(10)->get();
        $tasks = (clone $base)->with(['step', 'instance.version.template', 'instance.version.steps', 'instance.tasks.step', 'instance.workflowable.applicant', 'instance.workflowable.institution'])
            ->whereDoesntHave('step', fn ($query) => $query->whereIn('name', ['KAMKORDIK: Persamaan Persepsi', 'KAMKORDIK: Serah Terima'])->orWhereIn('conditions->behavior',['coordination','handover']))
            ->where('status', $status)->latest()->paginate(12)->withQueryString();
        return view('approvals.index', compact('tasks', 'stats', 'status', 'history'));
    }

    public function show(Request $request, WorkflowTask $workflowTask, WorkflowEngine $engine)
    {
        abort_unless($engine->canAct($request->user(), $workflowTask) || $workflowTask->acted_by === $request->user()->id || $request->user()->hasRole('super-admin'), 403);
        if (in_array($workflowTask->step->behavior(), ['coordination', 'handover'], true)) {
            $coordination = ApplicationCoordinationTask::where('application_id', $workflowTask->instance->workflowable_id)->latest()->first();
            if ($coordination) {
                return redirect()->route('kamkordik.coordinations.show', $coordination);
            }
        }
        $workflowTask->load(['step', 'actions.user', 'instance.version.template', 'instance.version.steps', 'instance.tasks.step', 'instance.tasks.actions', 'instance.workflowable.applicant', 'instance.workflowable.institution', 'instance.workflowable.activityType']);
        if ($workflowTask->step->conditions['payment_gate'] ?? false) {
            return redirect()->route('applications.show', $workflowTask->instance->workflowable_id);
        }
        if($workflowTask->step->behavior()==='ethics' && $workflowTask->instance->workflowable->ethicsApplication)return redirect()->route('ethics.show',$workflowTask->instance->workflowable->ethicsApplication);
        $executiveRecipients = $workflowTask->step->behavior() === 'disposition' ? $engine->dispositionRecipients($workflowTask) : collect();
        return view('approvals.show', ['task' => $workflowTask, 'canAct' => $workflowTask->status === 'pending' && $engine->canAct($request->user(), $workflowTask), 'executiveRecipients' => $executiveRecipients]);
    }

    public function act(Request $request, WorkflowTask $workflowTask, WorkflowEngine $engine)
    {
        $workflowTask->loadMissing('step');
        $requiresExecutiveRecipient = $workflowTask->step->behavior() === 'disposition' && $request->input('action') === 'approve';
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'revision', 'reject', 'pending'])],
            'note' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn () => in_array($request->action, ['revision', 'reject', 'pending'], true))],
            'recipient_id' => [Rule::requiredIf($requiresExecutiveRecipient), 'nullable', 'integer', 'exists:users,id'],
            'invoice_amount' => [Rule::requiredIf($request->action === 'approve' && ($workflowTask->step->conditions['creates_invoice'] ?? false)), 'nullable', 'numeric', 'min:1'],
            'decision_file' => ['nullable','file','mimes:pdf,doc,docx,jpg,jpeg,png','max:10240'],
        ]);
        $recipient = null;
        if ($requiresExecutiveRecipient) {
            $recipient = $engine->dispositionRecipients($workflowTask)->firstWhere('id',(int)$data['recipient_id']);
            abort_unless($recipient,422,'Penerima tidak sesuai tahap berikutnya.');
        }
        \Illuminate\Support\Facades\DB::transaction(function()use($engine,$workflowTask,$request,$data,$recipient){
            $engine->act($workflowTask, $request->user(), $data['action'], $data['note'] ?? null, $recipient, isset($data['invoice_amount']) ? (float)$data['invoice_amount'] : null);
            if($request->hasFile('decision_file')){
                $action=$workflowTask->actions()->latest('id')->firstOrFail();
                $file=$request->file('decision_file');
                $action->update(['metadata'=>array_merge($action->metadata??[],['attachment_path'=>$file->store('workflow-decisions/'.$workflowTask->id,'local'),'attachment_name'=>$file->getClientOriginalName()])]);
            }
        });
        $message = $recipient ? 'Disposisi berhasil dikirim ke '.$recipient->position.'.' : match ($data['action']) {'approve' => 'Persetujuan berhasil dicatat.', 'revision' => 'Permintaan perbaikan dikirim kepada pemohon.', 'pending' => 'Pengajuan ditandai pending. Keterangan telah dicatat.', default => 'Pengajuan telah ditolak.'};
        return redirect()->route('approvals.index')->with('success', $message);
    }

    public function attachment(Request $request,WorkflowTask $workflowTask,WorkflowAction $workflowAction,WorkflowEngine $engine)
    {
        abort_unless($workflowAction->workflow_task_id===$workflowTask->id,404);
        $application=$workflowTask->instance->workflowable;
        abort_unless($engine->canAct($request->user(),$workflowTask)||$workflowTask->acted_by===$request->user()->id||$application->applicant_id===$request->user()->id||$request->user()->hasRole('super-admin'),403);
        $path=$workflowAction->metadata['attachment_path']??null;
        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path),404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download($path,$workflowAction->metadata['attachment_name']??'surat-keterangan.pdf');
    }
}
