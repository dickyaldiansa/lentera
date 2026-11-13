<?php

namespace App\Http\Controllers;

use App\Models\{ActivityType,Department,Tariff};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MasterDataController extends Controller
{
    private function definitions(): array
    {
        return [
            'education_programs'=>['title'=>'Program Pendidikan','fields'=>[
                'name'=>['label'=>'Nama program','type'=>'text','required'=>true], 'category'=>['label'=>'Kategori','type'=>'text','required'=>true], 'duration_months'=>['label'=>'Durasi (bulan)','type'=>'number'], 'active'=>['label'=>'Status','type'=>'boolean'],
            ]],
            'departments'=>['title'=>'Unit / Instalasi','fields'=>[
                'name'=>['label'=>'Nama unit','type'=>'text','required'=>true], 'code'=>['label'=>'Kode','type'=>'text','required'=>true,'unique'=>true], 'type'=>['label'=>'Jenis','type'=>'select','options'=>['clinical'=>'Klinis','unit'=>'Unit']], 'active'=>['label'=>'Status','type'=>'boolean'],
            ]],
            'activity_types'=>['title'=>'Jenis Kegiatan','fields'=>[
                'name'=>['label'=>'Nama layanan','type'=>'text','required'=>true], 'code'=>['label'=>'Kode','type'=>'text','required'=>true,'unique'=>true], 'description'=>['label'=>'Deskripsi layanan','type'=>'textarea'], 'color'=>['label'=>'Warna','type'=>'color'], 'is_paid'=>['label'=>'Berbayar','type'=>'boolean'], 'requires_ethics'=>['label'=>'Memerlukan etik','type'=>'boolean'], 'requires_kamkordik'=>['label'=>'Memerlukan KAMKORDIK','type'=>'boolean'], 'active'=>['label'=>'Tampilkan layanan','type'=>'boolean'],
            ]],
            'application_follow_ups'=>['title'=>'Tindak Lanjut Pengajuan','fields'=>[
                'label'=>['label'=>'Nama tindak lanjut (Bahasa Indonesia)','type'=>'text','required'=>true], 'code'=>['label'=>'Kode sistem','type'=>'text','required'=>true,'unique'=>true], 'sort_order'=>['label'=>'Urutan tampil','type'=>'number','required'=>true], 'active'=>['label'=>'Status','type'=>'boolean'],
            ]],
            'payment_methods'=>['title'=>'Metode Pembayaran & Rekening','fields'=>[
                'name'=>['label'=>'Nama metode','type'=>'text','required'=>true,'unique'=>true], 'method_type'=>['label'=>'Jenis metode','type'=>'select','required'=>true,'options'=>['bank_transfer'=>'Transfer Bank','virtual_account'=>'Virtual Account','cash'=>'Tunai','other'=>'Lainnya']], 'bank_name'=>['label'=>'Nama bank','type'=>'text'], 'account_number'=>['label'=>'Nomor rekening / virtual account','type'=>'text'], 'account_holder'=>['label'=>'Nama pemilik rekening','type'=>'text'], 'instructions'=>['label'=>'Instruksi pembayaran','type'=>'textarea'], 'active'=>['label'=>'Status','type'=>'boolean'],
            ]],
            'activity_locations'=>['title'=>'Lokasi / Ruangan Kegiatan','fields'=>[
                'name'=>['label'=>'Nama lokasi / ruangan','type'=>'text','required'=>true], 'type'=>['label'=>'Jenis lokasi','type'=>'select','required'=>true,'options'=>['training_room'=>'Ruang Pelatihan','installation'=>'Instalasi','meeting_room'=>'Ruang Rapat','other'=>'Lainnya']], 'department_id'=>['label'=>'Unit / instalasi terkait','type'=>'department'], 'capacity'=>['label'=>'Kapasitas orang','type'=>'number'], 'location_detail'=>['label'=>'Lantai / detail lokasi','type'=>'text'], 'active'=>['label'=>'Status','type'=>'boolean'],
            ]],
            'signatories'=>['title'=>'Pejabat Penandatangan','fields'=>[
                'name'=>['label'=>'Nama pejabat','type'=>'text','required'=>true], 'position'=>['label'=>'Jabatan','type'=>'text','required'=>true], 'nip'=>['label'=>'NIP','type'=>'text'], 'signature_path'=>['label'=>'Gambar tanda tangan','type'=>'file'], 'valid_from'=>['label'=>'Berlaku mulai','type'=>'date'], 'valid_until'=>['label'=>'Berlaku sampai','type'=>'date'], 'active'=>['label'=>'Status','type'=>'boolean'],
            ]],
            'requirements'=>['title'=>'Persyaratan','fields'=>[
                'activity_type_id'=>['label'=>'Jenis kegiatan','type'=>'activity_type','required'=>true], 'name'=>['label'=>'Nama persyaratan','type'=>'text','required'=>true], 'description'=>['label'=>'Deskripsi','type'=>'textarea'], 'required'=>['label'=>'Wajib','type'=>'boolean'], 'max_size_kb'=>['label'=>'Maksimal ukuran (KB)','type'=>'number'],
            ]],
            'tariffs'=>['title'=>'Tarif Layanan','fields'=>[
                'category'=>['label'=>'Kelompok tarif','type'=>'select','required'=>true,'options'=>Tariff::CATEGORIES],
                'name'=>['label'=>'Nama layanan / tarif','type'=>'text','required'=>true],
                'participant_category'=>['label'=>'Jenjang pendidikan / golongan peserta','type'=>'text'],
                'calculation'=>['label'=>'Satuan tarif','type'=>'select','required'=>true,'options'=>Tariff::UNITS],
                'amount'=>['label'=>'Nominal (Rp)','type'=>'number','required'=>true],
                'min_participants'=>['label'=>'Minimal peserta kelompok (jika ada)','type'=>'number'],
                'max_participants'=>['label'=>'Maksimal peserta kelompok (jika ada)','type'=>'number'],
                'description'=>['label'=>'Keterangan / fasilitas','type'=>'textarea'],
                'activity_type_id'=>['label'=>'Khusus jenis kegiatan (kosong = mengikuti kelompok tarif)','type'=>'activity_type'],
                'department_id'=>['label'=>'Unit / instalasi','type'=>'department'],
                'sort_order'=>['label'=>'Urutan tampil','type'=>'number'],
                'published'=>['label'=>'Tampilkan kepada pemohon','type'=>'boolean'],
                'active'=>['label'=>'Status aktif','type'=>'boolean'],
            ]],
        ];
    }

    public function create(string $dataset){return $this->form($dataset,null);}
    public function edit(string $dataset,int $id){$this->definition($dataset);return $this->form($dataset,DB::table($dataset)->find($id)??abort(404));}
    public function store(Request $request,string $dataset){$definition=$this->definition($dataset);$data=$this->storeFiles($this->validated($request,$dataset,$definition),$dataset);$data+=['created_at'=>now(),'updated_at'=>now()];$id=DB::table($dataset)->insertGetId($data);$this->audit($dataset,'create',$id,[],(array)DB::table($dataset)->find($id));return redirect()->route('master.dataset',$dataset)->with('success','Data berhasil ditambahkan.');}
    public function update(Request $request,string $dataset,int $id){$definition=$this->definition($dataset);$item=DB::table($dataset)->find($id)??abort(404);$data=$this->validated($request,$dataset,$definition,$id);$oldFile=isset($data['signature_path'])?($item->signature_path??null):null;$data=$this->storeFiles($data,$dataset)+['updated_at'=>now()];DB::table($dataset)->where('id',$id)->update($data);if($oldFile)Storage::disk('local')->delete($oldFile);$this->audit($dataset,'update',$id,(array)$item,(array)DB::table($dataset)->find($id));return redirect()->route('master.dataset',$dataset)->with('success','Data berhasil diperbarui.');}
    public function destroy(string $dataset,int $id){
        abort_unless(in_array($dataset,['activity_types','application_follow_ups','payment_methods','activity_locations','signatories'],true),404);
        $item=DB::table($dataset)->find($id)??abort(404);
        if($dataset==='activity_types')abort_if(DB::table('applications')->where('activity_type_id',$id)->exists(),422,'Layanan sudah digunakan oleh pengajuan dan tidak dapat dihapus. Nonaktifkan layanan agar tidak tampil lagi.');
        if($dataset==='application_follow_ups')abort_if(DB::table('applications')->where('status',$item->code)->exists()||DB::table('application_status_histories')->where('from_status',$item->code)->orWhere('to_status',$item->code)->exists(),422,'Tindak lanjut sudah digunakan dan tidak dapat dihapus. Nonaktifkan agar tidak dapat dipilih lagi.');
        if($dataset==='payment_methods')abort_if(DB::table('payments')->where('method',$item->name)->exists(),422,'Metode pembayaran sudah digunakan dan tidak dapat dihapus. Nonaktifkan agar tidak dapat dipilih lagi.');
        DB::table($dataset)->where('id',$id)->delete();
        if($dataset==='signatories'&&$item->signature_path)Storage::disk('local')->delete($item->signature_path);
        $this->audit($dataset,'delete',$id,(array)$item,[]);
        return redirect()->route('master.dataset',$dataset)->with('success','Data berhasil dihapus.');
    }
    public function toggle(string $dataset,int $id){$definition=$this->definition($dataset);abort_unless(isset($definition['fields']['active']),422,'Data ini tidak memiliki status aktif.');$item=DB::table($dataset)->find($id)??abort(404);DB::table($dataset)->where('id',$id)->update(['active'=>!$item->active,'updated_at'=>now()]);$this->audit($dataset,'status',$id,(array)$item,(array)DB::table($dataset)->find($id));return back()->with('success','Status data berhasil diperbarui.');}

    private function form(string $dataset,$item){$definition=$this->definition($dataset);return view('operations.master-form',['dataset'=>$dataset,'definition'=>$definition,'item'=>$item,'activityTypes'=>ActivityType::orderBy('name')->get(),'departments'=>Department::orderBy('name')->get()]);}
    private function definition(string $dataset): array {$definitions=$this->definitions();abort_unless(isset($definitions[$dataset]),404);return $definitions[$dataset];}
    private function validated(Request $request,string $dataset,array $definition,?int $id=null): array
    {
        $rules=[];
        foreach($definition['fields'] as $name=>$field){
            $rule=[!empty($field['required'])?'required':'nullable'];
            $rule=array_merge($rule,match($field['type']){
                'number'=>['numeric','min:0'],
                'boolean'=>['boolean'],
                'activity_type'=>['exists:activity_types,id'],
                'department'=>['exists:departments,id'],
                'select'=>[Rule::in(array_keys($field['options']))],
                'color'=>['string','regex:/^#[0-9a-fA-F]{6}$/'],
                'date'=>['date'],
                'file'=>['file','mimes:png,jpg,jpeg','max:2048'],
                default=>['string','max:1000'],
            });
            if(!empty($field['unique']))$rule[]=Rule::unique($dataset,$name)->ignore($id);
            $rules[$name]=$rule;
        }
        if($dataset==='application_follow_ups')$rules['code'][]='regex:/^[a-z][a-z0-9_]*$/';
        if($dataset==='signatories')$rules['valid_until'][]='after_or_equal:valid_from';
        if($dataset==='tariffs'){
            $rules['name']=['required','string','max:255'];
            $rules['participant_category']=['nullable','string','max:255'];
            $rules['amount'][]='max:9999999999999.99';
            $rules['min_participants']=['nullable','integer','min:1'];
            $rules['max_participants']=['nullable','integer','min:1'];
            if($request->filled('min_participants'))$rules['max_participants'][]='gte:min_participants';
            $rules['sort_order']=['nullable','integer','min:0','max:100000'];
        }
        $data=$request->validate($rules);foreach($definition['fields'] as $name=>$field)if($field['type']==='boolean')$data[$name]=$request->boolean($name);if($dataset==='tariffs')$data['sort_order']=$data['sort_order']??0;return $data;
    }
    private function storeFiles(array $data,string $dataset): array {foreach($data as $name=>$value)if($value instanceof \Illuminate\Http\UploadedFile)$data[$name]=$value->store('master/'.$dataset,'local');return $data;}
    private function audit(string $module,string $action,int $id,array $old,array $new): void {DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'ip_address'=>request()->ip(),'module'=>'master.'.$module,'action'=>$action,'auditable_type'=>$module,'auditable_id'=>$id,'old_values'=>$old?json_encode($old):null,'new_values'=>$new?json_encode($new):null,'created_at'=>now(),'updated_at'=>now()]);}
}
