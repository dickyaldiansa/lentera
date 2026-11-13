<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class NumberGenerator {
 public function next(string $type): string {
  return DB::transaction(function () use ($type) {
   $year=(int)date('Y');
   $formats=['diklat'=>'DKL/{Y}/{S6}','student'=>'KMD/{Y}/{S6}','ethics'=>'KEPK/{Y}/{S6}','ec'=>'EC/KEPK/RS/{Y}/{S5}','invoice'=>'INV/{Y}/{S6}','meeting'=>'SE/KEPK/{Y}/{S4}','certificate'=>'SERT/DKL/RS/{Y}/{S6}'];
   $row=DB::table('document_numbers')->where('type',$type)->lockForUpdate()->first();
   if(!$row){ DB::table('document_numbers')->insert(['type'=>$type,'format'=>$formats[$type]??strtoupper($type).'/{Y}/{S6}','last_sequence'=>0,'year'=>$year,'created_at'=>now(),'updated_at'=>now()]); $row=DB::table('document_numbers')->where('type',$type)->first(); }
   $seq=$row->year===$year ? $row->last_sequence+1 : 1;
   DB::table('document_numbers')->where('id',$row->id)->update(['last_sequence'=>$seq,'year'=>$year,'updated_at'=>now()]);
   return preg_replace_callback('/\{S(\d+)\}/',fn($m)=>str_pad((string)$seq,(int)$m[1],'0',STR_PAD_LEFT),str_replace('{Y}',(string)$year,$row->format));
  });
 }
}
