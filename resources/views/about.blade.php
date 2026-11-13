<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Mengenal LENTERA, layanan terintegrasi pendidikan, pelatihan, penelitian dan kemitraan RSUD Embung Fatimah Kota Batam, serta sambutan Direktur.">
    <title>Apa Itu LENTERA — RSUD Embung Fatimah Kota Batam</title>
    @vite(['resources/css/app.css'])
</head>
<body class="landing landing-v2 about-page">
    @include('public.navigation')
    <main id="konten-utama">
        <section class="about-intro">
            <div class="about-width">
                <a class="about-back" href="{{ route('home') }}">← Kembali ke Beranda</a>
                <span class="landing-kicker"><i></i> Apa Itu LENTERA</span>
                <h1>Mengenal LENTERA<br><span>Embung Fatimah</span></h1>
                <p class="about-tagline">Satu Akses untuk Layanan Terintegrasi</p>
                <p class="about-lead">LENTERA adalah <strong>Layanan Terintegrasi Pendidikan, Pelatihan, Penelitian dan Kemitraan RSUD Embung Fatimah Kota Batam</strong>—sebuah inovasi layanan digital untuk mempermudah akses informasi dan proses administrasi dalam satu platform.</p>
                <a class="about-jump" href="#sambutan-direktur">Baca sambutan Direktur <span aria-hidden="true">↓</span></a>
            </div>
        </section>
        <section class="about-services about-width" aria-labelledby="manfaat-lentera">
            <div class="about-section-heading"><span class="landing-kicker"><i></i> Layanan yang Lebih Terarah</span><h2 id="manfaat-lentera">Satu platform, proses yang mudah ditelusuri.</h2><p>Melalui LENTERA, calon peserta didik, institusi pendidikan, peneliti, dan mitra dapat memperoleh informasi layanan serta mengakses proses pengajuan secara lebih terarah.</p></div>
            <div class="about-benefits">
                <article><span aria-hidden="true">01</span><h3>Informasi layanan</h3><p>Memperoleh informasi mengenai layanan dan persyaratan sesuai kebutuhan kegiatan.</p></article>
                <article><span aria-hidden="true">02</span><h3>Pengajuan permohonan</h3><p>Mengajukan permohonan pendidikan, pelatihan, penelitian, dan kemitraan sesuai jenis layanan yang tersedia.</p></article>
                <article><span aria-hidden="true">03</span><h3>Verifikasi & persetujuan</h3><p>Mengikuti tahapan pemeriksaan dan persetujuan melalui proses yang terstruktur dan terdokumentasi.</p></article>
                <article><span aria-hidden="true">04</span><h3>Pemantauan pengajuan</h3><p>Memantau status serta tindak lanjut permohonan sesuai fitur yang tersedia.</p></article>
            </div>
        </section>
        <section class="director-section" id="sambutan-direktur" aria-labelledby="judul-sambutan">
            <div class="director-layout about-width">
                <figure class="director-portrait">
                    <div class="director-image-frame"><img src="{{ asset('images/branding/direktur-sri-widjayanti-suryandari.png') }}" alt="drg. R.R. Sri Widjayanti Suryandari, Direktur RSUD Embung Fatimah Kota Batam" width="1382" height="1728" loading="lazy"></div>
                    <figcaption><strong>drg. R.R. Sri Widjayanti Suryandari</strong><span>Direktur RSUD Embung Fatimah Kota Batam</span></figcaption>
                </figure>
                <div class="director-message">
                    <span class="landing-kicker"><i></i> Sambutan Direktur</span>
                    <h2 id="judul-sambutan">Sambutan Direktur RSUD Embung Fatimah Kota Batam</h2>
                    <div class="director-message-body">
                        <p>Pengembangan LENTERA merupakan bagian dari komitmen RSUD Embung Fatimah Kota Batam untuk terus melakukan pembenahan dan inovasi dalam meningkatkan kualitas tata kelola pelayanan, khususnya pada bidang pendidikan, pelatihan, penelitian, dan kemitraan.</p>
                        <p>Kami menyambut baik hadirnya LENTERA sebagai sarana yang dapat menyederhanakan proses administrasi, memperjelas tahapan pelayanan, serta memberikan kemudahan dalam memperoleh informasi. Melalui sistem yang lebih terstruktur dan mudah ditelusuri, setiap proses diharapkan dapat berjalan lebih tertib, transparan, dan akuntabel.</p>
                        <p>Ke depan, LENTERA diharapkan terus dikembangkan sebagai bagian dari upaya RSUD Embung Fatimah dalam memperkuat perannya sebagai rumah sakit pendidikan. Kami percaya bahwa inovasi dan kolaborasi merupakan fondasi penting untuk menghadirkan pelayanan yang semakin baik dan memberikan manfaat bagi masyarakat.</p>
                    </div>
                    <div class="director-signoff"><strong>drg. R.R. Sri Widjayanti Suryandari</strong><span>Direktur RSUD Embung Fatimah Kota Batam</span></div>
                </div>
            </div>
        </section>
        <section class="about-commitment about-width" aria-labelledby="komitmen-lentera">
            <span class="landing-kicker"><i></i> Komitmen Kami</span>
            <h2 id="komitmen-lentera">Layanan yang tertib, transparan, dan akuntabel.</h2>
            <p>Dengan proses yang terarah dan terdokumentasi, LENTERA diharapkan dapat menghadirkan layanan yang lebih mudah, tertib, transparan, dan akuntabel. Pengembangannya menjadi bagian dari upaya RSUD Embung Fatimah Kota Batam untuk memperkuat tata kelola layanan serta mendukung pengembangan rumah sakit sebagai rumah sakit pendidikan yang terpercaya dan menjadi pilihan masyarakat.</p>
            <div class="about-actions"><a class="button landing-primary" href="{{ route('register') }}">Daftar Akun <span aria-hidden="true">→</span></a><a class="button secondary" href="{{ route('login') }}">Masuk</a></div>
        </section>
    </main>
    @include('public.footer')
</body>
</html>
