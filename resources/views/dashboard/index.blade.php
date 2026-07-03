<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <title>Admin Dashboard - {{ env('APP_NAME') }}</title>
    
    <x-assets />
    
    <style>
      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      .break-inside-avoid { break-inside: avoid; }
      .card-hover { transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease; }
      .card-hover:hover { transform: translateY(-4px); border-color: rgba(34, 211, 238, 0.28); box-shadow: 0 14px 30px rgba(0,0,0,0.25); }
      summary::-webkit-details-marker { display: none; }
    </style>
  </head>
  <body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white">
    <x-navbar />

    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <section id="admin-dashboard" class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
            
            <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400">Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Dashboard</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Dashboard <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Overview</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">At-a-glance metrics and system status.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-5 mb-10">
                
                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(34,211,238,0.15)] hover:border-cyan-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-cyan-500/10 rounded-full blur-2xl group-hover:bg-cyan-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-cyan-400 transition-colors">Total Wallpapers</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalWallpapers ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 border border-cyan-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(59,130,246,0.15)] hover:border-blue-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-blue-400 transition-colors">Total Users</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalUsers ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-400 border border-blue-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(99,102,241,0.15)] hover:border-indigo-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-indigo-400 transition-colors">Total Posts</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalPosts ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5L18.5 7H20a2 2 0 002-2v12a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(244,63,94,0.15)] hover:border-rose-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-rose-400 transition-colors">Total Characters</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalCharacters ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-400 border border-rose-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(16,185,129,0.15)] hover:border-emerald-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-emerald-400 transition-colors">Total Tags</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalTags ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(168,85,247,0.15)] hover:border-purple-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-purple-400 transition-colors">Total Series</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalSeries ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-400 border border-purple-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 shadow-lg backdrop-blur-sm hover:shadow-[0_0_20px_rgba(245,158,11,0.15)] hover:border-amber-500/30 transition-all duration-300 group relative overflow-hidden flex items-center justify-between transform hover:-translate-y-1">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider group-hover:text-amber-400 transition-colors">Total Artists</p>
                        <h3 class="text-3xl font-black text-white">{{ number_format($totalArtists ?? 0) }}</h3>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-400 border border-amber-500/20 shadow-inner relative z-10 group-hover:scale-110 transition-transform">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-2 xl:grid-cols-2 gap-8 mb-10">
                
                <div class="bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl p-6 sm:p-8 backdrop-blur-md relative overflow-hidden">
                    <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-cyan-500/5 blur-[80px] pointer-events-none"></div>
                    <h3 class="text-lg font-black text-white mb-6 flex items-center gap-3 relative z-10">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center border border-cyan-500/20 text-cyan-500 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        </div>
                        Fetch Wallpaper Status
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 relative z-10">
                        <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5 shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span> Pending
                            </p>
                            <p class="text-2xl font-black text-amber-400">{{ number_format($fetchWallpaperPending ?? 0) }}</p>
                        </div>
                        <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5 shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span> Processing
                            </p>
                            <p class="text-2xl font-black text-cyan-400">{{ number_format($fetchWallpaperProcessing ?? 0) }}</p>
                        </div>
                        <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5 shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span> Failed
                            </p>
                            <p class="text-2xl font-black text-rose-400">{{ number_format($fetchWallpaperFailed ?? 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl p-6 sm:p-8 backdrop-blur-md relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 w-48 h-48 bg-blue-500/5 blur-[80px] pointer-events-none"></div>
                    <h3 class="text-lg font-black text-white mb-6 flex items-center gap-3 relative z-10">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center border border-blue-500/20 text-blue-500 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </div>
                        Task Fetch Queue
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 relative z-10">
                        <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5 shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span> Running
                            </p>
                            <p class="text-2xl font-black text-blue-400">{{ number_format($taskFetchRunning ?? 0) }}</p>
                        </div>
                        <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5 shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span> Completed
                            </p>
                            <p class="text-2xl font-black text-emerald-400">{{ number_format($taskFetchCompleted ?? 0) }}</p>
                        </div>
                        <div class="bg-slate-950/50 rounded-xl p-5 border border-white/5 shadow-inner">
                            <p class="text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span> Pending
                            </p>
                            <p class="text-2xl font-black text-amber-400">{{ number_format($taskFetchPending ?? 0) }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="bg-slate-900/60 border border-white/10 rounded-[2rem] shadow-xl overflow-hidden p-6 sm:p-8 backdrop-blur-md relative">
                <div class="absolute bottom-0 right-1/4 w-64 h-64 bg-cyan-500/10 blur-[100px] pointer-events-none"></div>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 gap-4 relative z-10">
                    <h3 class="text-xl font-black text-white flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center border border-cyan-500/20 text-cyan-400 shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        Daily User Statistics
                        <span class="text-xs font-bold text-slate-500 bg-slate-800/50 px-3 py-1 rounded-lg ml-2">Last 7 Days</span>
                    </h3>
                </div>
                
                <div class="relative w-full h-80 z-10 bg-slate-950/30 rounded-xl border border-white/5 p-4 shadow-inner">
                    <canvas id="userStatsChart"></canvas>
                </div>
            </div>

        </section>
    </main>

    <x-footer />

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('userStatsChart').getContext('2d');
        
        const labels = {!! json_encode($chartLabels) !!};
        const dataCreated = {!! json_encode($chartDataCreated) !!};
        const dataModified = {!! json_encode($chartDataModified) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Users (created_at)',
                        data: dataCreated,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#10b981'
                    },
                    {
                        label: 'Active/Updated Users (modified_at)',
                        data: dataModified,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#cbd5e1',
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'sans-serif',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#94a3b8',
                            stepSize: 1,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false,
                        }
                    },
                    x: {
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.05)',
                            drawBorder: false,
                        }
                    }
                }
            }
        });
      });
    </script>
  </body>
</html>