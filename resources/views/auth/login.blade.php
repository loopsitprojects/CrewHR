<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Error Alert -->
    @if (session('error'))
        <div class="mb-4 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-start gap-2.5 shadow-2xs">
            <svg class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="leading-relaxed">{{ session('error') }}</div>
        </div>
    @endif

    <div class="mb-5 border-b border-slate-100 dark:border-slate-800/80 pb-4">
        <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Welcome Back</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
            <span>Sign in with your Work Email or Username</span>
        </p>
    </div>

    <!-- Google Work Email SSO Button -->
    <div class="space-y-3">
        <a href="{{ route('auth.google') }}" 
           class="w-full py-2.5 px-4 bg-white hover:bg-slate-50 dark:bg-[#0c1322] dark:hover:bg-[#141e33] border border-slate-200 dark:border-slate-700/80 active:scale-[0.99] text-slate-800 dark:text-slate-100 font-bold text-xs sm:text-sm rounded-xl shadow-2xs hover:shadow-xs transition-all flex items-center justify-center gap-2.5 cursor-pointer group">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Sign in with Google</span>
            <span class="text-[10px] uppercase font-extrabold bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 px-1.5 py-0.5 rounded border border-blue-200/60 dark:border-blue-800/60">Work Email</span>
        </a>

        <!-- Divider -->
        <div class="relative flex items-center justify-center pt-1 pb-1">
            <div class="border-t border-slate-200/80 dark:border-slate-800 w-full"></div>
            <span class="bg-white dark:bg-[#111927] px-3 text-[10px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider shrink-0">or continue with password</span>
            <div class="border-t border-slate-200/80 dark:border-slate-800 w-full"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" x-data="{ loginVal: 'admin', showPass: false }" class="space-y-4">
        @csrf

        <!-- Email or Username -->
        <div class="space-y-1.5">
            <label for="login" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Email or Username</label>
            <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 dark:text-slate-500 pointer-events-none">
                    <i data-lucide="user" class="w-4 h-4"></i>
                </span>
                <input id="login" 
                       type="text" 
                       name="login" 
                       x-model="loginVal" 
                       required 
                       autofocus 
                       autocomplete="username" 
                       placeholder="e.g. admin or admin@loopshr.com"
                       class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 dark:bg-[#0c1322] border border-slate-200 dark:border-slate-800 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 dark:focus:border-blue-500 transition-all shadow-2xs" />
            </div>
            <x-input-error :messages="$errors->get('login')" class="mt-1" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline">Forgot?</a>
                @endif
            </div>
            <div class="relative flex items-center">
                <span class="absolute left-3 text-slate-400 dark:text-slate-500 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
                <input id="password" 
                       :type="showPass ? 'text' : 'password'"
                       name="password"
                       value="password"
                       required 
                       autocomplete="current-password"
                       class="w-full pl-9 pr-10 py-2.5 bg-slate-50 dark:bg-[#0c1322] border border-slate-200 dark:border-slate-800 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 dark:focus:border-blue-500 transition-all shadow-2xs font-mono" />
                <button type="button" 
                        @click="showPass = !showPass" 
                        class="absolute right-3 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 focus:outline-none cursor-pointer">
                    <i data-lucide="eye" x-show="!showPass" class="w-4 h-4"></i>
                    <i data-lucide="eye-off" x-show="showPass" x-cloak class="w-4 h-4 text-blue-600 dark:text-blue-400"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" 
                       type="checkbox" 
                       name="remember" 
                       checked
                       class="rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-[#0c1322] text-blue-600 focus:ring-blue-500/30 w-4 h-4">
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Remember me</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" 
                    class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-500 active:scale-[0.99] text-white font-bold text-xs sm:text-sm rounded-xl shadow-[0_4px_14px_rgba(37,99,235,0.3)] hover:shadow-[0_6px_20px_rgba(37,99,235,0.4)] transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span>Log in</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Pre-configured Accounts Quick Switcher (Unified 60-30-10 category design) -->
        <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800/80 space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-slate-600 dark:text-slate-300 uppercase tracking-wider block">
                    Click Account to Autofill:
                </span>
                <span class="text-[10px] font-medium text-slate-600 dark:text-slate-300">Click to switch</span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <!-- Super Admin (Blue Dot) -->
                <button type="button" 
                        @click="loginVal = 'admin'" 
                        :class="loginVal === 'admin' ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/60 dark:bg-blue-500/10' : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/70 hover:bg-slate-100/80 dark:bg-[#0c1322] dark:hover:bg-[#141e33]'"
                        class="p-2.5 rounded-xl border text-left transition-all cursor-pointer group shadow-2xs">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                        <span class="font-extrabold text-slate-900 dark:text-slate-100 text-xs">Super Admin</span>
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate font-mono">admin / admin@loopshr.com</span>
                </button>

                <!-- HR Lead (Purple Dot) -->
                <button type="button" 
                        @click="loginVal = 'supuni'" 
                        :class="loginVal === 'supuni' ? 'border-purple-500 ring-2 ring-purple-500/20 bg-purple-50/60 dark:bg-purple-500/10' : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/70 hover:bg-slate-100/80 dark:bg-[#0c1322] dark:hover:bg-[#141e33]'"
                        class="p-2.5 rounded-xl border text-left transition-all cursor-pointer group shadow-2xs">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>
                        <span class="font-extrabold text-slate-900 dark:text-slate-100 text-xs">HR Lead</span>
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate font-mono">supuni / supuni@loopshr.com</span>
                </button>

                <!-- Manager (Amber Dot) -->
                <button type="button" 
                        @click="loginVal = 'arosh'" 
                        :class="loginVal === 'arosh' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/60 dark:bg-amber-500/10' : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/70 hover:bg-slate-100/80 dark:bg-[#0c1322] dark:hover:bg-[#141e33]'"
                        class="p-2.5 rounded-xl border text-left transition-all cursor-pointer group shadow-2xs">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                        <span class="font-extrabold text-slate-900 dark:text-slate-100 text-xs">Manager</span>
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate font-mono">arosh / arosh@loopshr.com</span>
                </button>

                <!-- Employee (Emerald Dot) -->
                <button type="button" 
                        @click="loginVal = 'shimal'" 
                        :class="loginVal === 'shimal' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/60 dark:bg-emerald-500/10' : 'border-slate-200/80 dark:border-slate-800 bg-slate-50/70 hover:bg-slate-100/80 dark:bg-[#0c1322] dark:hover:bg-[#141e33]'"
                        class="p-2.5 rounded-xl border text-left transition-all cursor-pointer group shadow-2xs">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <span class="font-extrabold text-slate-900 dark:text-slate-100 text-xs">Employee</span>
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 block truncate font-mono">shimal / shimal@loopshr.com</span>
                </button>
            </div>
        </div>
    </form>
</x-guest-layout>
