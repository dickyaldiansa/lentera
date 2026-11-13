@extends('layouts.app')
@section('title',$title)
@section('content')
<div class="page-head"><div><span class="eyebrow">{{ $eyebrow }}</span><h1>{{ $title }}</h1><p>{{ $description }}</p></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
@forelse($rows as $row)<tr>@foreach($row as $cell)<td>@if(isset($cell['status']))<x-status :status="$cell['status']"/>@else<b>{{ $cell['text'] }}</b>@if(!empty($cell['sub']))<small>{{ $cell['sub'] }}</small>@endif @endif</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headers) }}" class="empty">Belum ada data pada modul ini.</td></tr>@endforelse
</tbody></table></div>@if($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)<x-pagination :paginator="$paginator" label="data" />@endif</section>
@endsection
