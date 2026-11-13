@extends('layouts.app')
@section('title','Detail Koordinasi')
@section('content')
@php($task = $coordination)
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Detail Koordinasi</h1><p>{{ $task->application->number }} — {{ $task->application->title }}</p></div><a class="button secondary" href="{{ route('kamkordik.coordinations.index') }}">Kembali</a></div>
<section class="panel coordination-card">
    <div class="coordination-card-head"><div><b>{{ $task->application->number }} — {{ $task->application->title }}</b><small>{{ $task->application->applicant->name }} · {{ $task->application->activityType->name }} · dikirim oleh {{ $task->sender->name }}</small></div><span class="badge {{ in_array($task->status,['handover_completed','completed'])?'green':($task->status==='not_ready'?'red':'') }}">{{ ['pending'=>'Menunggu tindakan','needs_information'=>'Perlu data/kontak tambahan','scheduled'=>'Jadwal ditetapkan','handover_completed'=>'Serah terima mahasiswa praktik/magang','completed'=>'Koordinasi selesai','needs_revision'=>'Perlu tindak lanjut','not_ready'=>'Belum siap ditempatkan'][$task->status] }}</span></div>
    <p class="coordination-note">{{ $task->note }}</p>
    @if($task->status==='handover_completed' || $task->status==='completed')
        <div class="coordination-summary"><b>Koordinasi selesai</b>@if($task->scheduled_at)<span>Jadwal: {{ $task->scheduled_at->translatedFormat('d M Y · H:i') }} WIB</span>@endif @if($task->location_or_meeting_link)<span>Lokasi/media: {{ $task->location_or_meeting_link }}</span>@endif @if($task->response)<span>Catatan: {{ $task->response }}</span>@endif</div>
    @else
        <form method="post" action="{{ route('kamkordik.coordinations.respond',$task) }}" class="coordination-form open">@csrf
            <div class="form-grid"><label>Status koordinasi<select name="status" required><option value="needs_information" @selected($task->status==='needs_information')>Perlu data/kontak tambahan</option><option value="scheduled" @selected($task->status==='scheduled')>Jadwal persamaan persepsi ditetapkan</option><option value="handover_completed">Serah Terima Mahasiswa Praktik/Magang</option><option value="not_ready" @selected($task->status==='not_ready')>Belum siap ditempatkan</option></select></label><label>Waktu persamaan persepsi<input type="datetime-local" name="scheduled_at" value="{{ $task->scheduled_at?->format('Y-m-d\\TH:i') }}"></label><label>Kontak institusi / mahasiswa<input name="institution_contact" value="{{ $task->institution_contact }}" placeholder="Nama PIC atau mahasiswa mandiri"></label><label>Nomor kontak<input name="institution_contact_phone" value="{{ $task->institution_contact_phone }}" placeholder="08…"></label><label>Lokasi atau tautan pertemuan<input name="location_or_meeting_link" value="{{ $task->location_or_meeting_link }}" placeholder="Ruang rapat / tautan daring"></label><label>PIC rumah sakit / KAMKORDIK<input name="hospital_pic" value="{{ $task->hospital_pic }}" placeholder="Nama PIC"></label><label class="full">Instruksi untuk pemohon<textarea name="applicant_instruction" placeholder="Apa yang perlu dibawa atau dilakukan sebelum pertemuan">{{ $task->applicant_instruction }}</textarea></label><label class="full">Catatan hasil koordinasi<textarea name="response" required placeholder="Ringkasan komunikasi, kesepakatan, atau alasan penundaan">{{ $task->response }}</textarea></label></div>
            <label class="coordination-notify"><input type="checkbox" name="notify_applicant" value="1" checked> Kirim pembaruan ini kepada pemohon</label><button class="button purple-bg">Simpan Pembaruan Koordinasi</button>
        </form>
    @endif
</section>
@endsection

@if($task->status === 'scheduled')
    <style>.coordination-form.open{display:none!important}</style>
    <template id="handover-stage-form">
        <section class="coordination-handover-stage">
            <span class="eyebrow purple-text">Tahap Berikutnya</span>
            <h2>Serah Terima Mahasiswa Praktik/Magang</h2>
            <p>Persamaan persepsi telah selesai. Catat serah terima untuk melanjutkan pengajuan ke tahap Selesai.</p>
            <form method="post" action="{{ route('kamkordik.coordinations.respond', $task) }}" class="stack">
                @csrf
                <input type="hidden" name="status" value="handover_completed">
                <label>Catatan serah terima<textarea name="response" required rows="4" placeholder="Catat pelaksanaan serah terima mahasiswa praktik/magang."></textarea></label>
                <label class="coordination-notify"><input type="checkbox" name="notify_applicant" value="1" checked> Kirim pembaruan ini kepada pemohon</label>
                <button class="button purple-bg">Catat Serah Terima</button>
            </form>
        </section>
    </template>
@endif

@push('scripts')
    <script>
        document.querySelector('.coordination-form option[value="handover_completed"]')?.remove();
        const handoverStage = document.querySelector('#handover-stage-form');
        if (handoverStage) {
            document.querySelector('.coordination-card')?.append(handoverStage.content.cloneNode(true));
        }
    </script>
@endpush
