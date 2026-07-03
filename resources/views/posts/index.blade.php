<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Post Management - {{ env('APP_NAME') }}</title>
    <x-assets />
    <style>
      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      .break-inside-avoid { break-inside: avoid; }
      .card-hover { transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
      .card-hover:hover { transform: translateY(-4px); border-color: rgba(34, 211, 238, 0.28); box-shadow: 0 14px 30px rgba(0,0,0,0.25); }
      .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
      summary::-webkit-details-marker { display: none; }
      details.custom-dropdown > summary {
            list-style: none;
        }
        details.custom-dropdown > summary::-webkit-details-marker {
            display: none;
        }
    </style>
  </head>
  <body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white">
    <x-navbar />
    
    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400">Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Posts</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded-md border border-white/5">Listing</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Post <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Management</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Create, edit, and manage your blog articles.</p>
                </div>
                <a href="{{ route('posts.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    Create New Post
                </a>
            </div>

            <form method="GET" action="{{ url()->current() }}" class="relative mb-8 z-30 group">
                
                <div class="absolute inset-0 bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl backdrop-blur-md overflow-hidden pointer-events-none z-0">
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>
                </div>

                <div class="relative z-10 flex flex-col md:flex-row gap-5 items-end p-5 sm:p-6">
                    
                    <div class="w-full md:flex-1 relative z-10">
                        <label for="search" class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Search Query</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by title, ID, or content..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                        </div>
                    </div>

                    <div class="w-full md:w-44 relative z-20">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">SEO Status</label>
                        <details class="group custom-dropdown relative">
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

                    <div class="w-full md:w-auto flex gap-3 relative z-10">
                        <button type="submit" class="flex-1 md:flex-none px-7 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-black rounded-xl shadow-[0_0_15px_rgba(34,211,238,0.2)] hover:shadow-[0_0_25px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-2 outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Filter
                        </button>
                        @if(request()->anyFilled(['search', 'seo']))
                            <a href="{{ url()->current() }}" class="flex-1 md:flex-none px-5 py-3 bg-slate-800 hover:bg-red-500/20 border border-transparent hover:border-red-500/30 text-slate-300 hover:text-red-400 font-bold rounded-xl transition-all text-center flex items-center justify-center outline-none">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-12 relative z-10">
                @forelse($posts as $post)
                    <article class="group relative bg-slate-900/60 border border-white/5 rounded-[2rem] overflow-hidden shadow-lg hover:shadow-[0_0_30px_rgba(34,211,238,0.15)] hover:border-cyan-500/30 transition-all duration-500 backdrop-blur-sm flex flex-col transform hover:-translate-y-1">
                        
                        <div class="block aspect-[16/10] overflow-hidden relative bg-slate-950">
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }} - {{ env('APP_NAME') }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 group-hover:opacity-90 transition-all duration-700 ease-out" />
                            
                            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/60 via-transparent to-transparent pointer-events-none"></div>

                            <div class="absolute top-4 left-4 z-10">
                                <span class="bg-slate-900/80 backdrop-blur-md border border-white/10 text-cyan-400 text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-lg">
                                    {{ \Carbon\Carbon::parse($post->published_at ?? $post->created_at)->format('d M Y') }}
                                </span>
                            </div>

                            <div class="absolute top-4 right-4 z-10 flex gap-2">
                                <a href="{{ route('posts.edit', ['id' => $post->id]) }}" class="bg-slate-900/80 hover:bg-blue-600/90 border border-white/10 backdrop-blur-md text-slate-300 hover:text-white p-2.5 rounded-lg shadow-lg transition-colors" title="Edit Post">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form action="{{ route('posts.delete', ['id' => $post->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-slate-900/80 hover:bg-rose-600/90 border border-white/10 backdrop-blur-md text-slate-300 hover:text-white p-2.5 rounded-lg shadow-lg transition-colors" title="Delete Post">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="p-6 sm:p-8 flex flex-col flex-grow relative bg-slate-900/40">
                            <div class="flex items-center text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3 space-x-4">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-cyan-500/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg> 
                                    {{ number_format($post->views_count) }} Views
                                </span>
                            </div>
                            
                            <h2 class="text-xl sm:text-2xl font-black text-white leading-tight mb-3 group-hover:text-cyan-400 transition-colors line-clamp-2">
                                {{ $post->title }}
                            </h2>
                            
                            <p class="text-slate-400 text-sm mb-6 line-clamp-3 leading-relaxed flex-grow">
                                {{ $post->excerpt ?? Str::limit(strip_tags($post->body), 120) }}
                            </p>
                            
                            <div class="mt-auto pt-5 border-t border-white/5">
                                <a href="{{ route('posts.show', ['slug' => $post->slug]) }}" target="_blank" class="inline-flex items-center text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-cyan-400 transition-colors">
                                    View Public Page 
                                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full w-full flex flex-col items-center justify-center py-20 px-4 text-center bg-slate-900/40 border border-dashed border-slate-700 rounded-[2rem] backdrop-blur-md shadow-inner">
                        <div class="w-20 h-20 mb-5 rounded-full bg-slate-800 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5L18.5 7H20a2 2 0 002-2v12a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">No Posts Found</h3>
                        <p class="text-sm font-medium text-slate-500 max-w-sm mx-auto">There are no posts matching your filter criteria, or no posts have been created yet.</p>
                        @if(request()->anyFilled(['search', 'seo']))
                            <a href="{{ url()->current() }}" class="mt-4 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold rounded-lg transition-colors text-xs uppercase tracking-wider">
                                Clear Filters
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>

            @if($posts->hasPages())
            {{ $posts->withQueryString()->links('components.pagination') }}
            @endif
        </div>
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
    </script>
  </body>
</html>