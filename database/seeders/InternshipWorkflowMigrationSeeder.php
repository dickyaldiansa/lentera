<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationCoordinationTask;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\WorkflowEngine;
use Illuminate\Database\Seeder;

class InternshipWorkflowMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = app(WorkflowEngine::class);
        $actor = User::where('email', 'sunartititin5678@gmail.com')->firstOrFail();
        auth()->login($actor);

        Application::query()
            ->whereIn('status', ['draft', 'submitted'])
            ->whereDoesntHave('workflowInstance')
            ->whereHas('activityType', fn ($query) => $query->where('name', 'Magang'))
            ->each(function (Application $application) use ($workflow) {
                if ($application->status === 'draft') {
                    $application->update(['status' => 'submitted', 'submitted_at' => now()]);
                    $application->histories()->create([
                        'from_status' => 'draft',
                        'to_status' => 'submitted',
                        'note' => 'Pengajuan Magang diteruskan ke Arsiparis.',
                        'changed_by' => auth()->id(),
                    ]);
                }
                $workflow->startOrResume($application);
            });

        WorkflowInstance::query()
            ->with(['workflowable.activityType', 'tasks.step'])
            ->where('workflowable_type', Application::class)
            ->each(function (WorkflowInstance $instance) {
                $application = $instance->workflowable;
                if (! $application instanceof Application || $application->activityType?->name !== 'Magang') {
                    return;
                }
                $persamaanPersepsi = $instance->tasks->first(fn ($task) => $task->step?->name === 'KAMKORDIK: Persamaan Persepsi' && $task->status === 'approved');
                if ($persamaanPersepsi?->acted_by) {
                    $coordination = ApplicationCoordinationTask::firstOrNew(['application_id' => $application->id]);
                    $coordination->fill([
                        'sender_id' => $coordination->sender_id ?: $persamaanPersepsi->acted_by,
                        'recipient_id' => $coordination->recipient_id ?: $persamaanPersepsi->acted_by,
                        'recipient_role_slug' => 'kamkordik-admin',
                        'note' => $coordination->note ?: 'Persamaan persepsi disetujui. Lengkapi jadwal, PIC, instruksi, dan hasil koordinasi.',
                    ])->save();
                }
                $handoverTask = $instance->tasks->first(fn ($task) => $task->step?->name === 'KAMKORDIK: Serah Terima' && $task->status === 'pending');
                if ($handoverTask) {
                    $handoverTask->update(['assignee_user_id' => null, 'assignee_role_slug' => 'kamkordik-admin']);
                }
            });
    }
}
