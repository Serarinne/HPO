@php
    $firstSeries = $character->series->first();
    $seriesSlug = $firstSeries?->slug ?? 'unknown';
    $seriesName = $firstSeries?->name ?? '';
    
    $firstLetter = Str::upper(Str::substr($character->name, 0, 1));
    $hasImage = !empty($character->image);

    $ratingStyles = [
        'general'      => ['text' => 'GEN', 'class' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 shadow-[0_0_15px_rgba(16,185,129,0.2)]'],
        'sensitive'    => ['text' => 'SEN', 'class' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30 shadow-[0_0_15px_rgba(234,179,8,0.2)]'],
        'questionable' => ['text' => 'QST', 'class' => 'bg-orange-500/20 text-orange-400 border-orange-500/30 shadow-[0_0_15px_rgba(249,115,22,0.2)]'],
        'explicit'     => ['text' => 'EXP', 'class' => 'bg-rose-500/20 text-rose-400 border-rose-500/30 shadow-[0_0_15px_rgba(244,63,94,0.2)]'],
        'unknown'      => ['text' => 'UNK', 'class' => 'bg-slate-500/20 text-slate-300 border-slate-500/30 shadow-[0_0_15px_rgba(100,116,139,0.2)]'],
    ];

    $currentRating = $ratingStyles[$character->rating] ?? $ratingStyles['unknown'];
@endphp

<article
    id="character-card-{{ $character->id }}"
    x-data="{ isDebug: {{ $character->debug ? 'true' : 'false' }} }"
    class="break-inside-avoid block group relative rounded-[1.5rem] overflow-hidden border border-white/5 bg-slate-950 aspect-square shadow-lg hover:shadow-[0_0_25px_rgba(34,211,238,0.2)] hover:border-cyan-500/40 transform hover:-translate-y-1 transition-all duration-300 outline-none"
    title="{{ $character->name }}"
    aria-label="{{ $character->name }}"
    itemscope
    itemtype="https://schema.org/Person"
>
    <a href="{{ route('characters.edit', ['id' => $character->id]) }}" class="absolute inset-0 z-10 outline-none"></a>

    <div class="absolute inset-0 z-0 {{ !$hasImage ? 'flex justify-center items-center p-6 bg-slate-900/50 backdrop-blur-sm' : 'bg-slate-950' }}">
        @if($hasImage)
            <picture class="w-full h-full block group-hover:scale-110 transition-transform duration-700 ease-out transform-gpu">
                <source srcset="{{ $character->image['webp'] }}" type="image/webp">
                <img itemprop="image" src="{{ $character->image['jpg'] }}" alt="{{ $character->name }}" loading="lazy" decoding="async" width="400" height="400" class="w-full h-full object-cover" />
            </picture>
        @else
            <div class="w-20 h-20 bg-cyan-500/10 border border-cyan-500/30 rounded-[1.25rem] flex items-center justify-center text-cyan-400 font-black text-4xl uppercase shadow-[inset_0_0_20px_rgba(34,211,238,0.1)] group-hover:shadow-[inset_0_0_30px_rgba(34,211,238,0.3)] group-hover:bg-cyan-500/20 transition-all duration-500" aria-hidden="true">
                {{ $firstLetter }}
            </div>
        @endif
    </div>

    <div class="absolute top-3 left-3 flex flex-col gap-2 z-30 pointer-events-none">
        <button type="button" 
            @click.prevent.stop="isDebug = !isDebug; window.toggleDebug({{ $character->id }}, isDebug)" 
            class="pointer-events-auto w-8 h-8 rounded-xl border flex items-center justify-center transition-all duration-300 backdrop-blur-md outline-none" 
            :class="isDebug ? 'bg-amber-500/20 text-amber-400 border-amber-500/50 shadow-[0_0_15px_rgba(245,158,11,0.3)]' : 'bg-slate-900/60 text-slate-400 border-white/10 hover:border-cyan-500/40 hover:text-cyan-400 hover:shadow-[0_0_15px_rgba(34,211,238,0.2)]'" 
            title="Toggle Debug Mode">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
            </svg> 
        </button>

        <a href="{{ route('characters.merge.form', ['id' => $character->id]) }}"
           @click.stop
           class="pointer-events-auto w-8 h-8 rounded-xl border flex items-center justify-center transition-all duration-300 backdrop-blur-md outline-none bg-violet-500/20 text-violet-400 border-violet-500/40 shadow-[0_0_15px_rgba(139,92,246,0.2)] hover:bg-violet-500/30 hover:border-violet-400/60 hover:scale-105"
           title="Merge Character"
           aria-label="Merge {{ $character->name }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h3m5 0h-3M12 7v10m-4-4 4 4 4-4"></path>
            </svg>
        </a>

        <button type="button"
            data-name="{{ $character->name }}"
            @click.prevent.stop="window.deleteCharacterCard({{ $character->id }}, $el.dataset.name)"
            class="pointer-events-auto w-8 h-8 rounded-xl border flex items-center justify-center transition-all duration-300 backdrop-blur-md outline-none bg-rose-500/20 text-rose-400 border-rose-500/40 shadow-[0_0_15px_rgba(244,63,94,0.2)] hover:bg-rose-500/30 hover:border-rose-400/60 hover:scale-105"
            title="Delete Character"
            aria-label="Delete {{ $character->name }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"></path>
            </svg>
        </button>
    </div>

    <div class="absolute top-3 right-3 z-30 pointer-events-auto">
        <button type="button" 
            @click.prevent.stop="window.toggleRatingDropdown({{ $character->id }}, $event)" 
            class="h-8 px-3 rounded-xl border backdrop-blur-md transition-all duration-300 flex items-center justify-center font-black text-[10px] uppercase tracking-widest outline-none group/rating hover:scale-105 {{ $currentRating['class'] }}" 
            data-rating="{{ $character->rating }}">
            <span class="rating-text drop-shadow-md">{{ $currentRating['text'] }}</span>
        </button>
    </div>

    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/30 to-transparent pointer-events-none z-10 opacity-80 group-hover:opacity-100 transition-opacity duration-500"></div>

    <div class="absolute inset-x-0 bottom-0 p-5 pointer-events-none z-20 flex flex-col justify-end">
        <h3 class="text-white font-black text-base sm:text-lg leading-tight line-clamp-1 group-hover:text-cyan-400 transition-colors duration-300 drop-shadow-md" itemprop="name">
            {{ $character->name }}
        </h3>
        
        @if($seriesName)
            <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-widest text-slate-400 mt-1.5 line-clamp-1 group-hover:text-cyan-300 transition-colors duration-300" itemprop="memberOf">
                {{ $seriesName }}
            </p>
        @endif
    </div>
</article>