@if ($paginator->hasPages())
    <ul class="pagination justify-content-center">
        {{-- Previous Page Link and page 1 --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link">首页</span></li>
            <li class="page-item disabled" tabindex="-1"><span class="page-link">前一页</span></li>
            <li class="page-item active"><span class="page-link">1</span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">首页</a></li>
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">前一页</a></li>
            <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
        @endif

        @php
            $halfTotal      = intval($paginator->lastPage() / 2);
            $limit          = 6;
            $halfLimit      = intval($limit / 2);
            $pageRightBound = $paginator->currentPage() + $halfLimit;
            $pageLeftBound  = $paginator->currentPage() - $halfLimit;

        @endphp

        @if ($paginator->lastPage() <= $limit)
            @for ($page = 2; $page <= $paginator->lastPage(); $page++)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                @endif
            @endfor
        @else
            {{-- When there are less than $halfLimit pages to show on left or right of the current page  --}}
            @php
                if ($pageRightBound >= $paginator->lastPage() - 1) {
                    $pageLeftBound = $paginator->lastPage() - $limit;
                    $pageRightBound = $paginator->lastPage() - 1;
                }
                if ($pageLeftBound <= 2) {
                    $pageRightBound = 1 + $limit;
                    $pageLeftBound = 2;
                }
            @endphp

            {{-- Show three dot separator on the left --}}
            @if ($pageLeftBound > 2)
                <li class="page-item disabled" tabindex="-1"><span class="page-link">...</span></li>
            @endif

            @for ($page = $pageLeftBound; $page <= $pageRightBound; $page++)
                @if ($page == $paginator->currentPage())
                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                @endif
            @endfor

            {{-- Show three dot separator on the right --}}
            @if ($pageRightBound < $paginator->lastPage() - 1)
                <li class="page-item disabled" tabindex="-1"><span class="page-link">...</span></li>
            @endif

            {{-- Display the last page --}}
            @if ($paginator->currentPage() == $paginator->lastPage())
                <li class="page-item active"><span class="page-link">{{ $paginator->lastPage() }}</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a></li>
            @endif
        @endif


        {{-- Pagination Elements --}}

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">下一页</a></li>
            <li class="page-item"><a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">尾页</a></li>
        @else
            <li class="page-item disabled" tabindex="-1"><span class="page-link">下一页</span></li>
            <li class="page-item disabled"><span class="page-link">尾页</span></li>
        @endif
    </ul>
@endif
