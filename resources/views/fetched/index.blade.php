<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Moderation Queue - {{ env('APP_NAME') }}</title>
    <x-assets />
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .break-inside-avoid { break-inside: avoid; }
        .card-hover { transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(34, 211, 238, 0.5); box-shadow: 0 14px 30px rgba(34, 211, 238, 0.1); }
        summary::-webkit-details-marker { display: none; }
        .modal-scroll::-webkit-scrollbar { width: 6px; }
        .modal-scroll::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); }
        .modal-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 10px; }
        .modal-scroll::-webkit-scrollbar-thumb:hover { background: #6b7280; }
        details.custom-dropdown > summary {
            list-style: none;
        }
        details.custom-dropdown > summary::-webkit-details-marker {
            display: none;
        }
        /* Custom scrollbar for modal */
        .modal-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .modal-scroll::-webkit-scrollbar-track {
            background: transparent; 
        }
        .modal-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        .modal-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.2); 
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white">
    <x-navbar />
    
    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300" x-data="moderationPanel()">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <section id="admin-management" class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
            
            <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400"><i class="fa-solid fa-layer-group mr-1"></i> Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Moderation</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Moderation <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Queue</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Review, approve, or reject incoming fetched wallpapers.</p>
                </div>
                
                <div class="bg-slate-900/60 border border-amber-500/20 rounded-2xl p-4 flex items-center gap-4 shadow-[0_0_20px_rgba(245,158,11,0.1)] backdrop-blur-md relative overflow-hidden group w-full sm:w-auto">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition-all"></div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500 relative z-10 shadow-inner">
                        <i class="fa-solid fa-hourglass-half text-xl animate-pulse"></i>
                    </div>
                    <div class="relative z-10 pr-4">
                        <p class="text-[10px] font-bold text-amber-400 mb-0.5 uppercase tracking-wider">Pending Items</p>
                        <h3 class="text-2xl font-black text-white leading-none">{{ number_format($fetchCount) }}</h3>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('fetch-wallpapers.index') }}" class="relative mb-8 z-30 group">
                
                <div class="absolute inset-0 bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl backdrop-blur-md overflow-hidden pointer-events-none z-0">
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>
                </div>

                <div class="relative z-10 flex flex-col lg:flex-row gap-5 items-end p-5 sm:p-6">
                    <div class="w-full lg:flex-1 relative z-10">
                        <label for="search" class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-slate-500">
                                <i class="fa-solid fa-search"></i>
                            </div>
                            <input type="text" name="search" id="search" value="{{ $currentSearch }}" placeholder="Tags, characters, artists..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                        </div>
                    </div>

                    <div class="w-full sm:w-1/3 lg:w-44 relative z-20">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                        <details class="group custom-dropdown relative">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                <span class="text-sm truncate mr-2 {{ $currentStatus != 'pending' ? 'text-cyan-400 font-bold' : 'text-slate-400 font-medium' }}">{{ ucwords(str_replace('_', ' ', $currentStatus)) }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-slate-500 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1 max-h-60 overflow-y-auto">
                                @foreach(['pending', 'all_mod', 'processing', 'imported', 'failed', 'rejected', 'duplicate'] as $st)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="radio" name="status" value="{{ $st }}" @checked($currentStatus == $st) class="w-4 h-4 text-cyan-500 bg-slate-950 border-slate-700 focus:ring-cyan-500 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ str_replace('_', ' ', $st) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="w-full sm:w-1/3 lg:w-44 relative z-20">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Rating</label>
                        <details class="group custom-dropdown relative">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                <span class="text-sm truncate mr-2 {{ $currentRating ? 'text-cyan-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $currentRating ? ucfirst($currentRating) : 'All Ratings' }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-slate-500 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1 max-h-60 overflow-y-auto">
                                <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                    <input type="radio" name="rating" value="" @checked(!$currentRating) class="w-4 h-4 text-cyan-500 bg-slate-950 border-slate-700 focus:ring-cyan-500 shadow-inner">
                                    <span class="text-xs font-bold text-slate-300">All Ratings</span>
                                </label>
                                @foreach(['general', 'sensitive', 'questionable', 'explicit', 'unknown'] as $rt)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="radio" name="rating" value="{{ $rt }}" @checked($currentRating == $rt) class="w-4 h-4 text-cyan-500 bg-slate-950 border-slate-700 focus:ring-cyan-500 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ $rt }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="w-full sm:w-1/3 lg:w-44 relative z-20">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Source</label>
                        <details class="group custom-dropdown relative">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                <span class="text-sm truncate mr-2 {{ $currentSource != 'all' ? 'text-cyan-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $currentSource == 'all' ? 'All Sources' : ucfirst($currentSource) }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-slate-500 group-open:rotate-180 transition-transform"></i>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1 max-h-60 overflow-y-auto">
                                <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                    <input type="radio" name="source" value="all" @checked($currentSource == 'all') class="w-4 h-4 text-cyan-500 bg-slate-950 border-slate-700 focus:ring-cyan-500 shadow-inner">
                                    <span class="text-xs font-bold text-slate-300">All Sources</span>
                                </label>
                                @foreach(['danbooru', 'gelbooru', 'zerochan'] as $src)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="radio" name="source" value="{{ $src }}" @checked($currentSource == $src) class="w-4 h-4 text-cyan-500 bg-slate-950 border-slate-700 focus:ring-cyan-500 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ $src }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    </div>

                    <div class="w-full lg:w-auto flex gap-3 relative z-10">
                        <button type="submit" class="flex-1 lg:flex-none px-7 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-black rounded-xl shadow-[0_0_15px_rgba(34,211,238,0.2)] hover:shadow-[0_0_25px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-2 outline-none">
                            Filter
                        </button>
                        @if(request()->anyFilled(['search', 'rating', 'source']) || $currentStatus != 'pending')
                            <a href="{{ route('fetch-wallpapers.index') }}" class="flex-1 lg:flex-none px-5 py-3 bg-slate-800 hover:bg-red-500/20 border border-transparent hover:border-red-500/30 text-slate-300 hover:text-red-400 font-bold rounded-xl transition-all text-center flex items-center justify-center outline-none">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if($fetchedWallpapers->hasPages())
            {{ $fetchedWallpapers->links('components.pagination') }}
            @endif

            <div class="columns-3 md:columns-4 lg:columns-5 xl:columns-5 gap-5 space-y-5 relative z-10">
                @forelse($fetchedWallpapers as $wp)
                    <div id="card-{{ $wp->id }}" class="break-inside-avoid relative group rounded-[1.5rem] overflow-hidden bg-slate-900/60 border backdrop-blur-sm shadow-lg hover:shadow-[0_0_20px_rgba(34,211,238,0.15)] transform hover:-translate-y-1 transition-all duration-300 {{ $wp->is_duplicate ? 'border-rose-500/40 hover:border-rose-500/60 shadow-[0_0_15px_rgba(244,63,94,0.15)]' : 'border-white/5 hover:border-cyan-500/30' }}">
                        
                        <button @click.stop="deleteItem({{ $wp->id }})" class="absolute top-3 left-3 z-30 w-8 h-8 flex items-center justify-center rounded-lg bg-slate-950/80 text-slate-400 hover:bg-rose-500 hover:text-white border border-white/10 backdrop-blur-md transition-all opacity-0 group-hover:opacity-100 shadow-lg cursor-pointer outline-none" title="Delete Permanently">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                        
                        @if($wp->is_duplicate)
                            <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-rose-600/90 to-rose-500/90 text-white text-[9px] font-black text-center py-1.5 z-20 backdrop-blur-md uppercase tracking-widest pointer-events-none shadow-md">
                                <i class="fa-solid fa-clone mr-1"></i> Absolute Duplicate
                            </div>
                        @endif
                        
                        <div class="cursor-pointer relative overflow-hidden h-full flex flex-col" @click="checkApprove({{ $wp->id }}, {{ json_encode($wp) }})">
                            <img src="{{ $wp->thumbnail ?? $wp->thumbnail }}" class="w-full h-auto object-cover block transition-transform duration-700 ease-out group-hover:scale-105 @if($wp->status == 'processing') filter blur-md opacity-40 @endif @if($wp->status == 'rejected') filter grayscale opacity-20 @endif" loading="lazy" alt="Thumbnail" width="300" height="{{ $wp->width > 0 ? round(($wp->height / $wp->width) * 300) : 300 }}">
                            
                            <div class="absolute top-3 right-3 flex flex-col gap-1.5 items-end z-20 pointer-events-none">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-lg backdrop-blur-md border border-white/20 @if($wp->rating == 'explicit') bg-rose-500/90 text-white @elseif($wp->rating == 'questionable') bg-orange-500/90 text-white @elseif($wp->rating == 'sensitive') bg-amber-500/90 text-slate-950 @elseif($wp->rating == 'general') bg-emerald-500/90 text-white @else bg-slate-600/90 text-white @endif">
                                    {{ $wp->rating }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-lg bg-slate-950/80 text-slate-300 backdrop-blur-md border border-white/10">
                                    {{ $wp->source_api }}
                                </span>
                            </div>
                            
                            @if(in_array(strtolower($wp->file_type), ['mp4', 'webm', 'video/mp4', 'video/webm']))
                                <span class="absolute top-3 left-14 z-20 bg-purple-600/90 backdrop-blur-md text-white text-[9px] font-black px-2.5 py-1 rounded-lg shadow-lg uppercase tracking-widest border border-white/20 flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fa-solid fa-play"></i> VIDEO
                                </span>
                            @endif
                            
                            <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-300 z-10 flex justify-between items-end translate-y-2 group-hover:translate-y-0">
                                <div class="text-left overflow-hidden pr-2">
                                    <p class="text-xs text-white font-bold truncate tracking-wide">{{ is_array($wp->artists) ? implode(', ', array_slice($wp->artists, 0, 1)) : ($wp->artists ?: 'Unknown Artist') }}</p>
                                    <p class="text-[10px] text-cyan-400 font-mono mt-1 font-bold">{{ $wp->width }} &times; {{ $wp->height }} <span class="text-slate-500 mx-1">•</span> {{ strtoupper($wp->file_type) }}</p>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-cyan-500/20 flex items-center justify-center border border-cyan-500/50 text-cyan-400 shadow-[0_0_15px_rgba(34,211,238,0.4)] shrink-0">
                                    <i class="fa-solid fa-check text-[11px]"></i>
                                </div>
                            </div>
                            
                            @if($wp->status == 'processing')
                                <div class="absolute inset-0 flex items-center justify-center z-30 bg-slate-900/40 backdrop-blur-[2px]">
                                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-cyan-500 drop-shadow-[0_0_15px_rgba(34,211,238,0.5)]"></i>
                                </div>
                            @elseif($wp->status == 'failed')
                                <div class="absolute inset-0 flex items-center justify-center z-30 bg-rose-950/60 backdrop-blur-sm">
                                    <div class="bg-slate-900/80 p-4 rounded-full border border-rose-500/50 shadow-[0_0_20px_rgba(244,63,94,0.4)]">
                                        <i class="fa-solid fa-triangle-exclamation text-2xl text-rose-400"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-20 px-4 text-center bg-slate-900/40 border border-dashed border-slate-700 rounded-[2rem] backdrop-blur-md shadow-inner">
                        <div class="w-20 h-20 mb-5 rounded-full bg-slate-800 flex items-center justify-center shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-cyan-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-xl font-black text-white mb-2">Queue is Clear!</h3>
                        <p class="text-sm font-medium text-slate-500 max-w-sm mx-auto">No wallpapers found matching your filter criteria.</p>
                    </div>
                @endforelse
            </div>

            @if($fetchedWallpapers->hasPages())
                <div class="mt-8 bg-slate-900/40 border border-white/5 rounded-2xl p-4 backdrop-blur-sm relative z-10 shadow-lg">
                    {{ $fetchedWallpapers->links('components.pagination') }}
                </div>
            @endif

            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
                <div @click.away="closeModal()" x-show="modalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" class="bg-slate-900/95 backdrop-blur-xl rounded-[2rem] border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)] w-full max-w-6xl h-[85vh] flex flex-col overflow-hidden relative">
                    
                    <div class="absolute -top-32 -right-32 w-64 h-64 bg-cyan-500/10 blur-[80px] pointer-events-none"></div>

                    <div class="p-6 sm:p-8 border-b border-white/5 flex justify-between items-center bg-transparent shrink-0 relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="bg-amber-500/10 w-12 h-12 flex items-center justify-center rounded-2xl text-amber-500 border border-amber-500/20 shadow-inner">
                                <i class="fa-solid fa-clone text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-white leading-tight tracking-tight">Duplicate Resolution</h3>
                                <p class="text-xs font-mono font-medium text-slate-400 mt-1" x-text="message"></p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="text-slate-500 hover:text-white bg-slate-800/50 hover:bg-slate-700 p-3 rounded-full transition-all outline-none">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <div class="flex-grow flex flex-col lg:flex-row overflow-hidden relative z-10">
                        <div class="w-full lg:w-5/12 bg-slate-950/30 p-6 sm:p-8 flex flex-col border-b lg:border-b-0 lg:border-r border-white/5 relative">
                            <div class="absolute top-6 left-8 bg-cyan-500/90 backdrop-blur-md text-slate-950 text-[10px] font-black px-3 py-1.5 rounded-lg shadow-lg z-10 uppercase tracking-widest border border-cyan-400">Incoming Image</div>
                            
                            <template x-if="currentItem">
                                <div class="flex-grow flex items-center justify-center rounded-[1.5rem] overflow-hidden border border-white/5 bg-slate-900/50 p-4 shadow-inner relative mt-6 lg:mt-0">
                                    <img :src="`${currentItem.thumbnail}`" class="max-w-full max-h-full object-contain drop-shadow-2xl rounded-xl">
                                </div>
                            </template>

                            <div class="mt-6 bg-slate-900/80 rounded-[1.5rem] border border-white/5 p-5 space-y-4 shadow-inner">
                                <div class="flex justify-between items-center text-sm border-b border-white/5 pb-3">
                                    <span class="text-slate-500 font-bold uppercase tracking-widest text-[10px]">Source API</span>
                                    <a :href="currentItem?.source_url" target="_blank" class="text-cyan-400 hover:text-white font-mono font-bold transition-colors truncate w-48 text-right flex items-center justify-end gap-1.5" title="View Source">
                                        <span x-text="currentItem?.source_api"></span> 
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                    </a>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 font-bold uppercase tracking-widest text-[10px]">Resolution</span>
                                    <span class="text-white font-mono bg-slate-950 px-2.5 py-1 rounded-lg border border-slate-800 shadow-inner text-xs font-bold">
                                        <span x-text="currentItem?.width"></span> &times; <span x-text="currentItem?.height"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-7/12 flex flex-col bg-transparent">
                            <div class="p-6 sm:px-8 sm:py-5 border-b border-white/5 bg-slate-950/20 shrink-0 flex justify-between items-center">
                                <h4 class="text-[11px] font-black text-slate-300 uppercase tracking-widest flex items-center gap-2">
                                    <i class="fa-solid fa-database text-slate-500"></i> Found in DB (<span class="text-amber-500" x-text="duplicates.length"></span>)
                                </h4>
                                <span class="text-[9px] font-bold bg-slate-900 text-slate-400 px-2.5 py-1.5 rounded-lg border border-slate-800 font-mono tracking-wider shadow-inner">Sorted by match %</span>
                            </div>
                            
                            <div class="overflow-y-auto p-6 sm:p-8 flex-grow modal-scroll">
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                                    <template x-for="dupe in duplicates" :key="dupe.id">
                                        <div class="bg-slate-900/60 rounded-2xl border transition-all duration-300 group flex flex-col overflow-hidden relative shadow-lg" 
                                            :class="(currentItem.width * currentItem.height) > (dupe.width * dupe.height) ? 'border-emerald-500/40 hover:border-emerald-400 hover:shadow-[0_0_15px_rgba(16,185,129,0.2)]' : 'border-white/5 hover:border-amber-500/40 hover:shadow-[0_0_15px_rgba(245,158,11,0.15)]'">
                                            
                                            <div class="h-40 bg-slate-950/80 flex items-center justify-center p-3 relative overflow-hidden transition-colors">
                                                <img :src="`${dupe.thumbnail_url}`" class="max-w-full max-h-full object-contain opacity-70 group-hover:opacity-100 transition-all duration-500 group-hover:scale-105">
                                                
                                                <div class="absolute top-2 left-2 z-10">
                                                    <span class="bg-slate-950/90 text-white font-mono text-[9px] font-bold px-2 py-1 rounded-lg shadow-sm border border-slate-800 backdrop-blur-md flex items-center gap-1">
                                                        <i class="fa-solid fa-bullseye text-amber-500"></i><span x-text="dupe.distance.split(' ')[0]"></span>
                                                    </span>
                                                </div>

                                                <div class="absolute bottom-2 right-2 z-10 flex flex-col items-end gap-1.5">
                                                    <template x-if="(currentItem.width * currentItem.height) > (dupe.width * dupe.height)">
                                                        <span class="bg-emerald-600/90 text-white text-[9px] font-black px-2 py-1 rounded-lg shadow-lg uppercase tracking-wider flex items-center gap-1 backdrop-blur-md border border-emerald-500/30">
                                                            <i class="fa-solid fa-arrow-up text-[10px]"></i> UPGRADE
                                                        </span>
                                                    </template>
                                                    <span class="bg-slate-950/90 text-slate-300 text-[9px] font-mono font-bold px-2 py-1 rounded-lg border border-slate-800 shadow-sm backdrop-blur-md">
                                                        <span x-text="dupe.width"></span> &times; <span x-text="dupe.height"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="p-4 border-t border-white/5 bg-slate-900 flex-grow flex flex-col justify-end relative z-10">
                                                <div class="flex justify-between items-center gap-2">
                                                    <span class="text-[10px] text-slate-500 font-mono font-bold">ID: <span x-text="dupe.id" class="text-slate-400"></span></span>
                                                    <button @click="replaceItem(dupe.id, $event)" class="text-[10px] font-black px-4 py-2 rounded-lg shadow-lg uppercase tracking-widest transition-all outline-none" 
                                                            :class="(currentItem.width * currentItem.height) > (dupe.width * dupe.height) ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-[0_0_10px_rgba(16,185,129,0.3)]' : 'bg-slate-800 hover:bg-slate-700 text-white border border-slate-700'">
                                                        Replace
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 border-t border-white/5 bg-slate-950/50 flex flex-col sm:flex-row justify-between items-center shrink-0 gap-4 relative z-10">
                        <button @click="rejectFromModal()" class="w-full sm:w-auto flex justify-center items-center gap-2 bg-slate-900 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 text-xs font-black py-3 px-6 rounded-xl border border-rose-500/30 transition-all outline-none">
                            <i class="fa-solid fa-trash-can"></i> REJECT INCOMING
                        </button>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <button @click="closeModal()" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold py-3 px-6 rounded-xl transition-all border border-slate-700 outline-none">
                                CANCEL
                            </button>
                            <button @click="forceApprove($event)" class="w-full sm:w-auto flex justify-center items-center gap-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 text-xs font-black py-3 px-6 rounded-xl shadow-[0_0_15px_rgba(245,158,11,0.3)] transition-all outline-none">
                                <i class="fa-solid fa-check-double"></i> KEEP BOTH (FORCE)
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </section>
    </main>
    
    <x-footer />
    <script>
        document.addEventListener('click', function(event) {
            const dropdowns = document.querySelectorAll('details.custom-dropdown');
            dropdowns.forEach((details) => {
                if (!details.contains(event.target)) {
                    details.removeAttribute('open');
                }
            });
        });

        document.addEventListener('alpine:init', () => {
            Alpine.data('moderationPanel', () => ({
                modalOpen: false, 
                currentItem: null, 
                duplicates: [], 
                message: '',
                csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

                async checkApprove(id, itemData) {
                    this.updateCardStatus(id, 'processing');
                    try {
                        const url = `{{ route('fetch-wallpapers.approve', ':id') }}`.replace(':id', id);
                        const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } });
                        const data = await response.json();

                        if (data.status === 'success') {
                            this.removeCard(id);
                            showToast(data.message, 'success');
                        } else if (data.status === 'duplicate') {
                            this.removeCard(id); 
                            showToast(data.message, 'error');
                        } else if (data.status === 'warning') {
                            this.restoreCardStatus(id); 
                            this.currentItem = itemData; 
                            this.duplicates = data.duplicates;
                            this.message = data.message;
                            this.modalOpen = true;
                        } else {
                            this.restoreCardStatus(id);
                            showToast(data.message, 'error');
                        }
                    } catch (e) { 
                        console.error(e); 
                        this.restoreCardStatus(id); 
                        showToast('System Error', 'error'); 
                    }
                },

                async forceApprove(event) {
                    if (!this.currentItem) return;
                    const btn = event ? event.currentTarget : null;
                    let originalHtml = '';
                    if (btn) {
                        originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> PROCESSING...';
                        btn.disabled = true;
                        btn.classList.add('opacity-75', 'cursor-not-allowed');
                    }

                    const id = this.currentItem.id;
                    try {
                        const url = `{{ route('fetch-wallpapers.approve', ':id') }}?force=true`.replace(':id', id);
                        const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } });
                        const data = await response.json();
                        
                        if(data.status === 'success') {
                            this.closeModal();
                            this.removeCard(id);
                            showToast(data.message, 'success');
                        } else {
                            showToast(data.message || 'Failed to process forced approval.', 'error');
                        }
                    } catch(e) { 
                        console.error(e); 
                        showToast('Network / System Error', 'error'); 
                    } finally {
                        if (btn) { 
                            btn.innerHTML = originalHtml;
                            btn.disabled = false;
                            btn.classList.remove('opacity-75', 'cursor-not-allowed');
                        }
                    }
                },

                async replaceItem(targetId, event) {
                    if (!this.currentItem) return;
                    if (!confirm(`Replace Wallpaper ID ${targetId} with this new image?\nThis will overwrite the file and update metadata.`)) return;

                    const fetchId = this.currentItem.id;
                    const btn = event.currentTarget;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; 
                    btn.disabled = true;

                    try {
                        let url = `{{ route('fetch-wallpapers.replace', ['id' => ':fetchId', 'target_id' => ':targetId']) }}`.replace(':fetchId', fetchId).replace(':targetId', targetId);
                        const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } });
                        const data = await response.json();
                        
                        if (data.status === 'success') {
                            this.closeModal();
                            this.removeCard(fetchId);
                            showToast(data.message, 'success');
                        } else {
                            showToast(data.message || 'Failed', 'error');
                            btn.innerHTML = originalText; btn.disabled = false;
                        }
                    } catch (e) {
                        console.error(e); showToast('System Error', 'error');
                        btn.innerHTML = originalText; btn.disabled = false;
                    }
                },

                async rejectFromModal() {
                    if(this.currentItem) {
                        await this.rejectItem(this.currentItem.id);
                        this.closeModal();
                    }
                },

                async rejectItem(id) {
                    try {
                        const url = `{{ route('fetch-wallpapers.reject', ':id') }}`.replace(':id', id);
                        const response = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } });
                        if((await response.json()).status === 'success') {
                            this.removeCard(id); 
                            showToast('Item rejected/marked as duplicate.', 'success');
                        }
                    } catch(e) { console.error(e); }
                },

                async deleteItem(id) {
                    if(!confirm('Delete permanently?')) return;
                    try {
                        const url = `{{ route('fetch-wallpapers.destroy', ':id') }}`.replace(':id', id);
                        const response = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' } });
                        if((await response.json()).status === 'success') {
                            this.removeCard(id); 
                            showToast('Item permanently deleted.', 'success');
                        }
                    } catch(e) { console.error(e); }
                },

                updateCardStatus(id, status) {
                    const card = document.getElementById(`card-${id}`);
                    if(card) card.querySelector('img').classList.add('filter', 'blur-md', 'opacity-40');
                },
                
                restoreCardStatus(id) {
                    const card = document.getElementById(`card-${id}`);
                    if(card) card.querySelector('img').classList.remove('filter', 'blur-md', 'opacity-40');
                },
                
                removeCard(id) {
                    const card = document.getElementById(`card-${id}`);
                    if (card) {
                        card.style.transition = "all 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
                        const img = card.querySelector('img');
                        if (img) img.style.filter = "brightness(0.2) grayscale(100%)";
                        const overlayDiv = card.querySelector('.cursor-pointer');
                        if (overlayDiv) {
                            overlayDiv.style.position = 'relative';
                            overlayDiv.insertAdjacentHTML('beforeend', '<div class="absolute inset-0 bg-slate-950/80 z-30 flex items-center justify-center backdrop-blur-sm"><i class="fa-solid fa-check text-4xl text-cyan-500 opacity-50"></i></div>');
                        }
                        card.style.pointerEvents = 'none';
                        card.style.opacity = '0.4';
                        card.style.transform = 'scale(0.95)';
                    }
                },
                
                closeModal() { 
                    this.modalOpen = false; 
                    setTimeout(() => { this.currentItem = null; this.duplicates = []; }, 300); 
                }
            }));
        });

        function showToast(msg, type) {
            const div = document.createElement('div');
            const icon = type === 'success' ? '<i class="fa-solid fa-circle-check text-green-400 mr-2"></i>' : '<i class="fa-solid fa-circle-xmark text-red-400 mr-2"></i>';
            div.className = `fixed bottom-6 right-6 px-6 py-4 rounded-xl shadow-[0_10px_40px_rgba(0,0,0,0.5)] text-sm font-bold z-[100] transform transition-all duration-400 translate-y-12 opacity-0 border backdrop-blur-md flex items-center ${type==='success'?'bg-slate-900/90 border-green-500/30 text-slate-200':'bg-slate-900/90 border-red-500/30 text-slate-200'}`;
            div.innerHTML = `${icon} ${msg}`;
            document.body.appendChild(div);
            requestAnimationFrame(() => { setTimeout(() => div.classList.remove('translate-y-12', 'opacity-0'), 10); });
            setTimeout(() => { div.classList.add('translate-y-12', 'opacity-0'); setTimeout(()=>div.remove(), 400); }, 3500);
        }
    </script>
</body>
</html>