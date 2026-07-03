<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') | {{ env('APP_NAME') }}</title>
    <meta name="robots" content="noindex, nofollow" />
    
    <x-assets />
    
</head>
<body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white antialiased overflow-x-hidden [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-700 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-slate-600">
    
    <main class="flex-grow flex flex-col items-center justify-center relative text-slate-300 py-16 px-4">
        <div class="w-full max-w-4xl text-center relative z-10 flex flex-col items-center">
            
            <h1 class="text-[8rem] sm:text-[12rem] md:text-[15rem] leading-none font-extrabold bg-gradient-to-b from-cyan-400 via-cyan-500 to-slate-950 text-transparent bg-clip-text drop-shadow-xl mb-4 tracking-tighter">
                @yield('code')
            </h1>
            
            <h2 class="text-3xl sm:text-4xl font-black text-white mb-6 drop-shadow-md">@yield('title')</h2>
            
            <p class="text-slate-400 mb-12 max-w-2xl text-lg font-medium leading-relaxed">
                @yield('message')
            </p>
        </div>
    </main>

</body>
</html>