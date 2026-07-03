<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth bg-slate-950">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', env('APP_NAME'))</title>
    <meta name="robots" content="noindex, nofollow" />
    <x-assets />
    <style>
        .gradient-bg {
            background: radial-gradient(circle at 50% 30%, #0f172a 0%, #020617 100%);
        }
    </style>
</head>
<body class="gradient-bg text-slate-200 font-sans min-h-screen flex items-center justify-center p-4 selection:bg-cyan-500 selection:text-white antialiased overflow-x-hidden">
    
    <div class="relative w-full max-w-lg text-center z-10">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-cyan-500/10 rounded-full blur-[80px] pointer-events-none -z-10"></div>
        
        @yield('content')
    </div>

</body>
</html>