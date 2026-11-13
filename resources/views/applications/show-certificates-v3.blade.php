@extends('layouts.app')
@section('title',$application->number)
@section('content')
<div class="page-head"><div><span class="eyebrow blue-text">{{ $application->number }}</span><h1>{{ $application->title }}</h1><p>{{ $application->activityType->name }} · {{ $application->institution?->name??'Individu' }}</p></div><x-status :status="$application->status" :label="$followUpLabels[$application->status]??null"/></div>
<div class="detail-grid"><div>
<section class="panel"><div class="panel-head"><div><h2>Detail Kegiatan</h2><p>Informasi pengajuan utama</p></div></div><dl class="detail-list"><div><dt>Pemohon</dt><dd>{{ $application->applicant->name }}</dd></div><div><dt>Penanggung jawab</dt><dd>{{ $application->person_in_charge?:'-' }}</dd></div><div><dt>Unit tujuan</dt><dd>{{ $application->department?->name?:'-' }}</dd></div><div><dt>Jumlah peserta</dt><dd>{{ $application->participant_count }} orang</dd></div><div><dt>Periode</dt><dd>{{ $application->start_date->translatedFormat('d F Y') }} – {{ $application->end_date->translatedFormat('d F Y') }}</dd></div></dl></section>
<section class="panel"><div class="panel-head"><div><h2>Berkas Pengajuan</h2><p>Berkas yang diunggah oleh pemohon.</p></div></div><div class="documents">@forelse($application->documents as $document)<div><span class="file-icon">{{ strtoupper(pathinfo($document->name,PATHINFO_EXTENSION) ?: 'FILE') }}</span><div><b>{{ $document->name }}</b><small>{{ number_format($document->size/1024,1) }} KB · diunggah {{ $document->created_at->format('d M Y H:i') }}</small></div><a href="{{ route('documents.download',$document) }}">Unduh</a></div>@empty<div class="empty">Pemohon belum mengunggah berkas.</div>@endforelse</div></section>
<section class="panel"><div class="panel-head"><div><h2>Peserta Kegiatan</h2><p>Daftar peserta menjadi dasar penerbitan sertifikat.</p></div><span class="badge">{{ $application->participants->count() }}/{{ $application->participant_count }}</span></div><div class="documents">
@forelse($application->participants as $participant)
<div><span class="file-icon">{{ strtoupper(substr($participant->name,0,1)) }}</span><div><b>{{ $participant->name }}</b><small>{{ $participant->email?:($participant->identity_number?:'Identitas belum diisi') }}</small></div>
@if($participant->certificate)
<a href="{{ route('certificates.show',$participant->certificate) }}">Lihat sertifikat</a>
@else
<span class="badge">Belum diterbitkan</span>@if(auth()->user()->canDo('diklat.verify'))<details class="participant-actions"><summary>Edit</summary><form method="post" action="{{ route('participants.update',$participant) }}" class="participant-edit">@csrf @method('PUT')<input name="name" value="{{ $participant->name }}" required><input name="email" type="email" value="{{ $participant->email }}" placeholder="Email"><input name="identity_number" value="{{ $participant->identity_number }}" placeholder="Nomor identitas"><input name="phone" value="{{ $participant->phone }}" placeholder="Telepon"><button class="button secondary">Simpan</button></form></details><form method="post" action="{{ route('participants.destroy',$participant) }}" onsubmit="return confirm('Hapus peserta ini?')">@csrf @method('DELETE')<button class="mini-link danger" type="submit">Hapus</button></form>@endif
@endif
</div>
@empty
<div class="empty">Belum ada peserta.</div>
@endforelse
</div>
@if(!auth()->user()->hasRole('applicant') || !in_array($application->status,['completed','closed']))
@if($application->participants->count() < $application->participant_count)
@if($application->participant_count===1)<p class="form-hint">Pemohon otomatis dicatat sebagai peserta kegiatan.</p>@endif
<form method="post" action="{{ route('participants.store',$application) }}" class="form-grid" style="margin-top:16px">@csrf<label>Nama peserta<input name="name" required></label><label>Email<input name="email" type="email"></label><label>Nomor identitas<input name="identity_number"></label><label>Telepon<input name="phone"></label><div class="form-actions"><button class="button secondary">Tambah Peserta</button></div></form>
@else <div class="alert success">Daftar peserta sudah lengkap.</div>@endif
@endif
</section></div><aside>
@if(auth()->user()->hasRole('applicant') && in_array($application->status,['draft','revision_required']))
<section class="panel accent"><h2>{{ $application->status === 'draft' ? 'Kirim Pengajuan' : 'Lengkapi Perbaikan' }}</h2>
    <a class="button secondary" href="{{ route('applications.edit',$application) }}">Edit Pengajuan</a>
    <form method="post" enctype="multipart/form-data" action="{{ route('applications.documents',$application) }}" class="stack">@csrf
        <label>Berkas tambahan / perbaikan<input type="file" name="documents[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" multiple required></label>
        <button class="button secondary">Unggah Berkas</button>
    </form>
    <form method="post" action="{{ route('applications.submit',$application) }}">@csrf<button class="button">{{ $application->status === 'draft' ? 'Kirim Pengajuan' : 'Kirim Ulang Perbaikan' }}</button></form>
</section>
@endif
@php($latestCoordination=$application->coordinationTasks->sortByDesc('created_at')->first())
@if($latestCoordination)
<section class="panel coordination-applicant-panel"><div class="panel-head"><div><h2>Koordinasi KAMKORDIK</h2><p>Informasi persamaan persepsi dan serah terima.</p></div><span class="badge {{ in_array($latestCoordination->status,['handover_completed','completed'])?'green':($latestCoordination->status==='not_ready'?'red':'') }}">{{ ['pending'=>'Menunggu koordinasi','needs_information'=>'Perlu data tambahan','scheduled'=>'Jadwal ditetapkan','handover_completed'=>'Siap penempatan','completed'=>'Koordinasi selesai','needs_revision'=>'Perlu tindak lanjut','not_ready'=>'Belum siap ditempatkan'][$latestCoordination->status]??'Koordinasi' }}</span></div>
@if($latestCoordination->scheduled_at)<p><b>Jadwal:</b> {{ $latestCoordination->scheduled_at->translatedFormat('l, d F Y · H:i') }} WIB</p>@endif
@if($latestCoordination->location_or_meeting_link)<p><b>Lokasi/media:</b> {{ $latestCoordination->location_or_meeting_link }}</p>@endif
@if($latestCoordination->hospital_pic)<p><b>PIC KAMKORDIK:</b> {{ $latestCoordination->hospital_pic }}</p>@endif
@if($latestCoordination->applicant_instruction)<div class="coordination-instruction"><b>Instruksi untuk pemohon</b><p>{{ $latestCoordination->applicant_instruction }}</p></div>@endif
@if($latestCoordination->status==='needs_information')<p class="warning"><b>Mohon lengkapi data atau kontak yang diminta KAMKORDIK.</b></p>@endif
</section>
@endif
<section class="panel"><div class="panel-head"><div><h2>Riwayat Status</h2><p>Audit proses pengajuan</p></div></div><div class="timeline">
@foreach($application->histories->reverse() as $history)
<div><i></i><b>{{ $followUpLabels[$history->to_status]??str($history->to_status)->replace('_',' ')->title() }}</b><p>{{ $history->note }}</p><small>{{ $history->created_at->format('d M Y H:i') }} · {{ $history->actor->name }}</small></div>
@endforeach
</div></section>
@if(auth()->user()->canDo('diklat.verify') || auth()->user()->hasRole('director'))<section class="panel accent"><h2>Disposisi Persetujuan</h2><p>{{ auth()->user()->hasRole('director')?'Kirim pengajuan kepada Kepala Bagian Umum untuk tindak lanjut.':'Pilih Direktur atau Wakil Direktur sebagai penerima disposisi.' }}</p><form method="post" action="{{ route('applications.dispose-director',$application) }}" class="stack">@csrf<select name="recipient_id" required><option value="">Pilih penerima disposisi</option>@foreach($dispositionRecipients as $recipient)<option value="{{ $recipient->id }}">{{ $recipient->hasRole('head-general')?'Kepala Bagian Umum':($recipient->hasRole('director')?'Direktur':'Wakil Direktur') }} — {{ $recipient->name }}</option>@endforeach</select><textarea name="note" placeholder="Catatan disposisi (opsional)"></textarea><button class="button">Kirim Disposisi</button></form></section>@endif
@if(auth()->user()->canDo('diklat.verify'))
<section class="panel accent"><h2>Koordinasi KAMKORDIK</h2><p>Kirim permintaan koordinasi kepada Admin atau Ketua KAMKORDIK tanpa mengubah alur persetujuan.</p><form method="post" action="{{ route('applications.coordinate-kamkordik',$application) }}" class="stack">@csrf<select name="recipient_id" required><option value="">Pilih penerima koordinasi</option>@foreach($kamkordikRecipients as $recipient)<option value="{{ $recipient->id }}">{{ $recipient->hasRole('kamkordik-head')?'Ketua KAMKORDIK':'Admin KAMKORDIK' }} — {{ $recipient->name }}</option>@endforeach</select><textarea name="note" required placeholder="Keterangan atau kebutuhan koordinasi"></textarea><button class="button">Kirim Koordinasi</button></form>@if($application->coordinationTasks->isNotEmpty())<div class="timeline" style="margin-top:16px">@foreach($application->coordinationTasks as $task)<div><i></i><b>{{ $task->status==='pending'?'Menunggu tanggapan KAMKORDIK':($task->status==='completed'?'Koordinasi selesai':'Perlu tindak lanjut') }}</b><p>Kepada {{ $task->recipient->name }} — {{ $task->note }}</p>@if($task->response)<p><b>Tanggapan:</b> {{ $task->response }}</p>@endif</div>@endforeach</div>@endif</section>
@endif
@unless(auth()->user()->hasRole('applicant'))
@if($application->certificateEligible())
<section class="panel accent"><h2>Sertifikat Peserta</h2><p>Terbitkan sertifikat untuk peserta yang telah menyelesaikan kegiatan.</p>
@if($application->participants->isEmpty())
<div class="empty">Tambahkan peserta terlebih dahulu.</div>
@else
<form method="post" action="{{ route('certificates.store',$application) }}" class="stack">@csrf
@foreach($application->participants as $participant)
@if(!$participant->certificate)
<label class="certificate-participant"><input type="checkbox" name="participant_ids[]" value="{{ $participant->id }}" checked><span>{{ $participant->name }}</span></label>
@endif
@endforeach
<button class="button">Terbitkan Sertifikat Terpilih</button></form>
@endif
</section>
@endif
@if((!auth()->user()->canDo('diklat.verify') || auth()->user()->canDo('diklat.approve') || auth()->user()->hasRole('super-admin') || auth()->user()->hasRole('director')) && !auth()->user()->hasRole('deputy-director'))<section class="panel accent"><h2>Tindak Lanjut</h2><form method="post" action="{{ route('applications.transition',$application) }}" class="stack">@csrf<select name="status"><option value="">Pilih tindak lanjut berikutnya</option>
@foreach(auth()->user()->hasRole('director') ? $followUps->whereIn('code',['approved','revision_required','rejected']) : $followUps as $followUp)
<option value="{{ $followUp->code }}">{{ $followUp->code==='revision_required'?'Minta Perbaikan':$followUp->label }}</option>
@endforeach
</select><textarea name="note" placeholder="Catatan untuk pemohon"></textarea><button class="button">Perbarui Status</button></form></section>@endif
@endunless
</aside></div>
@include('applications.payment')
@include('applications.service-result')
@endsection

@if($application->activityType?->name === 'Magang' || $application->usesPaymentGate() || ($application->workflowInstance?->version->steps->first()?->conditions['managed']??false))
    @push('scripts')
        <script>
            document.querySelector('form[action$="dispose-director"]')?.closest('section')?.remove();
            document.querySelector('form[action$="coordinate-kamkordik"]')?.closest('section')?.remove();
        </script>
    @endpush
@endif

@if($activeWorkflowTask)
    <template id="active-workflow-task-panel">
        <section class="panel accent">
            <h2>{{ $activeWorkflowTask->step->name }}</h2>
            <p>{{ $activeWorkflowTask->step->assignee_value === 'arsiparis' ? 'Pilih Direktur atau Wakil Direktur sebagai penerima disposisi.' : 'Tugas persetujuan ini menunggu tindakan Anda.' }}</p>
            <a class="button wide" href="{{ route('approvals.show', $activeWorkflowTask) }}">{{ $activeWorkflowTask->step->assignee_value === 'arsiparis' ? 'Buat Disposisi' : 'Buka Tugas' }}</a>
        </section>
    </template>
    @push('scripts')
        <script>
            const activeTaskPanel = document.querySelector('#active-workflow-task-panel');
            document.querySelector('.detail-grid > aside')?.prepend(activeTaskPanel.content.cloneNode(true));
        </script>
    @endpush
@endif

@if($application->workflowInstance)
    <template id="applicant-approval-trail">
        <section class="panel applicant-approval-trail">
            <div class="panel-head"><div><h2>Jejak Persetujuan</h2><p>Seluruh tindakan tercatat permanen</p></div></div>
            <div class="approval-timeline">
                @foreach($application->workflowInstance->version->steps as $step)
                    @php($flowTask = $application->workflowInstance->tasks->sortByDesc('id')->firstWhere('workflow_step_id', $step->id))
                    <div class="{{ $flowTask?->status ?? 'queued' }}">
                        <i>{{ $flowTask?->status === 'approved' ? '✓' : ($flowTask?->status === 'pending' ? '…' : ($flowTask ? '!' : '·')) }}</i>
                        <div>
                            <b>{{ $step->name }}</b>
                            <small>{{ !$flowTask ? 'Belum dimulai' : ($flowTask->status === 'pending' ? 'Menunggu tindakan' : str($flowTask->status)->replace('_', ' ')->title()) }}</small>
                            @foreach($application->workflowInstance->tasks->where('workflow_step_id',$step->id)->flatMap->actions->sortBy('id') as $action)
                                <p><strong>{{ $action->actor_name }}</strong> · {{ $action->actor_position }}<br>{{ $action->acted_at->translatedFormat('d F Y, H:i') }}@if($action->note)<br>Catatan: {{ $action->note }}@endif @if($action->metadata['attachment_path']??null)<br><a class="mini-link" href="{{ route('approvals.attachments',[$action->workflow_task_id,$action]) }}">Surat keterangan / rekomendasi</a>@endif</p>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </template>
    @push('scripts')
        <script>
            const applicantApprovalTrail = document.querySelector('#applicant-approval-trail');
            const applicationContent = document.querySelector('.detail-grid > div');
            if (applicantApprovalTrail && applicationContent) {
                applicationContent.insertBefore(applicantApprovalTrail.content.cloneNode(true), applicationContent.children[1] ?? null);
            }
        </script>
    @endpush
@endif
