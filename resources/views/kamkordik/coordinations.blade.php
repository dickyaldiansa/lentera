@extends('layouts.app')
@section('title','Koordinasi Diklat')
@section('content')
@if(false)
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Koordinasi Pengajuan Diklat</h1><p>Permintaan koordinasi dari petugas Diklat yang ditugaskan kepada Anda.</p></div></div>
<div class="module-tabs"><a class="{{ $status==='pending'?'active':'' }}" href="{{ route('kamkordik.coordinations.index',['status'=>'pending']) }}">Menunggu Tanggapan <span>{{ $counts['pending']??0 }}</span></a><a class="{{ $status==='completed'?'active':'' }}" href="{{ route('kamkordik.coordinations.index',['status'=>'completed']) }}">Selesai <span>{{ $counts['completed']??0 }}</span></a><a class="{{ $status==='needs_revision'?'active':'' }}" href="{{ route('kamkordik.coordinations.index',['status'=>'needs_revision']) }}">Perlu Tindak Lanjut <span>{{ $counts['needs_revision']??0 }}</span></a></div>
<section class="panel"><div class="panel-head"><div><h2>Permintaan Koordinasi</h2><p>Tanggapi setiap permintaan agar Diklat dapat melanjutkan proses.</p></div></div><div class="documents">@forelse($tasks as $task)<div style="display:block"><div class="head-actions"><div><b>{{ $task->application->number }} — {{ $task->application->title }}</b><small>{{ $task->application->applicant->name }} · {{ $task->application->activityType->name }} · dikirim oleh {{ $task->sender->name }}</small></div><x-status :status="$task->status" :label="$task->status==='pending'?'Menunggu tanggapan':($task->status==='completed'?'Selesai':'Perlu tindak lanjut')"/></div><p style="margin:12px 0">{{ $task->note }}</p>@if($task->status==='pending')<form method="post" action="{{ route('kamkordik.coordinations.respond',$task) }}" class="stack">@csrf<select name="status" required><option value="completed">Koordinasi selesai</option><option value="needs_revision">Perlu tindak lanjut Diklat</option></select><textarea name="response" required placeholder="Tulis hasil koordinasi atau rekomendasi untuk Diklat"></textarea><button class="button purple-bg">Kirim Tanggapan</button></form>@else<div class="alert {{ $task->status==='completed'?'success':'error' }}">{{ $task->response }}</div>@endif</div>@empty<div class="empty">Belum ada permintaan koordinasi dari Diklat.</div>@endforelse</div>{{ $tasks->links() }}</section>
@endif
@php($labels=['pending'=>'Menunggu tindakan','needs_information'=>'Perlu data tambahan','scheduled'=>'Jadwal ditetapkan','handover_completed'=>'Serah terima terlaksana','completed'=>'Koordinasi selesai','needs_revision'=>'Perlu tindak lanjut','not_ready'=>'Belum siap ditempatkan'])
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Koordinasi Pengajuan Diklat</h1><p>Hubungi institusi pendidikan atau mahasiswa magang mandiri untuk menyepakati jadwal persamaan persepsi dan serah terima.</p></div></div>
<div class="module-tabs"><a class="{{ $status==='active'?'active':'' }}" href="{{ route('kamkordik.coordinations.index',['status'=>'active']) }}">Perlu Ditindaklanjuti <span>{{ $counts['active'] }}</span></a><a class="{{ $status==='completed'?'active':'' }}" href="{{ route('kamkordik.coordinations.index',['status'=>'completed']) }}">Siap Penempatan <span>{{ $counts['completed'] }}</span></a><a class="{{ $status==='not_ready'?'active':'' }}" href="{{ route('kamkordik.coordinations.index',['status'=>'not_ready']) }}">Belum Siap <span>{{ $counts['not_ready'] }}</span></a></div>
<section class="panel"><div class="panel-head"><div><h2>Permintaan Koordinasi</h2><p>Catat jadwal, PIC, dan instruksi agar pemohon mendapatkan informasi yang sama.</p></div></div><div class="coordination-list">
@forelse($tasks as $task)
    <article class="coordination-card" data-status="{{ $task->status }}">
        <div class="coordination-card-head"><div><b>{{ $task->application->number }} — {{ $task->application->title }}</b><small>{{ $task->application->applicant->name }} · {{ $task->application->activityType->name }} · dikirim oleh {{ $task->sender->name }}</small></div><span class="badge {{ in_array($task->status,['handover_completed','completed'])?'green':($task->status==='not_ready'?'red':'') }}">{{ $labels[$task->status] }}</span></div>
        <div class="coordination-stage">{{ $task->status==='handover_completed'?'Serah Terima':(str_contains(strtolower($task->note),'persamaan persepsi')?'Persamaan Persepsi':'Koordinasi Awal') }}</div><p class="coordination-note">{{ $task->note }}</p><small class="coordination-age">Masuk {{ $task->created_at->diffForHumans() }}</small><div class="coordination-card-actions"><a class="button purple-bg" href="{{ route('kamkordik.coordinations.show',$task) }}">Buka Koordinasi <span aria-hidden="true">→</span></a></div>
        @if($task->status==='handover_completed' || $task->status==='completed')
            <div class="coordination-summary"><b>Koordinasi selesai</b>@if($task->scheduled_at)<span>Jadwal: {{ $task->scheduled_at->translatedFormat('d M Y · H:i') }} WIB</span>@endif @if($task->location_or_meeting_link)<span>Lokasi/media: {{ $task->location_or_meeting_link }}</span>@endif @if($task->response)<span>Catatan: {{ $task->response }}</span>@endif</div>
        @elseif(false)
            <form method="post" action="{{ route('kamkordik.coordinations.respond',$task) }}" class="coordination-form">@csrf
                <div class="form-grid"><label>Status koordinasi<select name="status" required><option value="needs_information" @selected($task->status==='needs_information')>Perlu data/kontak tambahan</option><option value="scheduled" @selected($task->status==='scheduled')>Jadwal persamaan persepsi ditetapkan</option><option value="handover_completed">Serah terima terlaksana — siap penempatan</option><option value="not_ready" @selected($task->status==='not_ready')>Belum siap ditempatkan</option></select></label><label>Waktu persamaan persepsi<input type="datetime-local" name="scheduled_at" value="{{ $task->scheduled_at?->format('Y-m-d\\TH:i') }}"></label><label>Kontak institusi / mahasiswa<input name="institution_contact" value="{{ $task->institution_contact }}" placeholder="Nama PIC atau mahasiswa mandiri"></label><label>Nomor kontak<input name="institution_contact_phone" value="{{ $task->institution_contact_phone }}" placeholder="08…"></label><label>Lokasi atau tautan pertemuan<input name="location_or_meeting_link" value="{{ $task->location_or_meeting_link }}" placeholder="Ruang rapat / tautan daring"></label><label>PIC rumah sakit / KAMKORDIK<input name="hospital_pic" value="{{ $task->hospital_pic }}" placeholder="Nama PIC"></label><label class="full">Instruksi untuk pemohon<textarea name="applicant_instruction" placeholder="Apa yang perlu dibawa atau dilakukan sebelum pertemuan">{{ $task->applicant_instruction }}</textarea></label><label class="full">Catatan hasil koordinasi<textarea name="response" required placeholder="Ringkasan komunikasi, kesepakatan, atau alasan penundaan">{{ $task->response }}</textarea></label></div>
                <label class="coordination-notify"><input type="checkbox" name="notify_applicant" value="1" checked> Kirim pembaruan ini kepada pemohon</label><button class="button purple-bg">Simpan Pembaruan Koordinasi</button>
            </form>
        @endif
    </article>
@empty<div class="empty">Tidak ada koordinasi pada kategori ini.</div>@endforelse
</div><x-pagination :paginator="$tasks" label="koordinasi" /></section>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('option[value="handover_completed"]').forEach((option) => {
            option.textContent = 'Serah Terima Mahasiswa Praktik/Magang';
        });
        document.querySelectorAll('.coordination-card .coordination-form').forEach((form) => {
            const actions = document.createElement('div');
            actions.className = 'coordination-card-actions';
            const link = document.createElement('a');
            link.className = 'button secondary';
            link.textContent = 'Aksi';
            link.href = form.action.replace(/\/tanggapi$/, '');
            actions.append(link);
            form.before(actions);
        });
    </script>
@endpush
