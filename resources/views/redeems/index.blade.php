<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Redeem Codes Management - {{ env('APP_NAME') }}</title>
    <x-assets />
    <style>
      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      .break-inside-avoid { break-inside: avoid; }
      .card-hover { transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
      .card-hover:hover { transform: translateY(-4px); border-color: rgba(34, 211, 238, 0.28); box-shadow: 0 14px 30px rgba(0,0,0,0.25); }
      .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
      summary::-webkit-details-marker { display: none; }
      [x-cloak] { display: none !important; }
      .input-field { background-color: rgba(2, 6, 23, 0.6); border: 1px solid rgba(71, 85, 105, 0.4); color: #f8fafc; transition: all 0.2s ease; }
      .input-field:focus { border-color: #06b6d4; background-color: rgba(2, 6, 23, 0.8); box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2); outline: none; }
      details.custom-dropdown > summary {
            list-style: none;
        }
        details.custom-dropdown > summary::-webkit-details-marker {
            display: none;
        }
    </style>
  </head>
  <body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white" x-data="redeemCodeManager()">
    <x-navbar />
    
    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400">Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Redeem Codes</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded-md border border-white/5">Listing</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Redeem Codes <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Management</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Create, distribute, and manage promotional reward codes.</p>
                </div>
                <button type="button" @click="$dispatch('open-create-modal')" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all whitespace-nowrap cursor-pointer outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    Create New Code
                </button>
            </div>

            <form method="GET" action="{{ route('redeems.index') }}" class="relative mb-8 z-30 group">
                
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
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by code or reward description..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                        </div>
                    </div>

                    <div class="w-full md:w-44 relative z-20">
                        <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                        <details class="group custom-dropdown relative">
                            <summary class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner cursor-pointer list-none flex justify-between items-center select-none">
                                @php $statusCount = count(request('status', [])); @endphp
                                <span class="text-sm truncate mr-2 {{ $statusCount > 0 ? 'text-cyan-400 font-bold' : 'text-slate-400 font-medium' }}">{{ $statusCount > 0 ? $statusCount . ' Selected' : 'All Status' }}</span>
                                <svg class="w-4 h-4 text-slate-500 group-open:rotate-180 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="absolute top-full left-0 z-50 w-full mt-2 bg-slate-900/95 backdrop-blur-xl border border-white/10 rounded-xl shadow-2xl p-2 flex flex-col gap-1">
                                @foreach(['active', 'inactive', 'expired'] as $status)
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
                            <a href="{{ route('redeems.index') }}" class="flex-1 md:flex-none px-5 py-3 bg-slate-800 hover:bg-red-500/20 border border-transparent hover:border-red-500/30 text-slate-300 hover:text-red-400 font-bold rounded-xl transition-all text-center flex items-center justify-center outline-none">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-12 relative z-10">
                @forelse($codes as $code)
                    <article class="group relative bg-slate-900/60 border border-white/5 rounded-[2rem] overflow-hidden shadow-lg hover:shadow-[0_0_30px_rgba(34,211,238,0.15)] hover:border-cyan-500/30 transition-all duration-500 backdrop-blur-sm flex flex-col transform hover:-translate-y-1">
                        
                        <div class="block aspect-[21/9] overflow-hidden relative bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 flex items-center justify-center border-b border-white/5">
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wMykiLz48L3N2Zz4=')]"></div>
                            
                            <span class="text-3xl sm:text-4xl font-mono font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 tracking-[0.2em] break-all px-6 text-center group-hover:scale-105 transition-transform duration-700 ease-out z-10">{{ $code->code }}</span>
                            
                            <div class="absolute top-4 left-4 z-20">
                                <span class="bg-slate-950/80 backdrop-blur-md border border-white/10 text-cyan-400 text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded-lg shadow-lg">
                                    {{ \Carbon\Carbon::parse($code->created_at)->format('d M Y') }}
                                </span>
                            </div>

                            <div class="absolute top-4 right-4 z-20 flex gap-2">
                                <button type="button" @click="openEditModal({{ $code->id }}, '{{ addslashes($code->code) }}', '{{ addslashes($code->reward_description) }}', {{ $code->is_active }}, '{{ $code->expired_at }}')" class="bg-slate-900/80 hover:bg-blue-600/90 border border-white/10 backdrop-blur-md text-slate-300 hover:text-white p-2.5 rounded-lg shadow-lg transition-colors cursor-pointer" title="Edit Code">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button type="button" @click="confirmDelete({{ $code->id }}, '{{ addslashes($code->code) }}')" class="bg-slate-900/80 hover:bg-rose-600/90 border border-white/10 backdrop-blur-md text-slate-300 hover:text-white p-2.5 rounded-lg shadow-lg transition-colors cursor-pointer" title="Delete Code">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 sm:p-8 flex flex-col flex-grow relative bg-slate-900/40">
                            <div class="flex items-center justify-between mb-4">
                                <span class="flex items-center gap-2.5">
                                    <label class="relative inline-flex items-center cursor-pointer group/toggle" title="Toggle Status">
                                        <input type="checkbox" class="sr-only peer" {{ $code->is_active ? 'checked' : '' }} @change="toggleStatus({{ $code->id }}, $event.target.checked)">
                                        <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-300 after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500/80 peer-checked:border-emerald-500 peer-checked:after:border-white peer-checked:after:bg-white border border-slate-700 group-hover/toggle:border-slate-500 shadow-inner"></div>
                                    </label>
                                    <span class="text-[10px] font-bold uppercase tracking-widest {{ $code->is_active ? 'text-emerald-400' : 'text-slate-500' }}">
                                        {{ $code->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </span>

                                @if($code->expired_at)
                                    <span class="bg-rose-500/10 border border-rose-500/20 text-rose-400 px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest">
                                        Exp: {{ \Carbon\Carbon::parse($code->expired_at)->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 px-2 py-1 rounded-md text-[9px] font-bold uppercase tracking-widest italic">
                                        Never Expire
                                    </span>
                                @endif
                            </div>
                            
                            <h2 class="text-lg font-bold text-white mb-2 leading-tight">{{ $code->code }}</h2>
                            <p class="text-slate-400 text-sm mb-6 line-clamp-3 leading-relaxed flex-grow">{{ $code->reward_description }}</p>
                            
                            <div class="mt-auto pt-5 border-t border-white/5">
                                <button type="button" @click="copyToClipboard('{{ addslashes($code->code) }}')" class="inline-flex items-center text-xs font-bold uppercase tracking-widest text-slate-400 hover:text-cyan-400 transition-colors cursor-pointer">
                                    Copy Redeem Code 
                                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full w-full flex flex-col items-center justify-center py-20 px-4 text-center bg-slate-900/40 border border-dashed border-slate-700 rounded-[2rem] backdrop-blur-md shadow-inner">
                        <div class="w-20 h-20 mb-5 rounded-full bg-slate-800 flex items-center justify-center">
                            <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-white mb-2">No Codes Found</h3>
                        <p class="text-sm font-medium text-slate-500 max-w-sm mx-auto">There are no redeem codes matching your filter criteria, or none have been created yet.</p>
                    </div>
                @endforelse
            </div>

            @if($codes->hasPages())
            {{ $codes->withQueryString()->links('components.pagination') }}
            @endif
        </div>


        <div x-show="isCreateOpen" @open-create-modal.window="openCreateModal()" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;" x-cloak>
            <div x-show="isCreateOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="isCreateOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="isCreateOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4" class="relative w-full max-w-lg overflow-hidden rounded-[2rem] bg-slate-900/95 backdrop-blur-xl border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)]">
                    
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/20 blur-[80px] pointer-events-none"></div>

                    <form id="create-code-form" action="{{ route('redeems.store') }}" method="POST" @submit.prevent="submitCreateForm" class="relative z-10">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        
                        <div class="p-6 sm:p-8 border-b border-white/5 flex items-center justify-between">
                            <h3 class="text-xl font-black text-white tracking-tight flex items-center gap-3">
                                <span class="w-2 h-6 bg-cyan-400 rounded-full shadow-[0_0_10px_rgba(34,211,238,0.5)]"></span>
                                Create Redeem Code
                            </h3>
                            <button type="button" @click="isCreateOpen = false" class="text-slate-500 hover:text-white bg-slate-800/50 hover:bg-slate-700/50 p-2 rounded-full transition-colors cursor-pointer outline-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="p-6 sm:p-8 space-y-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">The Code</label>
                                <input type="text" x-model="formData.code" name="code" required maxlength="50" placeholder="e.g. WELCOME2026" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner font-mono uppercase tracking-widest placeholder-slate-600">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Reward Description</label>
                                <input type="text" x-model="formData.reward_description" name="reward_description" required maxlength="255" placeholder="e.g. 500 Coins + 10x Pulls" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div class="relative">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Status</label>
                                    <select name="is_active" x-model="formData.is_active" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white appearance-none cursor-pointer focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                                        <option value="1">✓ Active</option>
                                        <option value="0">✗ Inactive</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-3 top-6 flex items-center pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Expiration (Optional)</label>
                                    <input type="datetime-local" x-model="formData.expired_at" name="expired_at" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-300 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner [color-scheme:dark]">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-slate-950/30 p-6 sm:p-8 border-t border-white/5 flex justify-end gap-3 rounded-b-[2rem]">
                            <button type="button" @click="isCreateOpen = false" class="px-6 py-3 rounded-xl text-sm font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/10 hover:border-white/20 transition-all cursor-pointer">Cancel</button>
                            <button type="submit" id="btn-save-code" class="px-8 py-3 rounded-xl text-sm font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all cursor-pointer">Save Code</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="isEditOpen" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;" x-cloak>
            <div x-show="isEditOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="isEditOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="isEditOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4" class="relative w-full max-w-lg overflow-hidden rounded-[2rem] bg-slate-900/95 backdrop-blur-xl border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)]">
                    
                    <div class="absolute -top-24 -left-24 w-48 h-48 bg-blue-500/20 blur-[80px] pointer-events-none"></div>

                    <form id="edit-code-form" :action="`{{ url('/admin/redeems') }}/${editId}`" method="POST" @submit.prevent="submitEditForm" class="relative z-10">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="PATCH">
                        
                        <div class="p-6 sm:p-8 border-b border-white/5 flex items-center justify-between">
                            <h3 class="text-xl font-black text-white tracking-tight flex items-center gap-3">
                                <span class="w-2 h-6 bg-blue-400 rounded-full shadow-[0_0_10px_rgba(56,187,248,0.5)]"></span>
                                Edit Redeem Code
                            </h3>
                            <button type="button" @click="isEditOpen = false" class="text-slate-500 hover:text-white bg-slate-800/50 hover:bg-slate-700/50 p-2 rounded-full transition-colors cursor-pointer outline-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="p-6 sm:p-8 space-y-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">The Code</label>
                                <input type="text" x-model="formData.code" name="code" required maxlength="50" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner font-mono uppercase tracking-widest placeholder-slate-600">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Reward Description</label>
                                <input type="text" x-model="formData.reward_description" name="reward_description" required maxlength="255" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div class="relative">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Status</label>
                                    <select name="is_active" x-model="formData.is_active" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white appearance-none cursor-pointer focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner">
                                        <option value="1">✓ Active</option>
                                        <option value="0">✗ Inactive</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-3 top-6 flex items-center pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Expiration</label>
                                    <input type="datetime-local" x-model="formData.expired_at" name="expired_at" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-slate-300 focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner [color-scheme:dark]">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-slate-950/30 p-6 sm:p-8 border-t border-white/5 flex justify-end gap-3 rounded-b-[2rem]">
                            <button type="button" @click="isEditOpen = false" class="px-6 py-3 rounded-xl text-sm font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/10 hover:border-white/20 transition-all cursor-pointer">Cancel</button>
                            <button type="submit" id="btn-update-code" class="px-8 py-3 rounded-xl text-sm font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all cursor-pointer">Update Code</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div x-show="isDeleteOpen" class="fixed inset-0 z-[110] overflow-y-auto" style="display: none;" x-cloak>
            <div x-show="isDeleteOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm" @click="isDeleteOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="isDeleteOpen" x-transition class="relative w-full max-w-sm overflow-hidden rounded-[2rem] bg-slate-900/95 backdrop-blur-xl border border-white/10 shadow-[0_20px_60px_rgba(0,0,0,0.5)] p-8 text-center">
                    
                    <div class="absolute -bottom-16 -right-16 w-32 h-32 bg-rose-500/20 blur-[60px] pointer-events-none"></div>

                    <div class="w-20 h-20 mx-auto rounded-full bg-rose-500/10 border border-rose-500/20 flex items-center justify-center mb-6 shadow-inner relative z-10">
                        <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    
                    <h4 class="text-2xl font-black text-white mb-2 relative z-10">Delete Code?</h4>
                    <p class="text-sm font-medium text-slate-400 mb-8 relative z-10">Are you sure you want to permanently delete code <br><strong class="text-rose-400 font-mono text-base tracking-widest block mt-2 bg-rose-500/10 py-2 rounded-lg border border-rose-500/20" x-text="deleteCodeName"></strong></p>
                    
                    <div class="flex gap-3 w-full justify-center relative z-10">
                        <button type="button" @click="isDeleteOpen = false" class="px-6 py-3 rounded-xl text-sm font-bold text-slate-400 bg-slate-900 border border-white/10 hover:border-white/20 hover:text-white transition-all cursor-pointer w-full">Cancel</button>
                        <button type="button" @click="executeDelete()" id="btn-confirm-delete" class="px-6 py-3 rounded-xl text-sm font-black bg-rose-600 hover:bg-rose-500 text-white shadow-[0_0_15px_rgba(225,29,72,0.3)] transition-all cursor-pointer w-full">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </div>
        
        <form id="delete-form" :action="`{{ url('/admin/redeems') }}/${deleteId}`" method="POST" class="hidden">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="DELETE">
        </form>

    </main>

    <x-footer />
    <div id="toast-container" class="fixed top-24 right-5 z-[100] flex flex-col gap-2 pointer-events-none"></div>

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
          Alpine.data('redeemCodeManager', () => ({
              isCreateOpen: false,
              isEditOpen: false,
              isDeleteOpen: false,
              editId: null,
              deleteId: null,
              deleteCodeName: '',
              formData: { code: '', reward_description: '', is_active: '1', expired_at: '' },
              resetForm() { this.formData = { code: '', reward_description: '', is_active: '1', expired_at: '' }; },
              openCreateModal() { this.resetForm(); this.isCreateOpen = true; },
              openEditModal(id, code, description, isActive, expiredAt) {
                  this.editId = id;
                  this.formData.code = code;
                  this.formData.reward_description = description;
                  this.formData.is_active = isActive.toString();
                  this.formData.expired_at = expiredAt ? expiredAt.replace(' ', 'T').substring(0, 16) : '';
                  this.isEditOpen = true;
              },
              async submitCreateForm(e) {
                  const form = e.target;
                  const btn = document.getElementById('btn-save-code');
                  const originalText = btn.innerHTML;
                  btn.innerHTML = 'Saving...'; btn.disabled = true; btn.classList.add('opacity-75');
                  try {
                      const response = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: new FormData(form) });
                      const data = await response.json();
                      if (response.ok) { showToast(data.message || 'Code created successfully!', 'success'); setTimeout(() => window.location.reload(), 1000); } 
                      else { showToast(data.message || 'Failed to save.', 'error'); }
                  } catch (error) { showToast('Network error.', 'error'); } 
                  finally { btn.innerHTML = originalText; btn.disabled = false; btn.classList.remove('opacity-75'); }
              },
              async submitEditForm(e) {
                  const form = e.target;
                  const btn = document.getElementById('btn-update-code');
                  const originalText = btn.innerHTML;
                  btn.innerHTML = 'Updating...'; btn.disabled = true; btn.classList.add('opacity-75');
                  try {
                      const response = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: new FormData(form) });
                      const data = await response.json();
                      if (response.ok) { showToast(data.message || 'Code updated successfully!', 'success'); setTimeout(() => window.location.reload(), 1000); } 
                      else { showToast(data.message || 'Failed to update.', 'error'); }
                  } catch (error) { showToast('Network error.', 'error'); } 
                  finally { btn.innerHTML = originalText; btn.disabled = false; btn.classList.remove('opacity-75'); }
              },
              toggleStatus(id, newStatus) {
                  fetch(`{{ url('/admin/redeems') }}/${id}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ is_active: newStatus }) })
                  .then(res => res.json())
                  .then(data => { if(data.success) { showToast('Status updated.', 'success'); } else { throw new Error(); } })
                  .catch(() => { showToast('Failed to update status.', 'error'); setTimeout(() => window.location.reload(), 1000); });
              },
              confirmDelete(id, code) { this.deleteId = id; this.deleteCodeName = code; this.isDeleteOpen = true; },
              executeDelete() {
                  const btn = document.getElementById('btn-confirm-delete');
                  btn.disabled = true; btn.innerHTML = 'Deleting...';
                  document.getElementById('delete-form').submit();
              },
              copyToClipboard(text) {
                  navigator.clipboard.writeText(text).then(() => { showToast('Code copied to clipboard!', 'success'); }).catch(() => { showToast('Failed to copy.', 'error'); });
              }
          }));
      });

      function showToast(message, type = 'success') {
          const container = document.getElementById('toast-container');
          const style = type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border-rose-500/20 text-rose-400';
          const icon = type === 'success' ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
          const toast = document.createElement('div');
          toast.className = `px-4 py-3 rounded-xl border ${style} backdrop-blur-md shadow-lg transform transition-all duration-300 translate-x-10 opacity-0 text-sm font-medium flex items-center gap-3`;
          toast.innerHTML = `${icon} ${message}`;
          container.appendChild(toast);
          requestAnimationFrame(() => toast.classList.remove('translate-x-10', 'opacity-0'));
          setTimeout(() => { toast.classList.add('translate-x-10', 'opacity-0'); setTimeout(() => toast.remove(), 300); }, 3000);
      }
    </script>
  </body>
</html>