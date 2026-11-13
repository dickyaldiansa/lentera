@extends('layouts.app')
@section('title','KAMKORDIK')
@section('content')
@php($canManage=auth()->user()->hasRole('super-admin','kamkordik-admin','kamkordik-head'))
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Peserta Didik</h1><p>Kelola peserta, program pendidikan, rotasi klinis, dan kemajuan logbook.</p></div>@if($canManage)<a class="button purple-bg" href="{{ route('students.create') }}">＋ Peserta Baru</a>@endif</div>
<div class="module-tabs"><a class="active" href="{{ route('kamkordik.index') }}">Peserta</a><a href="{{ route('rotations.index') }}">Rotasi</a><a href="{{ route('logbooks.index') }}">Logbook <span>{{ $pending }}</span></a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Nomor / Nama</th><th>Institusi</th><th>Program</th><th>Periode</th><th>Rotasi</th><th>Status</th>@if($canManage)<th>Aksi</th>@endif</tr></thead><tbody>
@forelse($students as $student)<tr><td><b>{{ $student->name }}</b><small>{{ $student->number }} · NIM {{ $student->nim?:'-' }}</small></td><td>{{ $student->institution->name }}</td><td>{{ $student->program->name }}</td><td>{{ $student->period_start->format('d M Y') }} – {{ $student->period_end->format('d M Y') }}</td><td>{{ $student->rotations->count() }} stase</td><td><x-status :status="$student->status"/></td>
@if($canManage)<td><div class="table-actions"><a class="mini-link" href="{{ route('students.edit',$student) }}">Edit</a><form method="post" action="{{ route('students.status',$student) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $student->status==='active'?'inactive':'active' }}"><button class="mini-link danger" type="submit">{{ $student->status==='active'?'Nonaktifkan':'Aktifkan' }}</button></form></div></td>@endif
</tr>@empty<tr><td class="empty" colspan="{{ $canManage?7:6 }}">Belum ada peserta didik.</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$students" label="peserta didik" /></section>
@endsection
