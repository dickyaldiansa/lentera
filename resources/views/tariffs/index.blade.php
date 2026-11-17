@extends('layouts.app')
@section('title','Daftar Tarif')
@section('content')
<div class="page-head"><div><span class="eyebrow">Layanan Pemohon</span><h1>Daftar Tarif</h1><p>Tarif layanan pendidikan dan pelatihan RSUD Embung Fatimah Kota Batam.</p></div>@if(auth()->user()->hasRole('applicant'))<a class="button" href="{{ route('applications.create') }}">Buat Permohonan</a>@endif</div>
<section class="panel"><p>Perhatikan satuan tarif dan kelompok peserta. Tagihan ditetapkan Diklat sesuai layanan dan ketentuan yang berlaku.</p><div class="head-actions" style="flex-wrap:wrap">@foreach(\App\Models\Tariff::CATEGORIES as $key=>$label)@if($tariffs->has($key))<a class="button secondary" href="#tariff-{{ $key }}">{{ $label }}</a>@endif @endforeach</div></section>
@include('tariffs.regulations',['manage'=>false])
@foreach(\App\Models\Tariff::CATEGORIES as $key=>$label)
@if($tariffs->has($key))<section class="panel" id="tariff-{{ $key }}"><h2>{{ $label }}</h2>@include('tariffs.table',['items'=>$tariffs[$key]])</section>@endif
@endforeach
@if($tariffs->isEmpty())<section class="panel empty">Daftar tarif belum tersedia. Silakan hubungi Diklat.</section>@endif
@endsection
