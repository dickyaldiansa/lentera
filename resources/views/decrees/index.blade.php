@extends('layouts.app')
@section('title','Manajemen SK')
@section('content')
@php($canManage=auth()->user()->canDo('sk.manage'))
<div class="page-head">
    <div><span class="eyebrow">Arsip Dokumen Resmi</span><h1>Manajemen SK</h1><p>Kelola Surat Keputusan Diklat, KAMKORDIK, dan Komite Etik secara terpusat.</p></div>
    @if($canManage)<a class="button" href="{{ route('decrees.create') }}">＋ Tambah SK</a>@endif
</div>
<section class="panel">
    <form class="filters" method="get">
        <input name="q" value="{{ request('q') }}" placeholder="Cari nomor atau judul SK...">
        <select name="category"><option value="">Semua jenis</option>@foreach($categories as $value=>$label)<option value="{{ $value }}" @selected(request('category')===$value)>{{ $label }}</option>@endforeach</select>
        <select name="year"><option value="">Semua tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string)request('year')===(string)$year)>{{ $year }}</option>@endforeach</select>
        <select name="status"><option value="">Semua status</option>@foreach(['draft'=>'Draf','active'=>'Aktif','expired'=>'Kedaluwarsa','revoked'=>'Dicabut'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select>
        <button class="button secondary">Filter</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Nomor / Judul</th><th>Jenis</th><th>Tahun</th><th>Masa Berlaku</th><th>Pengelola</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    @forelse($decrees as $decree)
        <tr>
            <td><b>{{ $decree->number }}</b><small>{{ $decree->title }}</small></td>
            <td>{{ $categories[$decree->category] ?? str($decree->category)->title() }}</td>
            <td><b>{{ $decree->year }}</b><small>{{ $decree->issued_at?->format('d/m/Y') ?: 'Tanggal belum diisi' }}</small></td>
            <td>{{ $decree->effective_from?->format('d/m/Y') ?: '—' }}<small>s.d. {{ $decree->effective_until?->format('d/m/Y') ?: 'tidak dibatasi' }}</small></td>
            <td>{{ $decree->manager?->name ?: 'Belum ditetapkan' }}<small>{{ $decree->signatory ? 'TTD: '.$decree->signatory : 'Penandatangan belum diisi' }}</small></td>
            <td><x-status :status="$decree->status"/></td>
            <td><div class="table-actions">
                @if($decree->file_path)<a class="mini-link" href="{{ route('decrees.download',$decree) }}">Unduh</a>@endif
                @if($canManage)
                    <a class="mini-link" href="{{ route('decrees.edit',$decree) }}">Edit</a>
                    <form method="post" action="{{ route('decrees.status',$decree) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $decree->status==='active'?'revoked':'active' }}"><button class="mini-link {{ $decree->status==='active'?'danger':'' }}">{{ $decree->status==='active'?'Cabut':'Aktifkan' }}</button></form>
                @endif
            </div></td>
        </tr>
    @empty<tr><td colspan="7" class="empty">Belum ada arsip SK pada lingkup akses Anda.</td></tr>@endforelse
    </tbody></table></div>
    <x-pagination :paginator="$decrees" label="surat keputusan" />
</section>
@endsection
