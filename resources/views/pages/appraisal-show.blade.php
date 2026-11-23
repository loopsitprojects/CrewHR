@extends('layouts.app', ['title' => 'Loops HR - Performance Appraisal Evaluation', 'breadcrumb' => 'Appraisal Evaluation'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'evaluation',
    showChangeRequestModal: false,
    showUnlockModal: false,
    showLetterModal: false,
    selectedLetterToSign: null,
    showSignModal: false,
    signRole: 'signatory', // 'signatory' or 'employee'

    // Live Scoring Calculations
    scores: @js($appraisal->criteriaScores->map(fn($cs) => [
        'id' => $cs->id,
        'weightage' => (float)$cs->weightage,
        'self_rating' => $cs->self_rating ?: 0,
        'manager_rating' => $cs->final_rating ?: ($cs->manager_rating ?: 0)
    ])),

    get calculatedScore() {
        let total = 0;
        this.scores.forEach(item => {
            let r = item.manager_rating > 0 ? item.manager_rating : item.self_rating;
            total += (r * (item.weightage / 100));
        });
        return Math.round(total * 100) / 100;
    },

    get calculatedBand() {
        let s = this.calculatedScore;
        if (s >= 4.50) return { band: 'Exceptional', badge: '⭐ Exceptional', color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-800' };
        if (s >= 4.00) return { band: 'Exceeds Expectations', badge: '🔵 Exceeds Expectations', color: 'text-blue-600 bg-blue-50 dark:bg-blue-950/60 border-blue-300 dark:border-blue-800' };
        if (s >= 3.00) return { band: 'Meets Expectations', badge: '🟡 Meets Expectations', color: 'text-amber-600 bg-amber-50 dark:bg-amber-950/60 border-amber-300 dark:border-amber-800' };
        if (s >= 2.00) return { band: 'Needs Improvement', badge: '🟠 Needs Improvement', color: 'text-orange-600 bg-orange-50 dark:bg-orange-950/60 border-orange-300 dark:border-orange-800' };
        if (s > 0) return { band: 'Unsatisfactory', badge: '🔴 Unsatisfactory', color: 'text-rose-600 bg-rose-50 dark:bg-rose-950/60 border-rose-300 dark:border-rose-800' };
        return { band: 'Pending', badge: 'Pending Evaluation', color: 'text-slate-400 bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700' };
    }
}">

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl flex items-center justify-between text-xs font-bold text-emerald-800 dark:text-emerald-300">
            <div class="flex items-center gap-2">
                <i class="ph ph-check-circle text-lg text-emerald-500"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-emerald-500"><i class="ph ph-x"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl flex items-center justify-between text-xs font-bold text-rose-800 dark:text-rose-300">
            <div class="flex items-center gap-2">
                <i class="ph ph-warning-circle text-lg text-rose-500"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-rose-500"><i class="ph ph-x"></i></button>
        </div>
    @endif

    <!-- Employee & Appraisal Header Banner -->
    <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xl flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
                    {{ strtoupper(substr($appraisal->employee?->user?->name ?? 'E', 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-black text-slate-900 dark:text-white">
                            {{ $appraisal->employee?->user?->name ?? 'Staff Employee' }}
                        </h1>
                        <span class="text-xs font-bold text-slate-400">
                            ({{ $appraisal->employee?->employee_id_number ?? 'EMP-00' }})
                        </span>
                        <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full 
                            {{ $appraisal->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ ucwords(str_replace('_', ' ', $appraisal->status)) }}
                        </span>
                        @if($appraisal->is_locked)
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center gap-1 border border-slate-200 dark:border-slate-700">
                                <i class="ph ph-lock text-xs"></i> Form Locked
                            </span>
                        @else
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-200 flex items-center gap-1">
                                <i class="ph ph-lock-simple-open text-xs"></i> Editable
                            </span>
                        @endif
                    </div>
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-3">
                        <span><strong>Designation:</strong> {{ $appraisal->employee?->designation?->name ?? 'Staff' }}</span>
                        <span>&bull;</span>
                        <span><strong>Department:</strong> {{ $appraisal->employee?->department?->name ?? 'General' }}</span>
                        <span>&bull;</span>
                        <span><strong>Cycle:</strong> {{ $appraisal->cycle?->name }}</span>
                        @if($appraisal->cycle)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border {{ $appraisal->cycle->isMidYear() ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border-amber-300 dark:border-amber-800' : 'bg-blue-100 text-blue-800 dark:bg-blue-950/70 dark:text-blue-300 border-blue-300 dark:border-blue-800' }}">
                                <i class="ph {{ $appraisal->cycle->isMidYear() ? 'ph-calendar-check' : 'ph-trend-up' }}"></i>
                                {{ $appraisal->cycle->isMidYear() ? 'Mid-Year Review (Apr–Sep)' : 'Year-End Review (Oct–Mar)' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Hierarchy & Score Summary Badge -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Score Pill -->
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Weighted Score</span>
                    <div class="text-lg font-black text-slate-900 dark:text-white flex items-baseline justify-end gap-1">
                        <span x-text="calculatedScore.toFixed(2)">{{ number_format($appraisal->total_weighted_score, 2) }}</span>
                        <span class="text-xs text-slate-400">/ 5.00</span>
                    </div>
                    <span :class="calculatedBand.color" class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-black border" x-text="calculatedBand.badge">
                        {{ $appraisal->performance_band ?: 'Pending Evaluation' }}
                    </span>
                </div>

                <!-- Back button -->
                <a href="{{ route('appraisal.index') }}" onclick="handleModuleBack(event, '{{ route('appraisal.index') }}')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 text-xs font-bold flex items-center gap-1 cursor-pointer">
                    <i class="ph ph-arrow-left"></i>
                    <span>Roster</span>
                </a>
            </div>
        </div>

        <!-- Reporting Structure Breadcrumb -->
        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200/70 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 font-bold text-slate-600 dark:text-slate-300">
                <span class="text-slate-400 uppercase text-[10px]">Reporting Hierarchy:</span>
                <span class="px-2 py-0.5 rounded bg-white dark:bg-slate-800 text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700">
                    {{ $appraisal->employee?->user?->name }} (Staff)
                </span>
                @if($appraisal->workflow_type === 'three_tier' && $appraisal->reportingManager)
                    <i class="ph ph-arrow-right text-slate-400"></i>
                    <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                        {{ $appraisal->reportingManager->user->name ?? 'Manager' }} (Reporting Manager)
                    </span>
                @endif
                <i class="ph ph-arrow-right text-slate-400"></i>
                <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-900">
                    {{ $appraisal->hod->user->name ?? 'HOD' }} (Head of Department)
                </span>
            </div>

            <!-- Action buttons: Request Change / HR Unlock -->
            <div class="flex items-center gap-2">
                @if($canRequestChange)
                    <button @click="showChangeRequestModal = true" class="bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 px-3 py-1.5 rounded-xl font-bold transition-all text-xs flex items-center gap-1.5 cursor-pointer">
                        <i class="ph ph-arrow-counter-clockwise"></i>
                        <span>Request Change</span>
                    </button>
                @endif

                @if($canUnlock)
                    <button @click="showUnlockModal = true" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-xl font-bold transition-all text-xs flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i class="ph ph-lock-key-open"></i>
                        <span>Unlock Appraisal (HR)</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-6 border-b border-slate-100 dark:border-slate-800 -mb-6">
            <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-clipboard-text"></i>
                <span>Appraisal Evaluation Form</span>
            </button>

            @if($canSeeIncrement)
                <button @click="activeTab = 'increment'" :class="activeTab === 'increment' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-trend-up"></i>
                    <span>Increment & Promotion</span>
                    @if($appraisal->cycle && !$appraisal->cycle->allowsIncrements())
                        <span class="text-[9px] font-black px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">No Inc (Mid-Year)</span>
                    @elseif($appraisal->increment_percentage > 0 || $appraisal->is_promoted)
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    @endif
                </button>
            @endif

            <button @click="activeTab = 'letters'" :class="activeTab === 'letters' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-stamp"></i>
                <span>Letters & E-Signatures ({{ $appraisal->letters->count() }})</span>
            </button>

            <button @click="activeTab = 'audit'" :class="activeTab === 'audit' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Change Requests & Audit ({{ $appraisal->changeRequests->count() }})</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: EVALUATION FORM -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'evaluation'" class="space-y-6">
        <form id="evaluationForm" method="POST" action="{{ route('appraisal.draft', $appraisal->id) }}" class="space-y-6">
            @csrf

            <!-- Criteria Scoring Table -->
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Performance Criteria & Competency Ratings</h2>
                        <p class="text-xs text-slate-400">Score each criteria on a 1 to 5 scale with required qualitative feedback.</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-xl">
                        Template: {{ $appraisal->template?->title }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                            <tr>
                                <th class="p-4 pl-6 w-1/4">Evaluation Criteria</th>
                                <th class="p-4 text-center w-20">Weightage</th>
                                <th class="p-4 w-1/3">Employee Self-Evaluation</th>
                                <th class="p-4 w-1/3 pr-6">Manager / HOD Final Review</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($appraisal->criteriaScores as $idx => $criterion)
                                <tr class="hover:bg-slate-50/40 dark:hover:bg-slate-800/30 transition-colors">
                                    <!-- Criteria info -->
                                    <td class="p-4 pl-6 align-top">
                                        <div class="font-extrabold text-slate-900 dark:text-white text-xs">
                                            {{ $criterion->criteria_name }}
                                        </div>
                                        @if($criterion->criteria_description)
                                            <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                                                {{ $criterion->criteria_description }}
                                            </p>
                                        @endif
                                    </td>

                                    <!-- Weightage -->
                                    <td class="p-4 align-top text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 font-extrabold text-xs">
                                            {{ number_format($criterion->weightage, 0) }}%
                                        </span>
                                    </td>

                                    <!-- Employee Self-Evaluation -->
                                    <td class="p-4 align-top space-y-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Self-Rating (1-5)</label>
                                            <select name="scores[{{ $criterion->id }}][self_rating]" 
                                                    x-on:change="
                                                        let item = scores.find(s => s.id == {{ $criterion->id }});
                                                        if (item) item.self_rating = parseInt($event.target.value) || 0;
                                                    "
                                                    {{ ($canEditSelf) ? '' : 'disabled' }}
                                                    class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-80 disabled:cursor-not-allowed">
                                                <option value="">Select Self Rating</option>
                                                <option value="5" {{ $criterion->self_rating == 5 ? 'selected' : '' }}>5 - ⭐ Exceptional (Role Model)</option>
                                                <option value="4" {{ $criterion->self_rating == 4 ? 'selected' : '' }}>4 - 🔵 Exceeds Expectations</option>
                                                <option value="3" {{ $criterion->self_rating == 3 ? 'selected' : '' }}>3 - 🟡 Meets Expectations (Reliable)</option>
                                                <option value="2" {{ $criterion->self_rating == 2 ? 'selected' : '' }}>2 - 🟠 Needs Improvement</option>
                                                <option value="1" {{ $criterion->self_rating == 1 ? 'selected' : '' }}>1 - 🔴 Unsatisfactory</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Employee Comments</label>
                                            <textarea name="scores[{{ $criterion->id }}][self_comments]" 
                                                      rows="2" 
                                                      {{ ($canEditSelf) ? '' : 'readonly' }}
                                                      placeholder="Describe specific achievements, projects, or context..." 
                                                      class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-80 read-only:bg-slate-50/50">{{ $criterion->self_comments }}</textarea>
                                        </div>
                                    </td>

                                    <!-- Manager / HOD Final Review -->
                                    <td class="p-4 pr-6 align-top space-y-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Final Rating (1-5)</label>
                                            <select name="scores[{{ $criterion->id }}][manager_rating]" 
                                                    x-on:change="
                                                        let item = scores.find(s => s.id == {{ $criterion->id }});
                                                        if (item) item.manager_rating = parseInt($event.target.value) || 0;
                                                    "
                                                    {{ ($canEditManager || $canEditHod) ? '' : 'disabled' }}
                                                    class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-80 disabled:cursor-not-allowed">
                                                <option value="">Select Manager Rating</option>
                                                <option value="5" {{ ($criterion->final_rating ?? $criterion->manager_rating) == 5 ? 'selected' : '' }}>5 - ⭐ Exceptional (Role Model)</option>
                                                <option value="4" {{ ($criterion->final_rating ?? $criterion->manager_rating) == 4 ? 'selected' : '' }}>4 - 🔵 Exceeds Expectations</option>
                                                <option value="3" {{ ($criterion->final_rating ?? $criterion->manager_rating) == 3 ? 'selected' : '' }}>3 - 🟡 Meets Expectations (Reliable)</option>
                                                <option value="2" {{ ($criterion->final_rating ?? $criterion->manager_rating) == 2 ? 'selected' : '' }}>2 - 🟠 Needs Improvement</option>
                                                <option value="1" {{ ($criterion->final_rating ?? $criterion->manager_rating) == 1 ? 'selected' : '' }}>1 - 🔴 Unsatisfactory</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Manager / HOD Comments</label>
                                            <textarea name="scores[{{ $criterion->id }}][manager_comments]" 
                                                      rows="2" 
                                                      {{ ($canEditManager || $canEditHod) ? '' : 'readonly' }}
                                                      placeholder="Evaluation rationale and feedback..." 
                                                      class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-80 read-only:bg-slate-50/50">{{ $criterion->manager_comments }}</textarea>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Live Score Calculation Footer -->
                <div class="p-6 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-black uppercase text-slate-400">Total Computed Weighted Score</span>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-3xl font-black text-slate-900 dark:text-white" x-text="calculatedScore.toFixed(2)">
                                {{ number_format($appraisal->total_weighted_score, 2) }}
                            </span>
                            <span class="text-sm font-semibold text-slate-400">/ 5.00</span>
                            <span :class="calculatedBand.color" class="px-3 py-1 rounded-full text-xs font-black border" x-text="calculatedBand.badge">
                                {{ $appraisal->performance_band ?: 'Pending Evaluation' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-right text-[11px] text-slate-400 font-semibold max-w-xs">
                        Formula: $\text{Weighted Score} = \sum (\text{Rating} \times \frac{\text{Weightage}}{100})$. Automatically updated.
                    </div>
                </div>
            </div>

            <!-- General Overall Comments & Feedback Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-2">
                    <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                        <span>Employee General Self-Reflection</span>
                        <span class="text-[10px] text-slate-400 font-normal">Self Assessment</span>
                    </label>
                    <textarea name="general_employee_comments" rows="3" {{ $canEditSelf ? '' : 'readonly' }} placeholder="Highlights of achievements, career aspirations, and support needed..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-3 read-only:bg-slate-50/40">{{ $appraisal->general_employee_comments }}</textarea>
                </div>

                <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 shadow-sm space-y-2">
                    <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                        <span>Manager / HOD General Feedback</span>
                        <span class="text-[10px] text-slate-400 font-normal">Leadership Review</span>
                    </label>
                    <textarea name="general_manager_comments" rows="3" {{ ($canEditManager || $canEditHod) ? '' : 'readonly' }} placeholder="Summary of overall strengths, areas for development, and leadership review..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-3 read-only:bg-slate-50/40">{{ $appraisal->general_manager_comments }}</textarea>
                </div>
            </div>

            <!-- Action Buttons Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <div>
                    @if(!$appraisal->is_locked || in_array($role, ['Super Admin', 'HR Lead']))
                        <button type="submit" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-2">
                            <i class="ph ph-floppy-disk text-base text-blue-500"></i>
                            <span>Save Draft Scores</span>
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-2.5">
                    @if($canEditSelf)
                        <button type="button" 
                                onclick="if(confirm('Are you sure you want to submit your self-evaluation? The form will lock and forward to your manager/HOD.')) { document.getElementById('evaluationForm').action = '{{ route('appraisal.submit-self', $appraisal->id) }}'; document.getElementById('evaluationForm').submit(); }"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="ph ph-paper-plane-tilt text-base"></i>
                            <span>Submit Self-Evaluation</span>
                        </button>
                    @endif

                    @if($canEditManager)
                        <button type="button" 
                                onclick="if(confirm('Submit manager evaluation? This will forward the evaluation to HOD for final approval.')) { document.getElementById('evaluationForm').action = '{{ route('appraisal.submit-manager', $appraisal->id) }}'; document.getElementById('evaluationForm').submit(); }"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="ph ph-check-square-offset text-base"></i>
                            <span>Submit Manager Review</span>
                        </button>
                    @endif

                    @if($canEditHod)
                        <button type="button" 
                                onclick="if(confirm('Finalize appraisal? This will complete the performance evaluation and record final score.')) { document.getElementById('evaluationForm').action = '{{ route('appraisal.submit-hod', $appraisal->id) }}'; document.getElementById('evaluationForm').submit(); }"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2 cursor-pointer">
                            <i class="ph ph-seal-check text-base"></i>
                            <span>Finalize & Submit HOD Approval</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: INCREMENT & PROMOTION (HR Exclusive) -->
    <!-- ========================================== -->
    @if($canSeeIncrement)
    <div x-show="activeTab === 'increment'" class="space-y-6">
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
                <div>
                    <span class="text-[10px] font-black uppercase text-emerald-600 dark:text-emerald-400 tracking-wider">Compensation & Career Growth</span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Annual Increment & Promotion Terms</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        @if($canManageIncrement)
                            Configure compensation adjustments, designation advancement, and control employee & HOD visibility.
                        @else
                            Official salary increment and career progression terms approved by Executive Management.
                        @endif
                    </p>
                </div>

                @if($canManageIncrement && ($appraisal->cycle?->allowsIncrements() ?? true))
                    <button type="button" @click="showLetterModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer">
                        <i class="ph ph-file-plus text-base"></i>
                        <span>Generate Official Letter</span>
                    </button>
                @endif
            </div>

            @if($appraisal->cycle && !$appraisal->cycle->allowsIncrements())
                <!-- Mid-Year Review Informational Card (Increments Excluded) -->
                <div class="p-6 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 space-y-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center text-xl shrink-0">
                            <i class="ph ph-calendar-check"></i>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-extrabold text-amber-900 dark:text-amber-200">
                                    Mid-Year Review Policy: No Salary Increments
                                </h3>
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-200/80 dark:bg-amber-900/80 text-amber-900 dark:text-amber-200">
                                    Review Window: Oct 01st – 31st
                                </span>
                            </div>
                            <p class="text-xs text-amber-800 dark:text-amber-300 font-semibold">
                                Covers Performance Period: <strong>April to September</strong>
                            </p>
                        </div>
                    </div>

                    <div class="text-xs text-amber-800/95 dark:text-amber-400 font-medium leading-relaxed pl-13 space-y-2">
                        <p>
                            Under the financial year appraisal structure, the <strong>Mid-Year Review (October 01st to 31st)</strong> focuses specifically on:
                        </p>
                        <ul class="list-disc pl-5 space-y-1 text-slate-700 dark:text-slate-300 font-semibold">
                            <li>Reviewing employee progress against assigned deliverables</li>
                            <li>Identifying areas for improvement and professional development</li>
                            <li>Setting or adjusting KPIs and expectations for the remainder of the financial year</li>
                        </ul>
                        <div class="p-3 bg-white/70 dark:bg-slate-900/60 rounded-xl border border-amber-200/80 dark:border-amber-900/40 text-[11px] text-slate-600 dark:text-slate-400 font-medium">
                            <i class="ph ph-info text-amber-600 mr-1"></i>
                            <strong>Increment Notice:</strong> Salary increment and promotion recommendations are reserved exclusively for the <strong>Year-End Review (February 15th to March 15th)</strong>, where recommendations will be evaluated alongside the company's financial performance and approved budget.
                        </div>
                    </div>
                </div>
            @else
                <!-- Year-End Review Info Banner -->
                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 flex items-start gap-3">
                    <i class="ph ph-trend-up text-blue-600 dark:text-blue-400 text-lg shrink-0 mt-0.5"></i>
                    <div class="text-xs text-blue-900 dark:text-blue-300 font-semibold leading-relaxed">
                        <strong>Year-End Review (February 15th to March 15th):</strong> Covers performance from <strong>October to March</strong>. Final performance evaluation identifies employees for potential promotions and salary increment recommendations, considered alongside the company's financial performance and approved budget.
                    </div>
                </div>

                @if($canManageIncrement)
                <form action="{{ route('appraisal.increment-promotion', $appraisal->id) }}" method="POST" class="space-y-6 text-xs font-bold">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Salary Increment Percentage (%)</label>
                        <div class="relative">
                            <input type="number" step="0.1" min="0" max="100" name="increment_percentage" value="{{ old('increment_percentage', $appraisal->increment_percentage) }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-extrabold pr-8">
                            <span class="absolute right-3 top-2.5 text-slate-400 font-bold">%</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Promotion Recommended?</label>
                        <select name="is_promoted" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="0" {{ !$appraisal->is_promoted ? 'selected' : '' }}>No Promotion</option>
                            <option value="1" {{ $appraisal->is_promoted ? 'selected' : '' }}>Yes - Approved for Promotion</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">New Designation (If Promoted)</label>
                        <select name="new_designation_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">Keep Current ({{ $appraisal->employee?->designation?->name }})</option>
                            @foreach($designations as $des)
                                <option value="{{ $des->id }}" {{ $appraisal->new_designation_id == $des->id ? 'selected' : '' }}>
                                    {{ $des->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Effective Date</label>
                        <input type="date" name="effective_date" value="{{ old('effective_date', $appraisal->effective_date?->format('Y-m-d')) }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- Visibility Controls -->
                <div class="p-5 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-3">
                    <h4 class="text-xs font-black uppercase text-slate-500 tracking-wider">Granular Visibility Toggles</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 p-3 bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer">
                            <input type="checkbox" name="is_increment_visible_to_employee" value="1" {{ $appraisal->is_increment_visible_to_employee ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-slate-900 dark:text-white font-extrabold block">Visible to Employee</span>
                                <span class="text-[11px] font-normal text-slate-400">Release increment % and promotion details on employee's portal</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer">
                            <input type="checkbox" name="is_increment_visible_to_hod" value="1" {{ $appraisal->is_increment_visible_to_hod ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="text-slate-900 dark:text-white font-extrabold block">Visible to Head of Department</span>
                                <span class="text-[11px] font-normal text-slate-400">Allow HOD to view finalized increment percentage</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition-all flex items-center gap-2 cursor-pointer">
                        <i class="ph ph-floppy-disk text-base"></i>
                        <span>Save Increment Terms</span>
                    </button>
                </div>
            </form>
            @else
                <!-- Read-Only View for Employee / HOD -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Increment Percentage</span>
                        <div class="text-3xl font-black text-emerald-700 dark:text-emerald-300 mt-2">
                            +{{ number_format($appraisal->increment_percentage, 1) }}%
                        </div>
                        <span class="text-[10px] text-emerald-600/70 font-semibold">Applied to Base Salary</span>
                    </div>

                    <div class="p-5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800">
                        <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase">Promotion Status</span>
                        <div class="text-lg font-black text-indigo-700 dark:text-indigo-300 mt-2 flex items-center gap-1.5">
                            @if($appraisal->is_promoted)
                                <i class="ph ph-crown text-xl"></i>
                                <span>Promoted to {{ $appraisal->newDesignation?->name ?? 'New Role' }}</span>
                            @else
                                <span>No Role Change</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800">
                        <span class="text-[11px] font-bold text-slate-400 uppercase">Effective Date</span>
                        <div class="text-lg font-black text-slate-900 dark:text-white mt-2">
                            {{ $appraisal->effective_date ? $appraisal->effective_date->format('d F Y') : 'Immediate' }}
                        </div>
                    </div>
                </div>
            @endif
            @endif
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 3: LETTERS & E-SIGNATURES -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'letters'" class="space-y-6">
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
                <div>
                    <span class="text-[10px] font-black uppercase text-blue-600 dark:text-blue-400 tracking-wider">Digital Documentation</span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Official Letters & E-Signatures</h2>
                    <p class="text-xs text-slate-400 mt-1">Increment letters, promotion letters, warning notices, and confirmation letters with digital signing workflow.</p>
                </div>

                @if(in_array($role, ['Super Admin', 'HR Lead']))
                    <button type="button" @click="showLetterModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer">
                        <i class="ph ph-file-plus text-base"></i>
                        <span>Generate Letter</span>
                    </button>
                @endif
            </div>

            @if($appraisal->letters->isEmpty())
                <div class="text-center py-10 space-y-2">
                    <i class="ph ph-envelope-simple text-3xl text-slate-400"></i>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">No Letters Generated Yet</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">Once the appraisal is finalized, HR can generate an Increment or Promotion letter for digital signing.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($appraisal->letters as $letter)
                        <div class="p-6 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase text-blue-600 dark:text-blue-400 tracking-wider">{{ $letter->reference_no }}</span>
                                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ $letter->title }}</h3>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold self-start sm:self-auto
                                    {{ $letter->status === 'acknowledged_by_employee' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $letter->status === 'signed_by_management' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $letter->status === 'pending_signatory' ? 'bg-amber-100 text-amber-800' : '' }}">
                                    {{ ucwords(str_replace('_', ' ', $letter->status)) }}
                                </span>
                            </div>

                            <!-- Letter preview body -->
                            <div class="p-5 bg-white dark:bg-[#111927] border border-slate-200/80 dark:border-slate-800 rounded-2xl text-xs text-slate-700 dark:text-slate-200 leading-relaxed font-normal shadow-inner">
                                {!! $letter->template_content !!}
                            </div>

                            <!-- Digital Signature Audit Strip -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <!-- Management Signatory (Anoj / Shehan) -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#111927]">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Authorized Management Signatory</span>
                                    <div class="font-extrabold text-xs text-slate-900 dark:text-white">
                                        {{ $letter->signatory_name ?? 'Anoj / Shehan (Executive Director)' }}
                                    </div>
                                    @if($letter->signatory_signed_at)
                                        <div class="mt-2 text-emerald-600 dark:text-emerald-400 text-[11px] font-bold flex items-center gap-1.5">
                                            <i class="ph ph-seal-check text-sm"></i>
                                            <span>Digitally E-Signed on {{ $letter->signatory_signed_at->format('d M Y, h:i A') }}</span>
                                        </div>
                                    @else
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-amber-500 text-[11px] font-semibold">Awaiting Management Signature</span>
                                            @if(in_array($role, ['Super Admin', 'HR Lead']))
                                                <button @click="selectedLetterToSign = {{ $letter->id }}; signRole = 'signatory'; showSignModal = true;" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg text-xs font-bold cursor-pointer">
                                                    E-Sign Now
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Employee Acknowledgment -->
                                <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#111927]">
                                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-1">Employee Acknowledgment</span>
                                    <div class="font-extrabold text-xs text-slate-900 dark:text-white">
                                        {{ $letter->employee?->user?->name }}
                                    </div>
                                    @if($letter->employee_acknowledged_at)
                                        <div class="mt-2 text-emerald-600 dark:text-emerald-400 text-[11px] font-bold flex items-center gap-1.5">
                                            <i class="ph ph-check-circle text-sm"></i>
                                            <span>Acknowledged on {{ $letter->employee_acknowledged_at->format('d M Y, h:i A') }}</span>
                                        </div>
                                    @else
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-amber-500 text-[11px] font-semibold">Awaiting Employee Acknowledgment</span>
                                            @if($isEmployeeHimself && $letter->status === 'signed_by_management')
                                                <button @click="selectedLetterToSign = {{ $letter->id }}; signRole = 'employee'; showSignModal = true;" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-xs font-bold cursor-pointer">
                                                    Sign & Acknowledge
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- PDF Download CTA -->
                            <div class="flex justify-end pt-2">
                                <a href="{{ route('appraisal.letters.pdf', $letter->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm">
                                    <i class="ph ph-file-pdf text-base"></i>
                                    <span>Download Official PDF Letter</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: CHANGE REQUESTS & AUDIT HISTORY -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'audit'" class="space-y-6">
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div>
                <span class="text-[10px] font-black uppercase text-amber-600 dark:text-amber-400 tracking-wider">Audit Trail</span>
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Change Requests & Unlock History</h2>
                <p class="text-xs text-slate-400 mt-1">Audit log of all change requests submitted by employees or managers and HR unlock actions.</p>
            </div>

            @if($appraisal->changeRequests->isEmpty())
                <div class="text-center py-10 space-y-2">
                    <i class="ph ph-shield-check text-3xl text-emerald-500"></i>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">Clean Audit Trail</h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">No change requests or unlock disputes have been recorded for this appraisal form.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($appraisal->changeRequests as $cr)
                        <div class="py-4 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-slate-900 dark:text-white">{{ $cr->requestedBy?->name }}</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">Role: {{ $cr->role_at_request }}</span>
                                    <span class="text-[11px] text-slate-400 font-semibold">{{ $cr->created_at->format('d M Y, h:i A') }}</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black 
                                    {{ $cr->status === 'Approved_Unlocked' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $cr->status === 'Rejected' ? 'bg-rose-100 text-rose-800' : '' }}
                                    {{ $cr->status === 'Pending' ? 'bg-amber-100 text-amber-800' : '' }}">
                                    {{ $cr->status }}
                                </span>
                            </div>

                            <div class="p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl text-slate-700 dark:text-slate-300 font-normal">
                                <strong>Reason for Request:</strong> {{ $cr->reason }}
                            </div>

                            @if($cr->action_notes)
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 pl-2 border-l-2 border-slate-300 dark:border-slate-700">
                                    <strong>HR Action Note:</strong> {{ $cr->action_notes }} (Actioned by: {{ $cr->actionedBy?->name ?? 'HR Admin' }} on {{ $cr->actioned_at?->format('d M Y') }})
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: REQUEST CHANGE -->
    <!-- ========================================== -->
    <div x-show="showChangeRequestModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showChangeRequestModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-lg w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-arrow-counter-clockwise text-lg text-amber-500"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Request Appraisal Change</h3>
                </div>
                <button @click="showChangeRequestModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x"></i></button>
            </div>

            <form action="{{ route('appraisal.request-change', $appraisal->id) }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                <p class="text-slate-500 dark:text-slate-400 font-normal leading-relaxed">
                    Submitted appraisals are locked by policy. To adjust scores or comments, submit a formal reason. <strong>Only HR Administrators can unlock the appraisal</strong>.
                </p>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Reason for Change Request <span class="text-rose-500">*</span></label>
                    <textarea name="reason" rows="4" required minlength="10" placeholder="State clearly which criteria or scores need to be adjusted and why..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showChangeRequestModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 rounded-xl text-xs font-bold">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: HR DIRECT UNLOCK -->
    <!-- ========================================== -->
    <div x-show="showUnlockModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showUnlockModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-lock-key-open text-lg text-rose-600"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Unlock Appraisal Form</h3>
                </div>
                <button @click="showUnlockModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x"></i></button>
            </div>

            <form action="{{ route('appraisal.unlock', $appraisal->id) }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                <p class="text-slate-500 dark:text-slate-400 font-normal">
                    Unlocking restores edit permissions. Select which review stage the appraisal should revert to:
                </p>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Unlock Stage</label>
                    <select name="target_stage" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="pending_self_eval">Pending Self-Evaluation (Revert to Employee)</option>
                        @if($appraisal->workflow_type === 'three_tier' && $appraisal->reporting_manager_id)
                            <option value="pending_manager_eval">Pending Manager Review (Revert to Manager)</option>
                        @endif
                        <option value="pending_hod_eval">Pending HOD Review (Revert to HOD)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">HR Admin Note</label>
                    <textarea name="notes" rows="2" placeholder="Audit note explaining the unlock reason..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showUnlockModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl text-xs font-bold">Confirm Unlock</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: GENERATE LETTER -->
    <!-- ========================================== -->
    <div x-show="showLetterModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="showLetterModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-file-plus text-lg text-blue-600"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Generate Official Letter</h3>
                </div>
                <button @click="showLetterModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x"></i></button>
            </div>

            <form action="{{ route('appraisal.generate-letter', $appraisal->id) }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Letter Type</label>
                    <select name="letter_type" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="increment">Salary Increment Letter</option>
                        <option value="promotion">Promotion Notice Letter</option>
                        <option value="confirmation">Permanent Confirmation Letter (Post-Probation)</option>
                        <option value="warning">Performance Advisory Notice</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Authorized Management Signatory</label>
                    <select name="signatory_name" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="Anoj / Shehan (Executive Directors)">Anoj / Shehan (Executive Directors)</option>
                        <option value="Anoj (Executive Director)">Anoj (Executive Director)</option>
                        <option value="Shehan (Executive Director)">Shehan (Executive Director)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showLetterModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold">Generate & Queue</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: DIGITAL E-SIGNATURE PAD -->
    <!-- ========================================== -->
    <div x-show="showSignModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-init="
            $watch('showSignModal', value => {
                if (value) {
                    $nextTick(() => {
                        let canvas = document.getElementById('signaturePad');
                        if (!canvas) return;
                        let ctx = canvas.getContext('2d');
                        ctx.strokeStyle = '#2563EB';
                        ctx.lineWidth = 2;
                        let drawing = false;

                        canvas.onmousedown = (e) => { drawing = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY); };
                        canvas.onmousemove = (e) => { if(drawing) { ctx.lineTo(e.offsetX, e.offsetY); ctx.stroke(); } };
                        window.onmouseup = () => { drawing = false; };
                    });
                }
            });
         ">
        <div @click.away="showSignModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <i class="ph ph-pen-nib text-lg text-blue-600"></i>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white" x-text="signRole === 'signatory' ? 'Management Digital E-Signature' : 'Employee Acknowledgment Signature'"></h3>
                </div>
                <button @click="showSignModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x"></i></button>
            </div>

            <form :action="signRole === 'signatory' ? '/appraisal-letters/' + selectedLetterToSign + '/sign' : '/appraisal-letters/' + selectedLetterToSign + '/acknowledge'" 
                  method="POST" 
                  onsubmit="document.getElementById('sigInput').value = document.getElementById('signaturePad').toDataURL();"
                  class="space-y-4 text-xs font-bold">
                @csrf
                <input type="hidden" name="signature_data" id="sigInput">

                <p class="text-slate-400 font-normal">Draw your official signature in the box below to authenticate this letter.</p>

                <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-1 bg-slate-50 dark:bg-slate-900 flex justify-center">
                    <canvas id="signaturePad" width="360" height="150" class="cursor-crosshair bg-white dark:bg-slate-800 rounded-xl"></canvas>
                </div>

                <div class="flex items-center justify-between">
                    <button type="button" onclick="let c = document.getElementById('signaturePad'); c.getContext('2d').clearRect(0, 0, c.width, c.height);" class="text-[11px] text-rose-500 hover:underline">
                        Clear Canvas
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showSignModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold">Save Signature</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
