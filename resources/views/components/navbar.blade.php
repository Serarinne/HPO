@php
    $navLinks = [
        ['name' => 'Dashboard', 'route' => 'index', 'pattern' => 'index'],
        ['name' => 'Wallpapers', 'route' => 'wallpapers.index', 'pattern' => 'wallpapers.*'],
        ['name' => 'Characters', 'route' => 'characters.index', 'pattern' => 'characters.*'],
        ['name' => 'Series', 'route' => 'series.index', 'pattern' => 'series.*'],
        ['name' => 'Artists', 'route' => 'artists.index', 'pattern' => 'artists.*'],
        ['name' => 'Tags', 'route' => 'tags.index', 'pattern' => 'tags.*'],
        ['name' => 'Blog', 'route' => 'posts.index', 'pattern' => 'posts.*'],
        ['name' => 'Redeems', 'route' => 'redeems.index', 'pattern' => 'redeems.*'],
        ['name' => 'Fetch Tasks', 'route' => 'fetch-tasks.index', 'pattern' => 'fetch-tasks.*'],
        ['name' => 'Fetch WP', 'route' => 'fetch-wallpapers.index', 'pattern' => 'fetch-wallpapers.*'],
    ];
@endphp

<nav class="sticky top-0 z-50 bg-slate-950/95 backdrop-blur border-b border-white/10" x-data="{ open: false }" aria-label="Admin Navigation">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">

        {{-- Logo → Admin Dashboard --}}
        <a href="{{ route('index') }}" class="flex-shrink-0 flex items-center" aria-label="{{ env('APP_NAME') }} Admin">
            <img src="{{ env('STORAGE_URL') }}/assets/logo.png" alt="{{ env('APP_NAME') }} Logo" width="120" height="36" class="h-9 w-auto object-contain">
        </a>

        {{-- Desktop Nav --}}
        <ul class="hidden lg:flex items-center gap-1 text-sm font-medium text-slate-300 flex-1 min-w-0">
            @foreach($navLinks as $link)
                @php $isActive = request()->routeIs($link['pattern']); @endphp
                <li class="flex-shrink-0">
                    <a href="{{ route($link['route']) }}"
                       class="relative px-3 py-2 rounded-md transition-colors whitespace-nowrap {{ $isActive ? 'text-white bg-white/10 font-semibold' : 'hover:text-white hover:bg-white/5' }}"
                       @if($isActive) aria-current="page" @endif>
                        {{ $link['name'] }}
                        @if($isActive)
                            <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-4 h-0.5 bg-cyan-400 rounded-full"></span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Desktop Right: Settings + Back to Site --}}
        <div class="hidden lg:flex items-center gap-2 flex-shrink-0">

            {{-- Admin Settings --}}
            <a href="{{ route('settings') }}"
               class="flex items-center gap-1.5 text-sm font-medium px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('settings') ? 'text-white bg-white/10' : 'text-slate-300 hover:text-white hover:bg-white/5' }}"
               aria-label="Admin Settings">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="sr-only">Settings</span>
            </a>
        </div>

        {{-- Mobile Hamburger --}}
        <button @click="open = !open" class="lg:hidden text-slate-300 hover:text-white p-2 flex-shrink-0"
                :aria-expanded="open.toString()" aria-controls="mobile-menu" aria-label="Toggle mobile menu">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"></path>
            </svg>
        </button>
    </div>

    {{-- Mobile Menu --}}
    <div id="mobile-menu"
         x-show="open"
         x-transition
         x-cloak
         class="lg:hidden border-t border-white/10 bg-slate-950 max-h-[80vh] overflow-y-auto scroll-smooth"
         style="scrollbar-width: thin; scrollbar-color: #1e293b #0f172a;"
         x-init="$watch('open', value => {
             if (value) {
                 $nextTick(() => {
                     const activeLink = document.querySelector('.mobile-menu-active');
                     if (activeLink) activeLink.scrollIntoView({ block: 'center', behavior: 'smooth' });
                 });
             }
         })">
        <div class="max-w-[1400px] mx-auto px-4 py-4 flex flex-col gap-1 text-sm font-medium">

            @foreach($navLinks as $link)
                @php $isActive = request()->routeIs($link['pattern']); @endphp
                <a href="{{ route($link['route']) }}"
                   id="mobile-{{ Str::slug($link['name']) }}"
                   class="px-3 py-2.5 rounded-lg transition-all flex items-center gap-2 {{ $isActive ? 'text-white bg-cyan-400/20 border-l-2 border-cyan-400 mobile-menu-active' : 'text-slate-400 hover:text-white hover:bg-white/5' }}"
                   @if($isActive) aria-current="page" @endif>
                    <span>{{ $link['name'] }}</span>
                    @if($isActive)
                        <span class="w-2 h-2 rounded-full bg-cyan-400 ml-auto"></span>
                    @endif
                </a>
            @endforeach

            {{-- Admin Settings (Mobile) --}}
            <div class="border-t border-white/10 my-2 pt-2"></div>
            <a href="{{ route('settings') }}"
               class="{{ request()->routeIs('settings') ? 'text-white bg-cyan-400/20 border-l-2 border-cyan-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }} px-3 py-2.5 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Admin Settings
            </a>
        </div>
    </div>
</nav>