@if ($paginator->hasPages())
    <nav style="display:flex; justify-content:center; align-items:center; gap:6px; margin-top:10px;">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span style="padding:8px 14px; border-radius:4px; background:#f0f0f0; color:#aaa; font-size:14px;">&laquo; Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding:8px 14px; border-radius:4px; background:white; color:#333; text-decoration:none; font-size:14px; border:1px solid #ddd;">&laquo; Prev</a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="padding:8px 12px; color:#999; font-size:14px;">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="padding:8px 14px; border-radius:4px; background:#008060; color:white; font-size:14px; font-weight:600;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:8px 14px; border-radius:4px; background:white; color:#333; text-decoration:none; font-size:14px; border:1px solid #ddd;">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding:8px 14px; border-radius:4px; background:white; color:#333; text-decoration:none; font-size:14px; border:1px solid #ddd;">Next &raquo;</a>
        @else
            <span style="padding:8px 14px; border-radius:4px; background:#f0f0f0; color:#aaa; font-size:14px;">Next &raquo;</span>
        @endif

    </nav>

    <p style="text-align:center; color:#888; font-size:13px; margin-top:10px;">
        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
    </p>
@endif
