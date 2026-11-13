@extends('layouts.app')
@section('title',$user->exists?'Edit Pengguna':'Tambah Pengguna')
@section('content')
@php($activeValue=(string)old('active',$user->exists?(int)$user->active:1))
@php($selectedRoles=collect(old('roles',$user->roles->pluck('id')->all()))->map(fn($id)=>(string)$id))
<div class="page-head"><div><span class="eyebrow">Administrasi Sistem</span><h1>{{ $user->exists?'Edit Pengguna':'Tambah Pengguna' }}</h1><p>{{ $user->exists?'Perbarui profil, status, password, dan role pengguna.':'Buat akun baru dan tetapkan sedikitnya satu role.' }}</p></div></div>
<form class="panel form" method="post" action="{{ $user->exists?route('users.update',$user):route('users.store') }}">@csrf @if($user->exists)@method('PUT')@endif
    <div class="section-title"><b>Informasi Akun</b><span>01</span></div>
    <div class="form-grid">
        <label>Nama lengkap *<input name="name" value="{{ old('name',$user->name) }}" required></label><label>NIK<input name="nik" value="{{ old('nik',$user->nik) }}"></label>
        <label>Email *<input type="email" name="email" value="{{ old('email',$user->email) }}" required></label><label>Nomor telepon<input name="phone" value="{{ old('phone',$user->phone) }}"></label>
        <label>Jabatan<input name="position" value="{{ old('position',$user->position) }}"></label>
        <label>Institusi<select name="institution_id"><option value="">Tidak terikat institusi</option>@foreach($institutions as $institution)<option value="{{ $institution->id }}" @selected((string)old('institution_id',$user->institution_id)===(string)$institution->id)>{{ $institution->name }}</option>@endforeach</select></label>
        <label>Status<select name="active" @disabled(auth()->user()->is($user))><option value="1" @selected($activeValue==='1')>Aktif</option><option value="0" @selected($activeValue==='0')>Nonaktif</option></select>@if(auth()->user()->is($user))<input type="hidden" name="active" value="1"><small>Akun yang sedang digunakan harus tetap aktif.</small>@endif</label>
    </div>
    <div class="section-title form-section"><b>{{ $user->exists?'Ganti Password':'Password' }}</b><span>02</span></div>
    <div class="form-grid"><label>Password {{ $user->exists?'(opsional)':'*' }}<input type="password" name="password" {{ $user->exists?'':'required' }} minlength="8" autocomplete="new-password"><small>{{ $user->exists?'Kosongkan jika password tidak diubah.':'Minimal 8 karakter.' }}</small></label><label>Konfirmasi password {{ $user->exists?'':'*' }}<input type="password" name="password_confirmation" {{ $user->exists?'':'required' }} minlength="8" autocomplete="new-password"></label></div>
    <div class="section-title form-section"><b>Role Pengguna</b><span>03</span></div>
    <div class="choice-grid">@foreach($roles as $role)<label class="choice-card"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($selectedRoles->contains((string)$role->id))><span><b>{{ $role->name }}</b><small>{{ $role->slug }} · {{ $role->scope==='external'?'Eksternal':'Internal' }}</small></span></label>@endforeach</div>
    <div class="form-actions"><a class="button secondary" href="{{ route('users.index') }}">Batal</a><button class="button">Simpan Pengguna</button></div>
</form>
@endsection
