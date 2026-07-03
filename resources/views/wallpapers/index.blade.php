<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wallpaper Management - {{ env('APP_NAME') }}</title>
    <x-assets />
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .break-inside-avoid { break-inside: avoid; }
        .card-hover { transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(34, 211, 238, 0.28); box-shadow: 0 14px 30px rgba(0,0,0,0.25); }
        summary::-webkit-details-marker { display: none; }
        details.custom-dropdown > summary { list-style: none; }
        details.custom-dropdown > summary::-webkit-details-marker { display: none; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white">
    <x-navbar />
    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400">Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Wallpapers</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded-md border border-white/5">Listing</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Wallpaper <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Management</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Browse, filter, and manage all your wallpaper assets.</p>
                </div>
            </div>

            <form method="GET" action="{{ url()->current() }}" class="relative mb-8 z-30 group">
                
                <div class="absolute inset-0 bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl backdrop-blur-md overflow-hidden pointer-events-none z-0">
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>
                </div>

                <div class="relative z-10 flex flex-col md:flex-row gap-5 items-end p-5 sm:p-6">
                    <div class="w-full md:flex-1">
                        <label for="search" class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Search Query</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by title, tags, or ID..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                        </div>
                    </div>

                    <div class="w-full md:w-44 relative">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Content Rating</label>
                        <details class="group custom-dropdown">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                @php $ratingCount = count(request('rating', [])); @endphp
                                <span class="text-sm truncate mr-2 {{ $ratingCount > 0 ? 'text-cyan-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $ratingCount > 0 ? $ratingCount . ' Selected' : 'All Ratings' }}</span>
                                <svg class="w-4 h-4 text-slate-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1 max-h-60 overflow-y-auto">
                                @foreach(['general', 'sensitive', 'questionable', 'explicit', 'unknown'] as $rating)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="checkbox" name="rating[]" value="{{ $rating }}" @checked(in_array($rating, request('rating', []))) class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-slate-900 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ $rating }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="w-full md:w-40 relative">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">SEO Status</label>
                        <details class="group custom-dropdown">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                @php $seoCount = count(request('seo', [])); @endphp
                                <span class="text-sm truncate mr-2 {{ $seoCount > 0 ? 'text-purple-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $seoCount > 0 ? $seoCount . ' Selected' : 'All SEO' }}</span>
                                <svg class="w-4 h-4 text-slate-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1">
                                @foreach(['filled', 'empty'] as $seo)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="checkbox" name="seo[]" value="{{ $seo }}" @checked(in_array($seo, request('seo', []))) class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-purple-500 focus:ring-purple-500 focus:ring-offset-slate-900 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ $seo }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="w-full md:w-40 relative">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Workflow</label>
                        <details class="group custom-dropdown">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                @php $workflowCount = count(request('workflow', [])); @endphp
                                <span class="text-sm truncate mr-2 {{ $workflowCount > 0 ? 'text-emerald-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $workflowCount > 0 ? $workflowCount . ' Selected' : 'All Flow' }}</span>
                                <svg class="w-4 h-4 text-slate-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1">
                                @foreach(['debug', 'ready'] as $workflow)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="checkbox" name="workflow[]" value="{{ $workflow }}" @checked(in_array($workflow, request('workflow', []))) class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-900 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ $workflow }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="w-full md:w-auto flex gap-3">
                        <button type="submit" class="flex-1 md:flex-none px-7 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-black rounded-xl shadow-[0_0_15px_rgba(34,211,238,0.2)] hover:shadow-[0_0_25px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-2 outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Filter
                        </button>
                        @if(request()->anyFilled(['search', 'rating', 'seo', 'workflow']))
                            <a href="{{ url()->current() }}" class="flex-1 md:flex-none px-5 py-3 bg-slate-800 hover:bg-red-500/20 border border-transparent hover:border-red-500/30 text-slate-300 hover:text-red-400 font-bold rounded-xl transition-all text-center flex items-center justify-center">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="columns-2 md:columns-3 lg:columns-4 xl:columns-5 gap-5 space-y-5 relative z-10">
                @forelse($wallpapers as $wallpaper)
                    <div class="break-inside-avoid">
                        <x-wallpaper-card :wallpaper="$wallpaper" />
                    </div>
                @empty
                    <div class="col-span-full w-full flex flex-col items-center justify-center py-20 px-4 text-center bg-slate-900/40 border border-dashed border-slate-700 rounded-[2rem] backdrop-blur-md shadow-inner">
                        <div class="w-20 h-20 mb-5 rounded-full bg-slate-800 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">No Wallpapers Found</h3>
                        <p class="text-sm font-medium text-slate-500 max-w-sm mx-auto">We couldn't find any wallpapers matching your current filter criteria. Try adjusting your search or clearing the filters.</p>
                    </div>
                @endforelse
            </div>

            @if($wallpapers->hasPages())
                {{ $wallpapers->withQueryString()->links('components.pagination') }}
            @endif
        </div>
    </main>

    <x-footer />
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        window.toggleDebug = async function(wallpaperId, isDebug) {
            try {
                const urlTemplate = "{{ route('wallpapers.toggle-debug', ['id' => '__ID__']) }}";
                const url = urlTemplate.replace('__ID__', wallpaperId);
                const response = await fetch(url, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ debug: isDebug }) });
                const data = await response.json();
                if (!data.success) { console.error('Failed to update debug:', data.message); }
            } catch (error) { console.error('Network error occurred:', error); }
        };
        window.toggleRatingDropdown = async function(wallpaperId, event) {
            const button = event.currentTarget;
            const currentRating = button.getAttribute('data-rating');
            const ratingOptions = { 'general': 'General (GEN)', 'sensitive': 'Sensitive (SEN)', 'questionable': 'Questionable (QST)', 'explicit': 'Explicit (EXP)', 'unknown': 'Unknown (UNK)' };
            const { value: selectedRating } = await Swal.fire({ title: 'Change Wallpaper Rating', input: 'select', inputOptions: ratingOptions, inputValue: currentRating, showCancelButton: true, confirmButtonColor: '#0ea5e9', cancelButtonColor: '#64748b', confirmButtonText: 'Save', cancelButtonText: 'Cancel' });
            if (selectedRating && selectedRating !== currentRating) {
                try {
                    button.style.opacity = '0.5';
                    const urlTemplate = "{{ route('wallpapers.update-rating', ['id' => '__ID__']) }}";
                    const url = urlTemplate.replace('__ID__', wallpaperId);
                    const response = await fetch(url, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ rating: selectedRating }) });
                    const data = await response.json();
                    if (data.success) {
                        updateRatingButtonUI(button, selectedRating);
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Rating updated!', showConfirmButton: false, timer: 1500 });
                    }
                } catch (error) { console.error('Error:', error); Swal.fire('Error!', 'Failed to change rating.', 'error'); } finally { button.style.opacity = '1'; }
            }
        };
        function updateRatingButtonUI(buttonElement, newRating) {
            const textSpan = buttonElement.querySelector('.rating-text');
            buttonElement.className = buttonElement.className.replace(/(bg|border|text)-[a-z]+-[0-9]+\/?([0-9]+)?/g, '').trim();
            let baseClasses = "h-7 px-2 rounded-md border backdrop-blur-md transition-all flex items-center justify-center shadow-lg font-black text-[10px] tracking-tight ";
            switch(newRating) {
                case 'general': buttonElement.className = baseClasses + 'bg-emerald-500/90 text-white border-emerald-400/50'; textSpan.textContent = 'GEN'; break;
                case 'sensitive': buttonElement.className = baseClasses + 'bg-yellow-500/90 text-white border-yellow-400/50'; textSpan.textContent = 'SEN'; break;
                case 'questionable': buttonElement.className = baseClasses + 'bg-orange-500/90 text-white border-orange-400/50'; textSpan.textContent = 'QST'; break;
                case 'explicit': buttonElement.className = baseClasses + 'bg-rose-500/90 text-white border-rose-400/50'; textSpan.textContent = 'EXP'; break;
                default: buttonElement.className = baseClasses + 'bg-slate-500/90 text-white border-slate-400/50'; textSpan.textContent = 'UNK'; break;
            }
            buttonElement.setAttribute('data-rating', newRating);
        }
    </script>
    <script>
        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('details.custom-dropdown');
            dropdowns.forEach((details) => { if (!details.contains(event.target)) { details.removeAttribute('open'); } });
        });
    </script>
</body>
</html>