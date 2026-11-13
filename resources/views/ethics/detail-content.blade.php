<div class="page-head"><div><span class="eyebrow green-text">{{ $ethics->number }}</span><h1>{{ $ethics->title }}</h1><p>{{ $ethics->principal_investigator }} — {{ $ethics->institution?->name }}</p></div><x-status :status="$ethics->status"/></div>
<div class="detail-grid"><div>
<section class="panel"><h2>Protokol Penelitian</h2>
<dl class="detail-list">
@foreach(['principal_investigator'=>'Peneliti utama','research_type'=>'Jenis penelitian','location'=>'Lokasi','population'=>'Populasi','subjects'=>'Subjek','funding_source'=>'Sumber dana'] as $key=>$label)
<div><dt>{{ $label }}</dt><dd>{{ $ethics->$key ?: '-' }}</dd></div>@endforeach
<div><dt>Periode</dt><dd>{{ $ethics->start_date?->format('d M Y') }} — {{ $ethics->end_date?->format('d M Y') }}</dd></div></dl>
@if($ethics->application)
<h3>Berkas Permohonan Utama {{ $ethics->application->number }}</h3><p>{{ $ethics->application->purpose }}</p><p>{{ $ethics->application->description }}</p>
@forelse($ethics->application->documents as $document)<p><a class="mini-link" href="{{ route('ethics.documents',[$ethics,$document]) }}">{{ $document->name }}</a></p>@empty<p>Belum ada dokumen diunggah pemohon.</p>@endforelse
@elseif($ethics->status==='draft')<form method="post" action="{{ route('ethics.submit',$ethics) }}">@csrf<button class="button green-bg">Ajukan untuk Verifikasi</button></form>@endif
</section>
<section class="panel"><h2>Hasil Review</h2>
@forelse($ethics->reviews as $review)
<article class="review-card"><div><b>{{ $review->reviewer->name }}</b><x-status :status="$review->status"/></div><p>{{ $review->summary ?: 'Review belum diselesaikan.' }}</p>
@if($review->recommendation)<p>Rekomendasi: {{ str($review->recommendation)->replace('_',' ')->title() }}</p>@endif
@if($review->reviewer_id===auth()->id() && $review->status!=='completed' && !in_array($ethics->status,['revision','approved']))
<form class="review-form" method="post" action="{{ route('ethics.review',$review) }}">@csrf
@foreach(['scientific_validity'=>'Validitas ilmiah','risk_benefit'=>'Risiko/manfaat','privacy'=>'Privasi & kerahasiaan','informed_consent'=>'Informed consent'] as $key=>$label)
<label>{{ $label }}<input type="number" name="{{ $key }}" min="1" max="5" required></label>@endforeach
<label>Rekomendasi<select name="recommendation" required><option value="eligible">Layak</option><option value="eligible_with_revision">Layak dengan perbaikan</option><option value="revision_required">Perlu revisi</option><option value="meeting_required">Perlu sidang</option><option value="not_eligible">Tidak layak</option></select></label>
<label>Ringkasan<textarea name="summary" maxlength="2000"></textarea></label><button class="button green-bg">Kirim Review</button></form>@endif
</article>
@empty<div class="empty">Belum ada reviewer yang ditugaskan.</div>@endforelse
</section></div><aside>
@if(auth()->user()->hasRole('super-admin','ethics-admin','ethics-chair') && in_array($ethics->status,['administrative_verification','review']))
<section class="panel"><h2>Penugasan Reviewer</h2><form method="post" action="{{ route('ethics.assign',$ethics) }}" class="stack">@csrf
<label>Reviewer<select name="reviewer_id" required><option value="">Pilih reviewer</option>@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>@endforeach</select></label>
<label>Batas waktu<input type="date" name="deadline" required></label><button class="button green-bg">Tugaskan Reviewer</button></form></section>@endif
@if($ethics->clearance)
<section class="panel"><h2>Ethical Clearance</h2><b>{{ $ethics->clearance->number }}</b><p>Berlaku hingga {{ $ethics->clearance->valid_until->translatedFormat('d F Y') }}</p><a class="button secondary" href="{{ route('ec.verify',$ethics->clearance->verification_token) }}">Verifikasi dokumen</a></section>
@elseif(auth()->user()->hasRole('super-admin','ethics-chair') && $ethics->status==='decision')
<section class="panel"><h2>Terbitkan EC</h2><form method="post" action="{{ route('ethics.issue',$ethics) }}" class="stack">@csrf<label>Berlaku hingga<input type="date" name="valid_until" required></label><label>Ketua Komite<input name="chairperson" required></label><button class="button green-bg">Terbitkan Ethical Clearance</button></form></section>
@endif
@if($ethics->application && auth()->user()->hasRole('ethics-chair') && $ethics->status==='decision')
@php($decisionTask=$ethics->application->workflowInstance?->tasks()->where('status','pending')->latest('id')->first())
@if($decisionTask)<section class="panel"><h2>Keputusan Komite Etik</h2><form method="post" action="{{ route('approvals.act',$decisionTask) }}" class="stack">@csrf<label>Catatan keputusan<textarea name="note" required maxlength="2000"></textarea></label>
@if($decisionTask->step->allow_revision)<button class="button secondary" name="action" value="revision">Minta Perbaikan</button>@endif
@if($decisionTask->step->allow_reject)<button class="button secondary" name="action" value="reject">Tolak Permohonan</button>@endif</form></section>@endif
@endif
</aside></div>
