<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Merge Character - {{ env('APP_NAME') }}</title>
    <x-assets />
    
    <!-- Tom Select CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Tom Select Dark Mode Customization */
        .ts-control {
            background-color: #020617 !important; /* slate-950 */
            border: 1px solid #1e293b !important; /* slate-800 */
            color: #f8fafc !important; /* slate-50 */
            border-radius: 0.75rem !important;
            padding: 0.8rem 1rem !important;
            box-shadow: inset 0 2px 4px 0 rgb(0 0 0 / 0.05) !important;
        }
        .ts-control > input {
            color: #f8fafc !important;
        }
        .ts-dropdown {
            background-color: #0f172a !important; /* slate-900 */
            border: 1px solid #1e293b !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25) !important;
            color: #cbd5e1 !important; /* slate-300 */
            margin-top: 0.5rem;
            overflow: hidden;
        }
        .ts-dropdown .option {
            padding: 0.75rem 1rem;
        }
        .ts-dropdown .active {
            background-color: #06b6d4 !important; /* cyan-500 */
            color: white !important;
        }
        .ts-wrapper.single .ts-control:after {
            border-color: #64748b transparent transparent transparent; /* slate-500 */
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white">
    <x-navbar />
    
    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-rose-500/5 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <div class="max-w-[70rem] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <a href="#" class="text-cyan-400 hover:text-cyan-300 transition-colors">Admin Panel</a>
                        <span class="text-slate-700">&bull;</span>
                        <a href="{{ route('characters.index') }}" class="text-slate-400 hover:text-slate-300 transition-colors">Characters</a>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-md border border-rose-500/20">Merge Mode</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Merge <span class="text-transparent bg-clip-text bg-gradient-to-r from-rose-400 to-orange-500 drop-shadow-sm">Character</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Combine two characters. The source character will be deleted permanently.</p>
                </div>
                <div>
                    <a href="{{ route('characters.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white font-bold rounded-xl transition-all flex items-center gap-2 outline-none shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to List
                    </a>
                </div>
            </div>

            <div class="mb-8 bg-rose-500/10 border border-rose-500/30 rounded-2xl p-5 flex gap-4 items-start shadow-inner backdrop-blur-sm">
                <div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center shrink-0 border border-rose-500/30">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-rose-400 font-bold text-lg mb-1">Destructive Action Warning</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Merging <strong>{{ $character->name }}</strong> into another character will transfer all wallpapers, series, relationships, and API tags to the target. <strong class="text-slate-200">The current character ({{ $character->name }}) will be permanently deleted.</strong> This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-30 group">
                <div class="relative">
                    <div class="absolute inset-0 bg-slate-900/60 border border-rose-500/20 rounded-[2rem] shadow-xl backdrop-blur-md overflow-hidden pointer-events-none z-0">
                        <div class="absolute -top-24 -left-24 w-48 h-48 bg-rose-500/10 blur-[80px] transition-colors"></div>
                    </div>
                    
                    <div class="relative z-10 p-6 flex flex-col items-center text-center h-full">
                        <span class="absolute top-4 left-4 bg-rose-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-lg shadow-lg shadow-rose-500/30">
                            Source (Will be deleted)
                        </span>

                        <div class="w-32 h-32 rounded-2xl bg-slate-800 border-4 border-slate-900 shadow-xl overflow-hidden mt-6 mb-4">
                            @if(!empty($character->image['webp']))
                                <img src="{{ $character->image['webp'] }}" alt="{{ $character->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-500">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0M12 14a6 6 0 00-6 6h12a6 6 0 00-6-6z"></path></svg>
                                </div>
                            @endif
                        </div>
                        
                        <h2 class="text-2xl font-black text-white mb-1">{{ $character->name }}</h2>
                        <p class="text-sm text-slate-500 font-medium mb-6">{{ $character->slug }}</p>
                        
                        <div class="w-full grid grid-cols-2 gap-3 mt-auto">
                            <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 shadow-inner">
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">Wallpapers</p>
                                <p class="text-xl font-black text-cyan-400">{{ $character->wallpaperCount->total ?? 0 }}</p>
                            </div>
                            <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 shadow-inner">
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mb-1">Series</p>
                                <p class="text-xl font-black text-purple-400">{{ $character->series->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute inset-0 bg-slate-900/60 border border-cyan-500/20 rounded-[2rem] shadow-xl backdrop-blur-md overflow-hidden pointer-events-none z-0">
                        <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] transition-colors"></div>
                    </div>

                    <div class="relative z-10 p-6 flex flex-col h-full">
                        <span class="absolute top-4 left-4 bg-cyan-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-lg shadow-lg shadow-cyan-500/30">
                            Target (Will be kept)
                        </span>

                        <form id="mergeFormAction" class="mt-12 flex flex-col flex-grow h-full">
                            <div class="mb-auto">
                                <label for="target_id" class="block text-sm font-bold text-slate-300 mb-2">Select Target Character</label>
                                <p class="text-xs text-slate-500 mb-4">Search and select the character that will receive all data from the source character.</p>
                                
                                <div class="relative z-50">
                                    <select name="target_id" id="target_id" class="w-full" required>
                                        <option value="">Search character...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-8 pt-6 border-t border-white/5">
                                <label class="flex items-start gap-3 cursor-pointer group mb-6">
                                    <div class="relative flex items-center justify-center mt-0.5">
                                        <input type="checkbox" id="confirm_merge" required class="peer appearance-none w-5 h-5 border-2 border-slate-700 rounded bg-slate-950 checked:bg-rose-500 checked:border-rose-500 transition-colors">
                                        <svg class="absolute w-3 h-3 text-white pointer-events-none opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-400 group-hover:text-slate-300 transition-colors select-none">
                                        I understand that <strong>{{ $character->name }}</strong> will be permanently deleted and its relationships will be merged into the target.
                                    </span>
                                </label>

                                <button type="submit" id="btnSubmitMerge" class="w-full py-4 bg-gradient-to-r from-rose-500 to-orange-600 hover:from-rose-400 hover:to-orange-500 text-white font-black text-lg rounded-xl shadow-[0_0_15px_rgba(244,63,94,0.2)] hover:shadow-[0_0_25px_rgba(244,63,94,0.4)] transition-all flex items-center justify-center gap-2 outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    Execute Merge
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <x-footer />
    
    <!-- Tambahkan SweetAlert CDN jika x-assets Anda belum melampirkannya -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sourceCharacterId = {{ $character->id }};
        
        document.addEventListener('DOMContentLoaded', function() {
            
            // Inisialisasi Tom Select
            const targetSelect = new TomSelect('#target_id', {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                placeholder: 'Search for a character...',
                loadThrottle: 300,
                
                load: function(query, callback) {
                    if (!query.length) return callback();
                    
                    fetch(`{{ route('characters.list') }}?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(json => {
                            const filteredData = json.filter(item => item.id != sourceCharacterId);
                            callback(filteredData);
                        })
                        .catch(() => {
                            callback();
                        });
                },
                
                render: {
                    no_results: function(data, escape) {
                        return '<div class="no-results px-4 py-3 text-slate-500 text-sm">No characters found for "'+escape(data.input)+'"</div>';
                    },
                    loading: function(data, escape) {
                        return '<div class="loading px-4 py-3 text-cyan-500 text-sm">Searching...</div>';
                    }
                }
            });

            // Handle Submit Form
            const form = document.getElementById('mergeFormAction');
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const targetId = targetSelect.getValue();
                const confirmCheck = document.getElementById('confirm_merge').checked;

                if (!targetId) {
                    Swal.fire('Error', 'Please select a target character.', 'error');
                    return;
                }

                if (!confirmCheck) {
                    Swal.fire('Warning', 'Please check the confirmation box.', 'warning');
                    return;
                }

                const result = await Swal.fire({
                    title: 'Are you absolutely sure?',
                    text: "You are about to merge and delete the source character. This cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f43f5e', 
                    cancelButtonColor: '#334155', 
                    confirmButtonText: 'Yes, Execute Merge',
                    background: '#0f172a',
                    color: '#f8fafc',
                });

                if (result.isConfirmed) {
                    const btn = document.getElementById('btnSubmitMerge');
                    const originalText = btn.innerHTML;
                    
                    btn.disabled = true;
                    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';

                    try {
                        const url = "{{ route('characters.merge', ['id' => $character->id]) }}";
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json', 
                                'X-CSRF-TOKEN': csrfToken, 
                                'Accept': 'application/json', 
                                'X-Requested-With': 'XMLHttpRequest' 
                            },
                            body: JSON.stringify({ target_id: targetId })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Merged Successfully!',
                                text: data.message,
                                background: '#0f172a',
                                color: '#f8fafc',
                                confirmButtonColor: '#0ea5e9',
                            });
                            window.location.href = "{{ route('characters.index') }}";
                        } else {
                            throw new Error(data.message || 'Validation or Server Error');
                        }
                    } catch (error) {
                        console.error('Merge error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Merge Failed',
                            text: error.message,
                            background: '#0f172a',
                            color: '#f8fafc',
                            confirmButtonColor: '#f43f5e',
                        });
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                }
            });
        });
    </script>
</body>
</html>