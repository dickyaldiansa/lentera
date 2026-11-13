@extends('layouts.app')
@section('title',$student->exists?'Edit Peserta':'Peserta Baru')
@section('content')
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>{{ $student->exists?'Edit Peserta Didik':'Tambah Peserta Didik' }}</h1><p>Kelola identitas, program, periode, dan status pendidikan klinis peserta.</p></div></div>
<form class="panel form" method="post" action="{{ $student->exists?route('students.update',$student):route('students.store') }}">@csrf @if($student->exists)@method('PUT')@endif
<div class="form-grid">
<label>Nama lengkap *<input name="name" value="{{ old('name',$student->name) }}" required></label><label>NIM<input name="nim" value="{{ old('nim',$student->nim) }}"></label>
<label>Institusi *<select name="institution_id" required>@foreach($institutions as $institution)<option value="{{ $institution->id }}" @selected((string)old('institution_id',$student->institution_id)===(string)$institution->id)>{{ $institution->name }}</option>@endforeach</select></label>
<label>Program pendidikan *<select name="education_program_id" required>@foreach($programs as $program)<option value="{{ $program->id }}" @selected((string)old('education_program_id',$student->education_program_id)===(string)$program->id)>{{ $program->name }}</option>@endforeach</select></label>
<label>Bagian / unit penempatan *<select name="department_id" required><option value="">Pilih bagian atau unit</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)old('department_id',$student->department_id)===(string)$department->id)>{{ $department->name }}</option>@endforeach</select></label>
<label>Jenis kelamin<select name="gender"><option value="">Tidak dipilih</option><option value="L" @selected(old('gender',$student->gender)==='L')>Laki-laki</option><option value="P" @selected(old('gender',$student->gender)==='P')>Perempuan</option></select></label>
<label>Email<input type="email" name="email" value="{{ old('email',$student->email) }}"></label><label>Nomor HP<input name="phone" value="{{ old('phone',$student->phone) }}"></label>
<label>Tanggal mulai *<input type="date" name="period_start" value="{{ old('period_start',$student->period_start?->format('Y-m-d')) }}" required></label><label>Tanggal selesai *<input type="date" name="period_end" value="{{ old('period_end',$student->period_end?->format('Y-m-d')) }}" required></label>
@if($student->exists)<label>Status<select name="status">@foreach(['active'=>'Aktif','inactive'=>'Nonaktif','completed'=>'Selesai','dropped'=>'Mengundurkan diri'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$student->status)===$value)>{{ $label }}</option>@endforeach</select></label>@endif
</div><div class="form-actions"><a class="button secondary" href="{{ route('kamkordik.index') }}">Batal</a><button class="button purple-bg">Simpan Peserta</button></div></form>
@endsection
