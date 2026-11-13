@extends('layouts.app')
@section('title','Master Data')
@section('content')
<div class="page-head"><div><span class="eyebrow">Administrasi Sistem</span><h1>Master Data</h1><p>Konfigurasi referensi utama yang digunakan lintas modul.</p></div></div>
<div class="master-grid">@foreach($sets as [$label,$table,$icon,$count])<a class="master-card" href="{{ $table==='institutions'?route('institutions'):($table==='workflow_templates'?route('workflow-master.index'):route('master.dataset',$table)) }}"><x-nav-icon :name="$icon"/><div><b>{{ $label }}</b><small>{{ $count }} data tersimpan</small></div><span>→</span></a>@endforeach</div>
<section class="panel"><div class="panel-head"><div><h2>Konfigurasi Dinamis</h2><p>Persyaratan, tarif, jenis kegiatan, serta workflow persetujuan dapat dikelola tanpa mengubah source code.</p></div></div><p class="muted-copy">Workflow menggunakan versioning agar pengajuan yang sedang berjalan tetap konsisten ketika kebijakan baru diterbitkan.</p></section>
@endsection
