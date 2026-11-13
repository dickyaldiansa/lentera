<?php
namespace Database\Seeders;

use App\Models\{EthicsApplication,Student,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $assessor=User::where('email','kamkordik@lentera.test')->value('id')??User::value('id');
        if(DB::table('assessments')->count()===0 && $assessor){
            foreach(Student::limit(5)->get() as $index=>$student){$score=82+$index;DB::table('assessments')->insert(['student_id'=>$student->id,'clinical_rotation_id'=>$student->rotations()->value('id'),'assessor_id'=>$assessor,'scores'=>json_encode(['knowledge'=>$score,'skill'=>$score+2,'attitude'=>90,'communication'=>86,'professionalism'=>89]),'final_score'=>round(($score+$score+2+90+86+89)/5,2),'grade'=>$score>=85?'Sangat Baik':'Baik','note'=>'Kemajuan peserta sesuai target rotasi.','created_at'=>now(),'updated_at'=>now()]);}
        }
        if(DB::table('ethics_meetings')->count()===0) DB::table('ethics_meetings')->insert([['number'=>'SE/KEPK/2026/0001','scheduled_at'=>now()->addDays(4)->setTime(9,0),'location'=>'Ruang Komite Etik','agenda'=>'Pembahasan proposal penelitian periode Agustus','members'=>json_encode(['Ketua','Sekretaris','Reviewer']),'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()],['number'=>'SE/KEPK/2026/0002','scheduled_at'=>now()->addDays(11)->setTime(9,0),'location'=>'Ruang Rapat RSUDEF','agenda'=>'Evaluasi revisi dan keputusan ethical clearance','members'=>json_encode(['Ketua','Reviewer']),'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()]]);
        $approved=EthicsApplication::where('status','approved')->first();
        if($approved && DB::table('research_monitoring')->count()===0) DB::table('research_monitoring')->insert(['ethics_application_id'=>$approved->id,'type'=>'progress','report_date'=>today(),'summary'=>'Rekrutmen subjek dan pengumpulan data berjalan sesuai protokol yang disetujui.','status'=>'submitted','created_at'=>now(),'updated_at'=>now()]);
    }
}
