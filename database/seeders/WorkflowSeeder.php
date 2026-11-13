<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::updateOrCreate(
            ['slug' => 'workflow.approve'],
            ['name' => 'Workflow Approve']
        );

        $roleDefinitions = [
            'diklat-admin' => 'Admin Diklat',
            'diklat-coordinator' => 'Penanggung Jawab Diklat',
            'kamkordik-admin' => 'Admin KAMKORDIK',
            'kamkordik-head' => 'Ketua KAMKORDIK',
            'ethics-admin' => 'Sekretariat Komite Etik',
            'ethics-reviewer' => 'Reviewer Komite Etik',
            'ethics-chair' => 'Ketua Komite Etik',
            'unit-head' => 'Kepala Unit',
            'division-head' => 'Kepala Bidang',
            'finance-officer' => 'Pejabat Keuangan',
            'head-general' => 'Kepala Bagian Umum',
            'deputy-director' => 'Wakil Direktur',
            'director' => 'Direktur',
            'executive-approver' => 'Direktur atau Wakil Direktur',
        ];
        foreach ($roleDefinitions as $slug => $name) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'scope' => 'internal']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $institutionId = Institution::where('name', 'RSUD Embung Fatimah Kota Batam')->value('id');
        $officials = [
            ['kepalaunit@lentera.test', 'dr. Kepala Unit Demo', 'Kepala Unit', 'unit-head'],
            ['kepalabidang@lentera.test', 'dr. Kepala Bidang Demo', 'Kepala Bidang Pelayanan', 'division-head'],
            ['keuangan@lentera.test', 'Pejabat Keuangan Demo', 'Verifikator Keuangan', 'finance-officer'],
            ['wadir@lentera.test', 'dr. Wakil Direktur Demo', 'Wakil Direktur', 'deputy-director'],
            ['direktur@lentera.test', 'dr. Direktur Demo', 'Direktur', 'director'],
        ];
        foreach ($officials as [$email, $name, $position, $roleSlug]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name, 'position' => $position, 'institution_id' => $institutionId,
                'password' => Hash::make('password'), 'active' => true,
            ]);
                $roleIds = Role::whereIn('slug', in_array($roleSlug, ['director', 'deputy-director'], true)
                    ? [$roleSlug, 'executive-approver']
                    : [$roleSlug])->pluck('id');
                $user->roles()->sync($roleIds);
        }

        $adminId = User::where('email', 'admin@lentera.test')->value('id');
        $steps = [
            ['Verifikasi Admin Diklat', 'verification', 'diklat-admin', 24, 'always'],
            ['Persetujuan Penanggung Jawab Diklat', 'approval', 'diklat-coordinator', 24, 'always'],
            ['Verifikasi Admin KAMKORDIK', 'verification', 'kamkordik-admin', 24, 'requires_kamkordik'],
            ['Persetujuan Ketua KAMKORDIK', 'approval', 'kamkordik-head', 24, 'requires_kamkordik'],
            ['Verifikasi Sekretariat Komite Etik', 'verification', 'ethics-admin', 24, 'requires_ethics'],
            ['Review Etik', 'review', 'ethics-reviewer', 72, 'requires_ethics'],
            ['Keputusan Ketua Komite Etik', 'approval', 'ethics-chair', 48, 'requires_ethics'],
            ['Persetujuan Kepala Unit', 'approval', 'unit-head', 24, 'always'],
            ['Persetujuan Kepala Bidang', 'approval', 'division-head', 24, 'always'],
            ['Verifikasi Keuangan', 'finance', 'finance-officer', 24, 'paid'],
            ['Persetujuan Kepala Bagian Umum', 'approval', 'head-general', 24, 'always'],
            ['Persetujuan Wakil Direktur Umum & Keuangan', 'approval', 'deputy-director', 24, 'always'],
            ['Persetujuan Direktur', 'signature', 'director', 24, 'always'],
        ];

        $defaultTemplate = WorkflowTemplate::updateOrCreate(
            ['module' => 'diklat', 'activity_type_id' => null],
            ['name' => 'Persetujuan Kegiatan Rumah Sakit', 'description' => 'Alur standar dari admin unit sampai tanda tangan Direktur.', 'active' => true, 'created_by' => $adminId]
        );
        $this->publishWhenChanged($defaultTemplate, $steps, $adminId);

        $research = ActivityType::where('name', 'like', 'Penelitian%')->first();
        if (! $research) {
            return;
        }
        $researchTemplate = WorkflowTemplate::updateOrCreate(
            ['module' => 'diklat', 'activity_type_id' => $research->id],
            ['name' => 'Persetujuan Penelitian Rumah Sakit', 'description' => 'Workflow lintas Diklat, Komite Etik, unit, keuangan, dan manajemen.', 'active' => true, 'created_by' => $adminId]
        );
        $this->publishWhenChanged($researchTemplate, $steps, $adminId);

        $internship = ActivityType::where('name', 'Magang')->first();
        if (! $internship) {
            return;
        }

        $internshipSteps = [
            ['Disposisi Arsiparis', 'verification', 'arsiparis', 24, 'always'],
            ['Persetujuan Direktur atau Wakil Direktur', 'approval', 'executive-approver', 24, 'always', false],
            ['Persetujuan Kepala Bagian Umum', 'approval', 'head-general', 24, 'always'],
            ['Tindak Lanjut Diklat', 'verification', 'diklat-admin', 24, 'always'],
            ['KAMKORDIK: Persamaan Persepsi', 'review', 'kamkordik-admin', 48, 'always'],
            ['KAMKORDIK: Serah Terima', 'approval', 'kamkordik-admin', 24, 'always'],
            ['Selesai', 'approval', 'diklat-admin', 24, 'always'],
        ];
        $internshipTemplate = WorkflowTemplate::updateOrCreate(
            ['module' => 'diklat', 'activity_type_id' => $internship->id],
            ['name' => 'Alur Pengajuan Magang', 'description' => 'Alur Magang: pemohon, Arsiparis, Direktur atau Wakil Direktur, Kepala Bagian Umum, Diklat, KAMKORDIK, hingga selesai.', 'active' => true, 'created_by' => $adminId]
        );
        $this->publishWhenChanged($internshipTemplate, $internshipSteps, $adminId);
    }

    private function publishWhenChanged(WorkflowTemplate $template, array $steps, int $adminId): void
    {
        $latest = $template->versions()->with('steps')->latest('version_number')->first();
        $expected = collect($steps)->map(fn ($step) => [$step[1], $step[2], $step[3], $step[4], $step[5] ?? true])->values()->all();
        $current = $latest?->steps->sortBy('position')->map(fn ($step) => [
            $step->action_type,
            $step->assignee_value,
            $step->sla_hours,
            $step->conditions['type'] ?? 'always',
            (bool) $step->allow_revision,
        ])->values()->all();

        if ($current === $expected) {
            return;
        }

        $version = $template->versions()->create([
            'version_number' => ((int) $template->versions()->max('version_number')) + 1, 'status' => 'published',
            'published_at' => now(), 'created_by' => $adminId,
        ]);
        foreach ($steps as $index => $step) {
            [$name, $action, $role, $sla, $condition] = $step;
            $allowRevision = $step[5] ?? true;
            $version->steps()->create([
                'position' => $index + 1, 'name' => $name, 'action_type' => $action,
                'assignee_type' => 'role', 'assignee_value' => $role, 'sla_hours' => $sla,
                'allow_revision' => $allowRevision, 'allow_reject' => true, 'conditions' => ['type' => $condition],
            ]);
        }
    }
}
