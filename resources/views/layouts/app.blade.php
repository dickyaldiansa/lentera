<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Dashboard') · LENTERA EMBUNG FATIMAH</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand hospital-brand sidebar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/branding/rsud-embung-fatimah.png') }}" alt="RSUD Embung Fatimah Kota Batam">
            <span class="sidebar-product-name">LENTERA EMBUNG FATIMAH</span>
            <small class="sidebar-product-tagline">Layanan Terintegrasi Pendidikan dan Pelatihan<br>RSUD Embung Fatimah</small>
        </a>

        <nav class="module-nav">
            <span class="nav-label">Menu Utama</span>
            <a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><x-nav-icon name="dashboard"/><span>Dashboard</span></a>
            @if(auth()->user()->hasRole('applicant'))
                <a class="{{ request()->routeIs('my.applications')?'active':'' }}" href="{{ route('my.applications') }}"><x-nav-icon name="users"/><span>Pengajuan Saya</span>@if($applicantNotificationCount)<b class="nav-count" aria-label="{{ $applicantNotificationCount }} pengajuan memerlukan tindakan">{{ $applicantNotificationCount>99?'99+':$applicantNotificationCount }}</b>@endif</a>
                <a class="{{ request()->routeIs('my.activities')?'active':'' }}" href="{{ route('my.activities') }}"><x-nav-icon name="activity"/><span>Kegiatan Saya</span></a>
                <a class="{{ request()->routeIs('tariffs.index')?'active':'' }}" href="{{ route('tariffs.index') }}"><x-nav-icon name="payment"/><span>Daftar Tarif</span></a>
            @endif
            @if(auth()->user()->canDo('workflow.approve'))<a class="{{ request()->routeIs('approvals.*')?'active':'' }}" href="{{ route('approvals.index') }}"><x-nav-icon name="review"/><span>Persetujuan Saya</span>@if($approvalTaskCount)<b class="nav-count" aria-label="{{ $approvalTaskCount }} persetujuan menunggu">{{ $approvalTaskCount>99?'99+':$approvalTaskCount }}</b>@endif</a>@endif

            @if(auth()->user()->canDo('diklat.view') && !auth()->user()->hasRole('applicant'))
                <span class="nav-label blue">Diklat (Eksternal)</span>
                <a class="{{ request()->routeIs('applications.*')?'active':'' }}" href="{{ $newApplicationCount?route('applications.index',['status'=>'submitted']):route('applications.index') }}"><x-nav-icon name="application"/><span>Pengajuan Diklat</span>@if($newApplicationCount)<b class="nav-count" aria-label="{{ $newApplicationCount }} pengajuan baru">{{ $newApplicationCount>99?'99+':$newApplicationCount }}</b>@else<i class="chevron">›</i>@endif</a>
                @unless(auth()->user()->hasRole('director','deputy-director'))<a class="{{ request()->routeIs('diklat.activities')?'active':'' }}" href="{{ route('diklat.activities') }}"><x-nav-icon name="activity"/><span>Daftar Kegiatan</span></a>
                <a class="{{ request()->routeIs('diklat.certificates')?'active':'' }}" href="{{ route('diklat.certificates') }}"><x-nav-icon name="certificate"/><span>Sertifikat</span></a>
                <a class="{{ request()->routeIs('institutions')?'active':'' }}" href="{{ route('institutions') }}"><x-nav-icon name="institution"/><span>Institusi/Perusahaan</span></a>@endunless
                @if(auth()->user()->canDo('diklat.payment'))<a class="{{ request()->routeIs('diklat.payments')?'active':'' }}" href="{{ route('diklat.payments') }}"><x-nav-icon name="payment"/><span>Pembayaran</span></a>@endif
                @if(auth()->user()->canDo('report.view') && !auth()->user()->hasRole('applicant'))<a href="{{ route('reports',['module'=>'diklat']) }}"><x-nav-icon name="report"/><span>Laporan Diklat</span></a>@endif
            @endif

            @if(auth()->user()->canDo('kamkordik.view'))
                <span class="nav-label purple">KAMKORDIK (Pendidikan Klinik)</span>
                @if(auth()->user()->canDo('kamkordik.student.manage'))<a class="{{ request()->routeIs('education-institutions.*')?'active':'' }}" href="{{ route('education-institutions.index') }}"><x-nav-icon name="education"/><span>Institusi Pendidikan</span></a>@endif
                <a class="{{ request()->routeIs('kamkordik.index')?'active':'' }}" href="{{ route('kamkordik.index') }}"><x-nav-icon name="users"/><span>Peserta Didik</span></a>
                <a class="{{ request()->routeIs('kamkordik.coordinations.*')?'active':'' }}" href="{{ route('kamkordik.coordinations.index') }}"><x-nav-icon name="review"/><span>Koordinasi Pengajuan Diklat</span>@if($coordinationTaskCount)<b class="nav-count" aria-label="{{ $coordinationTaskCount }} koordinasi menunggu">{{ $coordinationTaskCount>99?'99+':$coordinationTaskCount }}</b>@endif</a>
                <a class="{{ request()->routeIs('rotations.*')?'active':'' }}" href="{{ route('rotations.index') }}"><x-nav-icon name="rotation"/><span>Penempatan & Rotasi</span></a>
                <a class="{{ request()->routeIs('logbooks.*')?'active':'' }}" href="{{ route('logbooks.index') }}"><x-nav-icon name="logbook"/><span>Logbook</span></a>
                <a class="{{ request()->routeIs('assessments.*')?'active':'' }}" href="{{ route('assessments.index') }}"><x-nav-icon name="assessment"/><span>Penilaian</span></a>
                <a class="{{ request()->routeIs('supervisors.*')?'active':'' }}" href="{{ route('supervisors.index') }}"><x-nav-icon name="supervisor"/><span>Pembimbing Klinis</span></a>
                @if(auth()->user()->canDo('report.view'))<a href="{{ route('reports',['module'=>'kamkordik']) }}"><x-nav-icon name="report"/><span>Laporan KAMKORDIK</span></a>@endif
            @endif

            @if(auth()->user()->canDo('ethics.view') && !auth()->user()->hasRole('applicant'))
                <span class="nav-label green">Komite Etik (Penelitian/Klinik)</span>
                <a class="{{ request()->routeIs('ethics.index','ethics.create','ethics.show')?'active':'' }}" href="{{ route('ethics.index') }}"><x-nav-icon name="ethics"/><span>Pengajuan Etik</span></a>
                @unless(auth()->user()->hasRole('applicant'))
                    <a class="{{ request()->routeIs('ethics.reviews-meetings')?'active':'' }}" href="{{ route('ethics.reviews-meetings') }}"><x-nav-icon name="review"/><span>Review & Sidang</span></a>
                    <a class="{{ request()->routeIs('ethics.decisions')?'active':'' }}" href="{{ route('ethics.decisions') }}"><x-nav-icon name="certificate"/><span>Keputusan & EC</span></a>
                    @unless(auth()->user()->hasRole('ethics-reviewer'))<a class="{{ request()->routeIs('ethics.monitoring')?'active':'' }}" href="{{ route('ethics.monitoring') }}"><x-nav-icon name="monitoring"/><span>Monitoring Penelitian</span></a>@endunless
                    @if(auth()->user()->canDo('report.view'))<a href="{{ route('reports',['module'=>'ethics']) }}"><x-nav-icon name="report"/><span>Laporan Etik</span></a>@endif
                @endunless
            @endif

            @if(auth()->user()->canDo('sk.view'))
                <span class="nav-label">Dokumen Resmi</span>
                <a class="{{ request()->routeIs('decrees.*')?'active':'' }}" href="{{ route('decrees.index') }}"><x-nav-icon name="certificate"/><span>Manajemen SK</span></a>
            @endif

            @if(auth()->user()->canDo('master.manage') || auth()->user()->canDo('users.manage'))
                <span class="nav-label">Master Data</span>
                @if(auth()->user()->canDo('master.manage'))
                    <a class="{{ request()->routeIs('master.*')?'active':'' }}" href="{{ route('master.index') }}"><x-nav-icon name="database"/><span>Master Data</span></a>
                    <a class="{{ request()->routeIs('workflow-master.*')?'active':'' }}" href="{{ route('workflow-master.index') }}"><x-nav-icon name="rotation"/><span>Pengaturan Alur Layanan</span></a>
                @endif
                @if(auth()->user()->canDo('users.manage'))<a class="{{ request()->routeIs('users.*','roles.*')?'active':'' }}" href="{{ route('users.index') }}"><x-nav-icon name="users"/><span>Pengguna & Role</span></a>@endif
            @endif
        </nav>

        <div class="sidebar-foot"><div class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div><div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->roles->pluck('name')->join(', ') }}</small></div></div>
    </aside>

    <div class="main">
        <header>
            <button class="menu-button" data-menu aria-label="Buka menu">☰</button>
            <form action="{{ route('search') }}" class="global-search"><span>⌕</span><input name="q" placeholder="Cari dalam lingkup akses Anda..." value="{{ request('q') }}"></form>
            <div class="header-actions">
                @if($notificationCount)<a class="notification" href="{{ route('notifications.index') }}" title="{{ $notificationCount }} notifikasi baru" aria-label="Buka {{ $notificationCount }} notifikasi baru"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><b>{{ $notificationCount>99?'99+':$notificationCount }}</b></a>
                @elseif($newApplicationCount)<a class="notification" href="{{ route('applications.index',['status'=>'submitted']) }}" title="{{ $newApplicationCount }} pengajuan baru" aria-label="Buka {{ $newApplicationCount }} pengajuan baru"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><b>{{ $newApplicationCount>99?'99+':$newApplicationCount }}</b></a>
                @elseif($applicantNotificationCount)<a class="notification" href="{{ route('my.applications') }}" title="{{ $applicantNotificationCount }} pengajuan memerlukan tindakan" aria-label="Buka {{ $applicantNotificationCount }} pengajuan yang memerlukan tindakan"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><b>{{ $applicantNotificationCount>99?'99+':$applicantNotificationCount }}</b></a>@endif
                <form method="post" action="{{ route('logout') }}">@csrf<button class="ghost">Keluar</button></form>
            </div>
        </header>
        <main>
            @if(session('success'))<div class="alert success">✓ {{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error"><b>Periksa kembali data:</b> {{ $errors->first() }}</div>@endif
            @yield('content')
        </main>
    </div>

    <nav class="mobile-bottom-nav" aria-label="Navigasi cepat">
        <a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}"><x-nav-icon name="dashboard"/><span>Beranda</span></a>
        @if(auth()->user()->hasRole('applicant'))
            <a class="{{ request()->routeIs('my.applications','my.activities')?'active':'' }}" href="{{ route('my.applications') }}"><x-nav-icon name="application"/><span>Pengajuan</span></a>
        @elseif(auth()->user()->canDo('workflow.approve'))
            <a class="{{ request()->routeIs('approvals.*')?'active':'' }}" href="{{ route('approvals.index') }}"><x-nav-icon name="review"/><span>Tugas</span>@if($approvalTaskCount)<b>{{ $approvalTaskCount>99?'99+':$approvalTaskCount }}</b>@endif</a>
        @elseif(auth()->user()->canDo('diklat.view'))
            <a class="{{ request()->routeIs('applications.*')?'active':'' }}" href="{{ route('applications.index') }}"><x-nav-icon name="application"/><span>Pengajuan</span>@if($newApplicationCount)<b>{{ $newApplicationCount>99?'99+':$newApplicationCount }}</b>@endif</a>
        @endif
        <a class="{{ request()->routeIs('notifications.*')?'active':'' }}" href="{{ route('notifications.index') }}"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><span>Notifikasi</span>@if($notificationCount)<b>{{ $notificationCount>99?'99+':$notificationCount }}</b>@endif</a>
        <button type="button" data-menu aria-controls="sidebar" aria-label="Buka seluruh menu"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg><span>Menu</span></button>
    </nav>
</div>
<script>const sidebar=document.querySelector('#sidebar');document.querySelectorAll('[data-menu]').forEach(button=>button.addEventListener('click',()=>{sidebar?.classList.toggle('open');const expanded=sidebar?.classList.contains('open')?'true':'false';document.querySelectorAll('[data-menu]').forEach(item=>item.setAttribute('aria-expanded',expanded));}));const disposeForm=document.querySelector('form[action$="dispose-director"]');if(disposeForm&&document.body.textContent.includes('Menunggu Persetujuan')){const panel=disposeForm.closest('section');panel.innerHTML='<h2>Disposisi Persetujuan</h2><p>Pengajuan sudah didisposisikan dan sedang menunggu tindakan penerima.</p>';}[...document.querySelectorAll('aside section')].find(panel=>panel.querySelector('h2')?.textContent.trim()==='Tindak Lanjut')?.remove();</script>
@stack('scripts')
</body>
</html>
