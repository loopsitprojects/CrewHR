<div class="space-y-6">
    <form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="active_module" value="system">

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
                <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-slate-200 dark:border-slate-700">Diagnostics</span>
            </div>

            <div class="space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <input type="checkbox" name="developer_mode" value="1" {{ ($settingsRaw['developer_mode'] ?? '0') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                    <div>
                        <span class="text-slate-900 dark:text-white font-extrabold block">Developer Mode (Dev Banners & Quick Role Switcher)</span>
                        <span class="text-[11px] font-normal text-slate-400">Displays fast role simulation tools in header and testing debugging panels</span>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                    <input type="checkbox" name="debug_logging_enabled" value="1" {{ ($settingsRaw['debug_logging_enabled'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-slate-700 focus:ring-slate-500">
                    <div>
                        <span class="text-slate-900 dark:text-white font-extrabold block">Verbose Audit & Policy Evaluation Logs</span>
                        <span class="text-[11px] font-normal text-slate-400">Record detailed pro-rata evaluation decisions and validation traces to log files</span>
                    </div>
                </label>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md transition-all cursor-pointer">
                    <i class="ph ph-floppy-disk text-base"></i>
                    <span>Save System Settings</span>
                </button>
            </div>
        </div>
    </form>

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
