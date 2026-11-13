@extends('layouts.app')
@section('title',$supervisor->exists?'Edit Pembimbing':'Tambah Pembimbing')
@section('content')
@php($activeValue=(string)old('active',$supervisor->exists?(int)$supervisor->active:1))
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>{{ $supervisor->exists?'Edit Pembimbing':'Tambah Pembimbing' }}</h1><p>Tautkan pembimbing dengan akun pengguna agar penugasan dan hak akses konsisten.</p></div></div>
<form class="panel form" method="post" action="{{ $supervisor->exists?route('supervisors.update',$supervisor):route('supervisors.store') }}">@csrf @if($supervisor->exists)@method('PUT')@endif
<div class="form-grid">
<label>Nama lengkap *<input name="name" value="{{ old('name',$supervisor->name) }}" required></label>
<label>Akun pengguna<select name="user_id"><option value="">Belum ditautkan</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)old('user_id',$supervisor->user_id)===(string)$user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></label>
<label>Bagian / Unit *<select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)old('department_id',$supervisor->department_id)===(string)$department->id)>{{ $department->name }}</option>@endforeach</select></label>
<label>Nomor profesi<input name="professional_number" value="{{ old('professional_number',$supervisor->professional_number) }}"></label>
<label>Spesialisasi<input name="specialization" value="{{ old('specialization',$supervisor->specialization) }}"></label>
<label>Status<select name="active"><option value="1" @selected($activeValue==='1')>Aktif</option><option value="0" @selected($activeValue==='0')>Nonaktif</option></select></label>
</div><div class="form-actions"><a class="button secondary" href="{{ route('supervisors.index') }}">Batal</a><button class="button purple-bg">Simpan Pembimbing</button></div></form>
@endsection
