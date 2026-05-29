@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full">

        {{-- Mobile View (Simple Prev/Next) --}}
        <div class="flex justify-between w-full sm:hidden gap-3">
            @if ($paginator->onFirstPage())
                <span class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-black text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-black text-sky-800 bg-white border border-sky-200 rounded-lg hover:bg-sky-50 hover:border-sky-500 active:bg-sky-100 transition-all shadow-sm">
                    <i data-lucide="chevron-left" class="w-4 h-4 text-sky-500"></i>
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-black text-sky-800 bg-white border border-sky-200 rounded-lg hover:bg-sky-50 hover:border-sky-500 active:bg-sky-100 transition-all shadow-sm">
                    Selanjutnya
                    <i data-lucide="chevron-right" class="w-4 h-4 text-sky-500"></i>
                </a>
            @else
                <span class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm font-black text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                    Selanjutnya
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </span>
            @endif
        </div>

        {{-- Desktop View (Full Numbers) --}}
        <div class="hidden sm:flex flex-col lg:flex-row flex-1 items-center justify-between w-full gap-4 min-w-0">
            <div class="shrink-0 order-2 lg:order-1 text-center lg:text-left">
                <p class="text-sm text-slate-500 font-medium">
                    Menampilkan <span class="font-black text-sky-800">{{ $paginator->firstItem() }}</span> hingga <span class="font-black text-sky-800">{{ $paginator->lastItem() }}</span> dari <span class="font-black text-slate-900">{{ $paginator->total() }}</span> hasil
                </p>
            </div>

            <div class="overflow-x-auto w-full max-w-full text-center lg:text-right order-1 lg:order-2 pb-1 sm:pb-0 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                <span class="relative z-0 inline-flex items-center justify-start rounded-xl gap-1 p-1 bg-white border border-sky-200 shadow-sm shrink-0">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-slate-400 bg-slate-50 rounded-lg cursor-not-allowed" aria-hidden="true">
                                <i data-lucide="chevron-left" class="w-4 h-4"></i>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-black text-sky-800 bg-sky-50 rounded-lg hover:bg-sky-100 hover:text-sky-900 transition-all" aria-label="{{ __('pagination.previous') }}">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-sm font-black text-slate-400 bg-transparent">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3.5 py-2 text-sm font-black text-white bg-sky-600 rounded-lg shadow-sm border border-sky-700 ring-1 ring-sky-200">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3.5 py-2 text-sm font-black text-slate-600 bg-transparent rounded-lg hover:bg-sky-50 hover:text-sky-800 transition-all" aria-label="Go to page {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-sm font-black text-sky-800 bg-sky-50 rounded-lg hover:bg-sky-100 hover:text-sky-900 transition-all" aria-label="{{ __('pagination.next') }}">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-sm font-bold text-slate-400 bg-slate-50 rounded-lg cursor-not-allowed" aria-hidden="true">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
