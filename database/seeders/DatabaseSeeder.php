<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Application;
use App\Models\ClinicalRotation;
use App\Models\ClinicalSupervisor;
use App\Models\Department;
use App\Models\EducationProgram;
use App\Models\EthicalClearance;
use App\Models\EthicsApplication;
use App\Models\EthicsReview;
use App\Models\Institution;
use App\Models\Invoice;
use App\Models\Logbook;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = ['diklat.view', 'diklat.create', 'diklat.verify', 'diklat.approve', 'diklat.payment', 'kamkordik.view', 'kamkordik.student.manage', 'kamkordik.rotation.manage', 'logbook.create', 'logbook.verify', 'ethics.view', 'ethics.create', 'ethics.review', 'ethics.approve', 'ethical_clearance.issue', 'master.manage', 'report.view', 'audit.view'];
        foreach ($permissions as $slug) {
            Permission::create(['slug' => $slug, 'name' => str($slug)->replace('.', ' ')->title()]);
        }
        $roleMap = [
            'super-admin' => ['Super Administrator', $permissions],
            'diklat-admin' => ['Admin Diklat', ['diklat.view', 'diklat.verify', 'diklat.payment', 'report.view']],
            'diklat-head' => ['Kepala Diklat', ['diklat.view', 'diklat.approve', 'report.view']],
            'kamkordik-admin' => ['Admin KAMKORDIK', ['kamkordik.view', 'kamkordik.student.manage', 'kamkordik.rotation.manage', 'logbook.verify', 'report.view']],
            'kamkordik-head' => ['Ketua KAMKORDIK', ['kamkordik.view', 'kamkordik.rotation.manage', 'report.view']],
            'clinical-supervisor' => ['Pembimbing Klinis', ['kamkordik.view', 'logbook.verify']],
            'student' => ['Peserta Didik', ['kamkordik.view', 'logbook.create']],
            'ethics-admin' => ['Admin Komite Etik', ['ethics.view', 'ethics.review', 'report.view']],
            'ethics-reviewer' => ['Reviewer Etik', ['ethics.view', 'ethics.review']],
            'ethics-chair' => ['Ketua Komite Etik', ['ethics.view', 'ethics.review', 'ethics.approve', 'ethical_clearance.issue', 'report.view']],
            'applicant' => ['Pemohon Eksternal', ['diklat.view', 'diklat.create', 'ethics.view', 'ethics.create']],
        ];
        foreach ($roleMap as $slug => [$name,$perms]) {
            $role = Role::create(['slug' => $slug, 'name' => $name, 'scope' => $slug === 'applicant' ? 'external' : 'internal']);
            $role->permissions()->sync(Permission::whereIn('slug', $perms)->pluck('id'));
        }

        $institutions = collect([
            ['RSUD Embung Fatimah Kota Batam', 'Rumah Sakit Umum Daerah Tipe B', null, 'images/branding/rsud-embung-fatimah.png', 'Jalan Letjen R. Suprapto No. 9, Batu Aji, Kota Batam, Kepulauan Riau 29425', '(0778) 364446'],
            ['Universitas Indonesia', 'Perguruan Tinggi', 'Fakultas Kedokteran', null, 'Jakarta, Indonesia', null], ['Universitas Gadjah Mada', 'Perguruan Tinggi', 'Fakultas Kedokteran', null, 'Yogyakarta, Indonesia', null], ['Universitas Airlangga', 'Perguruan Tinggi', 'Fakultas Keperawatan', null, 'Surabaya, Indonesia', null], ['Poltekkes Kemenkes Jakarta', 'Perguruan Tinggi', 'Keperawatan', null, 'Jakarta, Indonesia', null], ['PT Sehat Bersama', 'Perusahaan', null, null, 'Batam, Indonesia', null],
        ])->map(fn ($i) => Institution::updateOrCreate(['name' => $i[0]], ['type' => $i[1], 'faculty' => $i[2], 'logo_path' => $i[3], 'address' => $i[4], 'pic_name' => $i[0] === 'RSUD Embung Fatimah Kota Batam' ? 'Manajemen RSUDEF' : 'PIC '.$i[0], 'email' => $i[0] === 'RSUD Embung Fatimah Kota Batam' ? null : 'kerjasama@'.Str::slug($i[0], '').'.ac.id', 'phone' => $i[5] ?? '021-555-'.random_int(1000, 9999), 'cooperation_status' => 'active', 'mou_number' => $i[0] === 'RSUD Embung Fatimah Kota Batam' ? null : 'PKS/'.random_int(100, 999).'/2026', 'mou_start' => $i[0] === 'RSUD Embung Fatimah Kota Batam' ? null : now()->subYear(), 'mou_end' => $i[0] === 'RSUD Embung Fatimah Kota Batam' ? null : now()->addMonths(random_int(2, 24))]));
        $departments = collect(['Penyakit Dalam', 'Bedah', 'Anak', 'Obstetri dan Ginekologi', 'Keperawatan', 'Farmasi', 'Instalasi Rekam Medis', 'Unit Penelitian'])->map(fn ($name, $i) => Department::create(['name' => $name, 'code' => 'UNT-'.str_pad($i + 1, 2, '0', STR_PAD_LEFT), 'type' => $i < 4 ? 'clinical' : 'unit']));
        $programs = collect([['Profesi Dokter', 'Kedokteran'], ['PPDS Penyakit Dalam', 'Kedokteran'], ['Profesi Ners', 'Keperawatan'], ['Profesi Bidan', 'Kebidanan'], ['Profesi Apoteker', 'Farmasi']])->map(fn ($p) => EducationProgram::create(['name' => $p[0], 'category' => $p[1], 'duration_months' => 12]));
        $types = collect([
            ['Praktik / PKL', 'PKL', 1, 0, 0, 'Pengajuan praktik kerja lapangan untuk pelajar dan mahasiswa.'], ['Magang', 'MAG', 1, 0, 0, 'Program pemagangan terstruktur di lingkungan rumah sakit.'], ['Pendidikan Klinik', 'KLINIK', 1, 0, 1, 'Rotasi dan kegiatan pendidikan klinis peserta didik.'], ['Penelitian', 'RSC', 1, 1, 0, 'Pengajuan penelitian dan telaah kelayakan etik.'], ['Studi Pendahuluan', 'STP', 0, 1, 0, 'Perizinan studi awal untuk persiapan penelitian.'], ['Pengambilan Data', 'DATA', 1, 1, 0, 'Akses pengambilan data sesuai ketentuan rumah sakit.'], ['Observasi', 'OBS', 0, 0, 0, 'Kegiatan pengamatan layanan dan praktik profesional.'], ['Studi Banding / Kunjungan', 'SB', 1, 0, 0, 'Kunjungan pembelajaran antarinstansi atau organisasi.'], ['Pelatihan / Workshop', 'PLT', 1, 0, 0, 'Program peningkatan kompetensi dan keterampilan.'], ['Lainnya', 'OTH', 0, 0, 0, 'Layanan kegiatan lainnya sesuai kebutuhan pemohon.'],
        ])->map(fn ($t) => ActivityType::create(['name' => $t[0], 'code' => $t[1], 'is_paid' => $t[2], 'requires_ethics' => $t[3], 'requires_kamkordik' => $t[4], 'description' => $t[5], 'color' => $t[4] ? '#7654d7' : ($t[3] ? '#168b6a' : '#2864dc')]));
        foreach ($types as $type) {
            foreach (['Surat Permohonan Institusi', 'Proposal / Kerangka Acuan', 'Identitas atau Daftar Peserta'] as $name) {
                DB::table('requirements')->insert(['activity_type_id' => $type->id, 'name' => $name, 'required' => true, 'allowed_types' => json_encode(['pdf', 'docx']), 'max_size_kb' => 5120, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        DB::table('tariffs')->insert(['activity_type_id' => $types[0]->id, 'name' => 'Biaya praktik per peserta', 'calculation' => 'per_participant', 'amount' => 500000, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('workflow_definitions')->insert([['module' => 'diklat', 'activity_type_id' => $types[0]->id, 'name' => 'Workflow Diklat Standar', 'steps' => json_encode(['submitted', 'administrative_verification', 'verified', 'waiting_approval', 'approved', 'waiting_payment', 'payment_verified', 'permit_issued', 'running', 'completed', 'closed']), 'active' => true, 'created_at' => now(), 'updated_at' => now()], ['module' => 'ethics', 'activity_type_id' => $types[3]->id, 'name' => 'Workflow Penelitian', 'steps' => json_encode(['submitted', 'administrative_verification', 'review', 'revision', 'decision', 'approved']), 'active' => true, 'created_at' => now(), 'updated_at' => now()]]);

        $users = [];
        foreach ([['admin@lentera.test', 'Administrator', 'super-admin'], ['diklat@lentera.test', 'Rina Admin Diklat', 'diklat-admin'], ['kamkordik@lentera.test', 'Budi Admin KAMKORDIK', 'kamkordik-admin'], ['reviewer@lentera.test', 'dr. Maya Reviewer', 'ethics-reviewer'], ['ketuaetik@lentera.test', 'dr. Surya Ketua Etik', 'ethics-chair'], ['pemohon@lentera.test', 'Andi Saputra', 'applicant'], ['pembimbing@lentera.test', 'dr. Anita Pembimbing', 'clinical-supervisor']] as [$email,$name,$role]) {
            $u = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make('password'), 'phone' => '081234567890', 'active' => true]);
            $u->roles()->attach(Role::where('slug', $role)->value('id'));
            $users[$role] = $u;
        }
        $supervisor = ClinicalSupervisor::create(['user_id' => $users['clinical-supervisor']->id, 'department_id' => $departments[0]->id, 'name' => $users['clinical-supervisor']->name, 'professional_number' => 'SIP-2026-0102', 'specialization' => 'Penyakit Dalam']);
        for ($i = 1; $i <= 12; $i++) {
            $statuses = ['submitted', 'administrative_verification', 'revision_required', 'verified', 'waiting_approval', 'approved', 'waiting_payment', 'payment_verified', 'permit_issued', 'running', 'completed', 'closed'];
            $status = $statuses[($i - 1) % count($statuses)];
            $app = Application::create(['number' => 'DKL/2026/'.str_pad($i, 6, '0', STR_PAD_LEFT), 'applicant_id' => $users['applicant']->id, 'activity_type_id' => $types[($i - 1) % $types->count()]->id, 'institution_id' => $institutions[($i - 1) % $institutions->count()]->id, 'department_id' => $departments[($i - 1) % $departments->count()]->id, 'title' => ['Praktik Klinis Mahasiswa', 'Penelitian Kepuasan Pasien', 'Observasi Manajemen Bangsal', 'Studi Banding Pelayanan Digital'][$i % 4].' '.$i, 'person_in_charge' => 'Andi Saputra', 'participant_count' => random_int(1, 20), 'start_date' => now()->addDays($i * 3), 'end_date' => now()->addDays($i * 3 + 14), 'purpose' => 'Peningkatan kompetensi dan pengembangan pengetahuan.', 'description' => 'Kegiatan eksternal yang diajukan melalui portal Lentera.', 'status' => $status, 'submitted_at' => now()->subDays(20 - $i), 'created_at' => now()->subMonths($i % 8)->subDays($i), 'updated_at' => now()]);
            DB::table('application_status_histories')->insert(['application_id' => $app->id, 'from_status' => 'draft', 'to_status' => $status, 'note' => 'Status demo diperbarui sesuai workflow', 'changed_by' => $users['diklat-admin']->id, 'created_at' => now()->subDays($i), 'updated_at' => now()]);
            if ($status === 'waiting_payment') {
                Invoice::create(['number' => 'INV/2026/'.str_pad($i, 6, '0', STR_PAD_LEFT), 'application_id' => $app->id, 'subtotal' => 500000 * $app->participant_count, 'total' => 500000 * $app->participant_count, 'due_date' => now()->addWeek(), 'status' => 'unpaid', 'items' => [['description' => 'Biaya kegiatan', 'amount' => 500000 * $app->participant_count]]]);
            }
            if (in_array($status, ['running', 'completed'])) {
                Activity::create(['application_id' => $app->id, 'title' => $app->title, 'start_at' => $app->start_date->setTime(8, 0), 'end_at' => $app->end_date->setTime(16, 0), 'location' => $app->department->name, 'status' => $status === 'running' ? 'running' : 'completed']);
            }
        }
        for ($i = 1; $i <= 15; $i++) {
            $student = Student::create(['number' => 'KMD/2026/'.str_pad($i, 6, '0', STR_PAD_LEFT), 'institution_id' => $institutions[$i % 4]->id, 'education_program_id' => $programs[$i % $programs->count()]->id, 'nim' => '2026'.str_pad($i, 4, '0', STR_PAD_LEFT), 'name' => ['Aulia Rahman', 'Siti Nuraini', 'Dimas Pratama', 'Nadia Putri', 'Fajar Hidayat'][$i % 5].' '.$i, 'gender' => $i % 2 ? 'L' : 'P', 'email' => 'student'.$i.'@example.test', 'phone' => '08120000'.str_pad($i, 4, '0', STR_PAD_LEFT), 'period_start' => now()->subMonth(), 'period_end' => now()->addMonths(5), 'status' => 'active']);
            $rotation = ClinicalRotation::create(['student_id' => $student->id, 'department_id' => $departments[$i % 4]->id, 'clinical_supervisor_id' => $supervisor->id, 'stage' => $departments[$i % 4]->name, 'start_date' => now()->startOfMonth(), 'end_date' => now()->endOfMonth(), 'status' => 'running']);
            Logbook::create(['student_id' => $student->id, 'clinical_rotation_id' => $rotation->id, 'clinical_supervisor_id' => $supervisor->id, 'activity_date' => now()->subDays($i % 7), 'room' => 'Ruang '.($i % 4 + 1), 'activity_type' => 'Asesmen pasien', 'anonymous_case_number' => 'KASUS-'.str_pad($i, 3, '0', STR_PAD_LEFT), 'age_group' => 'Dewasa', 'diagnosis' => 'Diagnosis pendidikan', 'procedure' => 'Observasi dan diskusi penatalaksanaan', 'student_role' => 'Observer', 'status' => $i % 3 === 0 ? 'submitted' : 'verified', 'submitted_at' => now()->subDays(1), 'verified_at' => $i % 3 === 0 ? null : now()]);
        }
        for ($i = 1; $i <= 7; $i++) {
            $status = ['administrative_verification', 'review', 'revision', 'decision', 'approved'][$i % 5];
            $ethics = EthicsApplication::create(['number' => 'KEPK/2026/'.str_pad($i, 6, '0', STR_PAD_LEFT), 'applicant_id' => $users['applicant']->id, 'institution_id' => $institutions[$i % $institutions->count()]->id, 'title' => ['Efektivitas Edukasi terhadap Kepatuhan Pasien', 'Analisis Faktor Risiko Infeksi', 'Evaluasi Mutu Pelayanan Klinis'][$i % 3].' '.$i, 'principal_investigator' => 'Dr. Peneliti '.$i, 'research_type' => 'Observasional', 'location' => 'Rumah Sakit', 'population' => 'Pasien dewasa', 'subjects' => '120 responden', 'start_date' => now()->addMonth(), 'end_date' => now()->addMonths(6), 'funding_source' => 'Mandiri', 'status' => $status, 'submitted_at' => now()->subDays(14), 'created_at' => now()->subDays($i * 3), 'updated_at' => now()]);
            $review = EthicsReview::create(['ethics_application_id' => $ethics->id, 'reviewer_id' => $users['ethics-reviewer']->id, 'assigned_at' => now()->subDays(10), 'deadline' => now()->addDays(3), 'status' => in_array($status, ['decision', 'approved']) ? 'completed' : 'assigned', 'scores' => in_array($status, ['decision', 'approved']) ? ['scientific_validity' => 4, 'risk_benefit' => 4, 'privacy' => 5, 'informed_consent' => 4] : null, 'recommendation' => in_array($status, ['decision', 'approved']) ? 'eligible' : null, 'summary' => in_array($status, ['decision', 'approved']) ? 'Protokol memenuhi prinsip etik penelitian.' : null, 'completed_at' => in_array($status, ['decision', 'approved']) ? now()->subDays(3) : null]);
            if ($status === 'approved') {
                EthicalClearance::create(['ethics_application_id' => $ethics->id, 'number' => 'EC/KEPK/RS/2026/'.str_pad($i, 5, '0', STR_PAD_LEFT), 'issued_at' => today(), 'valid_until' => now()->addYear(), 'chairperson' => $users['ethics-chair']->name, 'verification_token' => Str::random(48), 'status' => 'active']);
            }
        }
        DB::table('announcements')->insert([['title' => 'Pemeliharaan sistem terjadwal', 'content' => 'Sistem tetap dapat digunakan; unggahan besar mungkin lebih lambat.', 'audience' => 'all', 'published_at' => now()->subHour(), 'created_by' => $users['super-admin']->id, 'created_at' => now(), 'updated_at' => now()], ['title' => 'Batas review proposal etik', 'content' => 'Reviewer dimohon menyelesaikan penilaian sesuai deadline.', 'audience' => 'ethics', 'published_at' => now()->subDay(), 'created_by' => $users['super-admin']->id, 'created_at' => now(), 'updated_at' => now()]]);
        foreach (['diklat' => 'DKL/{Y}/{S6}', 'student' => 'KMD/{Y}/{S6}', 'ethics' => 'KEPK/{Y}/{S6}', 'ec' => 'EC/KEPK/RS/{Y}/{S5}', 'invoice' => 'INV/{Y}/{S6}'] as $type => $format) {
            DB::table('document_numbers')->updateOrInsert(['type' => $type], ['format' => $format, 'last_sequence' => $type === 'student' ? 15 : ($type === 'diklat' ? 12 : 7), 'year' => 2026, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('settings')->insert([['group' => 'general', 'key' => 'hospital_name', 'value' => 'RSUD Embung Fatimah Kota Batam', 'type' => 'string', 'public' => true, 'created_at' => now(), 'updated_at' => now()], ['group' => 'files', 'key' => 'max_upload_mb', 'value' => '10', 'type' => 'integer', 'public' => false, 'created_at' => now(), 'updated_at' => now()], ['group' => 'notifications', 'key' => 'whatsapp_enabled', 'value' => '0', 'type' => 'boolean', 'public' => false, 'created_at' => now(), 'updated_at' => now()]]);
        $this->call(DemoAccountSeeder::class);
        $this->call(WorkflowSeeder::class);
        $this->call(OperationalDemoSeeder::class);
        $this->call(OrganizationAccessSeeder::class);
        app(\App\Services\InternshipPaymentWorkflow::class)->publish();
        app(\App\Services\TariffCatalogue::class)->import();
    }
}
