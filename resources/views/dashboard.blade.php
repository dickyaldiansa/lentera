@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<x-diklat-contact/>
<div class="page-head">
    <div>
        <span class="eyebrow">{{ now()->translatedFormat('l, d F Y') }}</span>
        <h1>Selamat datang, {{ str(auth()->user()->name)->before(' ') }}.</h1>
        <p>Ringkasan layanan yang sesuai dengan hak akses akun Anda.</p>
    </div>
    <div class="head-actions">
        <a class="button secondary" href="{{ route('guide') }}">Buka Panduan</a>
        @if(auth()->user()->canDo('master.manage'))<a class="button secondary" href="{{ route('workflow-master.index') }}">Pengaturan Alur Layanan</a>@endif
        @if(auth()->user()->hasRole('applicant'))<a class="button" href="{{ route('applications.create') }}">＋ Buat Permohonan</a>@endif
    </div>
</div>

<div class="stat-grid role-stat-grid">
    @if($access['diklat'])<div class="stat blue"><span>▤</span><small>{{ $access['applicant']?'Pengajuan Saya':'Pengajuan Diklat' }}</small><b>{{ $stats['applications'] }}</b><em>{{ $stats['waiting'] }} sedang diproses</em></div>@endif
    @if($access['kamkordik'])<div class="stat purple"><span>♙</span><small>{{ auth()->user()->hasRole('student')?'Profil Peserta':'Peserta Didik Aktif' }}</small><b>{{ $stats['students'] }}</b><em>Dalam akses pendidikan klinis</em></div>@endif
    @if($access['ethics'])<div class="stat green"><span>⚕</span><small>{{ $access['applicant']?'Pengajuan Etik Saya':'Pengajuan Etik' }}</small><b>{{ $stats['ethics'] }}</b><em>{{ $stats['clearances'] }} ethical clearance aktif</em></div>@endif
    @if($access['diklat'])<div class="stat amber"><span>▷</span><small>Kegiatan Berjalan</small><b>{{ $stats['activities'] }}</b><em>Terpantau oleh sistem</em></div>@endif
    @if($access['diklat'])<div class="stat green"><span>Rp</span><small>{{ $access['applicant']?'Total Pembayaran Saya':'Total Pembayaran Pemohon' }}</small><b class="currency">Rp{{ number_format($stats['payments_total'],0,',','.') }}</b><em>Hanya pembayaran terverifikasi</em></div>@endif
</div>

<div class="dashboard-grid single-dashboard">
    @if($access['diklat'])
        <section class="panel span2">
            <div class="panel-head"><div><h2>Tren Pengajuan</h2><p>{{ $access['applicant']?'Riwayat pengajuan Anda':'Pengajuan masuk per bulan' }}</p></div><span class="pill">8 bulan terakhir</span></div>
            <div class="chart">@php $max=max(1,$monthly->max('total')??1) @endphp @forelse($monthly as $month)<div class="bar-wrap"><b style="height:{{ max(12,$month->total/$max*150) }}px"><i>{{ $month->total }}</i></b><small>{{ substr($month->month,5) }}</small></div>@empty<div class="empty">Belum ada data tren.</div>@endforelse</div>
        </section>
    @endif
</div>

@if($access['diklat'])
<section class="panel">
    <div class="panel-head"><div><h2>{{ $access['applicant']?'Riwayat Pembayaran Saya':'Pembayaran Pemohon Terbaru' }}</h2><p>Daftar pembayaran yang dikirim melalui invoice Diklat</p></div>@if(!$access['applicant'] && auth()->user()->hasRole('super-admin','diklat-admin','diklat-head'))<a href="{{ route('diklat.payments') }}">Lihat semua →</a>@endif</div>
    <div class="table-wrap"><table><thead><tr><th>Invoice</th><th>Pengajuan / Pemohon</th><th>Jumlah</th><th>Metode</th><th>Tanggal Bayar</th><th>Status</th></tr></thead><tbody>
        @forelse($recentPayments as $payment)
            <tr>
                <td><a href="{{ route('applications.show',$payment->invoice->application) }}"><b>{{ $payment->invoice->number }}</b></a><small>{{ $payment->reference ?: 'Tanpa referensi' }}</small></td>
                <td><b>{{ $payment->invoice->application->title }}</b><small>{{ $payment->invoice->application->applicant->name }}</small></td>
                <td><b>Rp{{ number_format($payment->amount,0,',','.') }}</b></td>
                <td>{{ $payment->method ?: '-' }}</td>
                <td>{{ $payment->paid_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                <td><x-status :status="$payment->status" :label="$payment->status === 'verified' ? 'Sudah Bayar' : null"/></td>
            </tr>
        @empty<tr><td colspan="6" class="empty">Belum ada pembayaran.</td></tr>@endforelse
    </tbody></table></div>
</section>

<section class="panel">
    <div class="panel-head"><div><h2>{{ $access['applicant']?'Permohonan Saya':'Pengajuan Terbaru' }}</h2><p>Pergerakan terakhir layanan Diklat</p></div><a href="{{ $access['applicant'] ? route('my.applications') : route('applications.index') }}">Lihat semua →</a></div>
    <div class="table-wrap"><table><thead><tr><th>Nomor</th><th>Judul / Pemohon</th><th>Jenis</th><th>Periode</th><th>Status</th></tr></thead><tbody>
        @forelse($recent as $application)<tr><td><a href="{{ route('applications.show',$application) }}"><b>{{ $application->number }}</b></a></td><td><b>{{ $application->title }}</b><small>{{ $application->applicant->name }}</small></td><td>{{ $application->activityType->name }}</td><td>{{ $application->start_date->format('d M') }}–{{ $application->end_date->format('d M Y') }}</td><td><x-status :status="$application->status"/></td></tr>@empty<tr><td colspan="5" class="empty">Belum ada pengajuan.</td></tr>@endforelse
    </tbody></table></div>
</section>
@else
<section class="panel access-summary">
    <div class="panel-head"><div><h2>Akses Anda</h2><p>Gunakan modul yang tersedia pada menu di samping.</p></div><a href="{{ route('guide') }}">Pelajari alur →</a></div>
    <div class="access-chips">@if($access['kamkordik'])<span class="purple">KAMKORDIK</span>@endif @if($access['ethics'])<span class="green">KOMITE ETIK</span>@endif</div>
</section>
@endif
@endsection
