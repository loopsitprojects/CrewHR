<form action="{{ route('settings.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ previewDark: null, previewLight: null, previewIcon: null, darkName: '', lightName: '', iconName: '' }">
    @csrf
    <input type="hidden" name="active_module" value="general">

    <!-- 1. Application Branding & Logo Customization -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2.5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center border border-purple-100 dark:border-purple-900/60 shrink-0">
                    <i class="ph ph-paint-brush-broad text-base"></i>
                </div>
                <div>
                    <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight">Application Branding & Logo Settings</h2>
                    <p class="text-[10.5px] font-medium text-slate-400 leading-tight">Customize corporate logos, light/dark theme variants, and browser tab icon</p>
                </div>
            </div>
            <span class="hidden sm:inline-flex bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-purple-200/60 dark:border-purple-900/60">Branding Suite</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3.5">
            <!-- 1. Primary App Logo (Sidebar & Dark Themes) -->
            <div class="p-3 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 space-y-2.5 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-1.5">
                    <div class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                        <i class="ph ph-sidebar text-sm text-blue-600 dark:text-blue-400 shrink-0"></i>
                        <span class="truncate">Primary Logo (Sidebar)</span>
                    </div>
                    @if(\App\Models\Setting::hasCustomLogo('dark'))
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0">Custom</span>
                    @else
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-slate-200/70 dark:bg-slate-800 text-slate-500 dark:text-slate-400 shrink-0">Default</span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-28 sm:w-32 h-14 bg-[#0b132b] border border-slate-800 rounded-lg p-2 flex items-center justify-center shrink-0 relative group shadow-inner">
                        <img :src="previewDark ? previewDark : '{{ app_logo('dark') }}'" 
                             class="max-h-7 max-w-[96px] object-contain shrink-0 transition-transform duration-200 group-hover:scale-105" 
                             alt="Primary Logo">
                    </div>

                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <label class="flex-1 min-w-0 cursor-pointer inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-[#1e2d4d] hover:bg-slate-100 dark:hover:bg-[#25365c] text-slate-700 dark:text-slate-200 border border-slate-200/90 dark:border-slate-700 shadow-2xs transition-all">
                                <i class="ph ph-upload-simple text-sm text-blue-600 dark:text-blue-400 shrink-0"></i>
                                <span class="truncate" x-text="darkName ? darkName : 'Upload Logo'">Upload Logo</span>
                                <input type="file" name="app_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="if($event.target.files[0]) { darkName = $event.target.files[0].name; previewDark = URL.createObjectURL($event.target.files[0]); }">
                            </label>
                            @if(\App\Models\Setting::hasCustomLogo('dark'))
                                <button type="submit" form="reset-dark-logo" onclick="return confirm('Reset the primary sidebar logo back to system default?')" class="p-1.5 rounded-lg text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 transition-colors shrink-0" title="Reset to default">
                                    <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                                </button>
                            @endif
                        </div>
                        <div class="text-[9.5px] text-slate-400 font-medium truncate leading-tight">
                            PNG, SVG &bull; ~200&times;50px
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Light Theme Logo (Sign-in & PDF Exports) -->
            <div class="p-3 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 space-y-2.5 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-1.5">
                    <div class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                        <i class="ph ph-file-pdf text-sm text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                        <span class="truncate">Light & PDF Logo</span>
                    </div>
                    @if(\App\Models\Setting::hasCustomLogo('light'))
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0">Custom</span>
                    @else
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-slate-200/70 dark:bg-slate-800 text-slate-500 dark:text-slate-400 shrink-0">Default</span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-28 sm:w-32 h-14 bg-white border border-slate-200 rounded-lg p-2 flex items-center justify-center shrink-0 relative group shadow-inner">
                        <img :src="previewLight ? previewLight : '{{ app_logo('light') }}'" 
                             class="max-h-7 max-w-[96px] object-contain shrink-0 transition-transform duration-200 group-hover:scale-105" 
                             alt="Light Logo">
                    </div>

                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <label class="flex-1 min-w-0 cursor-pointer inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-[#1e2d4d] hover:bg-slate-100 dark:hover:bg-[#25365c] text-slate-700 dark:text-slate-200 border border-slate-200/90 dark:border-slate-700 shadow-2xs transition-all">
                                <i class="ph ph-upload-simple text-sm text-emerald-600 dark:text-emerald-400 shrink-0"></i>
                                <span class="truncate" x-text="lightName ? lightName : 'Upload Light'">Upload Light</span>
                                <input type="file" name="app_logo_light" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="if($event.target.files[0]) { lightName = $event.target.files[0].name; previewLight = URL.createObjectURL($event.target.files[0]); }">
                            </label>
                            @if(\App\Models\Setting::hasCustomLogo('light'))
                                <button type="submit" form="reset-light-logo" onclick="return confirm('Reset the light theme logo back to system default?')" class="p-1.5 rounded-lg text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 transition-colors shrink-0" title="Reset to default">
                                    <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                                </button>
                            @endif
                        </div>
                        <div class="text-[9.5px] text-slate-400 font-medium truncate leading-tight">
                            PNG, SVG &bull; Dark on transparent
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. App Icon & Browser Favicon (Collapsed Navigation & Tabs) -->
            <div class="p-3 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 space-y-2.5 flex flex-col justify-between">
                <div class="flex items-center justify-between gap-1.5">
                    <div class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-1.5 truncate">
                        <i class="ph ph-browsers text-sm text-cyan-600 dark:text-cyan-400 shrink-0"></i>
                        <span class="truncate">App Icon & Favicon</span>
                    </div>
                    @if(\App\Models\Setting::hasCustomIcon())
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0">Custom</span>
                    @else
                        <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full bg-slate-200/70 dark:bg-slate-800 text-slate-500 dark:text-slate-400 shrink-0">Default</span>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 bg-[#0b132b] border border-slate-800 rounded-lg p-2 flex items-center justify-center shrink-0 relative group shadow-inner">
                        <img :src="previewIcon ? previewIcon : '{{ app_icon() }}'" 
                             class="w-7 h-7 rounded-lg object-contain shrink-0 transition-transform duration-200 group-hover:scale-110 shadow-sm" 
                             alt="Icon Preview">
                    </div>

                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <label class="flex-1 min-w-0 cursor-pointer inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white dark:bg-[#1e2d4d] hover:bg-slate-100 dark:hover:bg-[#25365c] text-slate-700 dark:text-slate-200 border border-slate-200/90 dark:border-slate-700 shadow-2xs transition-all">
                                <i class="ph ph-upload-simple text-sm text-cyan-600 dark:text-cyan-400 shrink-0"></i>
                                <span class="truncate" x-text="iconName ? iconName : 'Upload Icon'">Upload Icon</span>
                                <input type="file" name="app_icon" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon" class="hidden" @change="if($event.target.files[0]) { iconName = $event.target.files[0].name; previewIcon = URL.createObjectURL($event.target.files[0]); }">
                            </label>
                            @if(\App\Models\Setting::hasCustomIcon())
                                <button type="submit" form="reset-icon" onclick="return confirm('Reset the app icon/favicon back to system default?')" class="p-1.5 rounded-lg text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 transition-colors shrink-0" title="Reset to default">
                                    <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                                </button>
                            @endif
                        </div>
                        <div class="text-[9.5px] text-slate-400 font-medium truncate leading-tight">
                            PNG, ICO, SVG &bull; 1:1 (64&times;64px)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Organization Information -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900/60">
                    <i class="ph ph-buildings text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Organization Profile & Brand Identity</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Configure your company name, location, and fiscal standards</p>
                </div>
            </div>
            <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-blue-200/60 dark:border-blue-900/60">Core Configuration</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold">
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Company / Organization Name *</label>
                <input type="text" name="company_name" value="{{ $settingsRaw['company_name'] ?? 'Loops HR Portal' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20" required>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Official Timezone</label>
                <select name="timezone" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                    <option value="Asia/Colombo (GMT+5:30)" {{ ($settingsRaw['timezone'] ?? 'Asia/Colombo (GMT+5:30)') == 'Asia/Colombo (GMT+5:30)' ? 'selected' : '' }}>Asia/Colombo (GMT+5:30 - Sri Lanka Standard Time)</option>
                    <option value="UTC (GMT+0:00)" {{ ($settingsRaw['timezone'] ?? '') == 'UTC (GMT+0:00)' ? 'selected' : '' }}>UTC (GMT+0:00)</option>
                    <option value="Asia/Dubai (GMT+4:00)" {{ ($settingsRaw['timezone'] ?? '') == 'Asia/Dubai (GMT+4:00)' ? 'selected' : '' }}>Asia/Dubai (GMT+4:00 - UAE Standard Time)</option>
                    <option value="Asia/Singapore (GMT+8:00)" {{ ($settingsRaw['timezone'] ?? '') == 'Asia/Singapore (GMT+8:00)' ? 'selected' : '' }}>Asia/Singapore (GMT+8:00 - SGT)</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Base Currency (Statutory & Payroll)</label>
                <input type="text" name="base_currency" value="{{ $settingsRaw['base_currency'] ?? 'LKR (Rs.)' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Fiscal Year Start Month</label>
                <select name="fiscal_year_start" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                    <option value="January" {{ ($settingsRaw['fiscal_year_start'] ?? 'January') == 'January' ? 'selected' : '' }}>January</option>
                    <option value="April" {{ ($settingsRaw['fiscal_year_start'] ?? '') == 'April' ? 'selected' : '' }}>April (Sri Lanka Financial Year standard)</option>
                    <option value="July" {{ ($settingsRaw['fiscal_year_start'] ?? '') == 'July' ? 'selected' : '' }}>July</option>
                    <option value="October" {{ ($settingsRaw['fiscal_year_start'] ?? '') == 'October' ? 'selected' : '' }}>October</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Headquarters / Primary Address</label>
                <input type="text" name="company_address" value="{{ $settingsRaw['company_address'] ?? 'No. 45, Alfred House Gardens, Colombo 03, Sri Lanka' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Official HR Contact Email</label>
                <input type="email" name="contact_email" value="{{ $settingsRaw['contact_email'] ?? 'hr@loops.lk' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>
        </div>
    </div>

    <!-- Save Actions for Organization & Branding Profile -->
    <div class="flex justify-end pt-1">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save Company & Branding Settings</span>
        </button>
    </div>
</form>

<!-- Dedicated Hidden Forms for Resetting Individual Logos -->
<form id="reset-dark-logo" action="{{ route('settings.logo.reset') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="type" value="dark">
</form>

<form id="reset-light-logo" action="{{ route('settings.logo.reset') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="type" value="light">
</form>

<form id="reset-icon" action="{{ route('settings.logo.reset') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="type" value="icon">
</form>

<!-- 3. Organization Departments & Structure -->
<div id="organization-departments" class="pt-2">
    @include('pages.settings.partials.departments_content')
</div>
