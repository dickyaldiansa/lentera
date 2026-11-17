<?php
namespace App\Http\Controllers;

use App\Models\{Activity,ClinicalRotation,ClinicalSupervisor,EthicsApplication,EthicsReview,Institution,Invoice,Student};
use App\Services\NumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function myApplications(Request $request)
    {
        $query=\App\Models\Application::with(['activityType','institution'])->where('applicant_id',$request->user()->id);
        return view('applications.index',['applications'=>$query->latest()->paginate(12)]);
    }

    public function myActivities(Request $request)
    {
        $query=Activity::with('application.activityType');
        if($request->user()->hasRole('applicant')) $query->whereHas('application',fn($q)=>$q->where('applicant_id',$request->user()->id));
        return $this->activityTable($query->latest('start_at')->paginate(15),'Kegiatan Saya','Jadwal dan progres kegiatan yang terkait dengan akun Anda.');
    }

    public function announcements(Request $request)
    {
        $audiences=['all'];
        if($request->user()->canDo('diklat.view'))$audiences[]='diklat';
        if($request->user()->canDo('kamkordik.view'))$audiences[]='kamkordik';
        if($request->user()->canDo('ethics.view'))$audiences[]='ethics';
        $items=DB::table('announcements')->whereIn('audience',$audiences)->latest('published_at')->paginate(15);
        $rows=$items->map(fn($a)=>[
            ['text'=>$a->title,'sub'=>str($a->content)->limit(90)],
            ['text'=>str($a->audience)->replace('_',' ')->title()],
            ['text'=>\Carbon\Carbon::parse($a->published_at)->translatedFormat('d M Y H:i')],
            ['status'=>\Carbon\Carbon::parse($a->published_at)->isFuture()?'scheduled':'published'],
        ]);
        return $this->table('Pengumuman','Menu Utama','Informasi resmi untuk pengguna LENTERA.',['Pengumuman','Audiens','Terbit','Status'],$rows,$items);
    }

    public function diklatActivities(Request $request)
    {
        $query=Activity::with('application.activityType');
        if($request->user()->hasRole('applicant'))$query->whereHas('application',fn($q)=>$q->where('applicant_id',$request->user()->id));
        return $this->activityTable($query->latest('start_at')->paginate(15),'Daftar Kegiatan',$request->user()->hasRole('applicant')?'Kegiatan yang terkait dengan akun Anda.':'Seluruh kegiatan eksternal yang telah dijadwalkan dan berjalan.');
    }

    public function payments()
    {
        $items=Invoice::with(['application.applicant','payments'])->latest()->paginate(15);
        return view('operations.payments',['invoices'=>$items]);
    }

    public function educationInstitutions()
    {
        $items=Institution::where(fn($q)=>$q->where('type','like','%Perguruan%')->orWhereNotNull('faculty'))->orderBy('name')->paginate(15);
        $rows=$items->map(fn($i)=>[
            ['text'=>$i->name,'sub'=>$i->faculty],['text'=>$i->study_program?:'-'],['text'=>$i->pic_name?:'-','sub'=>$i->email],['text'=>$i->mou_number?:'-'],['status'=>$i->cooperation_status],
        ]);
        return $this->table('Institusi Pendidikan','KAMKORDIK','Mitra pendidikan, fakultas, program studi, dan status kerja sama.',['Institusi','Program Studi','PIC','PKS/MoU','Status'],$rows,$items);
    }

    public function assessments(Request $request)
    {
        $students=Student::where('status','active');
        if($request->user()->hasRole('student'))$students->where('user_id',$request->user()->id);
        elseif($request->user()->hasRole('clinical-supervisor'))$students->whereHas('rotations.supervisor',fn($q)=>$q->where('user_id',$request->user()->id));
        $studentIds=(clone $students)->pluck('students.id');
        $items=DB::table('assessments')->join('students','students.id','=','assessments.student_id')->leftJoin('clinical_rotations','clinical_rotations.id','=','assessments.clinical_rotation_id')->whereIn('students.id',$studentIds)->select('assessments.*','students.name as student_name','students.number as student_number','clinical_rotations.stage')->latest('assessments.created_at')->paginate(15);
        $rotations=ClinicalRotation::with('student')->whereIn('status',['scheduled','running'])->whereIn('student_id',$studentIds)->get();
        return view('operations.assessments',['items'=>$items,'students'=>$students->orderBy('name')->get(),'rotations'=>$rotations]);
    }

    public function storeAssessment(Request $request)
    {
        $data=$request->validate(['student_id'=>'required|exists:students,id','clinical_rotation_id'=>'nullable|exists:clinical_rotations,id','knowledge'=>'required|numeric|min:0|max:100','skill'=>'required|numeric|min:0|max:100','attitude'=>'required|numeric|min:0|max:100','communication'=>'required|numeric|min:0|max:100','professionalism'=>'required|numeric|min:0|max:100','note'=>'nullable']);
        if($request->user()->hasRole('clinical-supervisor'))abort_unless(ClinicalRotation::where('student_id',$data['student_id'])->whereHas('supervisor',fn($q)=>$q->where('user_id',$request->user()->id))->exists(),403,'Peserta ini bukan penugasan Anda.');
        $scores=collect($data)->only(['knowledge','skill','attitude','communication','professionalism'])->map(fn($v)=>(float)$v)->all();$final=round(array_sum($scores)/count($scores),2);$grade=$final>=85?'Sangat Baik':($final>=75?'Baik':($final>=60?'Cukup':'Kurang'));
        DB::table('assessments')->insert(['student_id'=>$data['student_id'],'clinical_rotation_id'=>$data['clinical_rotation_id']??null,'assessor_id'=>$request->user()->id,'scores'=>json_encode($scores),'final_score'=>$final,'grade'=>$grade,'note'=>$data['note']??null,'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Penilaian peserta berhasil disimpan.');
    }

    public function supervisors()
    {
        $items=ClinicalSupervisor::with('department')->orderBy('name')->paginate(15);
        $rows=$items->map(fn($s)=>[['text'=>$s->name,'sub'=>$s->professional_number],['text'=>$s->department->name],['text'=>$s->specialization?:'-'],['status'=>$s->active?'active':'inactive']]);
        return $this->table('Pembimbing Klinis','KAMKORDIK','Daftar pembimbing, unit klinis, dan status penugasan.',['Pembimbing','Bagian','Spesialisasi','Status'],$rows,$items);
    }

    public function reviewsAndMeetings(Request $request)
    {
        $reviews=EthicsReview::with(['application','reviewer']);
        if($request->user()->hasRole('ethics-reviewer'))$reviews->where('reviewer_id',$request->user()->id);
        return view('operations.ethics-reviews',['reviews'=>$reviews->latest()->paginate(12),'meetings'=>DB::table('ethics_meetings')->latest('scheduled_at')->limit(10)->get()]);
    }

    public function storeMeeting(Request $request,NumberGenerator $numbers)
    {
        $data=$request->validate(['scheduled_at'=>'required|date','location'=>'nullable','agenda'=>'required']);
        DB::table('ethics_meetings')->insert($data+['number'=>$numbers->next('meeting'),'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Sidang etik berhasil dijadwalkan.');
    }

    public function decisions(Request $request)
    {
        $query=EthicsApplication::with(['institution','reviews','clearance'])->whereIn('status',['decision','approved','rejected']);
        if($request->user()->hasRole('ethics-reviewer'))$query->whereHas('reviews',fn($q)=>$q->where('reviewer_id',$request->user()->id));
        $items=$query->latest()->paginate(15);
        $rows=$items->map(fn($e)=>[['text'=>$e->number,'sub'=>$e->title],['text'=>$e->principal_investigator,'sub'=>$e->institution?->name],['text'=>$e->reviews->first()?->recommendation??'-'],['text'=>$e->clearance?->number??'-'],['status'=>$e->status]]);
        return $this->table('Keputusan & Ethical Clearance','Komite Etik','Keputusan akhir, rekomendasi reviewer, dan dokumen EC.',['Penelitian','Peneliti','Rekomendasi','Nomor EC','Status'],$rows,$items);
    }

    public function monitoring()
    {
        return view('operations.monitoring',['reports'=>DB::table('research_monitoring')->join('ethics_applications','ethics_applications.id','=','research_monitoring.ethics_application_id')->select('research_monitoring.*','ethics_applications.number','ethics_applications.title')->latest('report_date')->paginate(15),'research'=>EthicsApplication::where('status','approved')->orderBy('title')->get()]);
    }

    public function storeMonitoring(Request $request)
    {
        $data=$request->validate(['ethics_application_id'=>'required|exists:ethics_applications,id','type'=>'required|in:progress,amendment,incident,final','report_date'=>'required|date','summary'=>'required']);
        DB::table('research_monitoring')->insert($data+['status'=>'submitted','created_at'=>now(),'updated_at'=>now()]);return back()->with('success','Laporan monitoring berhasil dicatat.');
    }

    public function masterData()
    {
        $sets=[['Institusi','institutions','institution'],['Program Pendidikan','education_programs','education'],['Jenis Kegiatan','activity_types','activity'],['Tindak Lanjut Pengajuan','application_follow_ups','review'],['Unit/Instalasi','departments','institution'],['Lokasi/Ruangan Kegiatan','activity_locations','activity'],['Persyaratan','requirements','application'],['Tarif','tariffs','payment'],['Metode Pembayaran & Rekening','payment_methods','payment'],['Pejabat Penandatangan','signatories','certificate'],['Workflow Persetujuan','workflow_templates','rotation'],['Template Surat','document_templates','certificate']];
        foreach($sets as &$set)$set[] = DB::table($set[1])->count();
        return view('operations.master',compact('sets'));
    }

    public function masterDataset(string $dataset)
    {
        if($dataset==='tariffs')return view('tariffs.master',[
            'regulations'=>\App\Models\TariffRegulation::orderByDesc('is_current')->orderByDesc('year')->orderByDesc('id')->get(),
            'tariffs'=>\App\Models\Tariff::with('activityType')->orderBy('category')->orderBy('sort_order')->orderBy('id')->paginate(25),
        ]);
        $definitions=[
            'institutions'=>['Institusi',['name'=>'Nama','type'=>'Jenis','phone'=>'Telepon','cooperation_status'=>'Status']],
            'education_programs'=>['Program Pendidikan',['name'=>'Program','category'=>'Kategori','duration_months'=>'Durasi (bulan)','active'=>'Aktif']],
            'activity_types'=>['Layanan',['name'=>'Layanan','code'=>'Kode','description'=>'Deskripsi','is_paid'=>'Berbayar','requires_ethics'=>'Perlu Etik','requires_kamkordik'=>'Perlu KAMKORDIK','active'=>'Tampil']],
            'application_follow_ups'=>['Tindak Lanjut Pengajuan',['label'=>'Nama Tindak Lanjut','code'=>'Kode Sistem','sort_order'=>'Urutan','active'=>'Aktif']],
            'payment_methods'=>['Metode Pembayaran & Rekening',['name'=>'Metode','method_type'=>'Jenis','bank_name'=>'Bank','account_number'=>'Nomor Rekening/VA','account_holder'=>'Atas Nama','active'=>'Aktif']],
            'activity_locations'=>['Lokasi/Ruangan Kegiatan',['name'=>'Lokasi/Ruangan','type'=>'Jenis','capacity'=>'Kapasitas','location_detail'=>'Detail Lokasi','active'=>'Aktif']],
            'signatories'=>['Pejabat Penandatangan',['name'=>'Nama','position'=>'Jabatan','nip'=>'NIP','valid_from'=>'Berlaku Mulai','valid_until'=>'Berlaku Sampai','active'=>'Aktif']],
            'departments'=>['Unit/Instalasi',['name'=>'Unit','code'=>'Kode','type'=>'Jenis','active'=>'Aktif']],
            'requirements'=>['Persyaratan Dinamis',['name'=>'Persyaratan','required'=>'Wajib','max_size_kb'=>'Maks. KB']],
            'tariffs'=>['Tarif',['name'=>'Tarif','calculation'=>'Perhitungan','amount'=>'Nominal','active'=>'Aktif']],
            'workflow_definitions'=>['Workflow',['name'=>'Workflow','module'=>'Modul','steps'=>'Tahapan','active'=>'Aktif']],
            'document_templates'=>['Template Surat',['name'=>'Template','type'=>'Jenis','active'=>'Aktif']],
        ];
        abort_unless(isset($definitions[$dataset]),404);[$title,$columns]=$definitions[$dataset];$items=DB::table($dataset)->orderBy('id')->paginate(20);
        return view('operations.dataset',compact('title','dataset','columns','items'));
    }

    private function activityTable($items,string $title,string $description)
    {
        $rows=$items->map(fn($a)=>[['text'=>$a->title,'sub'=>$a->application?->number],['text'=>$a->application?->activityType?->name??'Kegiatan'],['text'=>$a->start_at->translatedFormat('d M Y H:i'),'sub'=>'s.d. '.$a->end_at->translatedFormat('d M Y H:i')],['text'=>$a->location?:'-'],['status'=>$a->status]]);
        return $this->table($title,'Kegiatan',$description,['Kegiatan','Jenis','Waktu','Lokasi','Status'],$rows,$items);
    }

    private function table(string $title,string $eyebrow,string $description,array $headers,$rows,$paginator)
    {
        return view('operations.table',compact('title','eyebrow','description','headers','rows','paginator'));
    }
}
