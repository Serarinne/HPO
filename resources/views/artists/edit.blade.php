<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Artist: {{ $artist->name }} - {{ config('app.name') }}</title>
    <x-assets />
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
        .focus-ring { @apply focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all duration-200; }
        select.social-select { -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none; }
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
                let response = await fetch(e.target.action, { 
                    method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } 
                }); 
                let resData = await response.json().catch(() => ({})); 
                if (response.ok) { 
                    this.showToast(resData.message || 'Artist changes saved successfully!', 'success'); 
                    if(resData.updated_at) { 
                        const infoEl = document.getElementById('info-updated-at'); 
                        if(infoEl) infoEl.innerText = resData.updated_at; 
                    } 
                    if(resData.slug) { 
                        const newUrl = `{{ route('artists.edit', ':id') }}`.replace(':id', {{ $artist->id }}); 
                        window.history.replaceState(null, null, newUrl); 
                    } 
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
                        <a href="{{ route('artists.index') }}" class="hover:text-cyan-400 transition-colors">Artists</a>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Editor</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded-md border border-white/5">#{{ $artist->id }}</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Edit <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm" id="header-name">{{ $artist->name }}</span>
                    </h1>
                </div>
                <a href="{{ route('artists.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/10 hover:border-white/20 hover:bg-slate-800 transition-all shadow-lg">
                    Back to List
                </a>
            </div>

            <div class="flex flex-col lg:grid lg:grid-cols-12 gap-8 lg:gap-10">
                <div class="lg:col-span-4 relative">
                    <div class="sticky top-24 space-y-6">
                        <div>
                            <div class="bg-slate-900/40 border border-white/10 p-2.5 rounded-[2rem] shadow-2xl backdrop-blur-md">
                                <div class="group relative w-full rounded-3xl overflow-hidden cursor-pointer bg-slate-950" onclick="document.getElementById('image').click()">
                                    <div class="absolute inset-0 bg-gradient-to-b from-cyan-500/10 to-blue-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10 pointer-events-none"></div>
                                    <img id="image-preview" src="{{ $artist->image['webp'] }}" alt="{{ $artist->name }}" class="w-full aspect-square object-cover transition-all duration-700 group-hover:scale-105 group-hover:opacity-60" fetchpriority="high" decoding="sync" />
                                    
                                    <div class="absolute inset-0 z-20 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                                        <div class="p-4 rounded-2xl bg-slate-900/80 border border-cyan-500/50 text-cyan-400 mb-3 shadow-[0_0_30px_rgba(34,211,238,0.3)] backdrop-blur-sm transform scale-90 group-hover:scale-100 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </div>
                                        <span class="text-white text-xs font-black uppercase tracking-widest drop-shadow-md">Replace Avatar</span>
                                    </div>
                                    <input type="file" name="image" id="image" form="artistForm" class="hidden" accept="image/*" onchange="previewImage(this)">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-500 text-center italic mt-3 font-medium">Recommended: Square orientation (1:1 ratio)</p>
                        </div>

                        <div class="bg-slate-900/40 border border-white/10 p-6 rounded-3xl shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 blur-[50px]"></div>
                            
                            <h3 class="text-xs font-black uppercase tracking-widest text-emerald-400 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                System Properties
                            </h3>
                            
                            <div class="pt-2">
                                <label class="relative flex items-center justify-between cursor-pointer group bg-slate-950/50 border border-slate-800 p-3 rounded-xl hover:border-slate-700 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-colors">Enable Debug Mode</span>
                                        <span class="text-[10px] text-slate-500 mt-0.5 font-medium">Hide from public listing</span>
                                    </div>
                                    <div class="relative">
                                        <input type="hidden" name="debug" value="0" form="artistForm">
                                        <input type="checkbox" name="debug" value="1" form="artistForm" {{ old('debug', $artist->debug) ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-800 rounded-full peer peer-checked:bg-cyan-500 transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5 shadow-inner"></div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 space-y-8">
                    <form id="artistForm" action="{{ route('artists.update', $artist->id) }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm" class="space-y-8">
                        @csrf
                        @method('PATCH')
                        
                        <div class="bg-slate-900/60 border border-white/10 p-6 sm:p-8 rounded-[2rem] shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden group">
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>
                            
                            <h2 class="text-xl font-black text-white flex items-center gap-3 border-b border-white/5 pb-4">
                                <span class="w-1.5 h-6 bg-gradient-to-b from-cyan-400 to-blue-600 rounded-full shadow-[0_0_10px_rgba(34,211,238,0.5)]"></span> 
                                Core Identity
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="name" class="block text-sm font-bold text-slate-400">Artist Name</label>
                                    <div class="relative">
                                        <input type="text" id="name" name="name" value="{{ old('name', $artist->name) }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-4 pr-10 py-3 text-white font-bold text-lg focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                                        <a href="https://www.google.com/search?q={{ urlencode($artist->name) }}" target="_blank" title="Search Google" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 bg-slate-800 hover:bg-cyan-500/20 text-slate-400 hover:text-cyan-400 rounded-lg transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></a>
                                    </div>
                                </div>
                                
                                <div class="space-y-2" x-data="{ clearField() { let el = document.getElementById('slug'); el.value = ''; el.focus(); } }">
                                    <label for="slug" class="block text-sm font-bold text-slate-400">Unique Slug</label>
                                    <div class="relative">
                                        <input type="text" id="slug" name="slug" value="{{ old('slug', $artist->slug) }}" required class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-4 pr-10 py-3 text-cyan-200 font-mono text-sm focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                                        <button type="button" @click="clearField()" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-500 hover:text-red-400 hover:bg-red-400/10 rounded-md transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2" x-data="{ clearField() { let el = document.getElementById('keywords'); el.value = ''; el.focus(); } }">
                                    <div class="flex justify-between items-end">
                                        <label for="keywords" class="block text-sm font-bold text-slate-400">Aliases / Keywords</label>
                                        <button type="button" @click="clearField()" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="keywords" name="keywords" rows="4" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm leading-relaxed resize-y focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner" placeholder="Other pen names, circles...">{{ old('keywords', $artist->keywords) }}</textarea>
                                </div>

                                <div class="space-y-2" x-data="{ clearField() { let el = document.getElementById('description'); el.value = ''; el.focus(); } }">
                                    <div class="flex justify-between items-end">
                                        <label for="description" class="block text-sm font-bold text-slate-400">Description</label>
                                        <button type="button" @click="clearField()" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="description" name="description" rows="4" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm leading-relaxed resize-y focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner" placeholder="Short biography or art style description...">{{ old('description', $artist->description) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div x-data="{ 
                            jsonInput: '', showSuccess: false, isGenerating: false, copySuccess: false, generatedPrompt: '', 
                            async generateAndCopyPrompt() { 
                                if (this.isGenerating) return; 
                                this.isGenerating = true; this.copySuccess = false; 
                                try { 
                                    const response = await fetch('{{ route('seo.artists', $artist->id) }}', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }); 
                                    if (!response.ok) throw new Error('Failed to get response from server'); 
                                    const data = await response.json(); 
                                    if (data.success && data.prompt) { 
                                        this.generatedPrompt = data.prompt; await navigator.clipboard.writeText(data.prompt); 
                                        this.copySuccess = true; setTimeout(() => this.copySuccess = false, 3000); 
                                    } else { alert('JSON format mismatch or empty prompt.'); } 
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
                                    const fields = [ 
                                        { id: 'meta_title', value: data.seo_title || data.title || data.meta_title }, 
                                        { id: 'meta_description', value: data.seo_description || data.description || data.meta_description }, 
                                        { id: 'meta_keywords', value: data.seo_keywords || data.keywords || data.meta_keywords }, 
                                        { id: 'keywords', value: data.alias || data.keywords || data.meta_keywords }, 
                                        { id: 'description', value: data.artist_description || data.description } 
                                    ]; 
                                    let appliedCount = 0; 
                                    fields.forEach(field => { 
                                        let el = document.getElementById(field.id); 
                                        if (el && el.value.trim() === '' && field.value) { el.value = field.value; appliedCount++; } 
                                    }); 
                                    if (appliedCount > 0) { this.showSuccess = true; setTimeout(() => this.showSuccess = false, 3000); } 
                                    else alert('Fields already filled or keys do not match. Clear manually if you wish to overwrite.'); 
                                } catch(e) { alert('Failed to execute JSON. Ensure it is valid!'); } 
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
                                    <span x-text="isGenerating ? 'Analyzing...' : (copySuccess ? 'Copied to Clipboard!' : 'Generate AI Prompt')"></span>
                                </button>
                            </div>

                            <div class="space-y-4 bg-slate-950/40 p-5 rounded-2xl border border-slate-800/50">
                                <div x-cloak x-show="generatedPrompt" class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wider flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> Generated AI Prompt</span>
                                        <button type="button" @click="copyPromptManual" class="text-[10px] bg-purple-500/20 hover:bg-purple-500 text-purple-300 hover:text-white px-2 py-1 rounded-md transition-colors">Copy Again</button>
                                    </div>
                                    <textarea x-model="generatedPrompt" readonly rows="2" class="w-full bg-slate-900 border border-purple-500/30 rounded-xl px-3 py-2 text-purple-200/80 text-xs font-mono resize-y outline-none"></textarea>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg> JSON Data Injector</label>
                                        <span x-cloak x-show="showSuccess" class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">✅ Applied!</span>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <textarea x-model="jsonInput" rows="1" class="flex-1 w-full bg-slate-900 border border-emerald-500/30 rounded-xl px-4 py-2.5 text-white text-sm font-mono resize-none focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all placeholder-slate-600" placeholder='Paste AI JSON response here...'></textarea>
                                        <button type="button" @click="applyJson" class="sm:w-auto w-full px-5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 rounded-xl text-sm font-black shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all flex items-center justify-center py-2.5 sm:py-0">Apply Data</button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2 md:col-span-2">
                                    <label for="meta_title" class="block text-sm font-bold text-slate-400">SEO Optimized Title</label>
                                    <div class="relative">
                                        <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $artist->seo_title) }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-4 pr-10 py-3 text-white text-sm focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">
                                        <button type="button" @click="clearField('meta_title')" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-500 hover:text-red-400 rounded-md"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between items-end">
                                        <label for="meta_description" class="block text-sm font-bold text-slate-400">SEO Meta Description</label>
                                        <button type="button" @click="clearField('meta_description')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="meta_description" name="meta_description" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-none focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('meta_description', $artist->seo_description) }}</textarea>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="flex justify-between items-end">
                                        <label for="meta_keywords" class="block text-sm font-bold text-slate-400">SEO Meta Keywords</label>
                                        <button type="button" @click="clearField('meta_keywords')" class="text-[10px] uppercase tracking-wider font-bold text-slate-500 hover:text-red-400 transition-colors">Clear</button>
                                    </div>
                                    <textarea id="meta_keywords" name="meta_keywords" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-none focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner">{{ old('meta_keywords', $artist->seo_keywords) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 py-2">
                            <div class="h-px bg-slate-800 flex-grow rounded-full"></div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Secondary Settings</span>
                            <div class="h-px bg-slate-800 flex-grow rounded-full"></div>
                        </div>

                        <div x-data="{ open: false }" class="bg-slate-900/60 border border-white/10 rounded-3xl shadow-lg backdrop-blur-md relative overflow-hidden group">
                            <button type="button" @click="open = !open" class="w-full px-6 py-5 flex items-center justify-between focus:outline-none hover:bg-slate-800/30 transition-colors">
                                <h2 class="text-lg font-bold text-white flex items-center gap-3">
                                    <span class="w-1.5 h-5 bg-gradient-to-b from-pink-400 to-rose-600 rounded-full shadow-[0_0_10px_rgba(244,114,182,0.5)]"></span> 
                                    Social Profiles
                                </h2>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] font-bold text-pink-400 bg-pink-500/10 px-2.5 py-1 rounded-md border border-pink-500/20" x-text="open ? 'Close Panel' : 'Manage Links'"></span>
                                    <svg :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </button>
                            
                            <div x-show="open" x-collapse x-cloak>
                                <div class="px-6 pb-6 pt-2 border-t border-white/5" x-data="socialLinksManager({{ json_encode($socialsData ?? []) }})">
                                    <div class="flex justify-end mb-4">
                                        <button type="button" @click="addLink()" class="px-3 py-1.5 bg-pink-500/10 text-pink-400 border border-pink-500/20 hover:bg-pink-500 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg> Add Social
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="(item, index) in links" :key="index">
                                            <div class="flex flex-col sm:flex-row items-stretch bg-slate-950/80 rounded-xl border border-slate-800 overflow-hidden focus-within:border-pink-500/50 transition-all group">
                                                <div class="relative bg-slate-900 border-b sm:border-b-0 sm:border-r border-slate-800 sm:w-40 flex-shrink-0">
                                                    <select x-model="item.platform" :name="`socials[${index}][platform]`" class="w-full bg-transparent border-none text-[10px] font-black text-pink-400 py-3.5 pl-4 pr-8 focus:ring-0 uppercase tracking-wider cursor-pointer outline-none appearance-none h-full">
                                                        <option value="facebook">Facebook</option>
                                                        <option value="x">X (Twitter)</option>
                                                        <option value="pixiv">Pixiv</option>
                                                        <option value="patreon">Patreon</option>
                                                        <option value="instagram">Instagram</option>
                                                        <option value="website">Website</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                    </div>
                                                </div>
                                                <input type="text" x-model="item.url" :name="`socials[${index}][url]`" class="flex-grow bg-transparent py-3 px-4 text-sm font-mono text-slate-200 outline-none placeholder-slate-600" placeholder="https://...">
                                                <div class="flex items-center border-t sm:border-t-0 sm:border-l border-slate-800 bg-slate-900 sm:bg-transparent">
                                                    <a x-show="item.url" :href="item.url" target="_blank" class="p-3 text-slate-400 hover:text-cyan-400 transition-colors flex-1 text-center" title="Test Link"><svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg></a>
                                                    <button type="button" @click="removeLink(index)" class="p-3 text-slate-500 hover:text-red-400 hover:bg-red-400/10 transition-colors flex-1 border-l border-slate-800 sm:border-l-0"><svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="links.length === 0" class="text-center py-6 border border-dashed border-slate-700/50 rounded-xl bg-slate-950/30 text-xs text-slate-500">No social profiles added yet.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="bg-slate-900/60 border border-white/10 rounded-3xl shadow-lg backdrop-blur-md relative overflow-hidden group">
                            <button type="button" @click="open = !open" class="w-full px-6 py-5 flex items-center justify-between focus:outline-none hover:bg-slate-800/30 transition-colors">
                                <h2 class="text-lg font-bold text-white flex items-center gap-3">
                                    <span class="w-1.5 h-5 bg-gradient-to-b from-indigo-400 to-violet-600 rounded-full shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span> 
                                    External API Mapping
                                </h2>
                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] font-bold text-indigo-400 bg-indigo-500/10 px-2.5 py-1 rounded-md border border-indigo-500/20" x-text="open ? 'Close Panel' : 'Manage Tags'"></span>
                                    <svg :class="open ? 'rotate-180' : ''" class="w-5 h-5 text-slate-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </button>
                            
                            <div x-show="open" x-collapse x-cloak>
                                <div class="px-6 pb-6 pt-2 border-t border-white/5" x-data="apiTagsManager({{ $artist->apiTags->toJson() }})">
                                    <div class="flex justify-end mb-4">
                                        <button type="button" @click="addTag()" class="px-3 py-1.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500 hover:text-white rounded-lg text-xs font-bold transition-all flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg> Add Tag
                                        </button>
                                    </div>
                                    <div class="space-y-3">
                                        <template x-for="(apiTag, index) in tags" :key="index">
                                            <div class="flex flex-col sm:flex-row items-stretch bg-slate-950/80 rounded-xl border border-slate-800 overflow-hidden focus-within:border-indigo-500/50 transition-all">
                                                <input type="text" x-model="apiTag.source_api" :name="`api_tags[${index}][source_api]`" class="sm:w-32 bg-slate-900 border-b sm:border-b-0 sm:border-r border-slate-800 text-xs font-black text-indigo-400 py-3 px-4 uppercase text-center outline-none" placeholder="SOURCE">
                                                <input type="text" x-model="apiTag.tag_name" :name="`api_tags[${index}][tag_name]`" class="flex-grow bg-transparent py-3 px-4 text-sm font-mono text-slate-200 outline-none" placeholder="tag_name_here">
                                                <div class="flex items-center border-t sm:border-t-0 sm:border-l border-slate-800 bg-slate-900 sm:bg-transparent">
                                                    <a x-show="apiTag.source_api && apiTag.tag_name" :href="buildLink(apiTag.source_api, apiTag.tag_name)" target="_blank" class="p-3 text-slate-400 hover:text-cyan-400 transition-colors flex-1 text-center" title="Test Link"><svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg></a>
                                                    <button type="button" @click="removeTag(index)" class="p-3 text-slate-500 hover:text-red-400 hover:bg-red-400/10 transition-colors flex-1 border-l border-slate-800 sm:border-l-0"><svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                            </div>
                                        </template>
                                        <div x-show="tags.length === 0" class="text-center py-6 border border-dashed border-slate-700/50 rounded-xl bg-slate-950/30 text-xs text-slate-500">No external API tags mapped yet.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-xl border-t border-white/10 py-4 shadow-[0_-10px_40px_rgba(0,0,0,0.5)]">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <form action="{{ route('artists.delete', $artist->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this artist? This action cannot be undone.');" class="w-full sm:w-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-xl text-sm font-bold text-red-400 hover:text-white bg-red-500/10 hover:bg-red-600 border border-red-500/20 hover:border-red-500/50 transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Delete Artist
                    </button>
                </form>
                
                <div class="flex items-center w-full sm:w-auto gap-4">
                    <a href="{{ route('artists.index') }}" class="flex-1 sm:flex-none text-center px-6 py-3 rounded-xl font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/5 hover:border-white/20 transition-all text-sm">Cancel</a>
                    <button type="submit" form="artistForm" :disabled="isSaving" class="flex-1 sm:flex-none px-8 py-3 rounded-xl font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-wait">
                        <svg x-cloak x-show="isSaving" class="w-5 h-5 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isSaving ? 'Publishing...' : 'Publish Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <x-footer />
    <script>
    const slugify = text => text.toString().toLowerCase().trim().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-');
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('image-preview').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }
    document.addEventListener('alpine:init', () => {
        Alpine.data('socialLinksManager', (initialLinks) => ({
            links: initialLinks || [],
            init() {
                if (!Array.isArray(this.links)) {
                    this.links = Object.values(this.links);
                }
            },
            addLink() { this.links.push({ platform: 'x', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));
        Alpine.data('apiTagsManager', (initialTags) => ({
            tags: initialTags || [],
            addTag() { 
                const nameInput = document.getElementById('name');
                const tagName = nameInput ? nameInput.value.trim() : '';
                const existingSources = this.tags.map(t => (t.source_api || '').toLowerCase());
                let targetSource = '';
                let targetValue = tagName;
                if (!existingSources.includes('danbooru')) {
                    targetSource = 'danbooru';
                } else if (!existingSources.includes('gelbooru')) {
                    targetSource = 'gelbooru';
                } else if (!existingSources.includes('zerochan')) {
                    targetSource = 'zerochan';
                } else {
                    targetSource = 'gelbooru';
                }
                if (targetSource === 'danbooru' || targetSource === 'gelbooru') {
                    targetValue = tagName.toLowerCase().replace(/\s+/g, '_');
                } else if (targetSource === 'zerochan') {
                    targetValue = tagName.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');
                }
                this.tags.push({ source_api: targetSource, tag_name: targetValue }); 
            },
            removeTag(index) { this.tags.splice(index, 1); },
            buildLink(source, tag) {
                if(!source || !tag) return '#';
                const s = source.toLowerCase().trim();
                const t = tag.trim();
                if (s.includes('danbooru')) return `https://danbooru.donmai.us/posts?tags=${t}+rating%3Ageneral`;
                if (s.includes('gelbooru')) return `https://gelbooru.com/index.php?page=post&s=list&tags=${t}+rating%3Ageneral`;
                if (s.includes('yandere'))  return `https://yande.re/post?tags=${t}`;
                if (s.includes('zerochan')) return `https://www.zerochan.net/${t}`;
                if (s.includes('konachan')) return `https://konachan.com/post?tags=${t}`;
                return `https://www.google.com/search?q=${t}`;
            }
        }));
    });
    document.addEventListener('DOMContentLoaded', () => {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        if(nameInput) {
            nameInput.addEventListener('input', () => {
                const val = nameInput.value;
                const headerName = document.getElementById('header-name');
                if(headerName) headerName.textContent = val || 'Artist';
                const currentSlug = slugInput.value;
                const expectedSlug = slugify(val.slice(0, -1)); 
                if(currentSlug === '' || currentSlug === expectedSlug) {
                    slugInput.value = slugify(val);
                }
            });
        }
    });
    </script>
</body>
</html>