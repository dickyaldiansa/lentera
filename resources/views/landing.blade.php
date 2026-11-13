<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="LENTERA EMBUNG FATIMAH — Layanan Terintegrasi Pendidikan dan Pelatihan RSUD Embung Fatimah.">
    <title>LENTERA EMBUNG FATIMAH</title>
    @vite(['resources/css/app.css'])
</head>
<body class="landing landing-v2">
    @include('public.navigation')

    <main>
        <section class="hero landing-hero">
            <div class="hero-copy">
                <span class="landing-kicker"><i></i> LENTERA EMBUNG FATIMAH</span>
                <h1>Satu pintu untuk pendidikan, pelatihan, dan penelitian.</h1>
                <p>LENTERA memudahkan seluruh proses pengajuan, verifikasi, pemantauan, hingga penerbitan izin secara digital, transparan, dan terintegrasi.</p>
                <div class="hero-actions">
                    <a class="button landing-primary" href="{{ route('register') }}">Ajukan Kegiatan <span aria-hidden="true">→</span></a>
                    <a class="button secondary landing-secondary" href="{{ route('login') }}">Lacak Pengajuan</a>
                </div>
                <div class="hero-assurance" aria-label="Keunggulan layanan"><span><i>✓</i> Pengajuan online</span><span><i>✓</i> Status real-time</span><span><i>✓</i> Dokumen terpusat</span></div>
            </div>
            <div class="process-showcase" aria-label="Alur layanan LENTERA">
                <div class="showcase-glow"></div>
                <div class="showcase-card">
                    <div class="showcase-head"><span>Alur pengajuan</span><b>Terintegrasi</b></div>
                    <ol class="process-list">
                        <li class="complete"><span>1</span><div><b>Lengkapi pengajuan</b><small>Isi data dan unggah dokumen</small></div><i>✓</i></li>
                        <li class="active"><span>2</span><div><b>Proses verifikasi</b><small>Pemeriksaan oleh unit terkait</small></div><i>•••</i></li>
                        <li><span>3</span><div><b>Persetujuan & izin</b><small>Dokumen terbit secara digital</small></div></li>
                    </ol>
                    <div class="showcase-status"><span><i></i> Sistem layanan aktif</span><small>Aman & terdokumentasi</small></div>
                </div>
                <div class="floating-badge"><strong>9</strong><span>Jenis layanan<br>tersedia</span></div>
            </div>
        </section>

        <section class="trust-strip" aria-label="Unit layanan"><span>Terintegrasi dengan</span><div><b>DIKLAT</b><b>KAMKORDIK</b><b>KOMITE ETIK</b></div></section>

        <section class="home-about-teaser about-width" aria-labelledby="mengenal-lentera-home">
            <div><h2 id="mengenal-lentera-home">Mengenal LENTERA Embung Fatimah</h2><p>Satu akses untuk informasi dan layanan pendidikan, pelatihan, penelitian, serta kemitraan RSUD Embung Fatimah Kota Batam.</p></div>
            <a class="button secondary" href="{{ route('about') }}">Selengkapnya →</a>
        </section>

        <section class="choices landing-services" id="layanan">
            <div class="section-heading">
                <span class="landing-kicker"><i></i> Layanan Kami</span>
                <h2>Semua kebutuhan dalam satu portal</h2>
                <p>Pilih layanan sesuai kebutuhan dan perhatikan penanda proses pada setiap layanan.</p>
            </div>
            <div class="choice-grid service-grid">
                @forelse($services as $service)
                    @php($color=$service->requires_kamkordik?'purple':($service->requires_ethics?'green':'blue'))
                    <a href="{{ route('register') }}" class="choice service-card {{ $color }}">
                        <div class="service-card-top"><span class="service-number">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><span class="service-tags">@if($service->requires_kamkordik)<em>KAMKORDIK</em>@endif @if($service->requires_ethics)<em>Telaah Etik</em>@endif <em>{{ $service->is_paid?'Berbayar':'Nonberbayar' }}</em></span></div>
                        <b>{{ $service->name }}</b><p>{{ $service->description?:'Layanan pengajuan kegiatan di RSUD Embung Fatimah.' }}</p><small>Mulai pengajuan <i aria-hidden="true">→</i></small>
                    </a>
                @empty<div class="empty">Belum ada layanan yang tersedia.</div>@endforelse
            </div>
            <p class="service-note">Biaya dan dokumen akhir mengikuti jenis, durasi, serta hasil verifikasi pengajuan.</p>
        </section>

        <section class="applicant-guide" id="panduan-pemohon">
            <div class="section-heading applicant-guide-heading">
                <span class="landing-kicker"><i></i> Panduan Pemohon</span>
                <h2>Ajukan kegiatan dalam enam langkah</h2>
                <p>Ikuti alur berikut agar pengajuan dapat diverifikasi dan diproses tanpa kendala.</p>
            </div>
            <ol class="public-flow" aria-label="Alur pengajuan pemohon">
                <li><span class="flow-number">01</span><div class="flow-copy"><small>LANGKAH PERTAMA</small><b>Buat akun pemohon</b><p>Daftar menggunakan identitas, email aktif, nomor HP, dan nama institusi Anda.</p></div></li>
                <li><span class="flow-number">02</span><div class="flow-copy"><small>PILIH LAYANAN</small><b>Buat pengajuan</b><p>Pilih jenis kegiatan lalu isi tujuan, unit, periode pelaksanaan, dan jumlah peserta.</p></div></li>
                <li><span class="flow-number">03</span><div class="flow-copy"><small>LENGKAPI BERKAS</small><b>Unggah dokumen</b><p>Lampirkan surat permohonan, proposal atau kerangka acuan, serta identitas peserta.</p></div></li>
                <li><span class="flow-number">04</span><div class="flow-copy"><small>PROSES RUMAH SAKIT</small><b>Verifikasi & persetujuan</b><p>Petugas memeriksa data. Perbaiki pengajuan melalui akun Anda jika ada permintaan revisi.</p><em class="flow-estimate">Estimasi awal 1–3 hari kerja*</em></div></li>
                <li><span class="flow-number">05</span><div class="flow-copy"><small>JIKA BERBAYAR</small><b>Selesaikan pembayaran</b><p>Lihat invoice dan unggah bukti pembayaran agar dapat diverifikasi oleh petugas.</p></div></li>
                <li><span class="flow-number">06</span><div class="flow-copy"><small>TAHAP AKHIR</small><b>Terima izin digital</b><p>Pantau status dan akses surat izin atau Ethical Clearance setelah pengajuan disetujui.</p></div></li>
            </ol>
            <p class="estimate-disclaimer">*Estimasi dihitung setelah dokumen dinyatakan lengkap dan dapat berubah sesuai jenis layanan, kebutuhan revisi, serta proses unit terkait.</p>

            <div class="applicant-guide-info">
                <article class="document-checklist"><div class="guide-info-icon">✓</div><div><span class="guide-info-label">Siapkan sebelum mengajukan</span><h3>Dokumen umum pemohon</h3><ul><li>Surat permohonan dari institusi</li><li>Proposal atau kerangka acuan kegiatan</li><li>Identitas atau daftar peserta</li><li>Dokumen tambahan sesuai jenis layanan</li></ul></div></article>
                <article class="status-explainer"><div><span class="guide-info-label">Pantau dari dashboard</span><h3>Kenali status pengajuan</h3></div><div class="public-status-list"><span><i class="status-blue"></i><b>Draf</b><small>Belum dikirim</small></span><span><i class="status-amber"></i><b>Diproses</b><small>Sedang diverifikasi</small></span><span><i class="status-purple"></i><b>Revisi</b><small>Perlu diperbaiki</small></span><span><i class="status-green"></i><b>Disetujui</b><small>Izin dapat diterbitkan</small></span></div></article>
            </div>
            <div class="applicant-guide-cta"><div><b>Sudah siap memulai?</b><p>Buat akun gratis dan pantau seluruh proses melalui satu dashboard.</p></div><div><a class="button landing-primary" href="{{ route('register') }}">Buat Akun Pemohon <span aria-hidden="true">→</span></a><a class="guide-login-link" href="{{ route('login') }}">Sudah punya akun? Masuk</a></div></div>
        </section>
    </main>

    @include('public.footer')
</body>
</html>
