<?php
namespace App\Http\Controllers;

use App\Models\{ApplicationDocument,EthicalClearance,EthicsApplication,EthicsReview,Institution,User};
use App\Services\{AuditService,NumberGenerator,WorkflowEngine};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EthicsController extends Controller
{
 public function index(Request $r){$q=EthicsApplication::with(['applicant','institution','reviews','clearance']);if($r->user()->hasRole('applicant'))$q->where('applicant_id',$r->user()->id);if($r->user()->hasRole('ethics-reviewer'))$q->whereHas('reviews',fn($x)=>$x->where('reviewer_id',$r->user()->id));if($r->status)$q->where('status',$r->status==='monitoring'?'approved':$r->status);return view('ethics.index',['applications'=>$q->latest()->paginate(12)->withQueryString()]);}
 public function create(){return view('ethics.form',['institutions'=>Institution::all()]);}
 public function store(Request $r,NumberGenerator $numbers){$data=$r->validate(['title'=>'required','principal_investigator'=>'required','institution_id'=>'nullable|exists:institutions,id','research_type'=>'nullable','location'=>'nullable','population'=>'nullable','subjects'=>'nullable','start_date'=>'nullable|date','end_date'=>'nullable|date|after_or_equal:start_date','funding_source'=>'nullable']);$data+=['number'=>$numbers->next('ethics'),'applicant_id'=>$r->user()->id];$app=EthicsApplication::create($data);AuditService::record('ethics','create',$app);return redirect()->route('ethics.show',$app)->with('success','Pengajuan etik disimpan.');}
 public function show(Request $r,EthicsApplication $ethicsApplication){
  $this->access($r,$ethicsApplication);
  $ethicsApplication->load(['applicant','institution','reviews.reviewer','clearance','application.documents']);
  return view('ethics.show',['ethics'=>$ethicsApplication,'reviewers'=>User::where('active',true)->whereHas('roles',fn($q)=>$q->where('slug','ethics-reviewer'))->get()]);
 }
 public function submit(Request $r,EthicsApplication $ethicsApplication,WorkflowEngine $workflow){abort_unless($ethicsApplication->applicant_id===$r->user()->id,403,'Anda hanya dapat mengirim pengajuan milik sendiri.');abort_if($ethicsApplication->application_id,422,'Kirim perbaikan melalui permohonan utama.');abort_unless(in_array($ethicsApplication->status,['draft','revision','revision_required']),422,'Pengajuan tidak dapat dikirim pada status saat ini.');$ethicsApplication->update(['status'=>'administrative_verification','submitted_at'=>now()]);$instance=$workflow->startOrResume($ethicsApplication,'ethics');return back()->with('success',$instance?'Pengajuan etik dikirim ke workflow persetujuan.':'Pengajuan etik berhasil dikirim.');}
 public function assign(Request $r,EthicsApplication $ethicsApplication){
  $this->activeLinkedStage($ethicsApplication);
  abort_unless(in_array($ethicsApplication->status,['administrative_verification','review','revision']),422,'Proposal belum siap untuk penugasan reviewer.');
  $data=$r->validate(['reviewer_id'=>'required|exists:users,id','deadline'=>'required|date|after_or_equal:today']);
  abort_unless(User::whereKey($data['reviewer_id'])->where('active',true)->whereHas('roles',fn($q)=>$q->where('slug','ethics-reviewer'))->exists(),422,'Pengguna yang dipilih bukan Reviewer Etik aktif.');
  EthicsReview::updateOrCreate(['ethics_application_id'=>$ethicsApplication->id,'reviewer_id'=>$data['reviewer_id']],$data+['assigned_at'=>today(),'status'=>'assigned']);
  $ethicsApplication->update(['status'=>'review']);
  if($ethicsApplication->application_id) User::findOrFail($data['reviewer_id'])->notify(new \App\Notifications\ApplicationProgressNotification($ethicsApplication->application,'Proposal penelitian menunggu review etik Anda.',route('ethics.show',$ethicsApplication)));
  return back()->with('success','Reviewer ditugaskan.');
 }
 public function review(Request $r,EthicsReview $review,WorkflowEngine $workflow){
  abort_unless($r->user()->id===$review->reviewer_id||$r->user()->hasRole('super-admin','ethics-chair'),403);
  $this->activeLinkedStage($review->application);
  if($review->application->application_id)abort_unless($review->status==='assigned',422,'Review sudah diselesaikan.');
  $data=$r->validate(['recommendation'=>'required|in:eligible,eligible_with_revision,revision_required,meeting_required,not_eligible','summary'=>'nullable|string|max:2000','scientific_validity'=>'required|integer|min:1|max:5','risk_benefit'=>'required|integer|min:1|max:5','privacy'=>'required|integer|min:1|max:5','informed_consent'=>'required|integer|min:1|max:5']);
  DB::transaction(function()use($r,$review,$workflow,$data){
   $old=$review->toArray();
   $scores=collect($data)->only(['scientific_validity','risk_benefit','privacy','informed_consent'])->all();
   $review->update(['scores'=>$scores,'recommendation'=>$data['recommendation'],'summary'=>$data['summary']??null,'status'=>'completed','completed_at'=>now()]);
   AuditService::record('ethics','review',$review,$old,$review->fresh()->toArray());
   $ethics=$review->application;
   if(!$ethics->application_id){$ethics->update(['status'=>in_array($data['recommendation'],['eligible','eligible_with_revision'])?'decision':'revision']);return;}
   if(in_array($data['recommendation'],['revision_required','eligible_with_revision'])){
    $ethics->update(['status'=>'revision']);
    $workflow->requestEthicsRevision($ethics,$r->user(),($data['summary']??null)?:'Komite Etik meminta perbaikan dokumen penelitian.');
   }elseif(in_array($data['recommendation'],['not_eligible','meeting_required'])){
    $ethics->update(['status'=>'decision']);
   }else{
    $ethics->update(['status'=>$ethics->reviews()->where('status','!=','completed')->exists()?'review':'decision']);
   }
  });
  return back()->with('success','Hasil review tersimpan.');
 }
 public function issue(Request $r,EthicsApplication $ethicsApplication,NumberGenerator $numbers,WorkflowEngine $workflow){
  $this->activeLinkedStage($ethicsApplication);
  abort_unless($ethicsApplication->status==='decision',422,'Ethical Clearance hanya dapat diterbitkan setelah tahap keputusan.');
  abort_if($ethicsApplication->clearance()->exists(),422,'Ethical Clearance sudah diterbitkan.');
  abort_unless($ethicsApplication->reviews()->where('status','completed')->exists(),422,'Review etik belum selesai.');
  if($ethicsApplication->application_id)abort_if($ethicsApplication->reviews()->where(fn($q)=>$q->where('status','!=','completed')->orWhereNotIn('recommendation',['eligible']))->exists(),422,'Seluruh reviewer harus selesai dan menyatakan layak sebelum EC diterbitkan.');
  $data=$r->validate(['valid_until'=>'required|date|after:today','chairperson'=>'required']);
  DB::transaction(function()use($r,$ethicsApplication,$numbers,$workflow,$data){
   $ethics=EthicsApplication::lockForUpdate()->findOrFail($ethicsApplication->id);
   abort_if($ethics->clearance()->exists(),422,'Ethical Clearance sudah diterbitkan.');
   $ec=EthicalClearance::create(['ethics_application_id'=>$ethics->id,'number'=>$numbers->next('ec'),'issued_at'=>today(),'valid_until'=>$data['valid_until'],'chairperson'=>$data['chairperson'],'verification_token'=>Str::random(48)]);
   $ethics->update(['status'=>'approved']);
   if($ethics->application_id){
    $instance=$ethics->application->workflowInstance;
    $task=$instance->tasks()->where('workflow_step_id',$instance->current_step_id)->where('status','pending')->firstOrFail();
    $workflow->act($task,$r->user(),'approve','Ethical Clearance '.$ec->number.' telah diterbitkan.');
   }
   AuditService::record('ethics','clearance.issue',$ec);
  });
  return back()->with('success','Ethical Clearance diterbitkan. Permohonan terkait dilanjutkan sesuai alur.');
 }
 public function document(Request $r,EthicsApplication $ethicsApplication,ApplicationDocument $document){
  $this->access($r,$ethicsApplication);
  abort_unless($document->application_id===$ethicsApplication->application_id,404);
  abort_unless(Storage::disk('local')->exists($document->path),404);
  return Storage::disk('local')->download($document->path,$document->name);
 }
 public function verify(string $token){$ec=EthicalClearance::with('application')->where('verification_token',$token)->firstOrFail();return view('ethics.verify',compact('ec'));}
 private function access(Request $r,EthicsApplication $ethics):void{
  if($r->user()->hasRole('applicant'))abort_unless($ethics->applicant_id===$r->user()->id,403);
  if($r->user()->hasRole('ethics-reviewer'))abort_unless($ethics->reviews()->where('reviewer_id',$r->user()->id)->exists(),403,'Proposal ini tidak ditugaskan kepada Anda.');
 }
 private function activeLinkedStage(EthicsApplication $ethics):void{
  if(!$ethics->application_id)return;
  $instance=$ethics->application?->workflowInstance;
  abort_unless($instance && $instance->status==='active' && $instance->currentStep?->behavior()==='ethics',422,'Tahap telaah etik permohonan utama belum aktif atau sedang menunggu perbaikan pemohon.');
 }
}
