@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp

<div class="pagination-shell">
        <div class="pagination-summary">
            <span>Affichage de <strong>{{ $paginator->firstItem() ?? 0 }}</strong> à <strong>{{ $paginator->lastItem() ?? 0 }}</strong> sur <strong>{{ $paginator->total() }}</strong></span>
            <form method="GET" action="{{ url()->current() }}" class="pagination-size-form" data-loading-title="Mise à jour de la liste" data-loading-message="Application du nombre de lignes choisi…">
                @foreach(request()->except(['page', 'per_page']) as $key => $value)
                    @if(is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="per-page-select">Lignes par page</label>
                <select id="per-page-select" name="per_page" onchange="this.form.submit()" aria-label="Nombre de lignes par page">
                    @foreach([10, 20, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if($lastPage > 1)
            <nav class="pagination-nav" aria-label="Pagination">
                @if($paginator->onFirstPage())
                    <span class="pagination-control disabled" aria-disabled="true">←</span>
                @else
                    <a class="pagination-control" href="{{ $paginator->previousPageUrl() }}" aria-label="Page précédente">←</a>
                @endif

                @if($startPage > 1)
                    <a href="{{ $paginator->url(1) }}">1</a>
                    @if($startPage > 2)<span class="pagination-ellipsis">…</span>@endif
                @endif

                @for($page = $startPage; $page <= $endPage; $page++)
                    @if($page === $currentPage)
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    @endif
                @endfor

                @if($endPage < $lastPage)
                    @if($endPage < $lastPage - 1)<span class="pagination-ellipsis">…</span>@endif
                    <a href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
                @endif

                @if($paginator->hasMorePages())
                    <a class="pagination-control" href="{{ $paginator->nextPageUrl() }}" aria-label="Page suivante">→</a>
                @else
                    <span class="pagination-control disabled" aria-disabled="true">→</span>
                @endif
            </nav>
        @endif
</div>
