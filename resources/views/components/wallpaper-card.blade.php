@php
    // Konfigurasi style dan teks untuk masing-masing tipe rating (Tema Glassmorphism Glow)
    $ratingStyles = [
        'general'      => ['text' => 'GEN', 'class' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 shadow-[0_0_15px_rgba(16,185,129,0.2)]'],
        'sensitive'    => ['text' => 'SEN', 'class' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30 shadow-[0_0_15px_rgba(234,179,8,0.2)]'],
        'questionable' => ['text' => 'QST', 'class' => 'bg-orange-500/20 text-orange-400 border-orange-500/30 shadow-[0_0_15px_rgba(249,115,22,0.2)]'],
        'explicit'     => ['text' => 'EXP', 'class' => 'bg-rose-500/20 text-rose-400 border-rose-500/30 shadow-[0_0_15px_rgba(244,63,94,0.2)]'],
        'unknown'      => ['text' => 'UNK', 'class' => 'bg-slate-500/20 text-slate-300 border-slate-500/30 shadow-[0_0_15px_rgba(100,116,139,0.2)]'],
    ];

    $currentRating = $ratingStyles[$wallpaper->rating] ?? $ratingStyles['unknown'];
@endphp

<a href="{{ route('wallpapers.edit', $wallpaper->id) }}" 
   class="break-inside-avoid block group relative rounded-[1.5rem] overflow-hidden border border-white/5 bg-slate-950 shadow-lg hover:shadow-[0_0_25px_rgba(34,211,238,0.2)] hover:border-cyan-500/40 transform hover:-translate-y-1 transition-all duration-300 outline-none" 
   title="Download {{ $wallpaper->seo_title }}" 
   aria-label="{{ $wallpaper->seo_title }}" 
   itemscope itemtype="https://schema.org/ImageObject">
    
    {{-- Container gambar dengan aspect-ratio untuk memesan ruang (Mencegah CLS/Lompatan Layout) --}}
    <div class="relative z-10 overflow-hidden bg-slate-900/50 w-full" 
         style="aspect-ratio: {{ $wallpaper->width > 0 ? $wallpaper->width . ' / ' . $wallpaper->height : '1 / 1' }};">
         
        <picture class="w-full h-full block group-hover:scale-110 transition-transform duration-700 ease-out transform-gpu">
            <source srcset="{{ $wallpaper->thumbnail['webp'] }}" type="image/webp">
            <img itemprop="thumbnailUrl" 
                 src="{{ $wallpaper->thumbnail['jpg'] }}" 
                 alt="{{ $wallpaper->seo_title }}" 
                 width="300" 
                 height="{{ $wallpaper->width > 0 ? round(($wallpaper->height / $wallpaper->width) * 300) : 300 }}" 
                 class="w-full h-full object-cover"
                 loading="eager"
                 decoding="sync">
        </picture>
    </div>

    {{-- Kumpulan Tombol & Badge Kiri Atas (Disusun menggunakan flex column agar tidak bertumpuk) --}}
    <div class="absolute top-3 left-3 flex flex-col items-start gap-2.5 z-30 pointer-events-none">
        
        {{-- Tombol Debug --}}
        <button type="button" 
            x-data="{ isDebug: {{ $wallpaper->debug ? 'true' : 'false' }} }" 
            @click.prevent.stop="isDebug = !isDebug; toggleDebug({{ $wallpaper->id }}, isDebug)" 
            class="pointer-events-auto w-8 h-8 rounded-xl border flex items-center justify-center transition-all duration-300 backdrop-blur-md outline-none" 
            :class="isDebug ? 'bg-amber-500/20 text-amber-400 border-amber-500/50 shadow-[0_0_15px_rgba(245,158,11,0.3)]' : 'bg-slate-900/60 text-slate-400 border-white/10 hover:border-cyan-500/40 hover:text-cyan-400 hover:shadow-[0_0_15px_rgba(34,211,238,0.2)]'" 
            title="Toggle Debug Mode">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg> 
        </button>

        {{-- Badge Live Video --}}
        @if($wallpaper->is_video)
            <div class="relative group-hover:scale-105 transition-transform duration-300 pointer-events-none">
                <div class="absolute inset-0 bg-cyan-400/30 blur-md rounded-full animate-pulse"></div>
                <div class="relative bg-slate-900/80 backdrop-blur-md border border-cyan-500/40 text-cyan-400 text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-xl flex items-center gap-1.5 shadow-[0_0_15px_rgba(34,211,238,0.2)]">
                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span>Live</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Tombol Ubah Rating (Kanan Atas) --}}
    <div class="absolute top-3 right-3 z-30 pointer-events-auto">
        <button type="button" 
            x-data 
            @click.prevent.stop="toggleRatingDropdown({{ $wallpaper->id }}, $event)" 
            class="h-8 px-3 rounded-xl border backdrop-blur-md transition-all duration-300 flex items-center justify-center font-black text-[10px] uppercase tracking-widest outline-none group/rating hover:scale-105 {{ $currentRating['class'] }}" 
            data-rating="{{ $wallpaper->rating }}">
            <span class="rating-text drop-shadow-md">{{ $currentRating['text'] }}</span>
        </button>
    </div>

    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none z-20"></div>
    
    <div class="absolute inset-x-0 bottom-0 p-5 opacity-0 group-hover:opacity-100 transition-all duration-500 translate-y-2 group-hover:translate-y-0 pointer-events-none z-30 flex flex-col justify-end">
        <div class="flex items-center justify-between w-full">
            <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-300 drop-shadow-md">
                <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                {{ Number::abbreviate($wallpaper->views_count ?? 0) }} Views
            </span>
            
            <span class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]">
                Edit
                <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </span>
        </div>
    </div>
</a>