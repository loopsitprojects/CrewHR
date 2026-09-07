<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 border-b border-gray-100 pb-4">
        <h2 class="text-lg font-bold text-gray-900">Welcome Back</h2>
        <p class="text-xs text-gray-500">Sign in with your Email or Username (Password: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-blue-600 font-bold">password</code>)</p>
    </div>

    <form method="POST" action="{{ route('login') }}" x-data="{ loginVal: 'admin', showPass: false }">
        @csrf

        <!-- Email or Username -->
        <div>
            <x-input-label for="login" :value="__('Email or Username')" />
            <x-text-input id="login" class="block mt-1 w-full" type="text" name="login" x-model="loginVal" required autofocus autocomplete="username" placeholder="e.g. admin or admin@loopshr.com" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative flex items-center mt-1">
                <input id="password" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full pr-10 text-sm font-semibold p-2 border"
                       :type="showPass ? 'text' : 'password'"
                       name="password"
                       value="password"
                       required autocomplete="current-password" />
                <button type="button" @click="showPass = !showPass" class="absolute right-3 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                    <i data-lucide="eye" x-show="!showPass" class="w-4 h-4"></i>
                    <i data-lucide="eye-off" x-show="showPass" x-cloak class="w-4 h-4 text-blue-600"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4 flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember" checked>
                <span class="ms-2 text-xs font-semibold text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center py-2.5 bg-blue-600 hover:bg-blue-700 font-bold text-xs shadow-md">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <!-- Pre-configured Accounts Quick Switcher -->
        <div class="mt-6 pt-4 border-t border-gray-100 space-y-2">
            <span class="text-[11px] font-extrabold text-gray-400 uppercase tracking-wider block">Click Account to Autofill:</span>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button type="button" @click="loginVal = 'admin'" class="p-2 rounded-lg border border-blue-200 bg-blue-50/50 hover:bg-blue-100 text-left transition-all">
                    <span class="font-black text-blue-900 block">Super Admin</span>
                    <span class="text-[10px] text-blue-700">admin / admin@loopshr.com</span>
                </button>
                <button type="button" @click="loginVal = 'supuni'" class="p-2 rounded-lg border border-emerald-200 bg-emerald-50/50 hover:bg-emerald-100 text-left transition-all">
                    <span class="font-black text-emerald-900 block">HR Lead</span>
                    <span class="text-[10px] text-emerald-700">supuni / supuni@loopshr.com</span>
                </button>
                <button type="button" @click="loginVal = 'arosh'" class="p-2 rounded-lg border border-amber-200 bg-amber-50/50 hover:bg-amber-100 text-left transition-all">
                    <span class="font-black text-amber-900 block">Manager</span>
                    <span class="text-[10px] text-amber-700">arosh / arosh@loopshr.com</span>
                </button>
                <button type="button" @click="loginVal = 'shimal'" class="p-2 rounded-lg border border-purple-200 bg-purple-50/50 hover:bg-purple-100 text-left transition-all">
                    <span class="font-black text-purple-900 block">Employee</span>
                    <span class="text-[10px] text-purple-700">shimal / shimal@loopshr.com</span>
                </button>
            </div>
        </div>
    </form>
</x-guest-layout>
