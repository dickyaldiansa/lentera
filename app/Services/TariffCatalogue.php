<?php

namespace App\Services;

use App\Models\Tariff;

class TariffCatalogue
{
    public function import():void
    {
        foreach($this->rows() as $index=>$row){
            // Stable keys prevent repeated imports from overwriting an administrator's edits.
            Tariff::firstOrCreate(['source_key'=>$row['source_key']],$row+['active'=>true,'published'=>true,'sort_order'=>$index+1]);
        }
    }

    public function rows():array
    {
        $rows=[];
        $add=function(string $key,string $category,string $name,string $audience,string $unit,int $amount,?int $min=null,?int $max=null,?string $description=null)use(&$rows){
            $rows[]=['source_key'=>$key,'category'=>$category,'name'=>$name,'participant_category'=>$audience,'calculation'=>$unit,'amount'=>$amount,
                'min_participants'=>$min,'max_participants'=>$max,'description'=>$description];
        };
        foreach([
            ['sma','SMA / Sederajat',25000,125000], ['d1-d3','D1–D3 / Sederajat',50000,150000],
            ['s1-profesi','S1, D4, Co-Ass, Apt, Ners',75000,200000], ['s2-ppds','S2, PPDS',85000,250000],
        ] as [$key,$audience,$day,$week]){
            $add('practice-'.$key.'-day','practice','Praktikum',$audience,'per_person_day',$day);
            $add('practice-'.$key.'-week','practice','Praktikum',$audience,'per_person_week',$week);
        }
        foreach([
            ['sma','SMA / Sederajat',200000,675000], ['d1-d3','D1–D3 / Sederajat',275000,700000],
            ['s1-profesi','S1, D4, Co-Ass, Apt, Ners',350000,850000],
            ['profesional','Dokter umum, Apoteker, Ners, Psikologi',400000,850000],
            ['karyawan','Karyawan',500000,850000],
        ] as [$key,$audience,$week,$month]){
            $add('internship-'.$key.'-week','internship','Magang',$audience,'per_person_week',$week);
            $add('internship-'.$key.'-month','internship','Magang',$audience,'per_person_month',$month);
        }
        foreach([
            ['sma','SMA / Sederajat',75000,150000], ['d1-d3','D1–D3 / Sederajat',100000,200000],
            ['d4','D4 / Sederajat',125000,225000], ['s1','S1 / Sederajat',150000,250000],
            ['s2','S2 / Sederajat',175000,275000],
        ] as [$key,$audience,$pre,$research]){
            $add('pre-research-'.$key,'pre_research','Pra penelitian',$audience,'per_activity',$pre);
            $add('research-'.$key,'research','Penelitian',$audience,'per_activity',$research);
        }
        $add('research-special-sample','research','Penelitian khusus','Penelitian khusus','per_sample',150000);
        foreach([['sma','SMA / Sederajat',150000],['d1-d3','DI–DIII / Sederajat',200000],['d4-s1','DIV, S1',250000]] as [$key,$audience,$price]){
            $add('enrichment-'.$key,'enrichment','Pengayaan mahasiswa',$audience,'per_person',$price);
        }
        foreach([
            ['eselon-2','Eselon II',1500000],['eselon-3a','Eselon III a',1250000],['eselon-3b','Eselon III b',1000000],
            ['eselon-4','Eselon IV',750000],['fungsional','Pejabat Fungsional',750000],['staf','Staf Pelaksana',500000],
        ] as [$key,$audience,$price]){
            $add('comparative-'.$key,'comparative_study','Studi banding / benchmarking / kaji banding',$audience,'per_person',$price);
        }
        foreach([[1,15,3500000],[16,20,7500000],[21,30,10000000],[31,50,15000000]] as [$min,$max,$price]){
            $add('comparative-group-'.$min.'-'.$max,'comparative_study','Studi banding berkelompok',($min===1?'Kelompok s/d 15 orang':"Kelompok $min s/d $max orang"),'per_group',$price,$min,$max,'Termasuk 1 snack + 1 makan + ruangan.');
        }
        foreach([['siswa','Siswa',50000],['mahasiswa','Mahasiswa',65000],['umum','Karyawan / Umum',300000]] as [$key,$audience,$price]){
            $add('comparative-'.$key,'comparative_study','Studi banding '.$audience,$audience,'unspecified',$price,null,null,'Satuan tidak dicantumkan pada cuplikan daftar tarif. Konfirmasi kepada Diklat.');
        }
        return $rows;
    }
}
