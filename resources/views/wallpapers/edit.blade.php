<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Edit Wallpaper #{{ $wallpaper->id }} - {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">

    <x-assets />

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
        .focus-ring { outline: none; transition: all 0.2s ease; }
        .focus-ring:focus { border-color: #06b6d4; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1); }
        .ts-control { background-color: #020617 !important; border: 1px solid #1e293b !important; color: #f8fafc !important; border-radius: 0.75rem !important; padding: 10px 16px !important; box-shadow: none !important; }
        .ts-control.focus { border-color: #06b6d4 !important; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.1) !important; }
        .ts-dropdown { background-color: #0f172a !important; border: 1px solid rgba(71, 85, 105, 0.5) !important; color: #e2e8f0 !important; border-radius: 0.75rem !important; overflow: hidden !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important; padding: 5px !important; z-index: 9999 !important; }
        .ts-dropdown .option { border-radius: 6px !important; padding: 8px 12px !important; }
        .ts-dropdown .active { background-color: rgba(6, 182, 212, 0.15) !important; color: #22d3ee !important; }
        .ts-control .item { background-color: rgba(6, 182, 212, 0.15) !important; border: 1px solid rgba(6, 182, 212, 0.3) !important; color: #22d3ee !important; border-radius: 8px !important; padding: 4px 10px !important; font-size: 0.75rem !important; font-weight: 600 !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white antialiased" x-data="{ deleteModalOpen: false }">

    <x-navbar />

    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300" x-data="{ 
        isSaving: false, 
        isReuploading: false, 
        deleteModalOpen: false,
        toast: { visible: false, message: '', type: 'success' }, 
        showToast(msg, type = 'success') { 
            this.toast.message = msg; 
            this.toast.type = type; 
            this.toast.visible = true; 
            setTimeout(() => this.toast.visible = false, 3000); 
        }, 
        async submitForm(e) { 
            if(this.isSaving) return; 
            this.isSaving = true; 
            let formData = new FormData(e.target); 
            try { 
                let response = await fetch(e.target.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); 
                let resData = await response.json().catch(() => ({})); 
                if (response.ok) { 
                    this.showToast('Changes published successfully!', 'success'); 
                    if(resData.updated_at) document.getElementById('info-updated-at').innerText = resData.updated_at; 
                } else { 
                    if (response.status === 422 && resData.errors) { 
                        const firstKey = Object.keys(resData.errors)[0]; 
                        this.showToast(resData.errors[firstKey][0], 'error'); 
                    } else { 
                        this.showToast(resData.message || 'Failed to save data.', 'error'); 
                    } 
                } 
            } catch (err) { 
                this.showToast('Network error occurred.', 'error'); 
            } finally { 
                this.isSaving = false; 
            } 
        }, 
        async submitReupload(e) { 
            if(this.isReuploading) return; 
            this.isReuploading = true; 
            let formData = new FormData(e.target); 
            try { 
                let response = await fetch(e.target.action, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); 
                let resData = await response.json().catch(() => ({})); 
                if (response.ok) { 
                    this.showToast('Media re-fetched successfully! Reloading page...', 'success'); 
                    setTimeout(() => window.location.reload(), 1500); 
                } else { 
                    this.showToast(resData.message || 'Failed to re-fetch media.', 'error'); 
                } 
            } catch (err) { 
                this.showToast('Network error occurred.', 'error'); 
            } finally { 
                this.isReuploading = false; 
            } 
        } 
    }">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <div x-cloak x-show="toast.visible" 
            x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-[-1rem] scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" 
            :class="toast.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400'" 
            class="fixed top-24 right-4 sm:right-8 z-[100] px-5 py-3.5 rounded-2xl border font-bold text-sm flex items-center gap-3 shadow-[0_10px_40px_rgba(0,0,0,0.5)] backdrop-blur-xl">
            <svg x-show="toast.type === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <svg x-show="toast.type === 'error'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span x-text="toast.message"></span>
        </div>

        <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <a href="{{ route('wallpapers.index') }}" class="hover:text-cyan-400 transition-colors">Wallpapers</a>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Editor</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded-md border border-white/5">#{{ $wallpaper->id }}</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none flex items-center gap-4">
                        Edit <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm font-mono">WP #{{ $wallpaper->id }}</span>
                    </h1>
                    <div class="flex items-center gap-3 pt-2">
                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-800 border border-slate-700 text-slate-300 font-mono uppercase tracking-widest shadow-sm">{{ $wallpaper->file_type ?? 'JPG' }}</span>
                        <span class="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                            Last Updated: <span class="text-emerald-400 font-bold" id="info-updated-at">{{ $wallpaper->updated_at->diffForHumans() }}</span>
                        </span>
                    </div>
                </div>
                <a href="{{ route('wallpapers.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/10 hover:border-white/20 hover:bg-slate-800 transition-all shadow-lg">
                    Back to List
                </a>
            </div>

            <div class="flex flex-col lg:grid lg:grid-cols-12 gap-8 lg:gap-10">
                <div class="lg:col-span-4 relative">
                    <div class="sticky top-24 space-y-5">
                        
                        <div class="bg-slate-900/40 border border-white/10 p-2.5 rounded-[2rem] shadow-2xl backdrop-blur-md relative group">
                            <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/5 to-transparent pointer-events-none rounded-[2rem]"></div>
                            <div class="w-full bg-slate-950 rounded-3xl overflow-hidden relative">
                                @if($wallpaper->is_video)
                                    <video width="{{ $wallpaper->width }}" height="{{ $wallpaper->height }}" poster="{{ $wallpaper->preview['jpg'] ?? '' }}" class="w-full h-auto max-h-[45vh] object-contain transition-transform duration-700 group-hover:scale-[1.02]" controls muted playsinline> 
                                        <source src="{{ $wallpaper->preview['mp4'] ?? '' }}" type="video/mp4">
                                    </video>
                                @else
                                    <picture>
                                        <source srcset="{{ $wallpaper->preview['webp'] ?? '' }}" type="image/webp">
                                        <img id="image-preview" src="{{ $wallpaper->preview['jpg'] ?? '' }}" alt="Wallpaper Preview" class="w-full h-auto max-h-[45vh] object-contain transition-transform duration-700 group-hover:scale-[1.02]" fetchpriority="high" decoding="sync" />
                                    </picture>
                                @endif
                            </div>
                        </div>

                        <div class="bg-red-950/20 border border-red-500/20 p-5 rounded-3xl shadow-xl backdrop-blur-md relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/10 blur-[50px] pointer-events-none"></div>
                            <h3 class="text-[10px] font-black text-red-400 uppercase flex items-center gap-2 mb-2 tracking-widest relative z-10">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.8)]"></span>
                                Emergency Reupload
                            </h3>
                            <p class="text-[10px] text-red-300/70 mb-4 leading-relaxed font-medium relative z-10">Use a new URL if original is corrupted or failed to process.</p>
                            
                            <form action="{{ route('wallpapers.reupload', $wallpaper->id) }}" method="POST" @submit.prevent="submitReupload" class="relative z-10">
                                @csrf
                                <div class="space-y-3" x-data="{ reuploadUrl: '' }">
                                    <input type="url" name="file_url" x-model="reuploadUrl" required class="w-full bg-slate-950 border border-red-500/30 focus:border-red-500 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 outline-none transition-colors shadow-inner" placeholder="https://domain.com/image.jpg">
                                    <button type="submit" :disabled="isReuploading || !reuploadUrl" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white border border-red-500/30 rounded-xl text-xs font-bold transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-wider">
                                        <svg x-show="!isReuploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                                        <svg x-cloak x-show="isReuploading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        <span x-text="isReuploading ? 'PROCESSING...' : 'RE-FETCH MEDIA'"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="bg-slate-900/40 border border-white/10 p-5 rounded-3xl shadow-xl backdrop-blur-md space-y-4">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> Asset Metadata
                            </h3>
                            <div class="space-y-3 pt-1">
                                <div class="flex justify-between items-center border-b border-white/5 pb-3">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Dimensions</span>
                                    <span class="text-[11px] font-mono text-white text-right font-medium">{{ $wallpaper->width }} x {{ $wallpaper->height }} px</span>
                                </div>
                                <div class="flex justify-between items-center border-b border-white/5 pb-3">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">File Size</span>
                                    <span class="text-[11px] font-mono font-bold text-cyan-400">{{ number_format(($wallpaper->file_size ?? 0) / 1048576, 2) }} MB</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Community Stats</span>
                                    <div class="flex items-center gap-4 text-[11px] font-mono font-bold text-slate-300">
                                        <div class="flex items-center gap-1.5" title="Total Views">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-cyan-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            <span>{{ $wallpaper->views_count }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5" title="Total Favorites">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-pink-500" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="m11.645 20.91-.007-.003-.022-.012a15.247 15.247 0 0 1-.383-.218 25.18 25.18 0 0 1-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0 1 12 5.052 5.5 5.5 0 0 1 16.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 0 1-4.244 3.17 15.247 15.247 0 0 1-.383.219l-.022.012-.007.004-.003.001a.752.752 0 0 1-.704 0l-.003-.001Z"/></svg>
                                            <span>{{ $wallpaper->favorites_count }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900/40 border border-white/10 p-5 rounded-3xl shadow-xl backdrop-blur-md space-y-4">
                            <h3 class="text-[10px] font-black text-orange-400 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-orange-400 shadow-[0_0_8px_rgba(251,146,60,0.8)]"></span> Source Traceability
                            </h3>
                            
                            @if(isset($wallpaper->source_api) && isset($wallpaper->source_id) && in_array(strtolower($wallpaper->source_api), ['danbooru', 'gelbooru', 'zerochan']))
                                @php
                                    $apiLinks = [
                                        'danbooru' => 'https://danbooru.donmai.us/posts/',
                                        'gelbooru' => 'https://gelbooru.com/index.php?page=post&s=view&id=',
                                        'zerochan' => 'https://www.zerochan.net/',
                                    ];
                                    $fullUrl = $apiLinks[strtolower($wallpaper->source_api)] . $wallpaper->source_id;
                                @endphp
                                <div class="bg-slate-950/60 border border-slate-800 p-4 rounded-2xl space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Source API</span>
                                        <span class="text-xs font-black text-cyan-400 uppercase tracking-wider">{{ $wallpaper->source_api }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Source ID</span>
                                        <span class="text-xs font-mono font-bold text-slate-300">{{ $wallpaper->source_id }}</span>
                                    </div>
                                    <a href="{{ $fullUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 flex justify-between items-center group bg-slate-900 border border-slate-700 hover:border-cyan-500/50 rounded-xl p-3 transition-all duration-200">
                                        <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wider group-hover:text-white transition-colors">
                                            Open Source Data
                                        </span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 group-hover:text-cyan-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    </a>
                                </div>
                            @endif
                            
                            <div>
                                <label for="source_url" class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">External Source URL</label>
                                <input type="url" id="source_url" name="source_url" form="wallpaperForm" value="{{ old('source_url', $wallpaper->source_url) }}" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500/50 rounded-xl px-4 py-3 text-sm font-mono text-white placeholder-slate-600 outline-none transition-colors shadow-inner" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 space-y-8">
                    <form id="wallpaperForm" action="{{ route('wallpapers.update', $wallpaper->id) }}" method="POST" @submit.prevent="submitForm" class="space-y-8">
                        @csrf
                        @method('PATCH')
                        
                        <div class="bg-slate-900/60 border border-white/10 p-6 sm:p-8 rounded-[2rem] shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden group">
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>
                            
                            <h2 class="text-xl font-black text-white flex items-center gap-3 border-b border-white/5 pb-4">
                                <span class="w-1.5 h-6 bg-gradient-to-b from-cyan-400 to-blue-600 rounded-full shadow-[0_0_10px_rgba(34,211,238,0.5)]"></span> 
                                Taxonomy & Setup
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="rating" class="block text-sm font-bold text-slate-400">Content Rating</label>
                                    <div class="relative">
                                        <select id="rating" name="rating" class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-4 pr-10 py-3 text-white font-bold text-sm uppercase appearance-none cursor-pointer focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                                            <option value="general" {{ $wallpaper->rating == 'general' ? 'selected' : '' }}>✅ General</option>
                                            <option value="sensitive" {{ $wallpaper->rating == 'sensitive' ? 'selected' : '' }}>⚠️ Sensitive</option>
                                            <option value="questionable" {{ $wallpaper->rating == 'questionable' ? 'selected' : '' }}>🔞 Questionable</option>
                                            <option value="explicit" {{ $wallpaper->rating == 'explicit' ? 'selected' : '' }}>🚫 Explicit</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg></div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-sm font-bold text-slate-400 invisible hidden md:block">Settings</label>
                                    <label class="relative flex items-center justify-between cursor-pointer group bg-slate-950 border border-slate-800 py-[11px] px-4 rounded-xl hover:border-slate-700 transition-colors shadow-inner h-[46px]">
                                        <div class="flex flex-col justify-center">
                                            <span class="text-xs font-bold text-slate-300 group-hover:text-white uppercase tracking-widest transition-colors leading-none mb-1">Debug Mode</span>
                                            <span class="text-[9px] text-slate-500 font-medium leading-none">Hide from public listing</span>
                                        </div>
                                        <div class="relative flex-shrink-0">
                                            <input type="hidden" name="debug" value="0">
                                            <input type="checkbox" name="debug" value="1" {{ old('debug', $wallpaper->debug) ? 'checked' : '' }} class="sr-only peer">
                                            <div class="w-10 h-5 bg-slate-800 rounded-full peer peer-checked:bg-cyan-500 transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5 shadow-inner"></div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="characters" class="block text-sm font-bold text-slate-400">Connected Characters</label>
                                    <div class="relative z-50">
                                        <select id="characters" name="characters[]" multiple placeholder="Select characters..."></select>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label for="artists" class="block text-sm font-bold text-slate-400">Artists / Authors</label>
                                    <div class="relative z-40">
                                        <select id="artists" name="artists[]" multiple placeholder="Select artists..."></select>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label for="tags" class="block text-sm font-bold text-slate-400">Tags / General Labels</label>
                                <div class="relative z-30">
                                    <select id="tags" name="tags[]" multiple placeholder="Select tags..."></select>
                                </div>
                            </div>
                        </div>

                        <div x-data="{ 
                            jsonInput: '', showSuccess: false, isGenerating: false, copySuccess: false, generatedPrompt: '', 
                            async generateAndCopyPrompt() { 
                                if (this.isGenerating) return; 
                                this.isGenerating = true; this.copySuccess = false; 
                                try { 
                                    const response = await fetch('{{ route('seo.wallpaper', $wallpaper->id) }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }); 
                                    if (!response.ok) throw new Error('Failed to respond from server'); 
                                    const data = await response.json(); 
                                    if ((data.status === 'success' || data.success) && data.prompt) { 
                                        this.generatedPrompt = data.prompt; await navigator.clipboard.writeText(data.prompt); 
                                        this.copySuccess = true; setTimeout(() => this.copySuccess = false, 3000); 
                                    } else alert('Invalid JSON format or empty prompt.'); 
                                } catch (error) { alert('An error occurred while fetching the prompt.'); } 
                                finally { this.isGenerating = false; } 
                            }, 
                            async copyPromptManual() { 
                                if (!this.generatedPrompt) return; 
                                await navigator.clipboard.writeText(this.generatedPrompt); 
                                this.copySuccess = true; setTimeout(() => this.copySuccess = false, 3000); 
                            }, 
                            applyJson() { 
                                if (!this.jsonInput.trim()) return; 
                                try { 
                                    let data = JSON.parse(this.jsonInput); 
                                    const fields = ['slug', 'image_alt', 'seo_title', 'seo_description', 'image_description', 'keywords', 'seo_keywords']; 
                                    let appliedCount = 0; 
                                    fields.forEach(field => { 
                                        let el = document.getElementById(field); 
                                        if (el && el.value.trim() === '' && data[field]) { el.value = data[field]; appliedCount++; } 
                                    }); 
                                    if (appliedCount > 0) { this.showSuccess = true; setTimeout(() => this.showSuccess = false, 3000); } 
                                    else alert('JSON fields are already filled or keys do not match. Clear manually to overwrite.'); 
                                } catch(e) { alert('Failed to execute JSON. Ensure the JSON format is valid!'); } 
                            }, 
                            clearField(id) { let el = document.getElementById(id); if(el) { el.value = ''; el.focus(); } } 
                        }" class="bg-slate-900/60 border border-white/10 p-6 sm:p-8 rounded-[2rem] shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden group">
                            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500/10 blur-[100px] pointer-events-none"></div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-white/5 pb-4">
                                <h2 class="text-xl font-black text-white flex items-center gap-3">
                                    <span class="w-1.5 h-6 bg-gradient-to-b from-purple-400 to-indigo-600 rounded-full shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span> 
                                    SEO & AI Optimizer
                                </h2>
                                <button type="button" @click="generateAndCopyPrompt" :disabled="isGenerating" class="px-5 py-2.5 bg-purple-500/10 text-purple-400 border border-purple-500/20 hover:bg-purple-500 hover:text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2 disabled:opacity-50">
                                    <svg x-show="!isGenerating && !copySuccess" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    <svg x-cloak x-show="isGenerating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    <svg x-cloak x-show="copySuccess" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="isGenerating ? 'ANALYZING...' : (copySuccess ? 'COPIED TO CLIPBOARD!' : 'GENERATE AI PROMPT')"></span>
                                </button>
                            </div>

                            <div class="space-y-4 bg-slate-950/40 p-5 rounded-2xl border border-slate-800/50">
                                <div x-cloak x-show="generatedPrompt" class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wider flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> Generated AI Prompt</span>
                                        <button type="button" @click="copyPromptManual" class="text-[10px] bg-purple-500/20 hover:bg-purple-500 text-purple-300 hover:text-white px-2 py-1 rounded-md transition-colors">Copy Again</button>
                                    </div>
                                    <textarea x-model="generatedPrompt" readonly rows="3" class="w-full bg-slate-900 border border-purple-500/30 rounded-xl px-3 py-2 text-purple-200/80 text-xs font-mono resize-y outline-none"></textarea>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg> JSON Data Injector</label>
                                        <span x-cloak x-show="showSuccess" class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">✅ Applied!</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <textarea x-model="jsonInput" rows="1" class="flex-1 w-full bg-slate-900 border border-emerald-500/30 rounded-xl px-4 py-2.5 text-white text-sm font-mono resize-none focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all placeholder-slate-600" placeholder='{"seo_title": "...", "seo_description": "...", ...}'></textarea>
                                        <button type="button" @click="applyJson" class="sm:w-auto w-full px-5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 rounded-xl text-sm font-black shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all flex items-center justify-center py-2.5 sm:py-0">Apply Data</button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="seo_title" class="block text-sm font-bold text-slate-400">Meta Title</label>
                                    <div class="relative">
                                        <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $wallpaper->seo_title) }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-4 pr-10 py-3 text-white text-sm focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">
                                        <button type="button" @click="clearField('seo_title')" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-500 hover:text-red-400 rounded-md"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label for="slug" class="block text-sm font-bold text-slate-400">Slug (URL)</label>
                                    <div class="relative">
                                        <input type="text" id="slug" name="slug" value="{{ old('slug', $wallpaper->slug) }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-4 pr-10 py-3 text-cyan-200 font-mono text-sm focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                                        <button type="button" @click="clearField('slug')" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-500 hover:text-red-400 rounded-md"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-end">
                                        <label for="seo_description" class="block text-sm font-bold text-slate-400">Meta Description</label>
                                        <button type="button" @click="clearField('seo_description')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="seo_description" name="seo_description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-y focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('seo_description', $wallpaper->seo_description) }}</textarea>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex justify-between items-end">
                                        <label for="image_description" class="block text-sm font-bold text-slate-400">Image Description</label>
                                        <button type="button" @click="clearField('image_description')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="image_description" name="image_description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-y focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('image_description', $wallpaper->image_description) }}</textarea>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <div class="flex justify-between items-end">
                                        <label for="seo_keywords" class="block text-sm font-bold text-slate-400">SEO Keywords</label>
                                        <button type="button" @click="clearField('seo_keywords')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="seo_keywords" name="seo_keywords" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-none focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('seo_keywords', $wallpaper->seo_keywords) }}</textarea>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex justify-between items-end">
                                        <label for="image_alt" class="block text-sm font-bold text-slate-400">Image HTML Alt</label>
                                        <button type="button" @click="clearField('image_alt')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="image_alt" name="image_alt" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-none focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('image_alt', $wallpaper->image_alt) }}</textarea>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between items-end">
                                    <label for="keywords" class="block text-sm font-bold text-slate-400">Internal Keywords</label>
                                    <button type="button" @click="clearField('keywords')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                </div>
                                <textarea id="keywords" name="keywords" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-none focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('keywords', $wallpaper->keywords) }}</textarea>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-xl border-t border-white/10 py-4 shadow-[0_-10px_40px_rgba(0,0,0,0.5)]">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                
                <button type="button" @click="deleteModalOpen = true" class="w-full sm:w-auto px-6 py-3 rounded-xl text-sm font-bold text-red-400 hover:text-white bg-red-500/10 hover:bg-red-600 border border-red-500/20 hover:border-red-500/50 transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Delete Permanently
                </button>

                <div class="flex items-center w-full sm:w-auto gap-4">
                    <a href="{{ route('wallpapers.index') }}" class="flex-1 sm:flex-none text-center px-6 py-3 rounded-xl font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/5 hover:border-white/20 transition-all text-sm">Cancel</a>
                    <button type="submit" form="wallpaperForm" :disabled="isSaving" class="flex-1 sm:flex-none px-8 py-3 rounded-xl font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-wait">
                        <svg x-cloak x-show="isSaving" class="w-5 h-5 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isSaving ? 'Publishing...' : 'Publish Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <x-footer />

    <template x-teleport="body">
        <div x-show="deleteModalOpen" style="display: none;" class="fixed inset-0 z-[200] flex items-center justify-center px-4 overflow-y-auto" role="dialog" aria-modal="true">

            <div x-show="deleteModalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="deleteModalOpen = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

            <div x-show="deleteModalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4" class="relative transform overflow-hidden rounded-3xl border border-red-500/20 bg-slate-900 p-8 text-left shadow-2xl shadow-red-900/20 transition-all w-full max-w-sm">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-500/10 border border-red-500/20 mb-6">
                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>

                <div class="text-center">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-white" id="modal-title">Delete Wallpaper?</h3>
                    <div class="mt-3">
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Are you sure you want to delete <span class="text-red-400 font-mono">#{{ $wallpaper->id }}</span>? This action creates a permanent database record removal and <strong class="text-slate-300">cannot be undone</strong>.
                        </p>
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse sm:flex-row sm:justify-center gap-3">
                    <button type="button" @click="deleteModalOpen = false" class="inline-flex w-full justify-center rounded-xl border border-slate-700 bg-slate-800/50 px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-slate-300 shadow-sm hover:bg-slate-700 hover:text-white transition-all sm:w-auto">
                        Cancel
                    </button>
                    <form action="{{ route('wallpapers.delete', $wallpaper->id) }}" method="POST" class="inline-flex w-full sm:w-auto m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-3 text-[10px] font-bold uppercase tracking-widest text-white shadow-lg shadow-red-600/20 hover:bg-red-500 transition-all">
                            Yes, Delete It
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        const PRELOAD_CHARS = @json($preloadedChars ?? []);
        const PRELOAD_ARTISTS = @json($preloadedArtists ?? []);
        const PRELOAD_TAGS = @json($preloadedTags ?? []);

        document.addEventListener('DOMContentLoaded', () => {
            const apiSelectConfig = (url, placeholder, preloadedData) => ({ valueField: 'id', labelField: 'text', searchField: 'text', create: false, plugins: ['remove_button'], options: preloadedData, items: preloadedData.map(i => i.id), load: (query, callback) => { if (!query.length) return callback(); fetch(`${url}?q=${encodeURIComponent(query)}`).then(r => r.json()).then(j => callback(j)).catch(() => callback()); }, render: { option: (data, escape) => `<div class="px-2 py-1 text-xs text-slate-300 hover:text-white">${escape(data.text)}</div>`, item: (data, escape) => `<div class="text-xs font-medium text-white">${escape(data.text)}</div>` } });
            if(document.getElementById('characters')) { new TomSelect('#characters', apiSelectConfig("{{ route('characters.list') }}", "Search Characters...", PRELOAD_CHARS)); }
            if(document.getElementById('artists')) { new TomSelect('#artists', apiSelectConfig("{{ route('artists.list') }}", "Search Artists...", PRELOAD_ARTISTS)); }
            if(document.getElementById('tags')) { new TomSelect('#tags', apiSelectConfig("{{ route('tags.list') }}", "Search Tags...", PRELOAD_TAGS)); }
            
            const metaTitleInput = document.getElementById('seo_title');
            const slugInput = document.getElementById('slug');
            const createSlug = (text) => text.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-').replace(/^-+/, '').replace(/-+$/, '');
            
            if(metaTitleInput && slugInput) { metaTitleInput.addEventListener('input', function() { if (slugInput.value === '' || slugInput.value === createSlug(this.defaultValue)) { slugInput.value = createSlug(this.value); } }); }
        });
    </script>
</body>
</html>