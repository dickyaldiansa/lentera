@extends('layouts.app')
@section('title','Kontak Admin Diklat')
@section('content')
<div class="page-head"><div><span class="eyebrow">Master Data</span><h1>Kontak Admin Diklat</h1><p>Atur nomor WhatsApp untuk tombol Hubungi Admin Diklat di beranda dan dashboard.</p></div></div>
<form class="panel form" method="post" action="{{ route('master.diklat-contact.update') }}">
    @csrf
    @method('PUT')
    <div class="form-grid">
        <label>Nomor WhatsApp Admin Diklat *
            <input type="tel" name="whatsapp_number" value="{{ old('whatsapp_number',$number) }}" placeholder="081234567890" maxlength="40" required aria-describedby="whatsapp-help">
            <small id="whatsapp-help">Gunakan nomor WhatsApp aktif dengan awalan 08, 62, atau +62. Contoh: 081234567890.</small>
            @error('whatsapp_number')<small role="alert">{{ $message }}</small>@enderror
        </label>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('master.index') }}">Kembali</a><button class="button">{{ $number?'Simpan Perubahan':'Tambah Nomor WhatsApp' }}</button></div>
</form>
@endsection
