@extends('layouts.app')
@section('title',$decree->exists?'Edit SK':'Tambah SK')
@section('content')
<div class="page-head"><div><span class="eyebrow">Arsip Dokumen Resmi</span><h1>{{ $decree->exists?'Edit SK':'Tambah SK' }}</h1><p>Dokumen disimpan privat dan hanya dapat diunduh oleh pengguna berwenang.</p></div></div>
<form class="panel form" method="post" enctype="multipart/form-data" action="{{ $decree->exists?route('decrees.update',$decree):route('decrees.store') }}">
    @csrf @if($decree->exists)@method('PUT')@endif
    <div class="section-title"><b>Identitas SK</b><span>01</span></div>
    <div class="form-grid">
        <label>Jenis SK *<select name="category" required>@foreach($categories as $value=>$label)<option value="{{ $value }}" @selected(old('category',$decree->category?:array_key_first($categories))===$value)>{{ $label }}</option>@endforeach</select></label>
        <label>Tahun *<input type="number" name="year" min="2000" max="{{ now()->year+5 }}" value="{{ old('year',$decree->year?:now()->year) }}" required></label>
        <label>Nomor SK *<input name="number" value="{{ old('number',$decree->number) }}" placeholder="Contoh: 445/001/SK/2026" required></label>
        <label>Tanggal ditetapkan<input type="date" name="issued_at" value="{{ old('issued_at',$decree->issued_at?->format('Y-m-d')) }}"></label>
        <label class="full">Judul SK *<input name="title" value="{{ old('title',$decree->title) }}" required></label>
    </div>
    <div class="section-title form-section"><b>Masa Berlaku & Penanggung Jawab</b><span>02</span></div>
    <div class="form-grid">
        <label>Berlaku mulai<input type="date" name="effective_from" value="{{ old('effective_from',$decree->effective_from?->format('Y-m-d')) }}"></label>
        <label>Berlaku sampai<input type="date" name="effective_until" value="{{ old('effective_until',$decree->effective_until?->format('Y-m-d')) }}"></label>
        <label>Penandatangan<input name="signatory" value="{{ old('signatory',$decree->signatory) }}" placeholder="Nama/jabatan penandatangan"></label>
        <label>Pengelola<select name="manager_id"><option value="">Belum ditetapkan</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected((string)old('manager_id',$decree->manager_id)===(string)$manager->id)>{{ $manager->name }} — {{ $manager->position ?: $manager->email }}</option>@endforeach</select></label>
        <label>Status *<select name="status" required>@foreach(['draft'=>'Draf','active'=>'Aktif','expired'=>'Kedaluwarsa','revoked'=>'Dicabut'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$decree->status?:'active')===$value)>{{ $label }}</option>@endforeach</select></label>
        <label>File PDF<input type="file" name="document" accept="application/pdf"><small>Maksimal 10 MB. {{ $decree->file_path?'Kosongkan jika file tidak diganti.':'File dapat ditambahkan kemudian.' }}</small></label>
        <label class="full">Catatan<textarea name="notes">{{ old('notes',$decree->notes) }}</textarea></label>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('decrees.index') }}">Batal</a><button class="button">Simpan SK</button></div>
</form>
@endsection
