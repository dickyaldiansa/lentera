<?php

namespace Tests\Feature;

use App\Models\{ActivityType,Application,ApplicationDocument,User,WorkflowTemplate};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowConfigurationTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void {parent::setUp();$this->seed(DatabaseSeeder::class);Storage::fake('local');}
    private function user(string $email='admin@lentera.test'):User{return User::where('email',$email)->firstOrFail();}
    private function step(string $behavior,string $role='diklat-admin',array $extra=[]):array{
        return array_merge(['name'=>'Nama bebas '.$behavior,'behavior'=>$behavior,'action_type'=>'approval','assignee'=>'role:'.$role,
            'sla_hours'=>24,'condition'=>'always','allow_revision'=>1,'allow_reject'=>1,'allow_pending'=>0,'outcome'=>'information'],$extra);
    }
    private function configure(string $category,array $steps,string $intent='publish'):WorkflowTemplate{
        $type=ActivityType::where('name',$category)->firstOrFail();
        $template=WorkflowTemplate::where('module','diklat')->where('activity_type_id',$type->id)->first();
        $payload=['name'=>'Alur '.$category,'module'=>'diklat','activity_type_id'=>$type->id,'active'=>1,'intent'=>$intent,'steps'=>$steps,'document_requirements'=>"Surat khusus kategori\nProposal khusus kategori"];
        $url=$template?route('workflow-master.update',$template):route('workflow-master.store');
        $this->actingAs($this->user())->{$template?'put':'post'}($url,$payload)->assertSessionHasNoErrors()->assertRedirect(route('workflow-master.index'));
        return WorkflowTemplate::where('module','diklat')->where('activity_type_id',$type->id)->firstOrFail();
    }
    private function application(string $category):Application{
        $applicant=$this->user('pemohon@lentera.test');
        $type=ActivityType::where('name',$category)->firstOrFail();
        $this->actingAs($applicant)->post(route('applications.store'),['activity_type_id'=>$type->id,'title'=>'Permohonan '.$category,'participant_count'=>1,'start_date'=>now()->addMonth()->toDateString(),'end_date'=>now()->addMonths(2)->toDateString()])->assertSessionHas('success');
        $application=Application::where('title','Permohonan '.$category)->latest('id')->firstOrFail();
        if(!$application->workflowInstance)$this->post(route('applications.submit',$application))->assertSessionHas('success');
        return $application->fresh();
    }
    private function current(Application $application){$instance=$application->fresh()->workflowInstance;return $instance->tasks()->where('workflow_step_id',$instance->current_step_id)->where('status','pending')->firstOrFail();}

    public function test_draft_does_not_replace_active_version_and_published_requirements_reach_form():void{
        $type=ActivityType::where('name','Magang')->firstOrFail();
        $template=WorkflowTemplate::where('activity_type_id',$type->id)->firstOrFail();
        $active=$template->publishedVersion->id;
        $this->configure('Magang',[$this->step('approval')],'draft');
        $this->assertSame($active,$template->fresh()->publishedVersion->id);
        $this->assertTrue((bool)$template->fresh()->active);
        $app=$this->application('Magang');
        $this->assertSame($active,$app->workflowInstance->workflow_version_id);
        $template=$this->configure('Observasi',[$this->step('administration'),$this->step('finish')]);
        $this->actingAs($this->user('pemohon@lentera.test'))->get(route('applications.create'))->assertOk()->assertSee('Surat khusus kategori');
        $this->actingAs($this->user())->get(route('workflow-master.edit',$template))->assertOk()->assertSee('Simpan Draf')->assertSee('Pratinjau Alur')->assertSee('Telaah etik');
    }

    public function test_revision_and_rejection_return_to_configured_step_and_keep_history():void{
        $this->configure('Observasi',[$this->step('administration'),$this->step('approval','division-head',['revision_target'=>1,'reject_behavior'=>'revision']),$this->step('finish')]);
        $app=$this->application('Observasi');$first=$this->current($app);
        $this->actingAs($this->user('diklat@lentera.test'))->post(route('approvals.act',$first),['action'=>'approve'])->assertSessionHas('success');
        $second=$this->current($app);
        $this->actingAs($this->user('kepalabidang@lentera.test'))->post(route('approvals.act',$second),['action'=>'reject','note'=>'Perbaiki surat'])->assertSessionHas('success');
        $this->assertSame('revision_required',$app->fresh()->status);
        $this->actingAs($this->user('pemohon@lentera.test'))->post(route('applications.submit',$app))->assertSessionHas('success');
        $this->assertSame($first->workflow_step_id,$this->current($app)->workflow_step_id);
        $this->assertSame('approved',$first->fresh()->status);
    }

    public function test_renamed_payment_and_coordination_stages_execute_and_issue_certificates():void{
        $this->configure('Praktik / PKL',[$this->step('invoice'),$this->step('payment'),$this->step('coordination','kamkordik-admin'),$this->step('handover','kamkordik-admin'),$this->step('finish','diklat-admin',['outcome'=>'certificate'])]);
        $app=$this->application('Praktik / PKL');
        $this->actingAs($this->user('diklat@lentera.test'))->post(route('approvals.act',$this->current($app)),['action'=>'approve','invoice_amount'=>250000])->assertSessionHas('success');
        $invoice=$app->fresh()->invoice;
        $this->assertFalse($app->coordinationTasks()->exists());
        $this->post(route('approvals.act',$this->current($app)),['action'=>'approve'])->assertStatus(422);
        $this->actingAs($this->user('pemohon@lentera.test'))->post(route('payments.store',$invoice),['amount'=>250000,'method'=>DB::table('payment_methods')->where('active',true)->value('name'),'proof'=>UploadedFile::fake()->image('bukti.jpg')])->assertSessionHas('success');
        $this->actingAs($this->user('diklat@lentera.test'))->post(route('payments.verify',$invoice->payments()->first()),['status'=>'verified'])->assertSessionHas('success');
        $coordination=$app->coordinationTasks()->firstOrFail();
        $this->actingAs($this->user('desi@lentera.test'))->post(route('approvals.act',$this->current($app)),['action'=>'approve'])->assertStatus(422);
        $this->post(route('kamkordik.coordinations.respond',$coordination),['status'=>'scheduled','response'=>'Jadwal sepakat'])->assertSessionHas('success');
        $this->post(route('kamkordik.coordinations.respond',$coordination),['status'=>'handover_completed','response'=>'Serah terima dilakukan'])->assertSessionHas('success');
        $this->assertTrue($app->fresh()->certificateEligible());
        $this->actingAs($this->user('diklat@lentera.test'))->get(route('diklat.certificates'))->assertSee($app->title);
    }

    public function test_permit_and_information_outcomes_do_not_enter_certificate_queue():void{
        $this->configure('Studi Banding / Kunjungan',[$this->step('approval'),$this->step('finish','diklat-admin',['outcome'=>'permit','letter_content'=>'Diizinkan melakukan kunjungan sesuai jadwal.'])]);
        $app=$this->application('Studi Banding / Kunjungan');
        $this->actingAs($this->user('diklat@lentera.test'))->post(route('approvals.act',$this->current($app)),['action'=>'approve'])->assertSessionHas('success');
        $this->assertSame('permit_issued',$app->fresh()->status);
        $this->assertCount(1,$app->letters);
        $this->assertFalse($app->certificateEligible());
        $this->configure('Observasi',[$this->step('approval'),$this->step('finish')]);
        $other=$this->application('Observasi');
        $this->actingAs($this->user('diklat@lentera.test'))->post(route('approvals.act',$this->current($other)),['action'=>'approve'])->assertSessionHas('success');
        $this->assertSame('completed',$other->fresh()->status);
        $this->assertFalse($other->fresh()->certificateEligible());
        $this->get(route('diklat.certificates'))->assertDontSee($other->title);
        $this->post(route('certificates.store',$other),['participant_ids'=>[$other->participants->first()->id]])->assertStatus(422);
        $this->post(route('applications.invoice',$other),['amount'=>100000])->assertStatus(422);
    }

    public function test_integrated_ethics_handles_documents_revision_and_ec_without_second_application():void{
        $this->configure('Penelitian',[$this->step('ethics','ethics-chair'),$this->step('finish')]);
        $app=$this->application('Penelitian');$ethics=$app->ethicsApplication;
        $this->assertNotNull($ethics);
        $task=$this->current($app);
        $chair=$this->user('handedi@lentera.test');$reviewer=$this->user('reviewer@lentera.test');
        $this->actingAs($chair)->post(route('approvals.act',$task),['action'=>'approve'])->assertStatus(422);
        Storage::disk('local')->put('test-proposal.pdf','proposal');
        $doc=ApplicationDocument::create(['application_id'=>$app->id,'name'=>'proposal.pdf','path'=>'test-proposal.pdf','mime'=>'application/pdf','size'=>8,'uploaded_by'=>$app->applicant_id]);
        $this->actingAs($reviewer)->get(route('ethics.documents',[$ethics,$doc]))->assertForbidden();
        $assign=['reviewer_id'=>$reviewer->id,'deadline'=>now()->addWeek()->toDateString()];
        $this->actingAs($this->user('tika@lentera.test'))->post(route('ethics.assign',$ethics),$assign)->assertSessionHas('success');
        $this->actingAs($reviewer)->get(route('ethics.documents',[$ethics,$doc]))->assertOk();
        $review=$ethics->reviews()->firstOrFail();
        $scores=['scientific_validity'=>4,'risk_benefit'=>4,'privacy'=>4,'informed_consent'=>4,'recommendation'=>'revision_required','summary'=>'Perbaiki consent'];
        $this->post(route('ethics.review',$review),$scores)->assertSessionHas('success');
        $this->assertSame('revision_required',$app->fresh()->status);
        $this->actingAs($this->user('pemohon@lentera.test'))->get(route('applications.show',$app))->assertOk()->assertSee('Perbaiki consent');
        $this->post(route('applications.submit',$app))->assertSessionHas('success');
        $this->actingAs($reviewer)->post(route('ethics.review',$review),array_replace($scores,['recommendation'=>'eligible']))->assertSessionHas('success');
        $this->actingAs($chair)->post(route('ethics.issue',$ethics),['valid_until'=>now()->addYear()->toDateString(),'chairperson'=>$chair->name])->assertSessionHas('success');
        $this->assertSame('completed',$app->fresh()->status);
        $this->assertSame(1,$app->ethicsApplication()->count());
        $this->assertNotNull($ethics->fresh()->clearance);
    }

    public function test_invalid_stage_order_or_missing_staff_cannot_be_published_and_applicant_cannot_edit():void{
        $this->actingAs($this->user('pemohon@lentera.test'))->get(route('workflow-master.index'))->assertForbidden();
        $type=ActivityType::where('name','Observasi')->firstOrFail();
        $payload=['name'=>'Alur salah','module'=>'diklat','activity_type_id'=>$type->id,'active'=>1,'intent'=>'publish','steps'=>[$this->step('payment'),$this->step('finish')]];
        $this->actingAs($this->user())->post(route('workflow-master.store'),$payload)->assertSessionHasErrors('steps');
        $payload['steps']=[$this->step('approval','role-tidak-ada'),$this->step('finish')];
        $this->post(route('workflow-master.store'),$payload)->assertSessionHasErrors('steps.0.assignee');
    }

    public function test_disposition_uses_next_stage_recipients_and_decision_letters_are_private():void{
        $this->configure('Observasi',[$this->step('disposition','arsiparis'),$this->step('approval','division-head'),$this->step('finish')]);
        $app=$this->application('Observasi');$task=$this->current($app);
        $fieldHead=$this->user('kepalabidang@lentera.test');
        $this->actingAs($this->user('sunartititin5678@gmail.com'))->get(route('approvals.show',$task))->assertOk()->assertSee($fieldHead->name);
        $this->post(route('approvals.act',$task),['action'=>'approve','recipient_id'=>$this->user('direktur@lentera.test')->id])->assertStatus(422);
        $this->post(route('approvals.act',$task),['action'=>'approve','recipient_id'=>$fieldHead->id])->assertSessionHas('success');
        $next=$this->current($app);
        $this->assertSame($fieldHead->id,$next->assignee_user_id);
        $this->actingAs($fieldHead)->post(route('approvals.act',$next),['action'=>'approve','decision_file'=>UploadedFile::fake()->create('rekomendasi.pdf',10,'application/pdf')])->assertSessionHas('success');
        $action=$next->actions()->firstOrFail();
        Storage::disk('local')->assertExists($action->metadata['attachment_path']);
        $url=route('approvals.attachments',[$next,$action]);
        $this->actingAs($this->user('pemohon@lentera.test'))->get($url)->assertOk();
        $this->actingAs($this->user('desi@lentera.test'))->get($url)->assertForbidden();
        $this->actingAs($this->user('pemohon@lentera.test'))->get(route('applications.show',$app))->assertOk()->assertSee('Surat keterangan / rekomendasi');
    }

    public function test_new_draft_keeps_general_routing_and_category_changes_cannot_change_running_workflow():void{
        $template=$this->configure('Observasi',[$this->step('approval')],'draft');
        $this->assertFalse((bool)$template->active);
        $app=$this->application('Observasi');
        $this->assertNotSame($template->id,$app->workflowInstance->version->workflow_template_id);
        $this->configure('Observasi',[$this->step('approval'),$this->step('finish')]);
        $this->assertNotSame($template->publishedVersion?->id,$app->fresh()->workflowInstance->workflow_version_id);
        $this->actingAs($this->user('diklat@lentera.test'))->post(route('approvals.act',$this->current($app)),['action'=>'revision','note'=>'Perbaiki berkas'])->assertSessionHas('success');
        $this->actingAs($this->user('pemohon@lentera.test'))->put(route('applications.update',$app),[
            'activity_type_id'=>ActivityType::where('name','Penelitian')->value('id'),'title'=>$app->title,'participant_count'=>1,
            'start_date'=>$app->start_date->toDateString(),'end_date'=>$app->end_date->toDateString(),
        ])->assertStatus(422);
    }
}
