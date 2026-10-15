@if ($paginator->hasPages())
    <nav class="pager" aria-label="Pages">
        <span class="muted">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
        <span class="pager-links">
            @if ($paginator->onFirstPage())
                <span class="btn btn-ghost btn-sm is-disabled">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-ghost btn-sm" rel="prev">Previous</a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-ghost btn-sm" rel="next">Next</a>
            @else
                <span class="btn btn-ghost btn-sm is-disabled">Next</span>
            @endif
        </span>
    </nav>
@endif
