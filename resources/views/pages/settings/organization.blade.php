<div class="space-y-6" x-data="{
    currentTab: '{{ request()->query('tab') === 'departments' ? 'general' : request()->query('tab', 'general') }}',
    setTab(tab) {
        this.currentTab = tab;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    }
}" x-init="
    if ('{{ request()->query('tab') }}' === 'departments') {
        $nextTick(() => {
            const el = document.getElementById('organization-departments');
            if (el) el.scrollIntoView({ behavior: 'smooth' });
        });
    }
">
    <!-- Organization & System Sub-Navigation Tabs Bar -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-1.5 shadow-sm">
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
            <!-- Sub-tab 1: Company Profile & Departments -->
            <button type="button" 
                    @click="setTab('general')"
                    :class="currentTab === 'general' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all cursor-pointer shrink-0">
                <i class="ph ph-buildings text-base"></i>
                <span>Company Profile & Departments ({{ count($departments) }})</span>
            </button>

            <!-- Sub-tab 2: System Engine & Diagnostics -->
            <button type="button" 
                    @click="setTab('system')"
                    :class="currentTab === 'system' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60 font-semibold'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all cursor-pointer shrink-0">
                <i class="ph ph-cpu text-base"></i>
                <span>System & Diagnostics</span>
            </button>
        </div>
    </div>

    <!-- Sub-tab View 1: General Organization & Departments -->
    <div x-show="currentTab === 'general'" x-cloak class="space-y-6">
        @include('pages.settings.general')
    </div>

    <!-- Sub-tab View 2: System & Diagnostics -->
    <div x-show="currentTab === 'system'" x-cloak class="space-y-6">
        @include('pages.settings.system')
    </div>
</div>
