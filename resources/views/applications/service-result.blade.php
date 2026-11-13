@if($application->ethicsApplication)
<section class="panel"><h2>Telaah Etik</h2><p>Proses etik terhubung dengan permohonan ini.</p><x-status :status="$application->ethicsApplication->status"/>
@foreach($application->ethicsApplication->reviews as $review)
@if($review->summary)<p><b>Catatan reviewer:</b> {{ $review->summary }}</p>@endif
@endforeach
@if($application->ethicsApplication->clearance)<p><a class="button secondary" href="{{ route('ec.verify',$application->ethicsApplication->clearance->verification_token) }}">Lihat Ethical Clearance</a></p>@endif
</section>
@endif
@if($application->letters->isNotEmpty())
<section class="panel"><h2>Hasil Layanan</h2>@foreach($application->letters as $letter)<p><a class="mini-link" href="{{ route('letters.verify',$letter->verification_token) }}">{{ $letter->subject }} — {{ $letter->number }}</a></p>@endforeach</section>
@endif
