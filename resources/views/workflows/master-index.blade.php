@extends('layouts.app')
@section('title','Pengaturan Alur Layanan')
@section('content')
<div class="page-head"><div><span class="eyebrow">Workflow Persetujuan</span><h1>Pengaturan Alur Layanan</h1><p>Atur proses, petugas, persyaratan, dan hasil akhir per kategori. Simpan rancangan sebagai draf sebelum diterbitkan.</p></div><a class="button" href="{{ route('workflow-master.create') }}">Buat Alur</a></div>
<section class="panel"><h2>Kategori Layanan</h2>
<div class="table-wrap"><table><thead><tr><th>Kategori</th><th>Alur pengajuan baru</th><th>Draf terbaru</th><th></th></tr></thead><tbody>
@foreach($activityTypes as $type)
@php($configured=$templates->first(fn($t)=>$t->module==='diklat' && $t->activity_type_id===$type->id))
@php($published=$configured?->publishedVersion)
<tr><td><b>{{ $type->name }}</b></td>
<td>@if($configured?->active && $published)<span class="badge green">Aktif v{{ $published->version_number }}</span><small>{{ $published->steps->count() }} tahap</small>@else<span class="badge">Belum ada alur khusus aktif</span><small>Alur umum yang sudah diterbitkan tetap berlaku.</small>@endif</td>
<td>{{ $configured?->latestVersion?->status==='draft'?'Draf v'.$configured->latestVersion->version_number:'—' }}</td>
<td><a class="button secondary" href="{{ $configured?route('workflow-master.edit',$configured):route('workflow-master.create',['activity_type_id'=>$type->id]) }}">{{ $configured?'Atur Alur':'Susun Draf' }}</a></td></tr>
@endforeach
</tbody></table></div></section>
<section class="panel"><h2>Seluruh Workflow Persetujuan</h2>
<div class="table-wrap"><table><thead><tr><th>Alur</th><th>Kategori</th><th>Versi terbit</th><th>Versi terbaru</th><th></th></tr></thead><tbody>
@foreach($templates as $template)
<tr><td>{{ $template->name }}</td><td>{{ $template->activityType?->name??'Alur umum '.$template->module }}</td><td>{{ $template->publishedVersion?'v'.$template->publishedVersion->version_number:'Belum diterbitkan' }} {{ $template->active?'(aktif)':'(nonaktif)' }}</td><td>{{ $template->latestVersion?'v'.$template->latestVersion->version_number.' — '.$template->latestVersion->status:'—' }}</td><td><a class="mini-link" href="{{ route('workflow-master.edit',$template) }}">Edit / Pratinjau</a></td></tr>
@endforeach
</tbody></table></div></section>
<section class="panel"><b>Pengajuan berjalan memakai versi sebelumnya</b><p>Menyimpan draf tidak mengubah alur aktif. Menerbitkan alur baru hanya memengaruhi pengajuan yang belum memulai workflow.</p></section>
@endsection
