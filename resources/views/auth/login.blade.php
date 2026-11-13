<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke portal LENTERA RSUD Embung Fatimah.">
    <title>Masuk · LENTERA RSUD Embung Fatimah</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page login-page login-v2">
    <section class="auth-visual login-visual" aria-label="Tentang LENTERA">
        <a class="login-hospital-logo" href="{{ route('home') }}" aria-label="Kembali ke halaman utama">
            <img src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah Kota Batam">
        </a>
        <div class="login-visual-copy">
            <span class="auth-kicker"><i></i> Portal Layanan Terintegrasi</span>
            <h1>Melayani pendidikan dan penelitian dengan lebih baik.</h1>
            <p>Satu sistem untuk mengelola layanan Diklat, KAMKORDIK, dan Komite Etik secara aman dan transparan.</p>
            <div class="auth-feature-list">
                <span><i>✓</i> Proses digital terintegrasi</span><span><i>✓</i> Pemantauan status real-time</span><span><i>✓</i> Dokumen aman dan terpusat</span>
            </div>
        </div>
        <small class="login-visual-footer">RSUD Embung Fatimah · Kota Batam</small>
    </section>

    <section class="auth-form login-form-panel">
        <form method="post" action="{{ route('login') }}" id="login-form" class="login-card">
            @csrf
            <a class="mobile-login-brand" href="{{ route('home') }}">
                <span><img src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah Kota Batam"></span><b>LENTERA</b>
            </a>
            <div class="login-heading">
                <span class="login-mark">L</span>
                <div><span class="login-overline">Selamat datang di LENTERA</span><h2>Masuk ke akun Anda</h2><p>Gunakan email dan kata sandi yang telah terdaftar.</p></div>
            </div>
            @if($errors->any())
                <div class="alert error" role="alert">{{ $errors->first() }}</div>
            @endif
            <div class="login-fields">
                <label for="login-email">Alamat email
                    <input id="login-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">
                </label>
                <label for="login-password">Kata sandi
                    <input id="login-password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi">
                </label>
            </div>
            <div class="login-options"><label class="check"><input type="checkbox" name="remember"> <span>Ingat saya</span></label></div>
            <button class="button wide login-submit">Masuk ke Dashboard <span aria-hidden="true">→</span></button>
            <p class="center register-prompt">Belum memiliki akun? <a href="{{ route('register') }}">Daftar sebagai pemohon</a></p>
        </form>
    </section>
</body>
</html>
