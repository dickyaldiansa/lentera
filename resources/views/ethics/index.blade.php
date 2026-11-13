@extends('layouts.app')
@section('title','Komite Etik')
@section('content')
<div class="page-head"><div><span class="eyebrow green-text">Komite Etik</span><h1>Pengajuan Ethical Clearance</h1><p>Review proposal, revisi, keputusan, dan monitoring penelitian.</p></div>@if(auth()->user()->canDo('ethics.create'))<a class="button green-bg" href="{{ route('ethics.create') }}">＋ Pengajuan Etik</a>@endif</div>
<div class="ethics-summary"><div><small>Pengajuan Baru</small><b>{{ $applications->where('status','draft')->count() }}</b></div><div><small>Dalam Review</small><b>{{ $applications->where('status','review')->count() }}</b></div><div><small>Perlu Revisi</small><b>{{ $applications->where('status','revision')->count() }}</b></div><div><small>EC Terbit</small><b>{{ $applications->filter(fn($application)=>$application->clearance)->count() }}</b></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Nomor</th><th>Judul Penelitian</th><th>Peneliti</th><th>Review</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($applications as $application)<tr><td><b>{{ $application->number }}</b><small>{{ $application->created_at->format('d M Y') }}</small></td><td><b>{{ $application->title }}</b><small>{{ $application->institution?->name }}</small></td><td>{{ $application->principal_investigator }}</td><td>{{ $application->reviews->where('status','completed')->count() }}/{{ $application->reviews->count() }}</td><td><x-status :status="$application->status"/></td><td><a class="icon-btn" href="{{ route('ethics.show',$application) }}">→</a></td></tr>@empty<tr><td colspan="6" class="empty">Belum ada pengajuan etik.</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$applications" label="pengajuan etik" /></section>
@endsection
