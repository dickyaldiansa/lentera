<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $guarded = [];
    protected $casts = ['amount'=>'decimal:2','active'=>'boolean','published'=>'boolean','min_participants'=>'integer','max_participants'=>'integer'];

    public const CATEGORIES = [
        'practice'=>'Praktik / Orientasi', 'internship'=>'Magang', 'pre_research'=>'Pra Penelitian',
        'research'=>'Penelitian', 'enrichment'=>'Pengayaan Mahasiswa', 'comparative_study'=>'Studi Banding', 'other'=>'Lainnya',
    ];
    public const UNITS = [
        'per_person_day'=>'Per orang / hari', 'per_person_week'=>'Per orang / minggu',
        'per_person_month'=>'Per orang / bulan', 'per_person'=>'Per orang',
        'per_activity'=>'Per kegiatan', 'per_sample'=>'Per sampel', 'per_group'=>'Per kelompok',
        'unspecified'=>'Satuan perlu konfirmasi Diklat',
        'flat'=>'Tarif tetap', 'per_participant'=>'Per peserta', 'per_day'=>'Per hari',
    ];

    public function activityType(){return $this->belongsTo(ActivityType::class);}
    public function scopeVisible($query){return $query->where('active',true)->where('published',true);}
    public function getCategoryLabelAttribute():string{return self::CATEGORIES[$this->category]??$this->category;}
    public function getUnitLabelAttribute():string{return self::UNITS[$this->calculation]??$this->calculation;}
    public function scopeForActivity($query,ActivityType $type){
        $category=self::categoryForActivity($type);
        return $query->where(fn($q)=>$q->where('activity_type_id',$type->id)->orWhere(fn($q)=>$q->whereNull('activity_type_id')->where('category',$category)));
    }
    public static function categoryForActivity(ActivityType $type):?string{
        $name=mb_strtolower($type->name);
        return match(true){
            str_contains($name,'pra penelitian'),str_contains($name,'studi pendahuluan'),str_contains($name,'pengambilan data')=>'pre_research',
            str_contains($name,'penelitian')=>'research',
            str_contains($name,'magang')=>'internship',
            str_contains($name,'praktik'),str_contains($name,'orientasi'),str_contains($name,'pendidikan klinik')=>'practice',
            str_contains($name,'studi banding'),str_contains($name,'kunjungan')=>'comparative_study',
            str_contains($name,'pengayaan')=>'enrichment',
            default=>null,
        };
    }
}
