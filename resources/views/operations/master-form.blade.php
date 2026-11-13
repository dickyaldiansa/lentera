@extends('layouts.app')
@section('title',($item?'Edit ':'Tambah ').$definition['title'])
@section('content')
<div class="page-head"><div><span class="eyebrow">Master Data</span><h1>{{ $item?'Edit':'Tambah' }} {{ $definition['title'] }}</h1><p>Perubahan akan digunakan oleh workflow dan form LENTERA.</p></div></div>
<form class="panel form" method="post" @if(collect($definition['fields'])->contains('type','file'))enctype="multipart/form-data" @endif action="{{ $item?route('master.update',[$dataset,$item->id]):route('master.store',$dataset) }}">@csrf @if($item)@method('PUT')@endif
@if($dataset==='tariffs')<p>Tarif aktif yang ditampilkan kepada pemohon menjadi acuan biaya. Perubahan tidak mengubah nominal invoice yang telah diterbitkan.</p>@endif
<div class="form-grid">@foreach($definition['fields'] as $name=>$field)@php($value=old($name,$item ? $item->$name : match($name){'active','required','published'=>1,'sort_order'=>0,'color'=>'#2563eb','max_size_kb'=>5120,'category'=>'other','calculation'=>'flat',default=>null}))
<label class="{{ $field['type']==='textarea'?'full':'' }}">{{ $field['label'] }} @if(!empty($field['required']))*@endif
@switch($field['type'])
@case('textarea')<textarea name="{{ $name }}">{{ $value }}</textarea>@break
@case('select')<select name="{{ $name }}">@foreach($field['options'] as $option=>$label)<option value="{{ $option }}" @selected((string)$value===(string)$option)>{{ $label }}</option>@endforeach</select>@break
@case('boolean')<select name="{{ $name }}"><option value="1" @selected((string)$value==='1')>Aktif / Ya</option><option value="0" @selected((string)$value==='0')>Nonaktif / Tidak</option></select>@break
@case('activity_type')<select name="{{ $name }}"><option value="">Semua jenis</option>@foreach($activityTypes as $type)<option value="{{ $type->id }}" @selected((string)$value===(string)$type->id)>{{ $type->name }}</option>@endforeach</select>@break
@case('department')<select name="{{ $name }}"><option value="">Semua unit</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)$value===(string)$department->id)>{{ $department->name }}</option>@endforeach</select>@break
@case('file')<input type="file" name="{{ $name }}" accept=".png,.jpg,.jpeg">@if($value)<small>Berkas sudah tersimpan. Pilih berkas baru untuk menggantinya.</small>@else<small>PNG/JPG/JPEG, maksimal 2 MB.</small>@endif @break
@default<input type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $value }}" @if(!empty($field['required']))required @endif>
@endswitch</label>@endforeach</div>
<div class="form-actions"><a class="button secondary" href="{{ route('master.dataset',$dataset) }}">Batal</a><button class="button">Simpan Data</button></div></form>
@endsection
