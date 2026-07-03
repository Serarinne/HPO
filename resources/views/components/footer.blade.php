<footer class="relative border-t border-white/10 bg-slate-900/50 backdrop-blur-xl overflow-hidden mt-auto" aria-labelledby="footer-heading">
    <h2 id="footer-heading" class="sr-only">Footer</h2>
    
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-4xl h-[150px] bg-cyan-500/5 blur-[80px] pointer-events-none rounded-full" aria-hidden="true"></div>
    
    <div class="relative z-10 max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row md:justify-between md:items-center gap-10">
        
        <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-4">
            <div class="space-y-1.5 max-w-md">
                <p class="text-xs font-medium text-slate-400 tracking-wide">
                    &copy; {{ date('Y') }} <span class="text-white font-bold">{{ env('APP_NAME') }}</span>. All rights reserved.
                </p>
                <p class="text-[11px] font-medium text-slate-300 tracking-wide leading-relaxed">
                    Honkai: Star Rail and all related characters and assets are the property and copyright of HoYoverse. This is an unofficial fan site.
                </p>
            </div>
        </div>

    </div>
</footer>