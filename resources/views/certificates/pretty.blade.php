@extends('layouts.app')
@section('title','Sertifikat '.$certificate->number)
@section('content')
<article class="certificate-document">
    <header class="certificate-header">
        <img class="certificate-logo" src="{{ asset('images/branding/pemko-batam.png') }}" alt="Pemerintah Kota Batam">
        <div><p>PEMERINTAH KOTA BATAM</p><h1>RSUD EMBUNG FATIMAH</h1><small>Jalan Letjen R. Suprapto No. 9, Batu Aji, Kota Batam</small></div>
        <img class="certificate-logo" src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah">
    </header>
    <div class="certificate-rule"></div>
    <div class="certificate-body">
        <span class="certificate-kicker">SERTIFIKAT KEIKUTSERTAAN</span>
        <h2>SERTIFIKAT</h2>
        <p class="certificate-number">Nomor: {{ $certificate->number }}</p>
        <p class="certificate-presented">Diberikan kepada</p>
        <h3>{{ $certificate->recipient_name }}</h3>
        <p class="certificate-text">Atas partisipasi dan kontribusinya dalam kegiatan<br><strong>{{ $certificate->application->title }}</strong><br>({{ $certificate->application->activityType->name }}) yang diselenggarakan pada<br>{{ $certificate->application->start_date->translatedFormat('d F Y') }} – {{ $certificate->application->end_date->translatedFormat('d F Y') }}.</p>
        <div class="certificate-footer"><div class="certificate-verify"><small>VERIFIKASI SERTIFIKAT</small><b>{{ $certificate->number }}</b><span>Scan atau buka: {{ route('certificates.verify',$certificate->verification_token) }}</span></div><div class="certificate-signature"><p>Batam, {{ $certificate->issued_at->translatedFormat('d F Y') }}</p><small>Direktur RSUD Embung Fatimah</small><div class="signature-space"></div><b>{{ $certificate->issuer?->name??'RSUD Embung Fatimah' }}</b></div></div>
    </div>
</article>
<div class="certificate-actions"><a class="button secondary" href="{{ route('applications.show',$certificate->application) }}">Kembali</a><button class="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
@endsection
