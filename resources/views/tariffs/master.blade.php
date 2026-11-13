@extends('layouts.app')
@section('title','Tarif Layanan')
@section('content')
<div class="page-head"><div><span class="eyebrow">Master Data</span><h1>Tarif Layanan</h1><p>Kelola nominal, satuan, kelompok peserta, dan tarif yang ditampilkan kepada pemohon.</p></div><div class="head-actions"><a class="button secondary" href="{{ route('master.index') }}">Kembali</a><a class="button" href="{{ route('master.create','tariffs') }}">Tambah Tarif</a></div></div>
<section class="panel"><p>Perubahan tarif berlaku sebagai acuan tagihan berikutnya. Invoice yang sudah terbit tetap menyimpan nominalnya.</p>
<div class="table-wrap"><table><thead><tr><th>Kelompok / Layanan</th><th>Peserta</th><th>Satuan</th><th>Nominal</th><th>Publikasi</th><th>Aksi</th></tr></thead><tbody>
@forelse($tariffs as $tariff)<tr>
<td><b>{{ $tariff->category_label }}</b><small>{{ $tariff->name }}</small>@if($tariff->activityType)<small>Khusus {{ $tariff->activityType->name }}</small>@endif @if($tariff->description)<small>{{ $tariff->description }}</small>@endif</td>
<td>{{ $tariff->participant_category ?: 'Semua peserta' }}@if($tariff->min_participants || $tariff->max_participants)<small>{{ $tariff->min_participants ?? '—' }}–{{ $tariff->max_participants ?? '—' }} peserta</small>@endif</td>
<td>{{ $tariff->unit_label }}</td><td>Rp{{ number_format($tariff->amount,0,',','.') }}</td>
<td><x-status :status="$tariff->active?'active':'inactive'"/><small>{{ $tariff->published?'Ditampilkan kepada pemohon':'Internal saja' }}</small></td>
<td><div class="table-actions"><a class="mini-link" href="{{ route('master.edit',['tariffs',$tariff]) }}">Edit</a><form method="post" action="{{ route('master.toggle',['tariffs',$tariff]) }}">@csrf @method('PATCH')<button class="mini-link" type="submit">{{ $tariff->active?'Nonaktifkan':'Aktifkan' }}</button></form></div></td>
</tr>@empty<tr><td colspan="6" class="empty">Belum ada tarif.</td></tr>@endforelse
</tbody></table></div><x-pagination :paginator="$tariffs" label="tarif"/>
</section>
@endsection
