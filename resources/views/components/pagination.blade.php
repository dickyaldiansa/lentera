@props(['paginator', 'label' => 'data'])

@if($paginator->total() > 0)
<div class="pagination-wrap">
    <p class="pagination-summary">
        Menampilkan <b>{{ $paginator->firstItem() }}</b>&ndash;<b>{{ $paginator->lastItem() }}</b>
        dari <b>{{ $paginator->total() }}</b> {{ $label }}
    </p>

    @if($paginator->hasPages())
    <nav class="pagination" aria-label="Navigasi halaman {{ $label }}">
        @if($paginator->onFirstPage())
            <span class="pagination-link disabled" aria-disabled="true">&lsaquo; Sebelumnya</span>
        @else
            <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Sebelumnya</a>
        @endif

        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $pages = collect([1, $lastPage])
                ->merge(range(max(1, $currentPage - 1), min($lastPage, $currentPage + 1)))
                ->unique()->sort()->values();
            $previousRenderedPage = null;
        @endphp
        <div class="pagination-pages">
            @foreach($pages as $page)
                @if($previousRenderedPage !== null && $page - $previousRenderedPage > 1)
                    <span class="pagination-ellipsis" aria-hidden="true">&hellip;</span>
                @endif
                @if($page === $currentPage)
                    <span class="pagination-link active" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pagination-link page-number" href="{{ $paginator->url($page) }}" aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                @endif
                @php($previousRenderedPage = $page)
            @endforeach
        </div>

        @if($paginator->hasMorePages())
            <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya &rsaquo;</a>
        @else
            <span class="pagination-link disabled" aria-disabled="true">Berikutnya &rsaquo;</span>
        @endif
    </nav>
    @endif
</div>
@endif
