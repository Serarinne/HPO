<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <title>Application Settings - {{ env('APP_NAME') }}</title>
    
    <x-assets />
    
    <style>
      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      .break-inside-avoid { break-inside: avoid; }
    </style>
  </head>
  <body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white">
    <x-navbar />

    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <section id="admin-settings" class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
            
            <div class="mb-10 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <span class="text-cyan-400">Admin Panel</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">System</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Application <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Settings</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Configure global application parameters, versions, and ad integrations.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-8 p-5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl backdrop-blur-md shadow-[0_0_15px_rgba(16,185,129,0.15)] flex items-center gap-3 relative z-10">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="font-bold text-sm">{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-8 p-6 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl backdrop-blur-md shadow-[0_0_15px_rgba(244,63,94,0.15)] flex flex-col gap-3 relative z-10">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 flex-shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <h4 class="font-black text-white">Please check the errors below:</h4>
                    </div>
                    <ul class="list-disc list-inside text-sm font-medium space-y-1.5 ml-8">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('settings.update') }}" method="POST" class="space-y-8 relative z-10">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 sm:p-8 shadow-xl backdrop-blur-sm relative overflow-hidden group h-fit">
                        <div class="absolute -right-16 -top-16 w-48 h-48 bg-cyan-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                        <h3 class="text-xl font-black text-white mb-6 border-b border-white/5 pb-4 flex items-center gap-3 relative z-10">
                            <span class="w-2 h-6 bg-cyan-400 rounded-full shadow-[0_0_10px_rgba(34,211,238,0.5)]"></span>
                            General Information
                        </h3>
                        <div class="space-y-5 relative z-10">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Package Name</label>
                                <input type="text" name="package_name" value="{{ old('package_name', $settings->package_name ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner placeholder-slate-600">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">App Version</label>
                                    <input type="text" name="app_version" value="{{ old('app_version', $settings->app_version ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner placeholder-slate-600">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Build Version</label>
                                    <input type="number" name="build_version" value="{{ old('build_version', $settings->build_version ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner placeholder-slate-600">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Play Store URL</label>
                                <input type="url" name="url_playstore" value="{{ old('url_playstore', $settings->url_playstore ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner placeholder-slate-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 sm:p-8 shadow-xl backdrop-blur-sm relative overflow-hidden group h-fit">
                        <div class="absolute -right-16 -top-16 w-48 h-48 bg-purple-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                        <h3 class="text-xl font-black text-white mb-6 border-b border-white/5 pb-4 flex items-center gap-3 relative z-10">
                            <span class="w-2 h-6 bg-purple-400 rounded-full shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
                            Ads Configuration
                        </h3>
                        <div class="space-y-5 relative z-10">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Banner Provider</label>
                                    <div class="relative">
                                        <select name="banner_provider" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner appearance-none cursor-pointer pr-10">
                                            <option value="off" {{ old('banner_provider', $settings->banner_provider ?? 'off') == 'off' ? 'selected' : '' }}>Off</option>
                                            <option value="admob" {{ old('banner_provider', $settings->banner_provider ?? '') == 'admob' ? 'selected' : '' }}>AdMob</option>
                                            <option value="applovin" {{ old('banner_provider', $settings->banner_provider ?? '') == 'applovin' ? 'selected' : '' }}>AppLovin</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg></div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Open App Provider</label>
                                    <div class="relative">
                                        <select name="open_app_provider" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner appearance-none cursor-pointer pr-10">
                                            <option value="off" {{ old('open_app_provider', $settings->open_app_provider ?? 'off') == 'off' ? 'selected' : '' }}>Off</option>
                                            <option value="admob" {{ old('open_app_provider', $settings->open_app_provider ?? '') == 'admob' ? 'selected' : '' }}>AdMob</option>
                                            <option value="applovin" {{ old('open_app_provider', $settings->open_app_provider ?? '') == 'applovin' ? 'selected' : '' }}>AppLovin</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg></div>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="sm:col-span-2 md:col-span-1">
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Transition Mode</label>
                                    <div class="relative">
                                        <select name="transition_mode" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner appearance-none cursor-pointer pr-10">
                                            <option value="off" {{ old('transition_mode', $settings->transition_mode ?? 'off') == 'off' ? 'selected' : '' }}>Off / Default</option>
                                            <option value="admob_rewarded" {{ old('transition_mode', $settings->transition_mode ?? '') == 'admob_rewarded' ? 'selected' : '' }}>AdMob Rewarded</option>
                                            <option value="admob_interstitial" {{ old('transition_mode', $settings->transition_mode ?? '') == 'admob_interstitial' ? 'selected' : '' }}>AdMob Interstitial</option>
                                            <option value="admob_rewarded_interstitial" {{ old('transition_mode', $settings->transition_mode ?? '') == 'admob_rewarded_interstitial' ? 'selected' : '' }}>AdMob Rewarded Interstitial</option>
                                            <option value="applovin_interstitial" {{ old('transition_mode', $settings->transition_mode ?? '') == 'applovin_interstitial' ? 'selected' : '' }}>AppLovin Interstitial</option>
                                            <option value="applovin_rewarded" {{ old('transition_mode', $settings->transition_mode ?? '') == 'applovin_rewarded' ? 'selected' : '' }}>AppLovin Rewarded</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg></div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">Ad Interval (Seconds)</label>
                                    <input type="number" name="ad_interval" value="{{ old('ad_interval', $settings->ad_interval ?? 30) }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner placeholder-slate-600">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 sm:p-8 shadow-xl backdrop-blur-sm relative overflow-hidden group h-fit">
                        <div class="absolute -right-16 -top-16 w-48 h-48 bg-amber-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                        <h3 class="text-xl font-black text-white mb-6 border-b border-white/5 pb-4 flex items-center gap-3 relative z-10">
                            <span class="w-2 h-6 bg-amber-400 rounded-full shadow-[0_0_10px_rgba(251,191,36,0.5)]"></span>
                            AdMob Settings
                        </h3>
                        <div class="space-y-5 relative z-10">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AdMob App ID</label>
                                <input type="text" name="admob_app_id" value="{{ old('admob_app_id', $settings->admob_app_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AdMob Banner ID</label>
                                <input type="text" name="admob_banner_id" value="{{ old('admob_banner_id', $settings->admob_banner_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AdMob Interstitial ID</label>
                                <input type="text" name="admob_interstitial_id" value="{{ old('admob_interstitial_id', $settings->admob_interstitial_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AdMob Rewarded ID</label>
                                <input type="text" name="admob_rewarded_id" value="{{ old('admob_rewarded_id', $settings->admob_rewarded_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AdMob Rewarded Interstitial ID</label>
                                <input type="text" name="admob_rewarded_interstitial_id" value="{{ old('admob_rewarded_interstitial_id', $settings->admob_rewarded_interstitial_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AdMob Open App ID</label>
                                <input type="text" name="admob_open_app_id" value="{{ old('admob_open_app_id', $settings->admob_open_app_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-amber-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-900/60 border border-white/5 rounded-[1.5rem] p-6 sm:p-8 shadow-xl backdrop-blur-sm relative overflow-hidden group h-fit">
                        <div class="absolute -right-16 -top-16 w-48 h-48 bg-emerald-500/10 rounded-full blur-[80px] pointer-events-none"></div>
                        <h3 class="text-xl font-black text-white mb-6 border-b border-white/5 pb-4 flex items-center gap-3 relative z-10">
                            <span class="w-2 h-6 bg-emerald-400 rounded-full shadow-[0_0_10px_rgba(52,211,153,0.5)]"></span>
                            AppLovin Settings
                        </h3>
                        <div class="space-y-5 relative z-10">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AppLovin SDK Key</label>
                                <input type="text" name="applovin_sdk_key" value="{{ old('applovin_sdk_key', $settings->applovin_sdk_key ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AppLovin Banner ID</label>
                                <input type="text" name="applovin_banner_id" value="{{ old('applovin_banner_id', $settings->applovin_banner_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AppLovin Interstitial ID</label>
                                <input type="text" name="applovin_interstitial_id" value="{{ old('applovin_interstitial_id', $settings->applovin_interstitial_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AppLovin Rewarded ID</label>
                                <input type="text" name="applovin_rewarded_id" value="{{ old('applovin_rewarded_id', $settings->applovin_rewarded_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 mb-2 uppercase tracking-wider">AppLovin Open App ID</label>
                                <input type="text" name="applovin_open_app_id" value="{{ old('applovin_open_app_id', $settings->applovin_open_app_id ?? '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner placeholder-slate-600 font-mono">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-10 flex justify-end">
                    <button type="submit" class="px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-3 outline-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Save Settings
                    </button>
                </div>
                
            </form>

        </section>
    </main>

    <x-footer />

  </body>
</html>