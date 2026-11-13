<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\OfficialDecree;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationAccessSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            'users.manage' => 'Kelola Pengguna dan Role',
            'sk.view' => 'Lihat Manajemen SK',
            'sk.manage' => 'Kelola Manajemen SK',
        ])->mapWithKeys(function ($name, $slug) {
            $permission = Permission::updateOrCreate(['slug' => $slug], ['name' => $name]);

            return [$slug => $permission->id];
        });

        $permissionIds = fn (array $slugs) => Permission::whereIn('slug', $slugs)->pluck('id');
        $roles = [
            'admin-umum' => ['Admin Umum', ['users.manage', 'master.manage', 'audit.view', 'report.view', 'sk.view', 'sk.manage']],
            'arsiparis' => ['Arsiparis', ['diklat.view', 'sk.view', 'sk.manage', 'workflow.approve']],
            'director' => ['Direktur', ['diklat.view', 'workflow.approve', 'report.view', 'sk.view']],
            'deputy-director' => ['Wakil Direktur Umum & Keuangan', ['workflow.approve', 'report.view', 'sk.view']],
            'head-general' => ['Kepala Bagian Umum', ['workflow.approve', 'report.view', 'sk.view']],
            'head-program' => ['Kepala Bagian Program', ['workflow.approve', 'report.view', 'sk.view']],
            'head-finance' => ['Kepala Bagian Keuangan', ['workflow.approve', 'report.view', 'sk.view']],
            'head-medical-services' => ['Kepala Bidang Pelayanan Medis', ['workflow.approve', 'report.view', 'sk.view']],
            'head-support-services' => ['Kepala Bidang Penunjang', ['workflow.approve', 'report.view', 'sk.view']],
            'head-nursing' => ['Kepala Bidang Keperawatan', ['workflow.approve', 'report.view', 'sk.view']],
            'diklat-coordinator' => ['Penanggung Jawab Diklat', ['diklat.view', 'diklat.approve', 'workflow.approve', 'report.view', 'sk.view']],
        ];

        foreach ($roles as $slug => [$name, $slugs]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'scope' => 'internal']);
            $role->permissions()->syncWithoutDetaching($permissionIds($slugs));
        }

        foreach (['diklat-admin', 'kamkordik-admin', 'ethics-admin'] as $slug) {
            Role::where('slug', $slug)->first()?->permissions()->syncWithoutDetaching($permissions->only(['sk.view', 'sk.manage'])->values());
        }
        foreach (['diklat-head', 'kamkordik-head', 'ethics-chair'] as $slug) {
            Role::where('slug', $slug)->first()?->permissions()->syncWithoutDetaching($permissions->only(['sk.view'])->values());
        }

        $institutionId = Institution::where('name', 'RSUD Embung Fatimah Kota Batam')->value('id');
        $accounts = [
            ['sunartititin5678@gmail.com', 'Titin', 'Arsiparis', ['admin-umum', 'arsiparis']],
            ['direktur@lentera.test', 'Bu Direktur', 'Direktur', ['director', 'executive-approver']],
            ['wadir@lentera.test', 'Wakil Direktur Umum & Keuangan', 'Wakil Direktur Umum & Keuangan', ['deputy-director', 'executive-approver']],
            ['kabag.umum@lentera.test', 'Kepala Bagian Umum', 'Kepala Bagian Umum', ['head-general']],
            ['mytha@lentera.test', 'Mytha', 'Admin Diklat', ['diklat-admin']],
            ['bucot@lentera.test', 'Bu COT', 'Penanggung Jawab Diklat', ['diklat-coordinator']],
            ['desi@lentera.test', 'Desi', 'Admin KAMKORDIK', ['kamkordik-admin']],
            ['rizal.marubob@lentera.test', 'Dr. Rizal Marubob', 'Ketua KAMKORDIK', ['kamkordik-head']],
            ['tika@lentera.test', 'Tika', 'Admin Komite Etik', ['ethics-admin']],
            ['handedi@lentera.test', 'Dr. Handedi', 'Ketua Komite Etik', ['ethics-chair']],
            ['kabag.program@lentera.test', 'Kepala Bagian Program', 'Kepala Bagian Program', ['head-program', 'unit-head']],
            ['kabag.keuangan@lentera.test', 'Kepala Bagian Keuangan', 'Kepala Bagian Keuangan', ['head-finance', 'finance-officer']],
            ['kabid.yanmed@lentera.test', 'Kepala Bidang Pelayanan Medis', 'Kepala Bidang Pelayanan Medis', ['head-medical-services', 'division-head']],
            ['kabid.penunjang@lentera.test', 'Kepala Bidang Penunjang', 'Kepala Bidang Penunjang', ['head-support-services', 'division-head']],
            ['kabid.keperawatan@lentera.test', 'Kepala Bidang Keperawatan', 'Kepala Bidang Keperawatan', ['head-nursing', 'division-head']],
        ];

        foreach ($accounts as [$email, $name, $position, $roleSlugs]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'position' => $position,
                'institution_id' => $institutionId,
                'password' => Hash::make('password'),
                'active' => true,
            ]);
            $user->roles()->sync(Role::whereIn('slug', $roleSlugs)->pluck('id'));
        }

        $managers = User::whereIn('email', ['mytha@lentera.test', 'desi@lentera.test', 'tika@lentera.test'])->pluck('id', 'email');
        foreach ([
            ['diklat', 2024, 'DRAFT/SK-DIKLAT/2024', 'SK Diklat Tahun 2024', 'mytha@lentera.test'],
            ['diklat', 2025, 'DRAFT/SK-DIKLAT/2025', 'SK Diklat Tahun 2025', 'mytha@lentera.test'],
            ['diklat', 2026, 'DRAFT/SK-DIKLAT/2026', 'SK Diklat Tahun 2026', 'mytha@lentera.test'],
            ['kamkordik', 2026, 'DRAFT/SK-KAMKORDIK/2026', 'SK KAMKORDIK', 'desi@lentera.test'],
            ['ethics', 2026, 'DRAFT/SK-KOMITE-ETIK/2026', 'SK Komite Etik', 'tika@lentera.test'],
        ] as [$category, $year, $number, $title, $managerEmail]) {
            OfficialDecree::updateOrCreate(['number' => $number], [
                'category' => $category,
                'title' => $title,
                'year' => $year,
                'manager_id' => $managers[$managerEmail] ?? null,
                'status' => 'draft',
                'notes' => 'Nomor resmi, tanggal, penandatangan, dan berkas PDF perlu dilengkapi.',
                'created_by' => User::where('email', 'titin@lentera.test')->value('id'),
            ]);
        }
    }
}
