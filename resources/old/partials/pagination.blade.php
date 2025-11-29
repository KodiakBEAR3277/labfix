{{-- 
    Pagination Partial
    File: resources/views/partials/pagination.blade.php
    
    Usage:
    @include('partials.pagination', ['items' => $tickets])
--}}

@props(['items', 'info' => null])

<div class="pagination">
    <div class="page-info">
        @if($info)
            {{ $info }}
        @else
            Showing {{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }} of {{ $items->total() }} items
        @endif
    </div>
    
    <div class="page-controls">
        {{-- Previous Button --}}
        <button class="page-btn" 
                @if(!$items->onFirstPage()) 
                    onclick="window.location.href='{{ $items->previousPageUrl() }}'"
                @else 
                    disabled
                @endif>
            ← Previous
        </button>
        
        {{-- Page Numbers --}}
        @foreach ($items->getUrlRange(1, $items->lastPage()) as $page => $url)
            <button class="page-btn {{ $page == $items->currentPage() ? 'active' : '' }}" 
                    onclick="window.location.href='{{ $url }}'">
                {{ $page }}
            </button>
        @endforeach
        
        {{-- Next Button --}}
        <button class="page-btn" 
                @if($items->hasMorePages()) 
                    onclick="window.location.href='{{ $items->nextPageUrl() }}'"
                @else 
                    disabled
                @endif>
            Next →
        </button>
    </div>
</div>