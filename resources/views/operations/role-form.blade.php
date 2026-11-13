@extends('layouts.app')
@section('title',$role->exists?'Edit Role':'Tambah Role')
@section('content')
@php($selectedPermissions=collect(old('permissions',$role->permissions->pluck('id')->all()))->map(fn($id)=>(string)$id))
<div class="page-head"><div><span class="eyebrow">Administrasi Sistem</span><h1>{{ $role->exists?'Edit Role':'Tambah Role' }}</h1><p>Atur identitas role dan permission yang diberikan kepada penggunanya.</p></div></div>
<form class="panel form" method="post" action="{{ $role->exists?route('roles.update',$role):route('roles.store') }}">@csrf @if($role->exists)@method('PUT')@endif
    <div class="section-title"><b>Informasi Role</b><span>01</span></div>
    <div class="form-grid">
        <label>Nama role *<input name="name" value="{{ old('name',$role->name) }}" required></label>
        <label>Slug *<input name="slug" value="{{ old('slug',$role->slug) }}" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="contoh: admin-unit" required @readonly($role->slug==='super-admin')><small>Huruf kecil, angka, dan tanda hubung.</small></label>
        <label>Lingkup *<select name="scope" required><option value="internal" @selected(old('scope',$role->scope?:'internal')==='internal')>Internal</option><option value="external" @selected(old('scope',$role->scope)==='external')>Eksternal</option></select></label>
    </div>
    <div class="section-title form-section"><b>Permission</b><span>02</span></div><p class="form-hint">Pilih izin yang dapat digunakan oleh role ini. Super Administrator tetap memiliki semua akses secara otomatis.</p>
    @forelse($permissions as $group=>$items)<fieldset class="permission-group"><legend>{{ str($group)->replace('_',' ')->title() }}</legend><div class="choice-grid">@foreach($items as $permission)<label class="choice-card"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($selectedPermissions->contains((string)$permission->id))><span><b>{{ $permission->name }}</b><small>{{ $permission->slug }}</small></span></label>@endforeach</div></fieldset>@empty<div class="empty">Belum ada permission yang tersedia.</div>@endforelse
    <div class="form-actions"><a class="button secondary" href="{{ route('users.index',['tab'=>'roles']) }}">Batal</a><button class="button">Simpan Role</button></div>
</form>
@endsection
