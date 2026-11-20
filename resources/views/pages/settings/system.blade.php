<div class="space-y-6"
     x-data="{
         saving: false,
         toastMsg: '',
         toastType: 'success',
         toggleSetting(key, enabled) {
             this.saving = true;
             fetch('{{ route('settings.module.toggle') }}', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Accept': 'application/json'
                 },
                 body: JSON.stringify({ module: key, enabled: enabled })
             })
             .then(r => r.json())
             .then(data => {
                 this.saving = false;
                 if (data.success) {
                     this.toastType = 'success';
                     this.toastMsg = data.message || 'Setting updated successfully.';
                     // Instantly update sidebar link visibility without requiring page refresh
                     const navLink = document.querySelector(`[data-settings-nav='${key}']`);
                     if (navLink) {
                         navLink.style.display = enabled ? '' : 'none';
                     }
                 } else {
                     this.toastType = 'error';
                     this.toastMsg = data.message || 'Failed to update setting.';
                 }
                 setTimeout(() => { this.toastMsg = ''; }, 3000);
             })
             .catch(err => {
                 this.saving = false;
                 this.toastType = 'error';
                 this.toastMsg = 'Network error while updating setting.';
                 setTimeout(() => { this.toastMsg = ''; }, 3000);
             });
         }
     }">

    <!-- Live Status Toast Banner -->
    <div x-show="toastMsg" x-cloak x-transition class="p-3.5 rounded-xl text-xs font-bold flex items-center justify-between shadow-sm"
         :class="toastType === 'success' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'">
        <div class="flex items-center gap-2">
            <i class="ph text-base" :class="toastType === 'success' ? 'ph-check-circle text-emerald-600 dark:text-emerald-400' : 'ph-warning-circle text-rose-600 dark:text-rose-400'"></i>
            <span x-text="toastMsg"></span>
        </div>
        <button type="button" @click="toastMsg = ''" class="hover:opacity-75 cursor-pointer"><i class="ph ph-x text-sm"></i></button>
    </div>

    <!-- 1. System & Developer Controls -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center border border-slate-200 dark:border-slate-700">
                    <i class="ph ph-cpu text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Core Engine & Developer Environment</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Manage low-level application state, diagnostics, and debugging switches</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="saving" x-cloak class="text-[11px] font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                    <i class="ph ph-circle-notch animate-spin text-sm"></i> Saving...
                </span>
                <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">Diagnostics</span>
            </div>
        </div>

        <div class="space-y-3">
            <!-- Developer Mode Toggle -->
            <div class="p-3.5 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 flex items-center justify-between gap-3">
                <div>
                    <span class="text-xs font-black text-slate-900 dark:text-white block">Developer Mode (Dev Banners & Quick Role Switcher)</span>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Displays fast role simulation tools in header and testing debugging panels</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0"
                       x-data="{ isChecked: {{ ($settingsRaw['developer_mode'] ?? '0') == '1' ? 'true' : 'false' }} }">
                    <input type="checkbox" 
                           value="1" 
                           {{ ($settingsRaw['developer_mode'] ?? '0') == '1' ? 'checked' : '' }}
                           @change="isChecked = $el.checked; toggleSetting('developer_mode', $el.checked)"
                           class="sr-only">
                    <div class="w-11 h-6 rounded-full transition-colors duration-200 relative p-0.5 shadow-inner cursor-pointer"
                         :class="isChecked ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                        <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 shadow-xs"
                             :class="isChecked ? 'translate-x-5' : 'translate-x-0'"></div>
                    </div>
                </label>
            </div>

            <!-- Verbose Debug Logging Toggle -->
            <div class="p-3.5 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 flex items-center justify-between gap-3">
                <div>
                    <span class="text-xs font-black text-slate-900 dark:text-white block">Verbose Audit & Policy Evaluation Logs</span>
                    <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Record detailed pro-rata evaluation decisions and validation traces to log files</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0"
                       x-data="{ isChecked: {{ ($settingsRaw['debug_logging_enabled'] ?? '1') == '1' ? 'true' : 'false' }} }">
                    <input type="checkbox" 
                           value="1" 
                           {{ ($settingsRaw['debug_logging_enabled'] ?? '1') == '1' ? 'checked' : '' }}
                           @change="isChecked = $el.checked; toggleSetting('debug_logging_enabled', $el.checked)"
                           class="sr-only">
                    <div class="w-11 h-6 rounded-full transition-colors duration-200 relative p-0.5 shadow-inner cursor-pointer"
                         :class="isChecked ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                        <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 shadow-xs"
                             :class="isChecked ? 'translate-x-5' : 'translate-x-0'"></div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <!-- 2. Settings Modules & Navigation Switcher -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-900/60">
                    <i class="ph ph-toggle-left text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Settings Modules & Navigation Visibility</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Switch on or off individual settings modules. Switched-off modules are hidden from sidebar navigation and direct administrative menus.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span x-show="saving" x-cloak class="text-[11px] font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1">
                    <i class="ph ph-circle-notch animate-spin text-sm"></i> Saving...
                </span>
                <span class="bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-indigo-200/60 dark:border-indigo-900/60">Module Switcher</span>
            </div>
        </div>

        <!-- Grid of 8 Modules -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            @php
                $settingsModules = [
                    [
                        'key' => 'organization',
                        'name' => 'Organization & System',
                        'desc' => 'Company profile, branding logos, departments structure, and core diagnostics',
                        'icon' => 'ph-buildings',
                        'color' => 'blue',
                        'is_core' => true,
                        'enabled' => true,
                    ],
                    [
                        'key' => 'leave',
                        'name' => 'Leave Management',
                        'desc' => 'Annual, casual, medical, short leave quotas, calendar holidays & leave rules',
                        'icon' => 'ph-calendar-check',
                        'color' => 'emerald',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('leave'),
                    ],
                    [
                        'key' => 'roles',
                        'name' => 'Roles & Access Control',
                        'desc' => 'System access roles, permission matrices, and security policy overrides',
                        'icon' => 'ph-shield-check',
                        'color' => 'purple',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('roles'),
                    ],
                    [
                        'key' => 'attendance',
                        'name' => 'Attendance & Shifts',
                        'desc' => 'Shift schedules, grace periods, OT rate multipliers & standard work hours',
                        'icon' => 'ph-clock-countdown',
                        'color' => 'cyan',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('attendance'),
                    ],
                    [
                        'key' => 'payroll',
                        'name' => 'Payroll & Tax',
                        'desc' => 'Sri Lanka statutory EPF/ETF contributions, APIT tax brackets & cutoff days',
                        'icon' => 'ph-money',
                        'color' => 'amber',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('payroll'),
                    ],
                    [
                        'key' => 'recruitment',
                        'name' => 'Recruitment & ATS',
                        'desc' => 'Candidate hiring pipelines, portal job posts & interview feedback deadlines',
                        'icon' => 'ph-briefcase',
                        'color' => 'pink',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('recruitment'),
                    ],
                    [
                        'key' => 'performance',
                        'name' => 'Performance & Appraisal',
                        'desc' => 'Evaluation appraisal cycles, KPI templates, rating scales & increment policies',
                        'icon' => 'ph-award',
                        'color' => 'orange',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('performance'),
                    ],
                    [
                        'key' => 'profile',
                        'name' => 'My Account & Security',
                        'desc' => 'Personal user credentials, staff avatar, password reset & profile information',
                        'icon' => 'ph-user-gear',
                        'color' => 'rose',
                        'is_core' => false,
                        'enabled' => is_settings_module_enabled('profile'),
                    ],
                ];
            @endphp

            @foreach($settingsModules as $mod)
                <div class="p-4 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 flex items-start justify-between gap-3 transition-all hover:border-slate-300 dark:hover:border-slate-700">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-{{ $mod['color'] }}-50 dark:bg-{{ $mod['color'] }}-950/60 text-{{ $mod['color'] }}-600 dark:text-{{ $mod['color'] }}-400 flex items-center justify-center shrink-0 border border-{{ $mod['color'] }}-100 dark:border-{{ $mod['color'] }}-900/60 shadow-2xs mt-0.5">
                            <i class="ph {{ $mod['icon'] }} text-lg"></i>
                        </div>
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="text-xs font-black text-slate-900 dark:text-white truncate">{{ $mod['name'] }}</h4>
                                @if($mod['is_core'])
                                    <span class="text-[9px] font-black px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shrink-0">Core Engine</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-relaxed">{{ $mod['desc'] }}</p>
                        </div>
                    </div>

                    <div class="shrink-0 pt-0.5">
                        @if($mod['is_core'])
                            <div class="flex items-center gap-1.5 opacity-60 cursor-not-allowed" title="Core module cannot be switched off">
                                <div class="w-11 h-6 bg-blue-600 rounded-full relative p-0.5">
                                    <div class="w-5 h-5 bg-white rounded-full translate-x-5 shadow-xs"></div>
                                </div>
                            </div>
                        @else
                            <label class="relative inline-flex items-center cursor-pointer"
                                   x-data="{ isChecked: {{ $mod['enabled'] ? 'true' : 'false' }} }">
                                <input type="checkbox" 
                                       name="settings_module_{{ $mod['key'] }}" 
                                       value="1" 
                                       {{ $mod['enabled'] ? 'checked' : '' }}
                                       @change="isChecked = $el.checked; toggleSetting('{{ $mod['key'] }}', $el.checked)"
                                       class="sr-only">
                                <div class="w-11 h-6 rounded-full transition-colors duration-200 relative p-0.5 shadow-inner cursor-pointer"
                                     :class="isChecked ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 shadow-xs"
                                         :class="isChecked ? 'translate-x-5' : 'translate-x-0'"></div>
                                </div>
                            </label>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 2. Cache Flush & Maintenance Tools -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
            <i class="ph ph-broom text-rose-600 dark:text-rose-400 text-lg"></i>
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Maintenance Operations & Cache Engine</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-rose-50/50 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/40 space-y-2">
                <h3 class="text-xs font-black text-rose-900 dark:text-rose-300 flex items-center gap-1.5">
                    <i class="ph ph-arrows-clockwise text-rose-600"></i> Clear Application & Template Cache
                </h3>
                <p class="text-[11px] text-rose-700 dark:text-rose-400 font-semibold">
                    Flushes Laravel compiled Blade views, configuration caches, and application state memory.
                </p>
                <form action="{{ route('settings.cache.clear') }}" method="POST" onsubmit="return confirm('Flush application and template cache now?')">
                    @csrf
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-4 py-2 rounded-lg flex items-center gap-1.5 shadow-sm transition-all cursor-pointer">
                        <i class="ph ph-trash text-sm"></i>
                        <span>Flush System Cache</span>
                    </button>
                </form>
            </div>

            <!-- Diagnostics Summary -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 space-y-2 text-xs font-semibold">
                <h3 class="font-black text-slate-900 dark:text-white">System Diagnostics</h3>
                <div class="space-y-1 text-[11px] text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between"><span>PHP Version:</span> <span class="font-mono font-bold text-slate-900 dark:text-white">{{ phpversion() }}</span></div>
                    <div class="flex justify-between"><span>Laravel Framework:</span> <span class="font-mono font-bold text-slate-900 dark:text-white">{{ app()->version() }}</span></div>
                    <div class="flex justify-between"><span>Database:</span> <span class="font-mono font-bold text-slate-900 dark:text-white">{{ config('database.default') }}</span></div>
                    <div class="flex justify-between"><span>App Environment:</span> <span class="font-mono font-bold text-slate-900 dark:text-white">{{ app()->environment() }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
