<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Loops HR Portal') }} - Sign In</title>

        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Favicon & Icons -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('loops-icon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('loops-icon.png') }}">

        <!-- Fonts: Plus Jakarta Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Lucide Icons -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; }
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="h-full min-h-screen antialiased bg-[#eef2f6] dark:bg-[#070b14] text-slate-800 dark:text-slate-200 transition-colors duration-200 relative selection:bg-blue-500 selection:text-white flex flex-col justify-center items-center p-4 sm:p-6"
          x-data="{ 
              theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
              toggleTheme() {
                  this.theme = this.theme === 'light' ? 'dark' : 'light';
                  localStorage.setItem('theme', this.theme);
                  if (this.theme === 'dark') {
                      document.documentElement.classList.add('dark');
                  } else {
                      document.documentElement.classList.remove('dark');
                  }
                  this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
              }
          }"
          x-init="$nextTick(() => { if (window.lucide) lucide.createIcons(); })">

        <!-- Subtle ambient radial glow behind login card -->
        <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-500/10 dark:bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Right Theme Toggle -->
        <div class="absolute top-4 right-4 z-20">
            <button type="button" 
                    @click="toggleTheme()" 
                    class="p-2 sm:px-3 sm:py-2 rounded-xl bg-white dark:bg-[#111927] border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-[#162032] shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5 text-xs font-semibold"
                    :title="theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                <i data-lucide="sun" x-show="theme === 'dark'" class="w-4 h-4 text-amber-400"></i>
                <i data-lucide="moon" x-show="theme !== 'dark'" class="w-4 h-4 text-slate-600"></i>
                <span class="hidden sm:inline" x-text="theme === 'dark' ? 'Light' : 'Dark'"></span>
            </button>
        </div>

        <div class="w-full max-w-md relative z-10 space-y-4">
            <!-- Login Card Container -->
            <div class="w-full bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-2xl shadow-[0_4px_25px_-4px_rgba(15,23,42,0.08),0_2px_10px_-2px_rgba(15,23,42,0.04)] dark:shadow-[0_20px_60px_-15px_rgba(0,0,0,0.6)] p-6 sm:p-8 transition-all">
                <!-- Brand Logo Inside Card -->
                <div class="text-center mb-6">
                    <a href="/" class="inline-flex items-center justify-center group transition-transform duration-200 hover:scale-105">
                        <!-- Dark Mode Logo -->
                        <img x-show="theme === 'dark'" 
                             src="{{ asset('LoopsWhite.png') }}" 
                             class="h-10 sm:h-11 w-auto object-contain mx-auto" 
                             alt="Loops HR Logo">
                        <!-- Light Mode Logo -->
                        <img x-show="theme !== 'dark'" 
                             src="{{ asset('LoopsBlack.png') }}" 
                             class="h-10 sm:h-11 w-auto object-contain mx-auto" 
                             alt="Loops HR Logo">
                    </a>
                </div>

                {{ $slot }}
            </div>

            <!-- Footer note -->
            <p class="text-center text-[11px] font-medium text-slate-600 dark:text-slate-300">
                &copy; {{ date('Y') }} Loops HR System. Enterprise Portal.
            </p>
        </div>
    </body>
</html>
