<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 border-b border-slate-100 dark:border-slate-800/80 pb-4">
        <h2 class="text-xl font-extrabold text-slate-900 dark:text-slate-100 tracking-tight">Welcome Back</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
            <span>Sign in with your Email or Username</span>
            <span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-500/30 px-1.5 py-0.5 rounded text-[11px] font-mono font-bold">
                password
            </span>
        </p>
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
