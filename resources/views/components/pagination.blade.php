@if ($paginator->hasPages())
    <div class="mt-12 flex justify-center px-4 relative z-10">
        <nav class="flex flex-wrap justify-center items-center gap-1.5 sm:gap-2 rounded-full border border-white/10 bg-slate-900/50 p-2 sm:p-2.5 shadow-2xl backdrop-blur-xl" role="navigation" aria-label="Data Bank Pagination">
            
            @if ($paginator->onFirstPage())
                <span class="rounded-full px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-600 cursor-not-allowed select-none" aria-disabled="true" aria-label="Previous Page">
                    Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-full px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-400 transition-all duration-300 hover:bg-white/10 hover:text-cyan-400 hover:shadow-[0_0_15px_rgba(34,211,238,0.1)] outline-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900" aria-label="Go to Previous Page">
                    Prev
                </a>
            @endif

            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();

                $pages = collect([1, 2, $current - 1, $current, $current + 1, $last - 1, $last])
                    ->filter(fn ($page) => $page >= 1 && $page <= $last)
                    ->unique()
                    ->sort()
                    ->values();
            @endphp

            @foreach ($pages as $index => $page)
                @if ($index > 0 && $pages[$index] - $pages[$index - 1] > 1)
                    <span class="px-1 sm:px-2 text-sm font-black text-slate-600 select-none flex items-center justify-center" aria-hidden="true">&hellip;</span>
                @endif

                @if ($page == $current)
                    <span class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full border border-cyan-500/40 bg-cyan-500/10 text-xs sm:text-sm font-black text-cyan-400 shadow-[0_0_20px_rgba(34,211,238,0.2)]" aria-current="page" aria-label="Page {{ $page }}">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full border border-transparent text-xs sm:text-sm font-bold text-slate-400 transition-all duration-300 hover:border-cyan-500/30 hover:bg-cyan-500/5 hover:text-cyan-300 hover:shadow-[0_0_15px_rgba(34,211,238,0.15)] outline-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900" aria-label="Go to page {{ $page }}">
                        {{ $page }}
                    </a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-full px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-400 transition-all duration-300 hover:bg-white/10 hover:text-cyan-400 hover:shadow-[0_0_15px_rgba(34,211,238,0.1)] outline-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900" aria-label="Go to Next Page">
                    Next
                </a>
            @else
                <span class="rounded-full px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-600 cursor-not-allowed select-none" aria-disabled="true" aria-label="Next Page">
                    Next
                </span>
            @endif
            
        </nav>
    </div>
@endif