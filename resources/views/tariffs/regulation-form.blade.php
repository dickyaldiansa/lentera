@extends('layouts.app')
@section('title',$regulation->exists?'Edit Dasar Hukum Tarif':'Tambah Dasar Hukum Tarif')
@section('content')
<div class="page-head"><div><span class="eyebrow">Master Data / Tarif</span><h1>{{ $regulation->exists?'Edit':'Tambah' }} Dasar Hukum Tarif</h1><p>Unggah Perda atau peraturan lain yang menjadi dasar tarif layanan.</p></div></div>
<form class="panel form" method="post" enctype="multipart/form-data" action="{{ $regulation->exists?route('tariff-regulations.update',$regulation):route('tariff-regulations.store') }}">
    @csrf
    @if($regulation->exists)@method('PUT')@endif
    <div class="form-grid">
        <label class="full">Judul peraturan *<input name="title" value="{{ old('title',$regulation->title) }}" maxlength="255" required></label>
        <label>Nomor peraturan *<input name="number" value="{{ old('number',$regulation->number) }}" maxlength="255" required></label>
        <label>Tahun *<input type="number" name="year" min="1900" max="{{ now()->year+5 }}" value="{{ old('year',$regulation->year??now()->year) }}" required></label>
        <label>Berlaku mulai<input type="date" name="effective_from" value="{{ old('effective_from',$regulation->effective_from?->format('Y-m-d')) }}"></label>
        @php($status=old('status',$regulation->exists?($regulation->is_current?'current':'archived'):'current'))
        <label>Status *<select name="status" required>
            <option value="current" @selected($status==='current')>Berlaku</option>
            <option value="archived" @selected($status==='archived')>Arsip</option>
        </select></label>
        <label class="full">Dokumen PDF {{ $regulation->exists?'':'*' }}<input type="file" name="document" accept=".pdf,application/pdf" @required(!$regulation->exists)><small>Maksimal 10 MB. @if($regulation->exists)Kosongkan untuk mempertahankan dokumen. Mengunggah PDF baru akan menyimpan dokumen lama sebagai arsip.@endif</small></label>
    </div>
    @if($regulation->exists)<p>Dokumen tersimpan: <a href="{{ route('tariff-regulations.document',$regulation) }}" target="_blank" rel="noopener">{{ $regulation->original_filename }}</a></p>@endif
    <p class="muted-copy">Menetapkan dokumen sebagai Berlaku akan mengarsipkan dasar hukum tarif yang sebelumnya berlaku. Perubahan dokumen tidak mengubah nominal tarif atau invoice.</p>
    <div class="form-actions"><a class="button secondary" href="{{ route('master.dataset','tariffs') }}">Batal</a><button class="button">Simpan Dokumen</button></div>
</form>
@endsection
