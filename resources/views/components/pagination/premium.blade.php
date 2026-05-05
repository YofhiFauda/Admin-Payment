@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full">
        
        {{-- Mobile View (Simple Prev/Next) --}}
        <div class="flex justify-between w-full sm:hidden gap-3">
            @if ($paginator->onFirstPage())
                <span class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-slate-400 bg-slate-50/80 border border-slate-200/60 rounded-xl cursor-not-allowed">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 hover:-translate-y-0.5 active:bg-slate-100 active:scale-95 transition-all shadow-sm">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-slate-500"></i>
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 hover:-translate-y-0.5 active:bg-slate-100 active:scale-95 transition-all shadow-sm">
                    Selanjutnya
                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
                </a>
            @else
                <span class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-bold text-slate-400 bg-slate-50/80 border border-slate-200/60 rounded-xl cursor-not-allowed">
                    Selanjutnya
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </span>
            @endif
        </div>

        {{-- Desktop View (Full Numbers) --}}
        <div class="hidden sm:flex flex-1 items-center justify-between w-full">
            <div>
                <p class="text-sm text-slate-500 font-medium">
                    Menampilkan <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span> hingga <span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span> dari <span class="font-bold text-slate-800">{{ $paginator->total() }}</span> hasil
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex shadow-sm rounded-xl gap-1 p-1 bg-white/60 backdrop-blur-md border border-slate-200/80">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-transparent rounded-lg cursor-not-allowed" aria-hidden="true">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-slate-600 bg-transparent rounded-lg hover:bg-white hover:text-slate-800 hover:shadow-sm transition-all" aria-label="{{ __('pagination.previous') }}">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-slate-400 bg-transparent">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3.5 py-2 text-sm font-black text-blue-600 bg-white rounded-lg shadow-sm border border-slate-100">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3.5 py-2 text-sm font-bold text-slate-500 bg-transparent rounded-lg hover:bg-white hover:text-slate-800 hover:shadow-sm transition-all" aria-label="Go to page {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-slate-600 bg-transparent rounded-lg hover:bg-white hover:text-slate-800 hover:shadow-sm transition-all" aria-label="{{ __('pagination.next') }}">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-transparent rounded-lg cursor-not-allowed" aria-hidden="true">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
