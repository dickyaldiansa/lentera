@extends('layouts.app') @section('title',$application->exists?'Edit Pengajuan':'Pengajuan Baru') @section('content')
<div class="page-head">
    <div>
        <span class="eyebrow blue-text">Diklat Eksternal</span>
        <h1>{{ $application->exists ? 'Edit Pengajuan' : 'Buat Pengajuan Baru' }}</h1>
        <p>Isi detail kegiatan dan lampirkan berkas permohonan bila sudah tersedia.</p>
    </div>
</div>
<form class="panel form" method="post" enctype="multipart/form-data" action="{{ $application->exists ? route('applications.update', $application) : route('applications.store') }}">
    @csrf @if($application->exists) @method('put') @endif
    <div class="section-title"><b>Informasi Kegiatan</b><span>01</span></div>
    <div class="form-grid">
        <label>Jenis kegiatan *<select id="activity-type" name="activity_type_id" required><option value="">Pilih jenis kegiatan</option>@foreach($types as $type)<option value="{{ $type->id }}" data-activity-name="{{ $type->name }}" @selected(old('activity_type_id', $application->activity_type_id) == $type->id)>{{ $type->name }}</option>@endforeach</select></label>
        <label>Judul kegiatan *<input name="title" value="{{ old('title', $application->title) }}" required></label>
        <label>Institusi<input name="institution_name" value="{{ old('institution_name', $application->institution?->name) }}" list="institution-suggestions" autocomplete="organization" placeholder="Ketik nama institusi"><datalist id="institution-suggestions">@foreach($institutions as $institution)<option value="{{ $institution->name }}">{{ $institution->type }}</option>@endforeach</datalist></label>
        <label>Unit tujuan<select name="department_id"><option value="">Pilih unit</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $application->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></label>
        <label>Penanggung jawab<input name="person_in_charge" value="{{ old('person_in_charge', $application->person_in_charge) }}"></label>
        <label>Jumlah peserta *<input type="number" min="1" name="participant_count" value="{{ old('participant_count', $application->participant_count ?? 1) }}" required></label>
        <label>Tanggal mulai *<input type="date" name="start_date" value="{{ old('start_date', $application->start_date?->format('Y-m-d')) }}" required></label>
        <label>Tanggal selesai *<input type="date" name="end_date" value="{{ old('end_date', $application->end_date?->format('Y-m-d')) }}" required></label>
        @include('tariffs.application')
        <label class="full">Tujuan kegiatan<textarea name="purpose" rows="3">{{ old('purpose', $application->purpose) }}</textarea></label>
        <label class="full">Deskripsi<textarea name="description" rows="4">{{ old('description', $application->description) }}</textarea></label>
        <div class="full upload-field">
            <label for="application-documents">Berkas permohonan <span class="optional">(opsional)</span></label>
            <input id="application-documents" type="file" name="documents[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" multiple>
            <small>Lampirkan surat permohonan atau berkas pendukung. Maksimal 20 berkas, 10 MB per berkas; format PDF, Word, Excel, JPG, JPEG, atau PNG.</small>
            <section id="document-requirements" class="upload-requirements" aria-labelledby="document-requirements-title" hidden>
                <b id="document-requirements-title">Persyaratan Dokumen</b>
                <ol id="document-requirements-list"></ol>
            </section>
            @error('documents')<small class="error-text">{{ $message }}</small>@enderror
            @error('documents.*')<small class="error-text">{{ $message }}</small>@enderror
        </div>
    </div>
    <div class="form-actions"><a class="button secondary" href="{{ route('applications.index') }}">Batal</a><button class="button">Simpan Pengajuan</button></div>
</form>
@endsection

@push('scripts')
<script>
(() => {
    @php
    $configuredRequirements=\App\Models\WorkflowTemplate::with('publishedVersion.steps')->where('module','diklat')->where('active',true)->whereNotNull('activity_type_id')->get()->mapWithKeys(fn($t)=>[$t->activity_type_id=>array_values(array_filter(array_map('trim',preg_split('/\R/',$t->publishedVersion?->steps->first()?->conditions['document_requirements']??''))))]);
    @endphp
    const configuredRequirements = {{ \Illuminate\Support\Js::from($configuredRequirements) }};
    const requirements = {
        preResearch: [
            'Surat permohonan yang ditujukan ke Direktur RSUD Embung Fatimah Kota Batam',
            'Daftar/tabel data yang dibutuhkan',
            'Kuesioner pra penelitian, jika ada',
        ],
        research: [
            'Surat permohonan izin penelitian dari Institusi Pendidikan yang ditujukan ke Direktur RSUD Embung Fatimah Kota Batam',
            'Surat pelaporan Easy Batam (Rekomendasi Penelitian)', 'CV', 'Proposal',
            'Informed Consent, jika ada', 'Kuesioner, jika ada', 'Form observasi, jika ada',
            'Ethical Clearance dari kampus, jika ada', 'PPT',
        ],
        clinicalEducation: [
            'Surat permohonan praktik/magang dari institusi pendidikan pemohon',
            'Panduan kompetensi pendidikan dari institusi pendidikan pemohon', 'Log book',
        ],
        visit: ['Surat permohonan studi banding'],
        observation: [
            'Surat permohonan observasi lapangan dari institusi pendidikan pemohon',
            'Panduan/pedoman/lembar observasi',
        ],
    };

    const select = document.getElementById('activity-type');
    const container = document.getElementById('document-requirements');
    const list = document.getElementById('document-requirements-list');

    const categoryFor = (name) => {
        const normalized = name.toLocaleLowerCase('id-ID');
        if (normalized.includes('penelitian') && !normalized.includes('pra')) return 'research';
        if (normalized.includes('pra penelitian') || normalized.includes('studi pendahuluan') || normalized.includes('pengambilan data')) return 'preResearch';
        if (normalized.includes('praktik') || normalized.includes('magang') || normalized.includes('pendidikan klinik')) return 'clinicalEducation';
        if (normalized.includes('studi banding') || normalized.includes('kunjungan')) return 'visit';
        if (normalized.includes('observasi')) return 'observation';
        return null;
    };

    const renderRequirements = () => {
        const name = select.options[select.selectedIndex]?.dataset.activityName || '';
        const custom = configuredRequirements[select.value];
        const items = custom?.length ? custom : requirements[categoryFor(name)];
        list.replaceChildren();
        container.hidden = !items;
        if (!items) return;
        items.forEach((item) => {
            const entry = document.createElement('li');
            entry.textContent = item;
            list.appendChild(entry);
        });
    };

    select.addEventListener('change', renderRequirements);
    renderRequirements();
    const renderTariffs = () => {
        let shown = false;
        document.querySelectorAll('[data-activity-tariffs]').forEach(section => {
            const matches = section.dataset.activityTariffs === select.value;
            section.hidden = !matches;
            shown = shown || matches;
        });
        document.getElementById('activity-tariff-panel').hidden = !shown;
    };
    select.addEventListener('change', renderTariffs);
    renderTariffs();
})();
</script>
@endpush
