<form action="{{ route('settings.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ previewDark: null, previewLight: null, previewIcon: null, darkName: '', lightName: '', iconName: '' }">
    @csrf
    <input type="hidden" name="active_module" value="general">

    <!-- 1. Application Branding & Logo Customization -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center border border-purple-100 dark:border-purple-900/60">
                    <i class="ph ph-paint-brush-broad text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Application Branding & Logo Settings</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Customize the corporate logos, theme variations, and browser favicon across the system</p>
                </div>
            </div>
            <span class="bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-purple-200/60 dark:border-purple-900/60">Branding Suite</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 1. Primary App Logo (Sidebar & Dark Themes) -->
            <div class="flex flex-col justify-between p-4 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                            <i class="ph ph-sidebar text-base text-blue-600 dark:text-blue-400"></i>
                            <span>Primary App Logo (Sidebar)</span>
                        </div>
                        @if(\App\Models\Setting::hasCustomLogo('dark'))
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Custom Active</span>
                        @else
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Default Logo</span>
                        @endif
                    </div>
                    <p class="text-[10.5px] text-slate-500 dark:text-slate-400 leading-tight">Displayed in the main dark sidebar navigation and dark theme views.</p>
                </div>

                <!-- Preview Area: Mimicking the dark sidebar -->
                <div class="bg-[#0b132b] border border-slate-800/80 rounded-xl p-4 flex flex-col items-center justify-center min-h-[90px] relative overflow-hidden group shadow-inner">
                    <span class="absolute top-1.5 left-2 text-[9px] uppercase font-bold tracking-wider text-slate-500 font-mono">Sidebar Preview</span>
                    <img :src="previewDark ? previewDark : '{{ app_logo('dark') }}'" 
                         class="h-9 w-auto max-w-[190px] object-contain shrink-0 transition-transform duration-200 group-hover:scale-105" 
                         alt="Primary Logo Preview">
                </div>

                <!-- Controls & Specs -->
                <div class="space-y-3 pt-1">
                    <div class="flex items-center gap-2">
                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-white dark:bg-[#1e2d4d] hover:bg-slate-50 dark:hover:bg-[#25365c] text-slate-700 dark:text-slate-200 border border-slate-200/90 dark:border-slate-700 shadow-2xs transition-all">
                            <i class="ph ph-upload-simple text-sm text-blue-600 dark:text-blue-400"></i>
                            <span class="truncate" x-text="darkName ? darkName : 'Upload New Logo'">Upload New Logo</span>
                            <input type="file" name="app_logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="if($event.target.files[0]) { darkName = $event.target.files[0].name; previewDark = URL.createObjectURL($event.target.files[0]); }">
                        </label>
                        @if(\App\Models\Setting::hasCustomLogo('dark'))
                            <button type="submit" form="reset-dark-logo" onclick="return confirm('Reset the primary sidebar logo back to system default?')" class="px-2.5 py-2 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 transition-colors" title="Reset to default Loops logo">
                                <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                            </button>
                        @endif
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium leading-relaxed">
                        PNG, SVG, JPG, WebP &bull; Max 3MB &bull; Recommended: Transparent background, ~200&times;50px.
                    </div>
                </div>
            </div>

            <!-- 2. Light Theme Logo (Sign-in & PDF Exports) -->
            <div class="flex flex-col justify-between p-4 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                            <i class="ph ph-file-pdf text-base text-emerald-600 dark:text-emerald-400"></i>
                            <span>Light Theme & PDF Logo</span>
                        </div>
                        @if(\App\Models\Setting::hasCustomLogo('light'))
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Custom Active</span>
                        @else
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Default Logo</span>
                        @endif
                    </div>
                    <p class="text-[10.5px] text-slate-500 dark:text-slate-400 leading-tight">Used on light login screen and exported PDF executive reports.</p>
                </div>

                <!-- Preview Area: Mimicking light background -->
                <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center min-h-[90px] relative overflow-hidden group shadow-inner">
                    <span class="absolute top-1.5 left-2 text-[9px] uppercase font-bold tracking-wider text-slate-400 font-mono">Light/PDF Preview</span>
                    <img :src="previewLight ? previewLight : '{{ app_logo('light') }}'" 
                         class="h-9 w-auto max-w-[190px] object-contain shrink-0 transition-transform duration-200 group-hover:scale-105" 
                         alt="Light Logo Preview">
                </div>

                <!-- Controls & Specs -->
                <div class="space-y-3 pt-1">
                    <div class="flex items-center gap-2">
                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-white dark:bg-[#1e2d4d] hover:bg-slate-50 dark:hover:bg-[#25365c] text-slate-700 dark:text-slate-200 border border-slate-200/90 dark:border-slate-700 shadow-2xs transition-all">
                            <i class="ph ph-upload-simple text-sm text-emerald-600 dark:text-emerald-400"></i>
                            <span class="truncate" x-text="lightName ? lightName : 'Upload Light Logo'">Upload Light Logo</span>
                            <input type="file" name="app_logo_light" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="hidden" @change="if($event.target.files[0]) { lightName = $event.target.files[0].name; previewLight = URL.createObjectURL($event.target.files[0]); }">
                        </label>
                        @if(\App\Models\Setting::hasCustomLogo('light'))
                            <button type="submit" form="reset-light-logo" onclick="return confirm('Reset the light theme logo back to system default?')" class="px-2.5 py-2 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 transition-colors" title="Reset to default light logo">
                                <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                            </button>
                        @endif
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium leading-relaxed">
                        PNG, SVG, JPG, WebP &bull; Max 3MB &bull; Recommended: Dark text on transparent background.
                    </div>
                </div>
            </div>

            <!-- 3. App Icon & Browser Favicon (Collapsed Navigation & Tabs) -->
            <div class="flex flex-col justify-between p-4 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#101726]/60 space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="font-extrabold text-xs text-slate-900 dark:text-white flex items-center gap-1.5">
                            <i class="ph ph-browsers text-base text-cyan-600 dark:text-cyan-400"></i>
                            <span>App Icon & Favicon</span>
                        </div>
                        @if(\App\Models\Setting::hasCustomIcon())
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">Custom Active</span>
                        @else
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Default Icon</span>
                        @endif
                    </div>
                    <p class="text-[10.5px] text-slate-500 dark:text-slate-400 leading-tight">Used when sidebar is collapsed and as browser tab favicon.</p>
                </div>

                <!-- Preview Area: Mimicking collapsed sidebar / tab -->
                <div class="bg-[#0b132b] border border-slate-800/80 rounded-xl p-4 flex flex-col items-center justify-center min-h-[90px] relative overflow-hidden group shadow-inner">
                    <span class="absolute top-1.5 left-2 text-[9px] uppercase font-bold tracking-wider text-slate-500 font-mono">Icon Preview</span>
                    <img :src="previewIcon ? previewIcon : '{{ app_icon() }}'" 
                         class="w-9 h-9 rounded-xl object-contain shrink-0 transition-transform duration-200 group-hover:scale-110 shadow-sm" 
                         alt="Icon Preview">
                </div>

                <!-- Controls & Specs -->
                <div class="space-y-3 pt-1">
                    <div class="flex items-center gap-2">
                        <label class="flex-1 cursor-pointer inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-white dark:bg-[#1e2d4d] hover:bg-slate-50 dark:hover:bg-[#25365c] text-slate-700 dark:text-slate-200 border border-slate-200/90 dark:border-slate-700 shadow-2xs transition-all">
                            <i class="ph ph-upload-simple text-sm text-cyan-600 dark:text-cyan-400"></i>
                            <span class="truncate" x-text="iconName ? iconName : 'Upload Icon'">Upload Icon</span>
                            <input type="file" name="app_icon" accept="image/png,image/jpeg,image/svg+xml,image/webp,image/x-icon" class="hidden" @change="if($event.target.files[0]) { iconName = $event.target.files[0].name; previewIcon = URL.createObjectURL($event.target.files[0]); }">
                        </label>
                        @if(\App\Models\Setting::hasCustomIcon())
                            <button type="submit" form="reset-icon" onclick="return confirm('Reset the app icon/favicon back to system default?')" class="px-2.5 py-2 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-900/60 transition-colors" title="Reset to default icon">
                                <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                            </button>
                        @endif
                    </div>
                    <div class="text-[10px] text-slate-400 font-medium leading-relaxed">
                        PNG, ICO, SVG, WebP &bull; Max 1MB &bull; Recommended: Square 1:1 ratio (64&times;64px).
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

    <!-- 3. Active Custom Company Holidays -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-calendar-plus text-blue-600 dark:text-blue-400 text-lg"></i>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Active Custom Company Holidays ({{ count($customHolidays) }})</h2>
            </div>
            <a href="{{ route('leave.index') }}" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline flex items-center gap-1">
                <span>Manage on Leave Calendar</span>
                <i class="ph ph-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            @forelse($customHolidays as $ch)
                <div class="border border-slate-200 dark:border-slate-800 rounded-xl p-3 bg-slate-50/50 dark:bg-[#1e2d4d]/50 flex items-center justify-between">
                    <div>
                        <div class="font-extrabold text-slate-900 dark:text-white">{{ $ch->title }}</div>
                        <div class="text-[11px] text-slate-400 font-semibold">{{ $ch->date }} • {{ $ch->category ?? 'Company Holiday' }}</div>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        {{ $ch->category ?? 'Paid' }}
                    </span>
                </div>
            @empty
                <div class="col-span-3 text-center py-6 text-slate-400 text-xs italic bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-800">
                    No custom company holidays registered yet. Add them from the Leave Calendar dashboard.
                </div>
            @endforelse
        </div>
    </div>

    <!-- 4. Workforce & Employee Profile Configuration -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <i class="ph ph-user-gear text-blue-600 dark:text-blue-400 text-lg"></i>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Workforce Profile Feature Visibility</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Configure which modular architecture sections appear on Employee profiles</p>
                </div>
            </div>
            @if(is_compensation_enabled())
                <span class="bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-emerald-200/60 dark:border-emerald-900/60">
                    Compensation Active
                </span>
            @else
                <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">
                    Compensation Hidden
                </span>
            @endif
        </div>

        <div class="p-4 rounded-xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/50 dark:bg-[#1e2d4d]/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-200/70 dark:border-amber-900/60 mt-0.5">
                    <i class="ph ph-coins text-lg"></i>
                </div>
                <div>
                    <label for="show_employee_compensation" class="text-xs font-bold text-slate-900 dark:text-white block cursor-pointer">
                        Employee Compensation & Statutory Payroll Section
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-relaxed mt-0.5">
                        Displays the Base Salary, Fixed Allowances, EPF/ETF statutory contributions, and Net Take-Home summary tab in employee create/edit forms. Disable this if your organization does not initially require payroll architecture.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <input type="hidden" name="submitted_show_employee_compensation" value="1">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="show_employee_compensation" name="show_employee_compensation" value="1" {{ is_compensation_enabled() ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save General & Branding Settings</span>
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
