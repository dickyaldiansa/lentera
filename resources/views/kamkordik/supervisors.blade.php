@extends('layouts.app')
@section('title','Pembimbing Klinis')
@section('content')
@php($canManage=auth()->user()->hasRole('super-admin','kamkordik-admin','kamkordik-head'))
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Pembimbing Klinis</h1><p>Kelola pembimbing, unit klinis, nomor profesi, dan status penugasan.</p></div>@if($canManage)<a class="button purple-bg" href="{{ route('supervisors.create') }}">＋ Tambah Pembimbing</a>@endif</div>
<section class="panel"><form class="filters"><input name="q" value="{{ request('q') }}" placeholder="Cari pembimbing..."><button class="button secondary">Cari</button></form><div class="table-wrap"><table><thead><tr><th>Pembimbing</th><th>Akun</th><th>Bagian</th><th>Spesialisasi</th><th>Status</th>@if($canManage)<th>Aksi</th>@endif</tr></thead><tbody>
@forelse($supervisors as $supervisor)<tr><td><b>{{ $supervisor->name }}</b><small>{{ $supervisor->professional_number?:'Nomor profesi belum diisi' }}</small></td><td>{{ $supervisor->user?->email?:'Belum ditautkan' }}</td><td>{{ $supervisor->department->name }}</td><td>{{ $supervisor->specialization?:'-' }}</td><td><x-status :status="$supervisor->active?'active':'inactive'"/></td>@if($canManage)<td><div class="table-actions"><a class="icon-btn" href="{{ route('supervisors.edit',$supervisor) }}">✎</a><form method="post" action="{{ route('supervisors.status',$supervisor) }}">@csrf @method('PATCH')<button class="icon-btn">{{ $supervisor->active?'×':'✓' }}</button></form></div></td>@endif</tr>@empty<tr><td colspan="{{ $canManage?6:5 }}" class="empty">Belum ada pembimbing klinis.</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$supervisors" label="pembimbing klinis" /></section>
@endsection
