@extends('layouts.app')
@section('title','Detail Persetujuan')
@section('content')
@php($item=$task->instance->workflowable)
<div class="page-head"><div><span class="eyebrow">{{ $task->instance->version->template->name }} · v{{ $task->instance->version->version_number }}</span><h1>{{ $item->number??'#'.$item->id }}</h1><p>{{ $item->title??'Detail pengajuan' }}</p></div><a class="button secondary" href="{{ route('approvals.index') }}">Kembali</a></div>
<div class="detail-grid approval-detail-grid"><div>
    <section class="panel"><div class="panel-head"><div><h2>Data Pengajuan</h2><p>Informasi yang menjadi dasar keputusan</p></div><x-status :status="$item->status"/></div><dl class="detail-list"><div><dt>Pemohon</dt><dd>{{ $item->applicant?->name??'-' }}</dd></div><div><dt>Institusi</dt><dd>{{ $item->institution?->name??'-' }}</dd></div><div><dt>Jenis</dt><dd>{{ $item->activityType?->name??str($task->instance->version->template->module)->title() }}</dd></div><div><dt>Tahap saat ini</dt><dd>{{ $task->step->name }}</dd></div><div class="wide"><dt>Judul</dt><dd>{{ $item->title??'-' }}</dd></div><div class="wide"><dt>Tujuan / Keterangan</dt><dd>{{ $item->purpose??$item->description??'-' }}</dd></div></dl></section>
    <section class="panel"><div class="panel-head"><div><h2>Jejak Persetujuan</h2><p>Seluruh tindakan tercatat permanen</p></div></div><div class="approval-timeline">@foreach($task->instance->version->steps as $step)@php($flowTask=$task->instance->tasks->sortByDesc('id')->firstWhere('workflow_step_id',$step->id))<div class="{{ $flowTask?->status??'queued' }}"><i>{{ $flowTask?->status==='approved'?'✓':($flowTask?->status==='pending'?'…':($flowTask?'!':'·')) }}</i><div><b>{{ $step->name }}</b><small>{{ !$flowTask?'Belum dimulai':($flowTask->status==='pending'?'Menunggu tindakan':str($flowTask->status)->replace('_',' ')->title()) }}</small>@foreach($task->instance->tasks->where('workflow_step_id',$step->id)->flatMap->actions->sortBy('id') as $action)<p><strong>{{ $action->actor_name }}</strong> · {{ $action->actor_position }}<br>{{ $action->acted_at->translatedFormat('d F Y, H:i') }}@if($action->note)<br>Catatan: {{ $action->note }}@endif @if($action->metadata['attachment_path']??null)<br><a class="mini-link" href="{{ route('approvals.attachments',[$action->workflow_task_id,$action]) }}">Surat keterangan / rekomendasi</a>@endif</p>@endforeach</div></div>@endforeach</div></section>
</div><aside>
    <section class="panel accent"><span class="eyebrow">Tugas Anda</span><h2>{{ $task->step->name }}</h2><p class="muted-copy">{{ str($task->step->action_type)->replace('_',' ')->title() }}@if($task->due_at) · batas {{ $task->due_at->translatedFormat('d M Y H:i') }}@endif</p>
    @if($canAct)<form id="tindakan" method="post" enctype="multipart/form-data" action="{{ route('approvals.act',$task) }}" class="approval-action-form">@csrf
    <label>Surat keterangan / rekomendasi (opsional)<input type="file" name="decision_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></label>
    @if($task->step->conditions['creates_invoice'] ?? false)
    <label>Nominal tagihan (Rp)<input type="number" name="invoice_amount" min="1" step="1" value="{{ old('invoice_amount') }}" placeholder="Tetapkan sesuai tarif yang berlaku"></label>
    <p>Setujui setelah administrasi lengkap. Invoice diterbitkan dengan nominal ini, lalu pemohon membayar dan mengunggah bukti.</p>
    <p><a class="mini-link" href="{{ route('tariffs.index') }}" target="_blank" rel="noopener">Lihat acuan daftar tarif</a></p>
    @endif
    <label>Catatan keputusan<textarea name="note" rows="5" placeholder="Tuliskan pertimbangan, terutama untuk perbaikan atau penolakan."></textarea></label><button class="button wide" name="action" value="approve">Setujui</button>@if($task->step->allow_revision)<button class="button secondary wide" name="action" value="revision">Minta Perbaikan</button>@endif @if($task->step->allow_reject)<button class="button danger-button wide" name="action" value="reject">Tolak</button>@endif</form>@else<div class="alert success">Tugas ini sudah ditindaklanjuti.</div>@endif
    @if($canAct && (($task->step->assignee_value==='executive-approver' && !($task->step->conditions['managed']??false)) || ($task->step->conditions['allow_pending']??false)))
        <form method="post" action="{{ route('approvals.act',$task) }}" class="approval-action-form">@csrf
            <label>Keterangan pending<textarea name="note" rows="3" required placeholder="Jelaskan alasan pengajuan masih pending."></textarea></label>
            <button class="button secondary wide" name="action" value="pending">Tandai Pending</button>
        </form>
    @endif

@if($canAct && $task->step->behavior() === 'disposition')
    <template id="archivist-disposition-form">
        <form id="tindakan" method="post" action="{{ route('approvals.act', $task) }}" class="approval-action-form">
            @csrf
            <label>Penerima disposisi
                <select name="recipient_id" required>
                    <option value="">Pilih penerima tahap berikutnya</option>
                    @foreach($executiveRecipients as $recipient)
                        <option value="{{ $recipient->id }}">{{ $recipient->position ?: $recipient->roles->pluck('name')->join(', ') }} — {{ $recipient->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Catatan disposisi <textarea name="note" rows="4" placeholder="Tuliskan keterangan disposisi (opsional)."></textarea></label>
            <button class="button wide" name="action" value="approve">Kirim Disposisi</button>
        </form>
    </template>
    @push('scripts')
        <script>
            const dispositionTemplate = document.querySelector('#archivist-disposition-form');
            const defaultApprovalForm = document.querySelector('#tindakan');
            if (dispositionTemplate && defaultApprovalForm) {
                defaultApprovalForm.replaceWith(dispositionTemplate.content.cloneNode(true));
            }
        </script>
    @endpush
@endif
    </section>
</aside></div>
@endsection
