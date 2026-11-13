<?php

namespace App\Services;

use App\Models\{ActivityType, WorkflowTemplate};
use Illuminate\Support\Facades\DB;

class InternshipPaymentWorkflow
{
    public function publish(): void
    {
        DB::transaction(function () {
            foreach (ActivityType::whereIn('name', ['Magang', 'Praktik / PKL', 'Praktik/PKL', 'Praktik'])->get() as $type) {
                $template = WorkflowTemplate::firstOrCreate(
                    ['module' => 'diklat', 'activity_type_id' => $type->id],
                    ['name' => 'Alur Pengajuan '.$type->name, 'active' => true]
                );
                $latest = $template->versions()->latest('version_number')->first();
                if ($latest?->steps()->where('conditions->payment_gate', true)->exists()) continue;
                $version = $template->versions()->create([
                    'version_number' => ($latest?->version_number ?? 0) + 1,
                    'status' => 'published', 'published_at' => now(),
                ]);
                $steps = [
                    ['Disposisi Arsiparis', 'verification', 'arsiparis'],
                    ['Persetujuan Direktur atau Wakil Direktur', 'approval', 'executive-approver'],
                    ['Persetujuan Kepala Bagian Umum', 'approval', 'head-general'],
                    ['Diklat: Verifikasi Administrasi', 'verification', 'diklat-admin', ['creates_invoice' => true]],
                    ['Pembayaran Pemohon dan Verifikasi Diklat', 'finance', 'diklat-admin', ['payment_gate' => true]],
                    ['KAMKORDIK: Persamaan Persepsi', 'review', 'kamkordik-admin'],
                    ['KAMKORDIK: Serah Terima', 'approval', 'kamkordik-admin'],
                    ['Selesai', 'approval', 'diklat-admin', ['completes_activity' => true]],
                ];
                foreach ($steps as $index => $step) {
                    $version->steps()->create([
                        'position' => $index + 1, 'name' => $step[0], 'action_type' => $step[1],
                        'assignee_type' => 'role', 'assignee_value' => $step[2], 'sla_hours' => 24,
                        'allow_revision' => !in_array($index, [1, 4]), 'allow_reject' => $index !== 4,
                        'conditions' => ['type' => 'always'] + ($step[3] ?? []),
                    ]);
                }
            }
        });
    }
}
