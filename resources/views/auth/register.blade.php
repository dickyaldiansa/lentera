<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Daftar akun pemohon LENTERA RSUD Embung Fatimah.">
    <title>Daftar · LENTERA RSUD Embung Fatimah</title>
    @vite(['resources/css/app.css'])
</head>
<body class="auth-page register-page register-v2">
    <section class="auth-visual register-visual" aria-label="Tentang pendaftaran LENTERA">
        <a class="login-hospital-logo" href="{{ route('home') }}" aria-label="Kembali ke halaman utama">
            <img src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah Kota Batam">
        </a>
        <div class="register-visual-copy">
            <span class="auth-kicker"><i></i> Portal Pemohon LENTERA</span>
            <h1>Satu akun untuk seluruh layanan rumah sakit.</h1>
            <p>Daftar sekali untuk mengakses layanan pendidikan, pelatihan, penelitian, dan ethical clearance secara terintegrasi.</p>
            <ol class="register-steps">
                <li><span>1</span><div><b>Lengkapi identitas</b><small>Masukkan data diri dan institusi</small></div></li>
                <li><span>2</span><div><b>Buat akun dengan aman</b><small>Gunakan email aktif dan kata sandi pribadi</small></div></li>
                <li><span>3</span><div><b>Mulai pengajuan</b><small>Pilih layanan sesuai kebutuhan Anda</small></div></li>
            </ol>
        </div>
        <small class="login-visual-footer">RSUD Embung Fatimah · Kota Batam</small>
    </section>

    <section class="auth-form register-form-panel">
        <form method="post" action="{{ route('register') }}" class="register-card">
            @csrf
            <a class="mobile-login-brand" href="{{ route('home') }}">
                <span><img src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah Kota Batam"></span><b>LENTERA</b>
            </a>
            <div class="register-heading">
                <div><span class="login-overline">Pendaftaran pemohon</span><h2>Buat akun LENTERA</h2><p>Lengkapi identitas berikut untuk memulai pengajuan.</p></div>
                <span class="secure-label"><i>✓</i> Data terlindungi</span>
            </div>
            @if($errors->any())
                <div class="alert error" role="alert"><b>Periksa kembali data Anda.</b> {{ $errors->first() }}</div>
            @endif

            <fieldset class="register-section">
                <legend><span>01</span><div><b>Identitas pribadi</b><small>Informasi dasar pemilik akun</small></div></legend>
                <div class="register-grid">
                    <label for="register-nik">NIK <em>Wajib</em><input id="register-nik" name="nik" value="{{ old('nik') }}" required inputmode="numeric" autocomplete="off" placeholder="16 digit NIK"></label>
                    <label for="register-name">Nama lengkap <em>Wajib</em><input id="register-name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Sesuai identitas resmi"></label>
                    <label for="register-email">Alamat email <em>Wajib</em><input id="register-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama@email.com"></label>
                    <label for="register-phone">Nomor HP <em>Wajib</em><input id="register-phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" placeholder="Contoh: 0812 3456 7890"></label>
                </div>
            </fieldset>

            <fieldset class="register-section">
                <legend><span>02</span><div><b>Informasi institusi</b><small>Asal institusi dan posisi Anda</small></div></legend>
                <div class="register-grid">
                    <label for="register-institution">Nama institusi <em>Wajib</em>
                        <input id="register-institution" name="institution_name" value="{{ old('institution_name') }}" list="institution-suggestions" required autocomplete="organization" placeholder="Ketik nama sekolah, kampus, atau perusahaan">
                        <datalist id="institution-suggestions">@foreach($institutions as $institution)<option value="{{ $institution->name }}">{{ $institution->type }}</option>@endforeach</datalist>
                    </label>
                    <label for="register-position">Status / Jabatan<input id="register-position" name="position" value="{{ old('position') }}" autocomplete="organization-title" placeholder="Contoh: Mahasiswa"></label>
                </div>
            </fieldset>

            <fieldset class="register-section password-section">
                <legend><span>03</span><div><b>Keamanan akun</b><small>Buat kata sandi yang mudah Anda ingat</small></div></legend>
                <div class="register-grid">
                    <label for="register-password">Kata sandi <em>Wajib</em><input id="register-password" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter"></label>
                    <label for="register-password-confirmation">Ulangi kata sandi <em>Wajib</em><input id="register-password-confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang kata sandi"></label>
                </div>
            </fieldset>

            <p class="register-consent">Dengan membuat akun, Anda menyatakan bahwa data yang diberikan benar dan dapat dipertanggungjawabkan.</p>
            <button class="button wide register-submit">Buat Akun <span aria-hidden="true">→</span></button>
            <p class="center register-login-prompt">Sudah memiliki akun? <a href="{{ route('login') }}">Masuk ke LENTERA</a></p>
        </form>
    </section>
</body>
</html>
