<div class="table-wrap"><table><thead><tr><th>Layanan</th><th>Jenjang / Golongan Peserta</th><th>Satuan</th><th>Tarif</th></tr></thead><tbody>
@foreach($items as $tariff)<tr><td>{{ $tariff->name }}@if($tariff->description)<small>{{ $tariff->description }}</small>@endif</td>
<td>{{ $tariff->participant_category ?: 'Semua peserta' }}@if($tariff->min_participants || $tariff->max_participants)<small>{{ $tariff->min_participants ?? '—' }}–{{ $tariff->max_participants ?? '—' }} peserta</small>@endif</td>
<td>{{ $tariff->unit_label }}</td><td><b>Rp{{ number_format($tariff->amount,0,',','.') }}</b></td></tr>@endforeach
</tbody></table></div>
