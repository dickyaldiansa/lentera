<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Application;
use App\Models\ApplicationCoordinationTask;
use App\Models\ApplicationFollowUp;
use App\Models\ClinicalRotation;
use App\Models\Department;
use App\Models\EthicsApplication;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Models\WorkflowTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LenteraWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_open_all_main_modules(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        foreach (['/dashboard', '/my/applications', '/my/activities', '/announcements', '/applications', '/diklat/activities', '/diklat/payments', '/kamkordik', '/kamkordik/institutions', '/kamkordik/rotations', '/kamkordik/logbooks', '/kamkordik/assessments', '/kamkordik/supervisors', '/ethics', '/ethics/reviews-meetings', '/ethics/decisions', '/ethics/monitoring', '/sk', '/calendar', '/reports?module=diklat', '/reports?module=kamkordik', '/reports?module=ethics', '/master-data', '/master-data/institutions', '/master-data/education_programs', '/master-data/activity_types', '/master-data/application_follow_ups', '/master-data/payment_methods', '/master-data/activity_locations', '/master-data/signatories', '/master-data/departments', '/master-data/requirements', '/master-data/tariffs', '/master-data/workflow_definitions', '/master-data/document_templates', '/users-roles', '/audit-logs'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk();
        }
    }

    public function test_applicant_can_create_and_submit_an_application(): void
    {
        Storage::fake('local');
        $user = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $type = ActivityType::firstOrFail();
        $response = $this->actingAs($user)->post('/applications', [
            'activity_type_id' => $type->id, 'title' => 'Pengajuan Test Otomatis', 'participant_count' => 2,
            'start_date' => now()->addMonth()->toDateString(), 'end_date' => now()->addMonth()->addWeek()->toDateString(),
            'institution_name' => 'Institusi Pengajuan Baru',
            'documents' => [UploadedFile::fake()->create('surat-permohonan.pdf', 120, 'application/pdf')],
        ]);
        $app = Application::where('title', 'Pengajuan Test Otomatis')->firstOrFail();
        $response->assertRedirect(route('applications.show', $app));
        $this->assertDatabaseHas('application_documents', ['application_id' => $app->id, 'name' => 'surat-permohonan.pdf']);
        $this->assertSame('Institusi Pengajuan Baru', $app->institution->name);
        Storage::disk('local')->assertExists($app->documents()->firstOrFail()->path);
        $diklat = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $diklat->id, 'notifiable_type' => User::class]);
        $this->actingAs($user)->post(route('applications.submit', $app))->assertSessionHas('success');
        $this->assertDatabaseHas('applications', ['id' => $app->id, 'status' => 'waiting_approval']);
        $this->assertDatabaseHas('application_status_histories', ['application_id' => $app->id, 'to_status' => 'submitted']);
    }

    public function test_existing_internship_keeps_its_legacy_workflow_after_new_version_is_published(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $internship = ActivityType::where('name', 'Magang')->firstOrFail();
        $newVersion = WorkflowTemplate::where('activity_type_id', $internship->id)->firstOrFail()->versions()->latest('version_number')->firstOrFail();
        $newVersion->update(['status' => 'draft']);
        $application = Application::create([
            'number' => 'DKL/2026/MAGANG-ALUR', 'applicant_id' => $applicant->id,
            'activity_type_id' => $internship->id, 'title' => 'Magang dengan Alur Tetap',
            'participant_count' => 1, 'start_date' => now()->addMonth(), 'end_date' => now()->addMonths(2),
            'status' => 'draft',
        ]);

        $this->actingAs($applicant)->post(route('applications.submit', $application))->assertSessionHas('success');
        $instance = WorkflowInstance::where('workflowable_type', Application::class)->where('workflowable_id', $application->id)->firstOrFail();
        $newVersion->update(['status' => 'published']);
        $this->assertNotSame($newVersion->id, $instance->workflow_version_id);
        $this->assertSame('Alur Pengajuan Magang', $instance->version->template->name);
        $this->actingAs($applicant)->get(route('applications.show', $application))
            ->assertOk()->assertSee('Jejak Persetujuan')->assertSee('Disposisi Arsiparis');

        $approveCurrent = function (string $email, string $role) use ($instance): void {
            $task = $instance->fresh('tasks.step')->tasks->firstWhere('status', 'pending');
            $this->assertSame($role, $task->assignee_role_slug);
            $this->actingAs(User::where('email', $email)->firstOrFail())
                ->post(route('approvals.act', $task), ['action' => 'approve'])
                ->assertRedirect(route('approvals.index'));
        };

        $director = User::where('email', 'direktur@lentera.test')->firstOrFail();
        $archivistTask = $instance->fresh('tasks.step')->tasks->firstWhere('status', 'pending');
        $this->assertSame('arsiparis', $archivistTask->assignee_role_slug);
        $archivist = User::where('email', 'sunartititin5678@gmail.com')->firstOrFail();
        $this->actingAs($archivist)->get(route('approvals.index'))
            ->assertOk()->assertSee('Disposisi ke Direktur')->assertDontSee('Minta Perbaikan')->assertDontSee('Tolak');
        $this->actingAs($archivist)->get(route('applications.show', $application))
            ->assertOk()->assertSee('Buat Disposisi');
        $this->actingAs($archivist)
            ->post(route('approvals.act', $archivistTask), ['action' => 'approve', 'recipient_id' => $director->id])
            ->assertRedirect(route('approvals.index'));

        $directorTask = $instance->fresh('tasks.step')->tasks->firstWhere('status', 'pending');
        $this->assertSame($director->id, $directorTask->assignee_user_id);
        $this->actingAs($director)->post(route('approvals.act', $directorTask), [
            'action' => 'pending', 'note' => 'Menunggu kelengkapan internal.',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('workflow_actions', ['workflow_task_id' => $directorTask->id, 'action' => 'pending']);
        $this->assertDatabaseHas('workflow_tasks', ['id' => $directorTask->id, 'status' => 'pending']);

        $this->actingAs($director)->post(route('approvals.act', $directorTask), ['action' => 'approve'])
            ->assertRedirect(route('approvals.index'));
        $approveCurrent('kabag.umum@lentera.test', 'head-general');
        $approveCurrent('mytha@lentera.test', 'diklat-admin');
        $this->assertDatabaseHas('application_coordination_tasks', ['application_id' => $application->id, 'recipient_role_slug' => 'kamkordik-admin']);
        $coordination = ApplicationCoordinationTask::where('application_id', $application->id)->firstOrFail();
        $this->actingAs(User::where('email', 'desi@lentera.test')->firstOrFail())
            ->get(route('kamkordik.coordinations.show', $coordination))
            ->assertOk()->assertSee('Detail Koordinasi')->assertSee('Serah Terima Mahasiswa Praktik/Magang');
        $kamkordikAdmin = User::where('email', 'desi@lentera.test')->firstOrFail();
        $this->actingAs($kamkordikAdmin)
            ->post(route('kamkordik.coordinations.respond', $coordination), [
                'status' => 'scheduled',
                'response' => 'Jadwal persamaan persepsi telah ditetapkan.',
            ])->assertSessionHas('success');
        $this->actingAs($kamkordikAdmin)
            ->get(route('kamkordik.coordinations.show', $coordination))
            ->assertOk()->assertSee('Serah Terima Mahasiswa Praktik/Magang');
        $this->actingAs($kamkordikAdmin)
            ->post(route('kamkordik.coordinations.respond', $coordination), [
                'status' => 'handover_completed',
                'response' => 'Serah terima mahasiswa praktik/magang telah terlaksana.',
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'approved']);
    }

    public function test_magang_and_praktik_require_verified_payment_before_coordination_and_finish_in_certificate_queue(): void
    {
        Storage::fake('local');
        $applicant = User::where('email','pemohon@lentera.test')->firstOrFail();
        $diklat = User::where('email','mytha@lentera.test')->firstOrFail();
        $director = User::where('email','direktur@lentera.test')->firstOrFail();
        $method = DB::table('payment_methods')->where('active',true)->value('name');
        foreach (['Magang','Praktik / PKL'] as $name) {
            $type = ActivityType::where('name',$name)->firstOrFail();
            $this->actingAs($applicant)->post(route('applications.store'), [
                'activity_type_id'=>$type->id,'title'=>'Alur Pembayaran '.$name,'participant_count'=>1,
                'start_date'=>now()->addMonth()->toDateString(),'end_date'=>now()->addMonths(2)->toDateString(),
            ])->assertSessionHas('success');
            $application = Application::where('title','Alur Pembayaran '.$name)->firstOrFail();
            if (!$application->workflowInstance) $this->actingAs($applicant)->post(route('applications.submit',$application))->assertSessionHas('success');
            $instance = $application->fresh()->workflowInstance;
            $current = fn() => $instance->fresh()->tasks()->where('status','pending')->firstOrFail();
            foreach (['sunartititin5678@gmail.com','direktur@lentera.test','kabag.umum@lentera.test'] as $email) {
                $this->actingAs(User::where('email',$email)->firstOrFail())->post(route('approvals.act',$current()),[
                    'action'=>'approve','recipient_id'=>$director->id,
                ])->assertSessionHas('success');
            }
            $adminTask=$current();
            $this->actingAs($diklat)->get(route('approvals.show',$adminTask))->assertOk()->assertSee('Nominal tagihan');
            $this->post(route('approvals.act',$adminTask),['action'=>'approve'])->assertSessionHasErrors('invoice_amount');
            $this->post(route('approvals.act',$adminTask),['action'=>'revision','note'=>'Lengkapi surat'])->assertSessionHas('success');
            $this->assertNull($application->fresh()->invoice);
            $this->actingAs($applicant)->post(route('applications.submit',$application))->assertSessionHas('success');
            $this->actingAs($diklat)->post(route('approvals.act',$current()),['action'=>'approve','invoice_amount'=>500000])->assertSessionHas('success');
            $invoice=$application->fresh()->invoice;
            $gate=$current();
            $this->assertSame('waiting_payment',$application->fresh()->status);
            $this->assertFalse($application->coordinationTasks()->exists());
            $this->actingAs($diklat)->post(route('applications.coordinate-kamkordik',$application),['recipient_id'=>User::where('email','desi@lentera.test')->value('id'),'note'=>'Lewati pembayaran'])->assertStatus(422);
            $this->actingAs($diklat)->post(route('approvals.act',$gate),['action'=>'approve'])->assertStatus(422);
            $this->actingAs($applicant)->get(route('applications.show',$application))->assertOk()->assertSee('Unggah Bukti Pembayaran');
            $upload=fn($amount=500000)=>['amount'=>$amount,'method'=>$method,'proof'=>UploadedFile::fake()->image('bukti.jpg')];
            $this->post(route('payments.store',$invoice),$upload(1))->assertStatus(422);
            $this->post(route('payments.store',$invoice),$upload())->assertSessionHas('success');
            $this->post(route('payments.store',$invoice),$upload())->assertStatus(422);
            $payment=$invoice->payments()->latest('id')->firstOrFail();
            $this->actingAs($applicant)->post(route('payments.verify',$payment),['status'=>'verified'])->assertForbidden();
            $this->actingAs($diklat)->post(route('payments.verify',$payment),['status'=>'rejected'])->assertSessionHasErrors('review_note');
            $this->post(route('payments.verify',$payment),['status'=>'rejected','review_note'=>'Bukti tidak terbaca'])->assertSessionHas('success');
            $this->assertFalse($application->coordinationTasks()->exists());
            $this->actingAs($applicant)->get(route('applications.show',$application))->assertOk()->assertSee('Bukti tidak terbaca');
            $this->post(route('payments.store',$invoice),$upload())->assertSessionHas('success');
            $payment=$invoice->payments()->latest('id')->firstOrFail();
            $this->actingAs($diklat)->post(route('payments.verify',$payment),['status'=>'verified'])->assertSessionHas('success');
            $this->post(route('payments.verify',$payment),['status'=>'verified'])->assertStatus(422);
            $this->assertSame('KAMKORDIK: Persamaan Persepsi',$current()->step->name);
            $coordination=$application->coordinationTasks()->firstOrFail();
            $kamkordik=User::where('email','desi@lentera.test')->firstOrFail();
            $this->actingAs($kamkordik)->post(route('kamkordik.coordinations.respond',$coordination),['status'=>'handover_completed','response'=>'Terlalu awal'])->assertStatus(422);
            $this->assertSame('pending',$coordination->fresh()->status);
            $this->actingAs($kamkordik)->post(route('kamkordik.coordinations.respond',$coordination),['status'=>'scheduled','response'=>'Jadwal ditetapkan'])->assertSessionHas('success');
            $this->post(route('kamkordik.coordinations.respond',$coordination),['status'=>'handover_completed','response'=>'Serah terima selesai'])->assertSessionHas('success');
            $this->assertSame('completed',$application->fresh()->status);
            $this->assertSame('approved',$instance->fresh()->tasks()->whereHas('step',fn($q)=>$q->where('name','Selesai'))->firstOrFail()->status);
            $this->actingAs($diklat)->get(route('diklat.certificates'))->assertOk()->assertSee('Siap Diterbitkan')->assertSee($application->title);
            $this->assertSame(0,$application->certificates()->count());
            $participant=$application->participants()->firstOrFail();
            $this->post(route('certificates.store',$application),['participant_ids'=>[$participant->id]])->assertSessionHas('success');
            $this->assertSame(1,$application->certificates()->count());
        }
    }

    public function test_payment_workflow_publication_is_idempotent_and_master_edits_preserve_gates(): void
    {
        $type=ActivityType::where('name','Magang')->firstOrFail();
        $template=WorkflowTemplate::where('activity_type_id',$type->id)->firstOrFail();
        $version=$template->versions()->latest('version_number')->firstOrFail();
        $count=$template->versions()->count();
        app(\App\Services\InternshipPaymentWorkflow::class)->publish();
        $this->assertSame($count,$template->versions()->count());
        $steps=$version->steps->map(fn($step)=>[
            'source_step_id'=>$step->id,'name'=>$step->name,'action_type'=>$step->action_type,
            'assignee'=>$step->assignee_type.':'.$step->assignee_value,'sla_hours'=>24,'condition'=>'always',
        ])->all();
        $admin=User::where('email','admin@lentera.test')->firstOrFail();
        $payload=['name'=>$template->name,'module'=>'diklat','activity_type_id'=>$type->id,'active'=>1,'steps'=>$steps];
        $this->actingAs($admin)->get(route('workflow-master.edit',$template))->assertOk()->assertSee('source_step_id');
        $this->put(route('workflow-master.update',$template),$payload)->assertSessionHas('success');
        $new=$template->versions()->latest('version_number')->firstOrFail();
        $this->assertSame(1,$new->steps->filter(fn($step)=>$step->conditions['payment_gate']??false)->count());
        $this->assertSame(1,$new->steps->filter(fn($step)=>$step->conditions['completes_activity']??false)->count());
        $payload['steps']=array_values(array_filter($steps,fn($step)=>$step['action_type']!=='finance'));
        $this->put(route('workflow-master.update',$template),$payload)->assertSessionHasErrors('steps');
    }

    public function test_archivist_can_open_the_application_queue(): void
    {
        $archivist = User::where('email', 'sunartititin5678@gmail.com')->firstOrFail();

        $this->actingAs($archivist)
            ->get(route('applications.index', ['status' => 'submitted']))
            ->assertOk();
    }

    public function test_new_internship_is_sent_to_archivist_without_a_manual_submit_step(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $internship = ActivityType::where('name', 'Magang')->firstOrFail();

        $this->actingAs($applicant)->post(route('applications.store'), [
            'activity_type_id' => $internship->id, 'title' => 'Magang Langsung ke Arsiparis',
            'participant_count' => 1, 'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
        ])->assertSessionHas('success');

        $application = Application::where('title', 'Magang Langsung ke Arsiparis')->firstOrFail();
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'waiting_approval']);
        $this->assertDatabaseHas('workflow_tasks', ['workflow_instance_id' => $application->workflowInstance->id, 'assignee_role_slug' => 'arsiparis', 'status' => 'pending']);
    }

    public function test_applicant_can_upload_multiple_application_documents_at_once(): void
    {
        Storage::fake('local');
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $application = Application::create([
            'number' => 'DKL/2026/UPLOAD',
            'applicant_id' => $applicant->id,
            'activity_type_id' => ActivityType::firstOrFail()->id,
            'title' => 'Pengajuan Upload Banyak Berkas',
            'participant_count' => 1,
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonth()->addDay(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($applicant)->post(route('applications.documents', $application), [
            'documents' => [
                UploadedFile::fake()->create('surat-permohonan.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->create('daftar-peserta.xlsx', 80, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ]);

        $response->assertSessionHas('success', '2 berkas berhasil tersimpan di penyimpanan privat.');
        $this->assertDatabaseHas('application_documents', ['application_id' => $application->id, 'name' => 'surat-permohonan.pdf']);
        $this->assertDatabaseHas('application_documents', ['application_id' => $application->id, 'name' => 'daftar-peserta.xlsx']);
        $this->assertCount(2, $application->fresh()->documents);
        foreach ($application->fresh()->documents as $document) {
            Storage::disk('local')->assertExists($document->path);
        }
    }

    public function test_personal_application_automatically_adds_the_applicant_as_participant(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $this->actingAs($applicant)->post('/applications', [
            'activity_type_id' => ActivityType::firstOrFail()->id,
            'title' => 'Pengajuan Pribadi Otomatis',
            'participant_count' => 1,
            'institution_name' => 'Politeknik Contoh',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
        ]);

        $application = Application::where('title', 'Pengajuan Pribadi Otomatis')->firstOrFail();
        $this->assertDatabaseHas('application_participants', ['application_id' => $application->id, 'name' => $applicant->name, 'email' => $applicant->email]);
    }

    public function test_only_applicant_can_create_an_application(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $diklat = User::where('email', 'diklat@lentera.test')->firstOrFail();

        $this->actingAs($applicant)->get(route('applications.create'))->assertOk();
        $this->actingAs($diklat)->get(route('applications.create'))->assertForbidden();
        $this->actingAs($diklat)->get(route('applications.index'))
            ->assertOk()->assertDontSee('Buat Permohonan');
    }

    public function test_demo_applicant_only_sees_own_sample_applications(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();

        $this->assertSame(2, Application::where('applicant_id', $applicant->id)->count());
        $this->actingAs($applicant)->get(route('my.applications'))
            ->assertOk()->assertSee('Permohonan Saya');
    }

    public function test_research_submission_starts_dynamic_approval_workflow(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $research = ActivityType::where('name', 'Penelitian')->firstOrFail();
        $application = Application::create([
            'number' => 'DKL/2026/099991', 'applicant_id' => $applicant->id, 'activity_type_id' => $research->id,
            'title' => 'Penelitian Workflow Dinamis', 'participant_count' => 1,
            'start_date' => now()->addMonth(), 'end_date' => now()->addMonths(2), 'status' => 'draft',
        ]);

        $this->actingAs($applicant)->post(route('applications.submit', $application))->assertSessionHas('success');
        $instance = WorkflowInstance::where('workflowable_type', $application->getMorphClass())->where('workflowable_id', $application->id)->firstOrFail();
        $task = $instance->tasks()->where('status', 'pending')->firstOrFail();
        $this->assertSame('diklat-admin', $task->assignee_role_slug);
        $this->assertSame('waiting_approval', $application->fresh()->status);

        $diklat = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $this->actingAs($diklat)->get(route('approvals.index'))->assertOk()->assertSee('Penelitian Workflow Dinamis');
        $this->actingAs($diklat)->post(route('approvals.act', $task), ['action' => 'approve', 'note' => 'Administrasi lengkap.'])->assertRedirect(route('approvals.index'));
        $this->assertDatabaseHas('workflow_tasks', ['workflow_instance_id' => $instance->id, 'assignee_role_slug' => 'diklat-coordinator', 'status' => 'pending']);
        $this->assertDatabaseHas('workflow_actions', ['workflow_task_id' => $task->id, 'action' => 'approve', 'actor_name' => $diklat->name]);

        $officials = [
            'diklat-coordinator' => 'bucot@lentera.test',
            'ethics-admin' => 'etik@lentera.test', 'ethics-reviewer' => 'reviewer@lentera.test',
            'ethics-chair' => 'ketuaetik@lentera.test', 'unit-head' => 'kepalaunit@lentera.test',
            'division-head' => 'kepalabidang@lentera.test', 'finance-officer' => 'keuangan@lentera.test',
            'head-general' => 'kabag.umum@lentera.test',
            'deputy-director' => 'wadir@lentera.test', 'director' => 'direktur@lentera.test',
        ];
        foreach ($officials as $role => $email) {
            $next = WorkflowTask::where('workflow_instance_id', $instance->id)->where('status', 'pending')->firstOrFail();
            $this->assertSame($role, $next->assignee_role_slug);
            $official = User::where('email', $email)->firstOrFail();
            $this->actingAs($official)->post(route('approvals.act', $next), ['action' => 'approve', 'note' => 'Disetujui pada tahap '.$next->step->name])->assertRedirect(route('approvals.index'));
        }
        $this->assertSame('approved', $instance->fresh()->status);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame(11, WorkflowAction::whereHas('task', fn ($query) => $query->where('workflow_instance_id', $instance->id))->count());
    }

    public function test_workflow_master_publishes_a_new_version_without_changing_running_instance(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $template = WorkflowTemplate::with('latestVersion')->firstOrFail();
        $oldVersion = $template->latestVersion->id;

        $this->actingAs($admin)->put(route('workflow-master.update', $template), [
            'name' => $template->name, 'module' => 'diklat', 'activity_type_id' => $template->activity_type_id,
            'description' => 'Kebijakan workflow versi pengujian', 'active' => 1,
            'steps' => [
                ['name' => 'Verifikasi Admin Diklat', 'action_type' => 'verification', 'assignee' => 'role:diklat-admin', 'sla_hours' => 24, 'condition' => 'always'],
                ['name' => 'Persetujuan Direktur', 'action_type' => 'signature', 'assignee' => 'role:director', 'sla_hours' => 24, 'condition' => 'always'],
            ],
        ])->assertRedirect(route('workflow-master.index'));

        $this->assertSame(2, $template->versions()->count());
        $this->assertNotSame($oldVersion, $template->fresh()->latestVersion->id);
        $this->actingAs($admin)->get(route('workflow-master.index'))->assertOk()->assertSee('Workflow Persetujuan');
    }

    public function test_workflow_validation_is_clear_in_indonesian_and_accepts_detailed_description(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $this->actingAs($admin)->get(route('workflow-master.create'))
            ->assertOk()
            ->assertSee('Batas maksimal')
            ->assertSee('data-character-limit', false);
        $valid = [
            'name' => 'Workflow Praktik dan Magang',
            'module' => 'diklat',
            'description' => str_repeat('Keterangan alur persetujuan. ', 60),
            'active' => 1,
            'steps' => [[
                'name' => 'Verifikasi Administrasi',
                'action_type' => 'verification',
                'assignee' => 'role:diklat-admin',
                'sla_hours' => 24,
                'condition' => 'always',
            ]],
        ];

        $this->actingAs($admin)->post(route('workflow-master.store'), $valid)
            ->assertRedirect(route('workflow-master.index'));
        $this->assertDatabaseHas('workflow_templates', ['name' => 'Workflow Praktik dan Magang']);

        $response = $this->actingAs($admin)->from(route('workflow-master.create'))->post(route('workflow-master.store'), array_replace_recursive($valid, [
            'name' => 'Workflow Tidak Valid',
            'steps' => [['name' => str_repeat('A', 256)]],
        ]));

        $response->assertRedirect(route('workflow-master.create'))->assertSessionHasErrors('steps.0.name');
        $this->assertSame('nama tahap tidak boleh lebih dari 255 karakter.', session('errors')->first('steps.0.name'));
    }

    public function test_revision_returns_to_the_same_dynamic_workflow_step(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $diklat = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $research = ActivityType::where('name', 'Penelitian')->firstOrFail();
        $application = Application::create([
            'number' => 'DKL/2026/099992', 'applicant_id' => $applicant->id, 'activity_type_id' => $research->id,
            'title' => 'Penelitian Perlu Revisi', 'participant_count' => 1,
            'start_date' => now()->addMonth(), 'end_date' => now()->addMonths(2), 'status' => 'draft',
        ]);
        $this->actingAs($applicant)->post(route('applications.submit', $application));
        $task = $application->workflowInstance()->firstOrFail()->tasks()->where('status', 'pending')->firstOrFail();

        $this->actingAs($diklat)->post(route('approvals.act', $task), ['action' => 'revision', 'note' => 'Lengkapi surat institusi.']);
        $this->assertSame('revision_required', $application->fresh()->status);
        $this->actingAs($applicant)->post(route('applications.submit', $application))->assertSessionHas('success');

        $pending = $application->workflowInstance->tasks()->where('status', 'pending')->latest()->firstOrFail();
        $this->assertSame($task->workflow_step_id, $pending->workflow_step_id);
        $this->assertSame('waiting_approval', $application->fresh()->status);
    }

    public function test_ethics_reviewer_only_sees_assigned_research(): void
    {
        $reviewer = User::where('email', 'reviewer@lentera.test')->firstOrFail();
        $this->actingAs($reviewer)->get('/ethics')->assertOk()->assertSee('Pengajuan Ethical Clearance');
        $assigned = EthicsApplication::whereHas('reviews', fn ($q) => $q->where('reviewer_id', $reviewer->id))->count();
        $this->assertGreaterThan(0, $assigned);
    }

    public function test_applicant_cannot_open_audit_log(): void
    {
        $user = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $this->actingAs($user)->get('/audit-logs')->assertForbidden();
    }

    public function test_each_account_is_restricted_to_its_module(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $diklat = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $kamkordik = User::where('email', 'kamkordik@lentera.test')->firstOrFail();
        $ethics = User::where('email', 'etik@lentera.test')->firstOrFail();
        $student = User::where('email', 'mahasiswa@lentera.test')->firstOrFail();

        $this->actingAs($applicant)->get('/applications')->assertOk();
        $this->actingAs($applicant)->get('/kamkordik')->assertForbidden();
        $this->actingAs($applicant)->get('/ethics/reviews-meetings')->assertForbidden();

        $this->actingAs($diklat)->get('/applications')->assertOk();
        $this->actingAs($diklat)->get('/kamkordik')->assertForbidden();
        $this->actingAs($diklat)->get('/ethics')->assertForbidden();

        $this->actingAs($kamkordik)->get('/kamkordik')->assertOk();
        $this->actingAs($kamkordik)->get('/applications')->assertForbidden();
        $this->actingAs($kamkordik)->get('/ethics')->assertForbidden();

        $this->actingAs($ethics)->get('/ethics')->assertOk();
        $this->actingAs($ethics)->get('/applications')->assertForbidden();
        $this->actingAs($ethics)->get('/kamkordik')->assertForbidden();

        $this->actingAs($student)->get('/kamkordik')->assertOk();
        $this->actingAs($student)->get('/applications')->assertForbidden();
        $this->actingAs($student)->get('/ethics')->assertForbidden();
    }

    public function test_guide_is_available_to_every_authenticated_role(): void
    {
        foreach (['pemohon@lentera.test', 'diklat@lentera.test', 'kamkordik@lentera.test', 'etik@lentera.test', 'mahasiswa@lentera.test'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail())->get('/guide')->assertOk()->assertSee('Panduan &amp; Alur Layanan', false);
        }
    }

    public function test_applicant_cannot_open_another_applicants_record(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $other = User::factory()->create();
        $application = Application::firstOrFail();
        $application->update(['applicant_id' => $other->id]);

        $this->actingAs($applicant)->get(route('applications.show', $application))->assertForbidden();
    }

    public function test_api_uses_the_same_module_permissions(): void
    {
        $diklat = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $kamkordik = User::where('email', 'kamkordik@lentera.test')->firstOrFail();
        $ethics = User::where('email', 'etik@lentera.test')->firstOrFail();

        $this->actingAs($diklat)->getJson('/api/applications')->assertOk();
        $this->actingAs($diklat)->getJson('/api/students')->assertForbidden();
        $this->actingAs($kamkordik)->getJson('/api/students')->assertOk();
        $this->actingAs($kamkordik)->getJson('/api/ethics/applications')->assertForbidden();
        $this->actingAs($ethics)->getJson('/api/ethics/applications')->assertOk();
        $this->actingAs($ethics)->getJson('/api/applications')->assertForbidden();
    }

    public function test_ethics_reviewer_cannot_open_unassigned_proposal(): void
    {
        $reviewer = User::where('email', 'reviewer@lentera.test')->firstOrFail();
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $proposal = EthicsApplication::create(['number' => 'KEPK/TEST/UNASSIGNED', 'applicant_id' => $applicant->id, 'title' => 'Proposal tanpa penugasan', 'principal_investigator' => 'Peneliti Test', 'status' => 'review']);

        $this->actingAs($reviewer)->get(route('ethics.show', $proposal))->assertForbidden();
    }

    public function test_rsud_embung_fatimah_identity_is_available(): void
    {
        $hospital = Institution::where('name', 'RSUD Embung Fatimah Kota Batam')->firstOrFail();
        $this->assertSame('images/branding/rsud-embung-fatimah.png', $hospital->logo_path);
        $this->assertFileExists(public_path($hospital->logo_path));
        $this->get('/')->assertOk()->assertSee('RSUD Embung Fatimah Kota Batam');
    }

    public function test_login_does_not_display_demo_accounts(): void
    {
        $this->get('/login')->assertOk()
            ->assertDontSee('Akun demonstrasi')
            ->assertDontSee('admin@lentera.test')
            ->assertDontSee('pemohon@lentera.test');
    }

    public function test_registration_accepts_a_typed_institution_name(): void
    {
        $response = $this->post('/register', [
            'nik' => '2171011508900001', 'name' => 'Pemohon Institusi Baru', 'email' => 'institusi-baru@example.test',
            'phone' => '081234567001', 'institution_name' => 'Universitas Demo Batam', 'position' => 'Mahasiswa',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $institution = Institution::where('name', 'Universitas Demo Batam')->firstOrFail();
        $this->assertDatabaseHas('users', ['email' => 'institusi-baru@example.test', 'institution_id' => $institution->id]);
        $this->assertSame('pending', $institution->cooperation_status);
    }

    public function test_authorized_admin_can_manage_institutions_and_master_data(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $response = $this->actingAs($admin)->post(route('institutions.store'), [
            'name' => 'Akademi Kesehatan Demo', 'type' => 'Perguruan Tinggi', 'cooperation_status' => 'pending', 'email' => 'pic@akademi.test',
        ]);
        $institution = Institution::where('name', 'Akademi Kesehatan Demo')->firstOrFail();
        $response->assertRedirect(route('institutions'));
        $this->actingAs($admin)->put(route('institutions.update', $institution), [
            'name' => 'Akademi Kesehatan Demo', 'type' => 'Perguruan Tinggi', 'cooperation_status' => 'active', 'email' => 'pic@akademi.test',
        ])->assertSessionHas('success');
        $this->assertDatabaseHas('institutions', ['id' => $institution->id, 'cooperation_status' => 'active']);

        $this->actingAs($admin)->post(route('master.store', 'departments'), [
            'name' => 'Unit Demo', 'code' => 'DEMO-UNIT', 'type' => 'unit', 'active' => 1,
        ])->assertRedirect(route('master.dataset', 'departments'));
        $this->assertDatabaseHas('departments', ['code' => 'DEMO-UNIT', 'active' => 1]);

        $account = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $this->actingAs($admin)->patch(route('users.status', $account))->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $account->id, 'active' => false]);
    }

    public function test_admin_can_create_edit_and_delete_public_services(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();

        $this->actingAs($admin)->post(route('master.store', 'activity_types'), [
            'name' => 'Layanan Uji Publik',
            'code' => 'LAY-UJI',
            'description' => 'Deskripsi layanan yang dikelola administrator.',
            'color' => '#2864dc',
            'is_paid' => 1,
            'requires_ethics' => 0,
            'requires_kamkordik' => 0,
            'active' => 1,
        ])->assertRedirect(route('master.dataset', 'activity_types'));

        $service = ActivityType::where('code', 'LAY-UJI')->firstOrFail();
        $this->get('/')->assertOk()->assertSee('Layanan Uji Publik')->assertSee('Deskripsi layanan yang dikelola administrator.');

        $this->actingAs($admin)->put(route('master.update', ['activity_types', $service->id]), [
            'name' => 'Layanan Uji Diperbarui',
            'code' => 'LAY-UJI',
            'description' => 'Deskripsi layanan yang diperbarui.',
            'color' => '#168b6a',
            'is_paid' => 0,
            'requires_ethics' => 1,
            'requires_kamkordik' => 0,
            'active' => 1,
        ])->assertRedirect(route('master.dataset', 'activity_types'));

        $this->get('/')->assertOk()->assertSee('Layanan Uji Diperbarui')->assertSee('Deskripsi layanan yang diperbarui.');
        $this->actingAs($admin)->delete(route('master.destroy', ['activity_types', $service->id]))
            ->assertRedirect(route('master.dataset', 'activity_types'));
        $this->assertDatabaseMissing('activity_types', ['id' => $service->id]);
        $this->get('/')->assertDontSee('Layanan Uji Diperbarui');
    }

    public function test_admin_cannot_delete_a_service_used_by_an_application(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $service = Application::firstOrFail()->activityType;

        $this->actingAs($admin)->delete(route('master.destroy', ['activity_types', $service->id]))
            ->assertUnprocessable();
        $this->assertDatabaseHas('activity_types', ['id' => $service->id]);
    }

    public function test_admin_can_manage_indonesian_application_follow_ups(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $application = Application::whereDoesntHave('workflowInstance')->firstOrFail();

        $this->actingAs($admin)->get(route('applications.show', $application))
            ->assertOk()
            ->assertSee('Verifikasi Administrasi')
            ->assertSee('Menunggu Persetujuan')
            ->assertDontSee('Administrative Verification');

        $this->actingAs($admin)->post(route('master.store', 'application_follow_ups'), [
            'label' => 'Pemeriksaan Dokumen Tambahan',
            'code' => 'additional_document_review',
            'sort_order' => 15,
            'active' => 1,
        ])->assertRedirect(route('master.dataset', 'application_follow_ups'));

        $followUp = ApplicationFollowUp::where('code', 'additional_document_review')->firstOrFail();
        $this->actingAs($admin)->put(route('master.update', ['application_follow_ups', $followUp->id]), [
            'label' => 'Pemeriksaan Berkas Tambahan',
            'code' => 'additional_document_review',
            'sort_order' => 15,
            'active' => 1,
        ])->assertRedirect(route('master.dataset', 'application_follow_ups'));

        $this->actingAs($admin)->get(route('applications.show', $application))->assertSee('Pemeriksaan Berkas Tambahan');
        $this->actingAs($admin)->post(route('applications.transition', $application), [
            'status' => 'additional_document_review',
            'note' => 'Berkas sedang diperiksa.',
        ])->assertSessionHas('success');
        $this->assertSame('additional_document_review', $application->fresh()->status);
        $this->actingAs($admin)->delete(route('master.destroy', ['application_follow_ups', $followUp->id]))
            ->assertUnprocessable();

        $unused = ApplicationFollowUp::create(['label' => 'Status Sementara', 'code' => 'temporary_status', 'sort_order' => 999, 'active' => true]);
        $this->actingAs($admin)->delete(route('master.destroy', ['application_follow_ups', $unused->id]))
            ->assertRedirect(route('master.dataset', 'application_follow_ups'));
        $this->assertDatabaseMissing('application_follow_ups', ['id' => $unused->id]);
    }

    public function test_admin_can_manage_payment_methods_locations_and_signatories(): void
    {
        Storage::fake('local');
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();
        $department = Department::firstOrFail();

        $this->actingAs($admin)->get(route('master.index'))->assertOk()
            ->assertSee('Metode Pembayaran &amp; Rekening', false)
            ->assertSee('Lokasi/Ruangan Kegiatan')
            ->assertSee('Pejabat Penandatangan');

        $this->actingAs($admin)->post(route('master.store', 'payment_methods'), [
            'name' => 'Transfer Bank Uji', 'method_type' => 'bank_transfer', 'bank_name' => 'Bank Riau Kepri Syariah',
            'account_number' => '1234567890', 'account_holder' => 'RSUD Embung Fatimah',
            'instructions' => 'Cantumkan nomor invoice pada berita transfer.', 'active' => 1,
        ])->assertRedirect(route('master.dataset', 'payment_methods'));
        $paymentMethod = DB::table('payment_methods')->where('name', 'Transfer Bank Uji')->first();
        $this->assertNotNull($paymentMethod);
        $this->actingAs($admin)->put(route('master.update', ['payment_methods', $paymentMethod->id]), [
            'name' => 'Transfer Bank RSUDEF', 'method_type' => 'bank_transfer', 'bank_name' => 'Bank Riau Kepri Syariah',
            'account_number' => '1234567890', 'account_holder' => 'RSUD Embung Fatimah',
            'instructions' => 'Cantumkan nomor invoice.', 'active' => 1,
        ])->assertRedirect(route('master.dataset', 'payment_methods'));
        $this->assertDatabaseHas('payment_methods', ['id' => $paymentMethod->id, 'name' => 'Transfer Bank RSUDEF']);

        $this->actingAs($admin)->post(route('master.store', 'activity_locations'), [
            'name' => 'Ruang Pelatihan Utama', 'type' => 'training_room', 'department_id' => $department->id,
            'capacity' => 40, 'location_detail' => 'Gedung A, Lantai 2', 'active' => 1,
        ])->assertRedirect(route('master.dataset', 'activity_locations'));
        $location = DB::table('activity_locations')->where('name', 'Ruang Pelatihan Utama')->first();
        $this->assertNotNull($location);

        $this->actingAs($admin)->post(route('master.store', 'signatories'), [
            'name' => 'dr. Pejabat Uji', 'position' => 'Direktur', 'nip' => '198001012010011001',
            'signature_path' => UploadedFile::fake()->create('tanda-tangan.png', 30, 'image/png'),
            'valid_from' => now()->toDateString(), 'valid_until' => now()->addYear()->toDateString(), 'active' => 1,
        ])->assertRedirect(route('master.dataset', 'signatories'));
        $signatory = DB::table('signatories')->where('nip', '198001012010011001')->first();
        $this->assertNotNull($signatory);
        Storage::disk('local')->assertExists($signatory->signature_path);

        $this->actingAs($admin)->delete(route('master.destroy', ['payment_methods', $paymentMethod->id]))->assertRedirect();
        $this->actingAs($admin)->delete(route('master.destroy', ['activity_locations', $location->id]))->assertRedirect();
        $this->actingAs($admin)->delete(route('master.destroy', ['signatories', $signatory->id]))->assertRedirect();
        $this->assertDatabaseMissing('payment_methods', ['id' => $paymentMethod->id]);
        $this->assertDatabaseMissing('activity_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('signatories', ['id' => $signatory->id]);
        Storage::disk('local')->assertMissing($signatory->signature_path);
    }

    public function test_kamkordik_admin_can_manage_supervisors_and_students(): void
    {
        $admin = User::where('email', 'kamkordik@lentera.test')->firstOrFail();
        $department = Department::firstOrFail();
        $this->actingAs($admin)->post(route('supervisors.store'), [
            'department_id' => $department->id, 'name' => 'dr. Pembimbing Demo', 'professional_number' => 'SIP-DEMO', 'active' => 1,
        ])->assertRedirect(route('supervisors.index'));
        $this->assertDatabaseHas('clinical_supervisors', ['name' => 'dr. Pembimbing Demo']);

        $student = Student::firstOrFail();
        $this->actingAs($admin)->put(route('students.update', $student), [
            'name' => 'Peserta Diperbarui', 'nim' => $student->nim, 'institution_id' => $student->institution_id,
            'education_program_id' => $student->education_program_id, 'gender' => $student->gender, 'email' => $student->email,
            'phone' => $student->phone, 'period_start' => $student->period_start->toDateString(), 'period_end' => $student->period_end->toDateString(), 'status' => 'completed',
        ])->assertRedirect(route('kamkordik.index'));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Peserta Diperbarui', 'status' => 'completed']);
    }

    public function test_kamkordik_admin_can_edit_and_cancel_a_rotation(): void
    {
        $admin = User::where('email', 'kamkordik@lentera.test')->firstOrFail();
        $rotation = ClinicalRotation::firstOrFail();
        $this->actingAs($admin)->put(route('rotations.update', $rotation), [
            'student_id' => $rotation->student_id, 'department_id' => $rotation->department_id,
            'clinical_supervisor_id' => $rotation->clinical_supervisor_id, 'stage' => 'Stase Diperbarui',
            'start_date' => $rotation->start_date->toDateString(), 'end_date' => $rotation->end_date->toDateString(), 'status' => 'running',
        ])->assertRedirect(route('rotations.index'));
        $this->assertDatabaseHas('clinical_rotations', ['id' => $rotation->id, 'stage' => 'Stase Diperbarui']);
        $this->actingAs($admin)->patch(route('rotations.cancel', $rotation))->assertSessionHas('success');
        $this->assertDatabaseHas('clinical_rotations', ['id' => $rotation->id, 'status' => 'cancelled']);
    }

    public function test_diklat_staff_sees_a_live_badge_for_new_applications(): void
    {
        $admin = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $expected = Application::whereIn('status', ['draft', 'submitted', 'waiting_approval'])->count();
        $this->assertGreaterThan(0, $expected);

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee($expected.' pengajuan baru')
            ->assertSee(route('applications.index', ['status' => 'submitted']), false);

        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $this->actingAs($applicant)->get('/dashboard')->assertOk()->assertDontSee('pengajuan baru');
    }

    public function test_applicant_sees_notifications_only_for_own_actionable_applications(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $expected = Application::where('applicant_id', $applicant->id)->whereIn('status', ['revision_required', 'waiting_payment'])->count();
        $this->assertGreaterThan(0, $expected);

        $this->actingAs($applicant)->get('/dashboard')->assertOk()
            ->assertSee($expected.' pengajuan memerlukan tindakan')
            ->assertSee(route('my.applications'), false);
    }

    public function test_dashboard_shows_payment_total_and_scopes_applicant_payments(): void
    {
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $application = Application::where('applicant_id', $applicant->id)->firstOrFail();
        $invoice = \App\Models\Invoice::updateOrCreate(
            ['application_id' => $application->id],
            ['number' => 'INV/TEST/DASHBOARD', 'subtotal' => 750000, 'total' => 750000, 'status' => 'paid']
        );
        \App\Models\Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 750000,
            'method' => 'Transfer Bank',
            'reference' => 'REF-DASHBOARD-PAYMENT',
            'status' => 'verified',
            'paid_at' => now(),
            'verified_at' => now(),
        ]);

        $ownPayment = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('applications', 'applications.id', '=', 'invoices.application_id')
            ->where('applications.applicant_id', $applicant->id)
            ->where('payments.status', 'verified')
            ->select('payments.*', 'invoices.number as invoice_number')
            ->first();

        $this->assertNotNull($ownPayment);
        $ownTotal = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('applications', 'applications.id', '=', 'invoices.application_id')
            ->where('applications.applicant_id', $applicant->id)
            ->where('payments.status', 'verified')
            ->sum('payments.amount');

        $this->actingAs($applicant)->get('/dashboard')->assertOk()
            ->assertSee('Total Pembayaran Saya')
            ->assertSee('Rp'.number_format($ownTotal, 0, ',', '.'))
            ->assertSee('REF-DASHBOARD-PAYMENT');

        $admin = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $total = DB::table('payments')->where('status', 'verified')->sum('amount');
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('Total Pembayaran Pemohon')
            ->assertSee('Rp'.number_format($total, 0, ',', '.'))
            ->assertSee('Pembayaran Pemohon Terbaru');
    }

    public function test_payment_upload_waits_for_verification_and_rejection_allows_resubmission(): void
    {
        Storage::fake('local');
        $applicant = User::where('email', 'pemohon@lentera.test')->firstOrFail();
        $application = Application::where('applicant_id', $applicant->id)->firstOrFail()->replicate();
        $application->number = 'DKL/TEST/PAYMENT-STATUS';
        $application->status = 'waiting_payment';
        $application->save();
        $invoice = \App\Models\Invoice::create([
            'number' => 'INV/TEST/PAYMENT-STATUS',
            'application_id' => $application->id,
            'subtotal' => 500000,
            'total' => 500000,
            'status' => 'unpaid',
        ]);
        $method = DB::table('payment_methods')->where('active', true)->value('name');

        $this->actingAs($applicant)->post(route('payments.store', $invoice), [
            'amount' => 500000,
            'method' => $method,
            'reference' => 'PAYMENT-STATUS-1',
            'proof' => UploadedFile::fake()->image('bukti-bayar.jpg'),
        ])->assertSessionHas('success', 'Bukti pembayaran dikirim dan sedang menunggu verifikasi pembayaran.');

        $payment = \App\Models\Payment::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'waiting_payment_verification']);
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'from_status' => 'waiting_payment',
            'to_status' => 'waiting_payment_verification',
        ]);

        $admin = User::where('email', 'diklat@lentera.test')->firstOrFail();
        $this->actingAs($admin)->get(route('diklat.payments'))->assertOk()
            ->assertSee('PAYMENT-STATUS-1')
            ->assertSee(route('payments.proof', $payment), false)
            ->assertSee('data-payment-proof', false)
            ->assertSee('proof-modal', false)
            ->assertSee('Verifikasi')
            ->assertSee('Tolak');
        $this->actingAs($admin)->get(route('applications.show', $application))->assertOk()
            ->assertSee('Lihat Bukti '.$method)
            ->assertSee(route('payments.proof', $payment), false)
            ->assertSee('proof-modal', false);
        $this->actingAs($admin)->get(route('payments.proof', $payment))->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="bukti-pembayaran-INV-TEST-PAYMENT-STATUS.jpg"');

        $this->actingAs($admin)->post(route('payments.verify', $payment), ['status' => 'rejected'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'waiting_payment']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'unpaid']);
    }

    public function test_paginated_pages_use_the_application_pagination_component(): void
    {
        $admin = User::where('email', 'admin@lentera.test')->firstOrFail();

        foreach (['/kamkordik', '/master-data/requirements', '/applications', '/diklat/payments'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk()
                ->assertSee('pagination-wrap', false);
        }

        $application = Application::firstOrFail()->replicate();
        $application->number = 'DKL/TEST/PAGINATION';
        $application->save();
        $this->actingAs($admin)->get('/applications?status=&q=&filter=retained')->assertOk()
            ->assertSee('filter=retained', false);
    }
}
