@extends('layouts.app')
@section('title',$title)
@section('content')
@php
    $editable=in_array($dataset,['education_programs','activity_types','application_follow_ups','payment_methods','activity_locations','signatories','departments','requirements','tariffs'],true);
    $hasActive=array_key_exists('active',$columns);
    $booleanColumns=['active','required','is_paid','requires_ethics','requires_kamkordik'];
@endphp
<div class="page-head">
    <div><span class="eyebrow">Master Data</span><h1>{{ $title }}</h1><p>{{ $dataset==='activity_types'?'Kelola layanan yang tampil di halaman publik dan formulir pengajuan.':($dataset==='application_follow_ups'?'Kelola pilihan tindak lanjut berbahasa Indonesia untuk pengajuan.':'Data referensi yang digunakan oleh workflow dan formulir LENTERA.') }}</p></div>
    <div class="page-actions"><a class="button secondary" href="{{ route('master.index') }}">← Kembali</a>@if($editable)<a class="button" href="{{ route('master.create',$dataset) }}">＋ {{ $dataset==='activity_types'?'Tambah Layanan':($dataset==='application_follow_ups'?'Tambah Tindak Lanjut':'Tambah Data') }}</a>@endif</div>
</div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>ID</th>@foreach($columns as $column=>$label)<th>{{ $label }}</th>@endforeach @if($editable)<th>Aksi</th>@endif</tr></thead><tbody>
@forelse($items as $item)<tr><td>{{ $item->id }}</td>@foreach($columns as $column=>$label)<td>@php($value=$item->$column) @if(is_string($value) && str_starts_with($value,'['))<small>{{ str($value)->limit(100) }}</small>@elseif($column==='amount')Rp{{ number_format((float)$value,0,',','.') }}@elseif(in_array($column,$booleanColumns,true))<x-status :status="$value?'active':'inactive'"/>@else{{ $value?:'-' }}@endif</td>@endforeach
@if($editable)<td><div class="table-actions"><a class="mini-link" href="{{ route('master.edit',[$dataset,$item->id]) }}">Edit</a>@if($hasActive)<form method="post" action="{{ route('master.toggle',[$dataset,$item->id]) }}">@csrf @method('PATCH')<button class="mini-link" type="submit">{{ $item->active?'Nonaktifkan':'Aktifkan' }}</button></form>@endif @if(in_array($dataset,['activity_types','application_follow_ups','payment_methods','activity_locations','signatories'],true))<form method="post" action="{{ route('master.destroy',[$dataset,$item->id]) }}" onsubmit="return confirm('Hapus data ini secara permanen?')">@csrf @method('DELETE')<button class="mini-link danger" type="submit">Hapus</button></form>@endif</div></td>@endif
</tr>@empty<tr><td colspan="{{ count($columns)+1+($editable?1:0) }}" class="empty">Belum ada data.</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$items" label="data" /></section>
@endsection
