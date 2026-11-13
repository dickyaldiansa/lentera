@extends('layouts.app')
@section('title','Sertifikat Diklat')
@section('content')
<section class="panel">
    <h2>Siap Diterbitkan</h2>
    <p>Kegiatan berstatus selesai. Periksa peserta lalu terbitkan sertifikat.</p>
    <div class="table-wrap"><table><thead><tr><th>Pengajuan</th><th>Kegiatan</th><th>Peserta</th><th></th></tr></thead><tbody>
    @forelse($readyApplications as $application)
    <tr><td>{{ $application->number }}</td><td>{{ $application->title }}<small>{{ $application->activityType?->name }}</small></td>
        <td>{{ $application->participants->count() }} / {{ $application->participant_count }}</td>
        <td><a class="button secondary" href="{{ route('applications.show',$application) }}">Periksa Peserta & Terbitkan</a></td></tr>
    @empty<tr><td colspan="4" class="empty">Belum ada kegiatan yang menunggu penerbitan sertifikat.</td></tr>@endforelse
    </tbody></table></div>
    {{ $readyApplications->withQueryString()->links() }}
</section>
<div class="page-head"><div><span class="eyebrow blue-text">Diklat Eksternal</span><h1>Sertifikat</h1><p>Daftar sertifikat peserta kegiatan eksternal.</p></div></div><section class="panel"><div class="table-wrap"><table><thead><tr><th>Nomor</th><th>Peserta</th><th>Kegiatan</th><th>Tanggal Terbit</th><th></th></tr></thead><tbody>@forelse($certificates as $certificate)<tr><td>{{ $certificate->number }}</td><td>{{ $certificate->recipient_name }}</td><td>{{ $certificate->application->title }}</td><td>{{ $certificate->issued_at->translatedFormat('d M Y') }}</td><td><a class="mini-link" href="{{ route('certificates.show',$certificate) }}">Buka</a></td></tr>@empty<tr><td colspan="5" class="empty">Belum ada sertifikat diterbitkan.</td></tr>@endforelse</tbody></table></div>{{ $certificates->links() }}</section>
@endsection
