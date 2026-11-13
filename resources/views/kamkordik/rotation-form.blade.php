@extends('layouts.app')
@section('title','Edit Rotasi')
@section('content')
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Edit Rotasi Klinis</h1><p>Perbarui penempatan, pembimbing, periode, atau status rotasi.</p></div></div>
<form class="panel form" method="post" action="{{ route('rotations.update',$rotation) }}">@csrf @method('PUT')<div class="form-grid">
<label>Peserta *<select name="student_id" required>@foreach($students as $student)<option value="{{ $student->id }}" @selected(old('student_id',$rotation->student_id)==$student->id)>{{ $student->name }}</option>@endforeach</select></label>
<label>Bagian *<select name="department_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$rotation->department_id)==$department->id)>{{ $department->name }}</option>@endforeach</select></label>
<label>Pembimbing<select name="clinical_supervisor_id"><option value="">Belum ditentukan</option>@foreach($supervisors as $supervisor)<option value="{{ $supervisor->id }}" @selected(old('clinical_supervisor_id',$rotation->clinical_supervisor_id)==$supervisor->id)>{{ $supervisor->name }}</option>@endforeach</select></label>
<label>Nama stase *<input name="stage" value="{{ old('stage',$rotation->stage) }}" required></label><label>Tanggal mulai *<input type="date" name="start_date" value="{{ old('start_date',$rotation->start_date->format('Y-m-d')) }}" required></label><label>Tanggal selesai *<input type="date" name="end_date" value="{{ old('end_date',$rotation->end_date->format('Y-m-d')) }}" required></label>
<label>Status<select name="status">@foreach(['scheduled'=>'Terjadwal','running'=>'Berjalan','completed'=>'Selesai','cancelled'=>'Dibatalkan'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$rotation->status)===$value)>{{ $label }}</option>@endforeach</select></label>
</div><div class="form-actions"><a class="button secondary" href="{{ route('rotations.index') }}">Batal</a><button class="button purple-bg">Simpan Rotasi</button></div></form>
@endsection
