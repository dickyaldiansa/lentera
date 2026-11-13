@extends('layouts.app')
@section('title','Logbook Digital')
@section('content')
<div class="page-head"><div><span class="eyebrow purple-text">KAMKORDIK</span><h1>Logbook Digital</h1><p>Catatan aktivitas klinis dengan identitas kasus anonim.</p></div></div>
<div class="module-tabs"><a href="{{ route('kamkordik.index') }}">Peserta</a><a href="{{ route('rotations.index') }}">Rotasi</a><a class="active" href="{{ route('logbooks.index') }}">Logbook</a></div>

@if(auth()->user()->canDo('logbook.create'))
<details class="panel create-drawer">
    <summary>＋ Tambah Aktivitas Logbook</summary>
    <form class="form-grid" method="post" action="{{ route('logbooks.store') }}">@csrf
        <label>Rotasi<select name="clinical_rotation_id" required>@foreach($rotations as $rotation)<option value="{{ $rotation->id }}">{{ $rotation->student->name }} — {{ $rotation->stage }}</option>@endforeach</select></label>
        <label>Tanggal<input type="date" name="activity_date" required></label><label>Ruangan<input name="room"></label><label>Jenis aktivitas<input name="activity_type" required placeholder="Observasi / tindakan / diskusi"></label><label>Nomor kasus anonim<input name="anonymous_case_number" placeholder="KASUS-001"></label><label>Kelompok umur<input name="age_group" placeholder="Dewasa"></label><label>Diagnosis<input name="diagnosis"></label><label>Peran peserta<input name="student_role"></label><label class="full">Tindakan<textarea name="procedure"></textarea></label><button class="button purple-bg">Simpan Logbook</button>
    </form>
</details>
@endif

<section class="panel"><div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Peserta</th><th>Aktivitas / Kasus</th><th>Stase</th><th>Status</th><th>Validasi</th></tr></thead><tbody>
@forelse($logbooks as $logbook)<tr><td>{{ $logbook->activity_date->format('d M Y') }}</td><td><b>{{ $logbook->student->name }}</b></td><td><b>{{ $logbook->activity_type }}</b><small>{{ $logbook->anonymous_case_number?:'Tanpa identitas pasien' }} · {{ $logbook->diagnosis }}</small></td><td>{{ $logbook->rotation->stage }}</td><td><x-status :status="$logbook->status"/></td><td>@if(auth()->user()->canDo('logbook.verify') && $logbook->status==='submitted')<form method="post" action="{{ route('logbooks.verify',$logbook) }}">@csrf<input type="hidden" name="status" value="verified"><button class="link-button">Verifikasi</button></form>@else—@endif</td></tr>@empty<tr><td colspan="6" class="empty">Belum ada entri logbook.</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$logbooks" label="logbook" /></section>
@endsection
