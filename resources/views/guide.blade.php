@extends('layouts.app')
@section('title','Panduan & Alur Layanan')
@section('content')
<div class="page-head guide-head">
    <div><span class="eyebrow blue-text">Pusat Bantuan LENTERA</span><h1>Panduan & Alur Layanan</h1><p>Panduan ini otomatis disesuaikan dengan hak akses akun {{ auth()->user()->roles->pluck('name')->join(', ') }}.</p></div>
    <span class="access-badge"><i>✓</i> Akses terverifikasi</span>
</div>

<nav class="guide-jump" aria-label="Daftar panduan">
    @if(in_array('applicant',$sections))<a href="#pemohon"><span>01</span>Pemohon</a>@endif
    @if(in_array('diklat',$sections))<a href="#diklat"><span>02</span>Diklat</a>@endif
    @if(in_array('kamkordik',$sections))<a href="#kamkordik"><span>03</span>KAMKORDIK</a>@endif
    @if(in_array('ethics',$sections))<a href="#etik"><span>04</span>Komite Etik</a>@endif
</nav>

@if(in_array('applicant',$sections))
<section class="guide-module blue-guide" id="pemohon">
    <div class="guide-module-head"><span class="guide-icon">P</span><div><small>HAK AKSES PEMOHON</small><h2>Mengajukan dan memantau layanan</h2><p>Pemohon hanya dapat melihat serta mengubah pengajuan miliknya sendiri.</p></div></div>
    <div class="permission-summary"><b>Yang dapat dilakukan</b><span>✓ Buat pengajuan Diklat</span><span>✓ Unggah dokumen</span><span>✓ Kirim bukti pembayaran</span><span>✓ Ajukan penelitian etik</span><span>✓ Pantau status & dokumen</span><em>Tidak dapat memverifikasi, menyetujui, atau melihat data pemohon lain.</em></div>
    <div class="flow-block"><h3>Alur pengajuan kegiatan</h3><ol class="workflow-steps">
        <li><span>1</span><div><b>Buat draf</b><p>Pilih jenis kegiatan, institusi, unit tujuan, periode, dan jumlah peserta.</p></div></li>
        <li><span>2</span><div><b>Lengkapi dokumen</b><p>Unggah surat permohonan, proposal, dan identitas peserta sesuai persyaratan.</p></div></li>
        <li><span>3</span><div><b>Kirim pengajuan</b><p>Pastikan data benar, kemudian kirim untuk verifikasi administratif Diklat.</p></div></li>
        <li><span>4</span><div><b>Revisi atau pembayaran</b><p>Perbaiki data bila diminta. Jika ada biaya, kirim bukti pembayaran melalui invoice.</p></div></li>
        <li><span>5</span><div><b>Terima izin</b><p>Unduh dan verifikasi surat digital setelah seluruh proses disetujui.</p></div></li>
    </ol></div>
    <div class="guide-actions">@if(auth()->user()->hasRole('applicant'))<a class="button" href="{{ route('applications.create') }}">Buat Permohonan</a>@endif<a class="button secondary" href="{{ auth()->user()->hasRole('applicant') ? route('my.applications') : route('applications.index') }}">Lihat Daftar Pengajuan</a></div>
</section>
@endif

@if(in_array('diklat',$sections))
<section class="guide-module blue-guide" id="diklat">
    <div class="guide-module-head"><span class="guide-icon">D</span><div><small>HAK AKSES DIKLAT</small><h2>Verifikasi dan pengelolaan kegiatan eksternal</h2><p>Akses operasional ditentukan oleh peran Admin Diklat atau Kepala Diklat.</p></div></div>
    <div class="role-matrix"><div><b>Admin Diklat</b><p>Melihat pengajuan, memeriksa dokumen, meminta revisi, menerbitkan invoice, dan memverifikasi pembayaran.</p></div><div><b>Kepala Diklat</b><p>Melakukan persetujuan akhir, penolakan, penerbitan surat, dan melihat laporan layanan.</p></div></div>
    <div class="flow-block"><h3>Alur kerja Diklat</h3><ol class="workflow-steps compact-flow">
        <li><span>1</span><div><b>Pengajuan masuk</b><p>Status submitted diterima dari pemohon.</p></div></li><li><span>2</span><div><b>Verifikasi administratif</b><p>Periksa data dan kelengkapan dokumen.</p></div></li><li><span>3</span><div><b>Persetujuan</b><p>Kepala Diklat menyetujui atau menolak.</p></div></li><li><span>4</span><div><b>Pembayaran</b><p>Terbitkan invoice dan verifikasi bukti bayar jika layanan berbayar.</p></div></li><li><span>5</span><div><b>Izin & pelaksanaan</b><p>Terbitkan surat digital, pantau kegiatan, lalu tutup proses.</p></div></li>
    </ol></div>
    <div class="guide-actions"><a class="button" href="{{ route('applications.index') }}">Buka Pengajuan Diklat</a></div>
</section>
@endif

@if(in_array('kamkordik',$sections))
<section class="guide-module purple-guide" id="kamkordik">
    <div class="guide-module-head"><span class="guide-icon">K</span><div><small>HAK AKSES KAMKORDIK</small><h2>Pengelolaan pendidikan klinis</h2><p>Data peserta dibatasi berdasarkan peran dan penugasan klinis.</p></div></div>
    <div class="role-matrix three"><div><b>Admin / Ketua KAMKORDIK</b><p>Mengelola peserta, rotasi, penempatan, pembimbing, penilaian, dan laporan.</p></div><div><b>Pembimbing Klinis</b><p>Hanya melihat peserta yang ditugaskan, memberi penilaian, serta memverifikasi logbook.</p></div><div><b>Peserta Didik</b><p>Hanya melihat profil, rotasi, nilai, dan mengisi logbook miliknya sendiri.</p></div></div>
    <div class="flow-block"><h3>Alur pendidikan klinis</h3><ol class="workflow-steps compact-flow">
        <li><span>1</span><div><b>Registrasi peserta</b><p>Admin mencatat institusi, program, dan periode pendidikan.</p></div></li><li><span>2</span><div><b>Penempatan rotasi</b><p>Tentukan stase, unit, jadwal, dan pembimbing klinis.</p></div></li><li><span>3</span><div><b>Pelaksanaan</b><p>Peserta mengisi aktivitas pada logbook digital.</p></div></li><li><span>4</span><div><b>Validasi</b><p>Pembimbing memeriksa logbook peserta yang menjadi tanggung jawabnya.</p></div></li><li><span>5</span><div><b>Penilaian</b><p>Nilai kompetensi direkap sebagai hasil pendidikan klinis.</p></div></li>
    </ol></div>
    <div class="guide-actions"><a class="button purple-bg" href="{{ route('kamkordik.index') }}">Buka KAMKORDIK</a><a class="button secondary" href="{{ route('logbooks.index') }}">Buka Logbook</a></div>
</section>
@endif

@if(in_array('ethics',$sections))
<section class="guide-module green-guide" id="etik">
    <div class="guide-module-head"><span class="guide-icon">E</span><div><small>HAK AKSES KOMITE ETIK</small><h2>Telaah dan keputusan etik penelitian</h2><p>Setiap proposal hanya dapat diproses oleh petugas serta reviewer yang berwenang.</p></div></div>
    <div class="role-matrix three"><div><b>Admin Komite Etik</b><p>Memverifikasi administrasi, menugaskan reviewer, dan menjadwalkan sidang.</p></div><div><b>Reviewer Etik</b><p>Hanya melihat proposal yang ditugaskan dan mengirim hasil penilaian etik.</p></div><div><b>Ketua Komite Etik</b><p>Menetapkan keputusan, menerbitkan Ethical Clearance, dan mengawasi monitoring.</p></div></div>
    <div class="flow-block"><h3>Alur telaah etik</h3><ol class="workflow-steps compact-flow">
        <li><span>1</span><div><b>Verifikasi administrasi</b><p>Admin memeriksa kelengkapan protokol yang masuk.</p></div></li><li><span>2</span><div><b>Penugasan reviewer</b><p>Proposal diberikan hanya kepada reviewer terkait.</p></div></li><li><span>3</span><div><b>Review atau revisi</b><p>Reviewer menilai aspek ilmiah, manfaat-risiko, privasi, dan informed consent.</p></div></li><li><span>4</span><div><b>Keputusan</b><p>Komite menetapkan kelayakan berdasarkan hasil review atau sidang.</p></div></li><li><span>5</span><div><b>EC & monitoring</b><p>Ketua menerbitkan Ethical Clearance dan penelitian dipantau sampai selesai.</p></div></li>
    </ol></div>
    <div class="guide-actions"><a class="button green-bg" href="{{ route('ethics.index') }}">Buka Komite Etik</a></div>
</section>
@endif

<section class="guide-note"><span>i</span><div><b>Butuh akses tambahan?</b><p>Hak akses tidak dapat diubah sendiri. Hubungi Administrator LENTERA jika tugas atau penempatan Anda berubah.</p></div></section>
@endsection
