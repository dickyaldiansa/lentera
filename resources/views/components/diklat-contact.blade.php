@if($url = \App\Services\DiklatContact::url())
<a class="button whatsapp-contact" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
    <x-nav-icon name="whatsapp"/>
    <span>Hubungi Admin Diklat</span>
</a>
@endif
