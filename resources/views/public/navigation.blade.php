<nav class="public-nav landing-nav public-site-nav" aria-label="Navigasi utama">
    <a class="landing-brand" href="{{ route('home') }}" aria-label="LENTERA EMBUNG FATIMAH — Beranda">
        <span class="landing-logo-frame"><img src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah Kota Batam"></span>
        <span class="landing-wordmark"><strong>LENTERA EMBUNG FATIMAH</strong><small>Layanan Terintegrasi Pendidikan dan Pelatihan<br>RSUD Embung Fatimah</small></span>
    </a>
    <div class="landing-nav-actions">
        <a href="{{ route('home') }}#layanan" class="nav-service-link">Layanan</a>
        <a href="{{ route('home') }}#panduan-pemohon" class="nav-guide-link">Panduan</a>
        <a href="{{ route('about') }}" class="nav-about-link" @if(request()->routeIs('about'))aria-current="page"@endif>Apa Itu LENTERA</a>
        <a href="{{ route('login') }}" class="link">Masuk</a>
        <a href="{{ route('register') }}" class="button nav-cta">Buat Akun <span aria-hidden="true">→</span></a>
    </div>
</nav>
