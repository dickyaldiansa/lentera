@extends('layouts.app')
@section('title','Persetujuan Saya')
@section('content')
<div class="page-head"><div><span class="eyebrow">Dashboard Pejabat</span><h1>Persetujuan Saya</h1><p>Tugas verifikasi dan persetujuan yang ditugaskan kepada jabatan Anda.</p></div></div>
<div class="approval-stat-grid">
    <a class="approval-stat red {{ $status==='pending'?'active':'' }}" href="{{ route('approvals.index',['status'=>'pending']) }}"><span>Menunggu Persetujuan</span><b>{{ $stats['pending'] }}</b></a>
    <a class="approval-stat amber {{ $status==='revision_required'?'active':'' }}" href="{{ route('approvals.index',['status'=>'revision_required']) }}"><span>Perlu Revisi</span><b>{{ $stats['revision'] }}</b></a>
    <a class="approval-stat green {{ $status==='approved'?'active':'' }}" href="{{ route('approvals.index',['status'=>'approved']) }}"><span>Disetujui</span><b>{{ $stats['approved'] }}</b></a>
</div>
@if($history->isNotEmpty())<section class="panel"><div class="panel-head"><div><h2>Riwayat Keputusan Anda</h2><p>Pengajuan yang sudah Anda tindak lanjuti.</p></div></div><div class="documents">@foreach($history as $item)<div><span class="file-icon">{{ $item->status==='approved'?'✓':'!' }}</span><div><b>{{ $item->instance->workflowable->number??'Pengajuan' }} — {{ $item->instance->workflowable->title??'' }}</b><small>{{ $item->status==='approved'?'Disetujui':($item->status==='revision_required'?'Minta perbaikan':'Ditolak') }} · {{ $item->acted_at->format('d M Y H:i') }}</small></div><a href="{{ route('approvals.show',$item) }}">Lihat</a></div>@endforeach</div></section>@endif
<div class="approval-list">
@forelse($tasks as $task)@php($item=$task->instance->workflowable)
    <article class="approval-card">
        <div class="approval-card-head"><div><span>{{ $item->number??'#'.$item->id }} — {{ $item->activityType?->name??str($task->instance->version->template->module)->title() }}</span><h2>{{ $item->title??'Pengajuan layanan' }}</h2></div><span class="badge {{ $task->status==='approved'?'green':($task->status==='revision_required'?'red':'blue') }}">{{ $task->status==='pending'?'Menunggu':($task->status==='revision_required'?'Perlu Revisi':'Disetujui') }}</span></div>
        <div class="approval-meta"><span><b>{{ $item->applicant?->name??'-' }}</b><small>Pemohon</small></span><span><b>{{ $item->institution?->name??'Individu' }}</b><small>Institusi</small></span><span><b>{{ $task->step->name }}</b><small>Tahap Anda</small></span>@if($task->due_at)<span><b class="{{ $task->due_at->isPast()?'danger-text':'' }}">{{ $task->due_at->diffForHumans() }}</b><small>Batas tindakan</small></span>@endif</div>
        <div class="approval-progress">@foreach($task->instance->version->steps as $step)@php($flowTask=$task->instance->tasks->sortByDesc('id')->firstWhere('workflow_step_id',$step->id))<span class="{{ $flowTask?->status??'queued' }}"><i>{{ $flowTask?->status==='approved'?'✓':($flowTask?->status==='pending'?'→':($flowTask?'!':'·')) }}</i>{{ $step->name }}</span>@endforeach</div>
        <div class="approval-card-actions">
            <a class="button secondary" href="{{ route('approvals.show',$task) }}">Lihat Detail</a>
            @if($task->status==='pending' && $task->step->behavior()==='disposition')
                <a class="button" href="{{ route('approvals.show',$task) }}">Disposisi ke Direktur</a>
            @elseif($task->status==='pending' && (($task->step->conditions['managed'] ?? false) || ($task->step->conditions['creates_invoice'] ?? false) || ($task->step->conditions['payment_gate'] ?? false)))
                <a class="button" href="{{ route('approvals.show',$task) }}">{{ ($task->step->conditions['payment_gate'] ?? false) ? 'Periksa Pembayaran' : 'Verifikasi Administrasi & Tagihan' }}</a>
            @elseif($task->status==='pending')
                <form method="post" action="{{ route('approvals.act',$task) }}">@csrf<input type="hidden" name="action" value="approve"><button class="button">Setujui</button></form>
                @if($task->step->allow_revision)<a class="button secondary" href="{{ route('approvals.show',$task) }}?decision=revision#tindakan">Minta Perbaikan</a>@endif
                @if($task->step->allow_reject)<a class="button danger-button" href="{{ route('approvals.show',$task) }}?decision=reject#tindakan">Tolak</a>@endif
            @endif
        </div>
    </article>
@empty<div class="panel empty">Tidak ada tugas pada kategori ini.</div>@endforelse
</div><x-pagination :paginator="$tasks" label="tugas persetujuan" />
@endsection
