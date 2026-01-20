@if($historyData->hasPages())
<div class="card-footer bg-white py-3 no-print">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Menampilkan {{ $historyData->firstItem() ?? 0 }} - {{ $historyData->lastItem() ?? 0 }} dari {{ $historyData->total() }} data
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
                {{-- Previous Page Link --}}
                @if($historyData->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">&laquo; Sebelumnya</span>
                    </li>
                @else
                    @php
                        $prevUrl = $historyData->previousPageUrl();
                        // Preserve existing query parameters
                        $queryParams = request()->except('page');
                        if (!empty($queryParams)) {
                            $prevUrl .= (strpos($prevUrl, '?') === false ? '?' : '&') . http_build_query($queryParams);
                        }
                    @endphp
                    <li class="page-item">
                        <a class="page-link" href="{{ $prevUrl }}" rel="prev">&laquo; Sebelumnya</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach($historyData->getUrlRange(1, $historyData->lastPage()) as $page => $url)
                    @php
                        // Preserve existing query parameters
                        $queryParams = request()->except('page');
                        if (!empty($queryParams)) {
                            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
                        }
                    @endphp
                    @if($page == $historyData->currentPage())
                        <li class="page-item active">
                            <span class="page-link">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if($historyData->hasMorePages())
                    @php
                        $nextUrl = $historyData->nextPageUrl();
                        // Preserve existing query parameters
                        $queryParams = request()->except('page');
                        if (!empty($queryParams)) {
                            $nextUrl .= (strpos($nextUrl, '?') === false ? '?' : '&') . http_build_query($queryParams);
                        }
                    @endphp
                    <li class="page-item">
                        <a class="page-link" href="{{ $nextUrl }}" rel="next">Selanjutnya &raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link">Selanjutnya &raquo;</span>
                    </li>
                @endif
            </ul>
        </nav>
        <div class="text-muted small">
            Halaman {{ $historyData->currentPage() }} dari {{ $historyData->lastPage() }}
        </div>
    </div>
</div>
@endif