<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fetch Tasks Management - {{ env('APP_NAME') }}</title>
    <x-assets />
    <style>
      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      .break-inside-avoid { break-inside: avoid; }
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
            
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400">Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Fetch Tasks</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Task <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Queue</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Monitor and manage background API fetching queue.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl backdrop-blur-md shadow-[0_0_15px_rgba(16,185,129,0.15)] flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="font-bold text-sm">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl backdrop-blur-md shadow-[0_0_15px_rgba(244,63,94,0.15)] flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p class="font-bold text-sm">{{ session('error') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="bg-slate-900/60 border border-white/10 rounded-2xl p-5 shadow-lg backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-slate-500/10 rounded-full blur-xl group-hover:bg-slate-500/20 transition-all"></div>
                    <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider relative z-10">Queue Size</p>
                    <h3 class="text-3xl font-black text-white relative z-10">{{ number_format($stats['total'] ?? 0) }}</h3>
                </div>
                <div class="bg-slate-900/60 border border-blue-500/20 rounded-2xl p-5 shadow-[0_0_15px_rgba(59,130,246,0.1)] backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/30 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-blue-400 mb-1 uppercase tracking-wider">Running</p>
                        <h3 class="text-3xl font-black text-blue-400">{{ number_format($stats['running'] ?? 0) }}</h3>
                    </div>
                </div>
                <div class="bg-slate-900/60 border border-purple-500/20 rounded-2xl p-5 shadow-[0_0_15px_rgba(168,85,247,0.1)] backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-purple-500/10 rounded-full blur-xl group-hover:bg-purple-500/30 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-purple-400 mb-1 uppercase tracking-wider">Rerunning</p>
                        <h3 class="text-3xl font-black text-purple-400">{{ number_format($stats['rerunning'] ?? 0) }}</h3>
                    </div>
                </div>
                <div class="bg-slate-900/60 border border-cyan-500/20 rounded-2xl p-5 shadow-[0_0_15px_rgba(6,182,212,0.1)] backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/30 transition-all"></div>
                    <p class="text-[10px] font-bold text-cyan-400 mb-1 uppercase tracking-wider relative z-10">Pending</p>
                    <h3 class="text-3xl font-black text-cyan-400 relative z-10">{{ number_format($stats['pending'] ?? 0) }}</h3>
                </div>
                <div class="bg-slate-900/60 border border-emerald-500/20 rounded-2xl p-5 shadow-[0_0_15px_rgba(16,185,129,0.1)] backdrop-blur-md relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/30 transition-all"></div>
                    <p class="text-[10px] font-bold text-emerald-400 mb-1 uppercase tracking-wider relative z-10">Completed</p>
                    <h3 class="text-3xl font-black text-emerald-400 relative z-10">{{ number_format($stats['completed'] ?? 0) }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-slate-900/60 border border-white/10 p-6 rounded-[2rem] flex flex-col backdrop-blur-md shadow-xl relative overflow-hidden group">
                    <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-cyan-500/10 blur-[80px] pointer-events-none group-hover:bg-cyan-500/20 transition-all"></div>
                    <h4 class="text-lg font-black text-white mb-5 flex items-center gap-3 relative z-10">
                        <span class="p-2 bg-cyan-500/10 border border-cyan-500/20 rounded-xl text-cyan-400 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </span> 
                        Inject Manual Task
                    </h4>
                    <form action="{{ route('fetch-tasks.store') }}" method="POST" class="flex flex-col sm:flex-row gap-4 relative z-10">
                        @csrf
                        <div class="flex-grow">
                            <input type="text" name="tag_name" placeholder="Tag Name (e.g. firefly_honkai)" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner text-sm">
                        </div>
                        <div class="w-full sm:w-44 relative">
                            <select name="source_api" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-300 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer appearance-none">
                                <option value="danbooru">Danbooru</option>
                                <option value="gelbooru">Gelbooru</option>
                                <option value="zerochan">Zerochan</option>
                            </select>
                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
                        </div>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-black rounded-xl transition-all shadow-[0_0_15px_rgba(34,211,238,0.2)] hover:shadow-[0_0_25px_rgba(34,211,238,0.4)] whitespace-nowrap outline-none">
                            Add Task
                        </button>
                    </form>
                </div>
                
                <div class="bg-slate-900/60 border border-white/10 p-6 rounded-[2rem] flex flex-col backdrop-blur-md shadow-xl relative overflow-hidden group">
                    <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-amber-500/10 blur-[80px] pointer-events-none group-hover:bg-amber-500/20 transition-all"></div>
                    <h4 class="text-lg font-black text-white mb-5 flex items-center gap-3 relative z-10">
                        <span class="p-2 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-500 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </span> 
                        Auto Populate Queue
                    </h4>
                    <form action="{{ route('fetch-tasks.populate') }}" method="POST" class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mt-auto relative z-10">
                        @csrf
                        <input type="hidden" name="type" value="character">
                        <p class="text-sm font-medium text-slate-400 flex-grow">Generate missing fetch tasks based on existing Character Database.</p>
                        <button type="submit" class="px-6 py-3 bg-slate-900 border border-amber-500/30 hover:bg-amber-500/10 hover:border-amber-500 text-amber-400 hover:text-amber-300 font-black rounded-xl transition-all shadow-[0_0_15px_rgba(245,158,11,0.1)] hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] whitespace-nowrap outline-none w-full sm:w-auto text-center">
                            Populate Characters
                        </button>
                    </form>
                </div>
            </div>

            <form method="GET" action="{{ url()->current() }}" class="relative mb-8 z-30 group">
                <div class="absolute inset-0 bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl backdrop-blur-md overflow-hidden pointer-events-none z-0">
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>
                </div>

                <div class="relative z-10 flex flex-col md:flex-row gap-5 items-end p-5 sm:p-6">
                    <div class="w-full md:flex-1 relative z-10">
                        <label for="search" class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Search Queue</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by tag name or source API..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                        </div>
                    </div>

                    <div class="w-full md:w-56 relative z-20">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                        <details class="group custom-dropdown relative">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                @php $statusCount = count(request('status', [])); @endphp
                                <span class="text-sm truncate mr-2 {{ $statusCount > 0 ? 'text-cyan-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $statusCount > 0 ? $statusCount . ' Selected' : 'All Status' }}</span>
                                <svg class="w-4 h-4 text-slate-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1">
                                @foreach(['pending', 'running', 'rerunning', 'completed', 'failed'] as $status)
                                    <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-800/50 rounded-lg cursor-pointer transition-colors">
                                        <input type="checkbox" name="status[]" value="{{ $status }}" @checked(in_array($status, request('status', []))) class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-cyan-500 focus:ring-cyan-500 focus:ring-offset-slate-900 shadow-inner">
                                        <span class="text-xs font-bold text-slate-300 capitalize">{{ $status }}</span>
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
                        @if(request()->anyFilled(['search', 'status']))
                            <a href="{{ url()->current() }}" class="flex-1 md:flex-none px-5 py-3 bg-slate-800 hover:bg-red-500/20 border border-transparent hover:border-red-500/30 text-slate-300 hover:text-red-400 font-bold rounded-xl transition-all text-center flex items-center justify-center outline-none">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl overflow-hidden mb-8 backdrop-blur-md relative z-10">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/80 border-b border-white/5 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                <th class="px-6 py-5">ID & Timeline</th>
                                <th class="px-6 py-5">Tag Target</th>
                                <th class="px-6 py-5">Progress & Checkpoint</th>
                                <th class="px-6 py-5">Status</th>
                                <th class="px-6 py-5">Activity Info</th>
                                <th class="px-6 py-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($tasks as $task)
                            
                            @php
                                // Cek apakah task mungkin stuck (berjalan tapi > 10 menit tidak update)
                                $isStuck = in_array(strtolower($task->status), ['running', 'rerunning']) 
                                           && $task->last_run_at 
                                           && \Carbon\Carbon::parse($task->last_run_at)->diffInMinutes(now()) > 10;
                            @endphp

                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm text-slate-300 font-mono font-bold">#{{ $task->id }}</span>
                                        <span class="text-[10px] text-slate-500" title="{{ $task->created_at }}">Added: {{ $task->created_at->format('M d, Y') }}</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="text-sm font-bold text-white group-hover:text-cyan-400 transition-colors">{{ $task->tag_name }}</span>
                                        <span class="inline-flex w-fit items-center gap-1.5 px-2 py-0.5 rounded text-[9px] font-black text-pink-400 bg-pink-500/10 border border-pink-500/20 uppercase tracking-widest">{{ $task->source_api }}</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5 text-sm text-slate-400">
                                        <span class="font-medium">Page: <strong class="text-white bg-white/10 px-2 py-0.5 rounded ml-1">{{ $task->current_page }}</strong></span>
                                        <span class="text-xs font-mono text-slate-500">ID: {{ $task->last_source_id ?? 'None' }}</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5 items-start">
                                        @if(strtolower($task->status) === 'completed')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 text-[10px] font-bold border border-emerald-500/20 uppercase tracking-wider"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span> Completed</span>
                                        @elseif(in_array(strtolower($task->status), ['running', 'rerunning']))
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-blue-500/10 text-blue-400 text-[10px] font-bold border border-blue-500/20 uppercase tracking-wider"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-ping shadow-[0_0_8px_rgba(59,130,246,0.8)]"></span> {{ $task->status }}</span>
                                        @elseif(strtolower($task->status) === 'failed')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-rose-500/10 text-rose-400 text-[10px] font-bold border border-rose-500/20 uppercase tracking-wider"><span class="w-1.5 h-1.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]"></span> Failed</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-cyan-500/10 text-cyan-400 text-[10px] font-bold border border-cyan-500/20 uppercase tracking-wider"><span class="w-1.5 h-1.5 rounded-full bg-cyan-500 shadow-[0_0_8px_rgba(6,182,212,0.8)]"></span> Pending</span>
                                        @endif
                                        
                                        @if($isStuck)
                                            <span class="text-[9px] font-bold text-rose-400 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Stuck?</span>
                                        @endif
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5 text-[11px] font-medium">
                                        @if($task->last_run_at)
                                            <span class="text-slate-300" title="{{ $task->last_run_at }}">Run: <span class="text-cyan-400">{{ \Carbon\Carbon::parse($task->last_run_at)->diffForHumans() }}</span></span>
                                        @else
                                            <span class="text-slate-500">Run: Never</span>
                                        @endif
                                        
                                        <span class="text-slate-400" title="{{ $task->updated_at }}">Updated: {{ \Carbon\Carbon::parse($task->updated_at)->diffForHumans() }}</span>
                                        
                                        <span class="text-slate-500 mt-1" title="Postingan terbaca paling baru">Barrier: {{ $task->last_post_date ? \Carbon\Carbon::parse($task->last_post_date)->format('d M Y') : 'No Barrier' }}</span>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form action="{{ route('fetch-tasks.reset', ['id' => $task->id]) }}" method="POST" onsubmit="return confirm('Reset task: {{ $task->tag_name }}?');" class="inline-block">
                                            @csrf
                                            <button type="submit" class="p-2 text-slate-400 hover:text-cyan-400 hover:bg-cyan-500/20 rounded-lg transition-colors cursor-pointer border border-transparent hover:border-cyan-500/30" title="Reset Task to Pending">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('fetch-tasks.destroy', ['id' => $task->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete {{ $task->tag_name }}?');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/20 rounded-lg transition-colors cursor-pointer border border-transparent hover:border-rose-500/30" title="Delete Task">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 mb-4 rounded-full bg-slate-800 flex items-center justify-center shadow-inner">
                                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                        </div>
                                        <h3 class="text-lg font-black text-white">No Fetch Tasks Found</h3>
                                        <p class="text-sm font-medium text-slate-500 mt-2 max-w-sm">There are no background fetch tasks matching your current filter criteria.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($tasks->hasPages())
                    {{ $tasks->withQueryString()->links('components.pagination') }}
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