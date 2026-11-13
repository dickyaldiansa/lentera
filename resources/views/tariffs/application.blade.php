@php($visibleTariffs=\App\Models\Tariff::visible()->orderBy('sort_order')->orderBy('id')->get())
<div class="full">
    <a class="mini-link" href="{{ route('tariffs.index') }}" target="_blank" rel="noopener">Lihat seluruh daftar tarif</a>
    <div id="activity-tariff-panel" hidden>
        <p>Tarif berikut sesuai kategori yang dipilih. Perhatikan satuannya; nominal tagihan ditetapkan Diklat setelah verifikasi.</p>
        @foreach($types as $type)
        @php($category=\App\Models\Tariff::categoryForActivity($type))
        @php($items=$visibleTariffs->filter(fn($tariff)=>(int)$tariff->activity_type_id===$type->id || (!$tariff->activity_type_id && $category!==null && $tariff->category===$category)))
        @if($items->isNotEmpty())
        <section class="panel" data-activity-tariffs="{{ $type->id }}" hidden><h2>Tarif {{ $type->name }}</h2>@include('tariffs.table',['items'=>$items])</section>
        @endif
        @endforeach
    </div>
</div>
