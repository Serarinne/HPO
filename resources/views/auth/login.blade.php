<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth bg-slate-950">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    
    <title>Login to {{ env('APP_NAME') }}</title>
    <meta name="description" content="Login to {{ env('APP_NAME') }} to save, manage, and organize your favorite high-quality Honkai: Star Rail wallpapers." />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="canonical" href="{{ route('login') }}" />

    <meta property="og:title" content="Login | {{ env('APP_NAME') }}" />
    <meta property="og:description" content="Login to {{ env('APP_NAME') }} to save, manage, and organize your favorite high-quality Honkai: Star Rail wallpapers." />
    <meta property="og:url" content="{{ route('login') }}" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="{{ env('APP_NAME') }}" />

    <x-assets />
    
  </head>
  
  <body class="bg-slate-950 text-slate-200 font-sans min-h-screen flex flex-col selection:bg-cyan-500 selection:text-white antialiased overflow-x-hidden [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-700 [&::-webkit-scrollbar-thumb]:rounded-full hover:[&::-webkit-scrollbar-thumb]:bg-slate-600">
    <main class="flex-grow flex flex-col items-center justify-center p-4 py-12 relative overflow-hidden text-slate-300">
        <div class="w-full max-w-md bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl p-8 sm:p-12 relative z-10 group overflow-hidden">
            
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-cyan-500/10 rounded-full blur-[60px] pointer-events-none transition-all duration-700"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-blue-500/10 rounded-full blur-[60px] pointer-events-none transition-all duration-700"></div>

            <div class="text-center mb-10 relative z-10">
                <div class="w-16 h-16 mx-auto bg-cyan-500/10 rounded-2xl flex items-center justify-center mb-6 border border-cyan-500/20 shadow-inner group-hover:scale-105 transition-transform duration-500">
                    <svg class="w-8 h-8 text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                
                <h1 class="text-3xl sm:text-4xl font-black text-white tracking-tight mb-3 drop-shadow-md">
                    Welcome <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">Back</span>
                </h1>
                <p class="text-slate-400 text-sm font-medium leading-relaxed">
                    Login to access your Data Bank and manage your favorite wallpapers.
                </p>
            </div>

            @if (session('error') || session('warning'))
                <div class="flex items-start {{ session('warning') ? 'bg-amber-500/10 border-amber-500/20 text-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.1)]' : 'bg-rose-500/10 border-rose-500/20 text-rose-400 shadow-[0_0_15px_rgba(244,63,94,0.1)]' }} backdrop-blur-md border px-5 py-4 rounded-2xl text-sm mb-6 relative z-10" role="alert">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="leading-relaxed font-bold tracking-wide">{{ session('error') ?? session('warning') }}</span>
                </div>
            @endif

            <div id="error-message" class="hidden flex items-start bg-rose-500/10 border border-rose-500/20 text-rose-400 backdrop-blur-md px-5 py-4 rounded-2xl text-sm mb-6 shadow-[0_0_15px_rgba(244,63,94,0.1)] relative z-10" role="alert">
                <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span id="error-text" class="leading-relaxed font-bold tracking-wide"></span>
            </div>

            <div class="pt-2 relative z-10">
                <button id="google-login-btn" class="w-full bg-white hover:bg-slate-50 text-slate-900 font-bold text-sm sm:text-base py-4 px-6 rounded-full flex items-center justify-center transition-all duration-300 shadow-[0_0_20px_rgba(255,255,255,0.15)] hover:shadow-[0_0_30px_rgba(255,255,255,0.3)] hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-cyan-500 relative group/btn overflow-hidden">
                    <svg class="w-6 h-6 mr-3 flex-shrink-0 group-hover/btn:scale-110 transition-transform" viewBox="0 0 24 24">
                        <path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#4285F4" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#34A853" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span id="btn-text" class="tracking-wide">Continue with Google</span>
                    <svg id="loading-spinner" class="animate-spin hidden h-5 w-5 text-slate-900 absolute right-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </main>

    <script type="module">
      import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-app.js";
      import { getAuth, signInWithPopup, GoogleAuthProvider } from "https://www.gstatic.com/firebasejs/10.8.1/firebase-auth.js";

      const firebaseConfig = {
        apiKey: "{{ env('FIREBASE_API_KEY') }}",
        authDomain: "{{ env('FIREBASE_AUTH_DOMAIN') }}",
        projectId: "{{ env('FIREBASE_PROJECT_ID') }}",
      };

      const app = initializeApp(firebaseConfig);
      const auth = getAuth(app);
      const provider = new GoogleAuthProvider();

      const loginBtn = document.getElementById('google-login-btn');
      const btnText = document.getElementById('btn-text');
      const spinner = document.getElementById('loading-spinner');
      const errorMessage = document.getElementById('error-message');
      const errorText = document.getElementById('error-text');
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      loginBtn.addEventListener('click', async () => {
        loginBtn.disabled = true;
        loginBtn.classList.add('opacity-90', 'cursor-not-allowed');
        btnText.textContent = 'Processing...';
        spinner.classList.remove('hidden');
        errorMessage.classList.add('hidden');

        try {
          const result = await signInWithPopup(auth, provider);
          const idToken = await result.user.getIdToken();

          const response = await fetch("{{ route('login.firebase') }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: JSON.stringify({ firebase_token: idToken })
          });

          const data = await response.json();
          
          if (data.success) {
            window.location.href = data.redirect;
          } else {
            throw new Error(data.message || 'Failed to connect to server.');
          }
        } catch (error) {
          errorText.textContent = error.message.includes('popup-closed')
            ? 'Login process cancelled.'
            : 'An error occurred. Please try again.';
          
          errorMessage.classList.remove('hidden');
          
          loginBtn.disabled = false;
          loginBtn.classList.remove('opacity-90', 'cursor-not-allowed');
          btnText.textContent = 'Continue with Google';
          spinner.classList.add('hidden');
        }
      });
    </script>
  </body>
</html>