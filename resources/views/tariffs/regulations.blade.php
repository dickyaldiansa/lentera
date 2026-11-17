<section class="panel" id="dasar-hukum-tarif">
    <div class="panel-head"><div><h2>{{ $manage?'Dokumen Dasar Hukum Tarif':'Dasar Hukum Tarif' }}</h2><p>Peraturan yang menjadi acuan tarif layanan beserta arsip dokumen sebelumnya.</p></div>
        @if($manage)<a class="button" href="{{ route('tariff-regulations.create') }}">Tambah Dokumen</a>@endif
    </div>
    <div class="table-wrap"><table><thead><tr><th>Peraturan</th><th>Berlaku mulai</th><th>Status</th><th>Dokumen</th></tr></thead><tbody>
        @forelse($regulations as $regulation)
        <tr><td><b>{{ $regulation->title }}</b><small>Nomor {{ $regulation->number }} Tahun {{ $regulation->year }}</small></td>
            <td>{{ $regulation->effective_from?->format('d/m/Y')??'—' }}</td>
            <td><span class="pill">{{ $regulation->is_current?'Berlaku':'Arsip' }}</span></td>
            <td><div class="table-actions">
                <a class="mini-link" href="{{ route('tariff-regulations.document',$regulation) }}" target="_blank" rel="noopener">Lihat PDF</a>
                <a class="mini-link" href="{{ route('tariff-regulations.document',[$regulation,'download'=>1]) }}">Unduh PDF</a>
                @if($manage)<a class="mini-link" href="{{ route('tariff-regulations.edit',$regulation) }}">Edit</a>@endif
            </div></td></tr>
        @empty<tr><td colspan="4" class="empty">Dokumen dasar hukum tarif belum ditambahkan.</td></tr>@endforelse
    </tbody></table></div>
</section>
