@extends('layouts.app', ['title' => 'Loops HR - Performance Appraisal Evaluation', 'breadcrumb' => 'Appraisal Evaluation'])

@section('content')
@php
    $appStatus = $appraisal->status;
@endphp
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
    },

    autoExpand(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.max(38, el.scrollHeight) + 'px';
    }
}">

    <!-- Status & Policy Indicators -->
    @if($isAdminOrHr)
        <div class="px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-white/[0.04] border border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-xs">
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-medium text-[11px]">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 dark:bg-blue-400"></span>
                <span>Admin override active</span>
            </div>
            <span class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Super Admin</span>
        </div>
    @endif

    @if($isEmployeeOverdue && !$isAdminOrHr)
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center shrink-0 text-base shadow-sm">
                    <i class="ph ph-lock-key"></i>
                </div>
                <div>
                    <span class="font-black text-rose-900 dark:text-rose-200 block">Self-Evaluation Submission Duration Ended</span>
                    <span class="text-rose-700/90 dark:text-rose-300/90 font-medium">
                        The submission window closed on <strong>{{ $appraisal->cycle?->self_eval_deadline?->format('d M Y') ?? 'the scheduled deadline' }}</strong>. This evaluation form has been locked. To submit your evaluation, request an unlock from HR.
                    </span>
                </div>
            </div>
            <button type="button" @click="showChangeRequestModal = true" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl font-bold text-xs shrink-0 flex items-center gap-1.5 shadow-sm cursor-pointer self-start sm:self-auto">
                <i class="ph ph-lock-key-open"></i>
                <span>Request HR Unlock</span>
            </button>
        </div>
    @endif

    @if($isManagerOverdue && !$isAdminOrHr)
        <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center shrink-0 text-base shadow-sm">
                    <i class="ph ph-hourglass-high"></i>
                </div>
                <div>
                    <span class="font-black text-amber-900 dark:text-amber-200 block">Manager Review Duration Passed</span>
                    <span class="text-amber-800/90 dark:text-amber-300/90 font-medium">
                        The review window for managers closed on <strong>{{ $appraisal->cycle?->manager_eval_deadline?->format('d M Y') ?? 'the scheduled deadline' }}</strong>. Review submissions are locked. To complete this review, request an unlock from HR.
                    </span>
                </div>
            </div>
            <button type="button" @click="showChangeRequestModal = true" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl font-bold text-xs shrink-0 flex items-center gap-1.5 shadow-sm cursor-pointer self-start sm:self-auto">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Request HR Unlock</span>
            </button>
        </div>
    @endif

    @if($appraisal->is_override_unlocked && $appraisal->unlocked_until && now()->lte($appraisal->unlocked_until))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 text-base shadow-sm">
                    <i class="ph ph-lock-key-open"></i>
                </div>
                <div>
                    <span class="font-black text-emerald-950 dark:text-emerald-200 block">Temporary HR Extension Active</span>
                    <span class="text-emerald-800/90 dark:text-emerald-300/90 font-medium">
                        This appraisal was unlocked by HR with an extension valid until <strong>{{ $appraisal->unlocked_until->format('d M Y, h:i A') }}</strong>. Please complete and submit before the extension closes.
                    </span>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 self-start sm:self-auto shrink-0">
                Extension Active
            </span>
        </div>
    @endif

    <!-- Employee & Appraisal Header Banner -->
    <div class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-base flex items-center justify-center shadow-xs shrink-0">
                    {{ strtoupper(substr($appraisal->employee?->user?->name ?? 'E', 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-lg font-black text-slate-900 dark:text-white">
                            {{ $appraisal->employee?->user?->name ?? 'Staff Employee' }}
                        </h1>
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800/70 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                            {{ $appraisal->employee?->employee_id_number ?? 'EMP-00' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                            <span class="w-1.5 h-1.5 rounded-full {{ $appraisal->status === 'completed' ? 'bg-emerald-500 dark:bg-[#10b981]' : ($appStatus === 'pending_self_eval' ? 'bg-amber-500 dark:bg-[#d97706]' : 'bg-blue-500 dark:bg-blue-400') }} shrink-0"></span>
                            <span>{{ ucwords(str_replace('_', ' ', $appraisal->status)) }}</span>
                        </span>
                        @if($appraisal->status !== 'completed')
                            @if($appraisal->is_override_unlocked && $appraisal->unlocked_until && now()->lte($appraisal->unlocked_until))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                    <i class="ph ph-lock-key-open text-xs text-emerald-400"></i>
                                    <span>Extension Active</span>
                                </span>
                            @elseif($isEmployeeOverdue || $isManagerOverdue || $appraisal->is_locked)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60">
                                    <i class="ph ph-lock text-xs text-rose-400"></i>
                                    <span>Locked</span>
                                </span>
                            @endif
                        @endif
                    </div>
                    
                    <!-- Clean Subtitle Metadata -->
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>{{ $appraisal->employee?->designation?->name ?? 'Staff' }}</span>
                        <span>&bull;</span>
                        <span>{{ $appraisal->employee?->department?->name ?? 'General' }}</span>
                        <span>&bull;</span>
                        <span>{{ $appraisal->cycle?->name }}</span>
                        @if($appraisal->workflow_type === 'three_tier' && $appraisal->reportingManager)
                            <span>&bull;</span>
                            <span>Mgr: <strong class="text-slate-700 dark:text-slate-300">{{ $appraisal->reportingManager->user->name }}</strong></span>
                        @endif
                        @if($appraisal->hod)
                            <span>&bull;</span>
                            <span>HOD: <strong class="text-slate-700 dark:text-slate-300">{{ $appraisal->hod->user->name ?? 'HOD' }}</strong></span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Score Summary & Actions (Cleanly Aligned on Horizontal Plane) -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0 justify-end">
                <!-- Score Pill (Without Duplicate 'Pending Evaluation' badge) -->
                <div class="px-3 py-1.5 bg-slate-50 dark:bg-slate-800/70 rounded-xl border border-slate-200/80 dark:border-slate-700/60 text-right flex items-center gap-2">
                    <div class="text-sm font-black text-slate-900 dark:text-white flex items-baseline justify-end gap-1 leading-none">
                        <span x-text="calculatedScore.toFixed(2)">{{ number_format($appraisal->total_weighted_score, 2) }}</span>
                        <span class="text-[11px] text-slate-400">/ 5.00</span>
                    </div>
                    <template x-if="calculatedScore > 0">
                        <span :class="calculatedBand.color" class="px-1.5 py-0.5 rounded text-[9px] font-bold border" x-text="calculatedBand.badge">
                            {{ $appraisal->performance_band }}
                        </span>
                    </template>
                </div>

                <!-- Action buttons: Muted Outlined/Ghost Buttons -->
                @if($canRequestChange)
                    <button type="button" @click="showChangeRequestModal = true" class="px-3 py-1.5 rounded-xl border border-slate-300 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-600 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/60 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer">
                        <i class="ph ph-arrow-counter-clockwise text-xs text-slate-400"></i>
                        <span>Request Change</span>
                    </button>
                @endif

                @if($canUnlock)
                    <button type="button" @click="showUnlockModal = true" class="px-3 py-1.5 rounded-xl border border-slate-300 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-600 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/60 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer">
                        <i class="ph ph-lock-key-open text-xs text-slate-400"></i>
                        <span>Unlock</span>
                    </button>
                @endif

                <!-- Back to Roster button -->
                <a href="{{ route('appraisal.index') }}" onclick="handleModuleBack(event, '{{ route('appraisal.index') }}')" class="px-3 py-1.5 rounded-xl border border-slate-300 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-600 text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800/60 text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer">
                    <i class="ph ph-arrow-left text-xs text-slate-400"></i>
                    <span>Roster</span>
                </a>
            </div>
        </div>

        @php
            $hasReportingManager = (bool)($appraisal->reporting_manager_id && $appraisal->reportingManager && $appraisal->workflow_type !== 'two_tier' && $appraisal->reporting_manager_id !== $appraisal->hod_id);
            $appStatus = $appraisal->status;

            if ($hasReportingManager) {
                // 4 Stages: Self-Evaluation -> Manager Review -> HOD Moderation -> Final Sign-off
                $s1Done = in_array($appStatus, ['submitted_self_eval', 'pending_manager_eval', 'submitted_manager_eval', 'pending_hod_eval', 'completed']) || !empty($appraisal->self_submitted_at);
                $s1Active = ($appStatus === 'pending_self_eval');

                $s2Done = in_array($appStatus, ['submitted_manager_eval', 'pending_hod_eval', 'completed']) || !empty($appraisal->manager_submitted_at);
                $s2Active = in_array($appStatus, ['submitted_self_eval', 'pending_manager_eval']);

                $s3Done = ($appStatus === 'completed') || !empty($appraisal->hod_submitted_at);
                $s3Active = in_array($appStatus, ['submitted_manager_eval', 'pending_hod_eval']);

                $s4Done = ($appStatus === 'completed');
                $s4Active = false;

                if ($s4Done) {
                    $progressPercent = 100;
                    $currentStageLabel = 'Completed & Finalized';
                } elseif ($s3Active) {
                    $progressPercent = 66.6;
                    $currentStageLabel = '3. HOD Moderation';
                } elseif ($s2Active) {
                    $progressPercent = 33.3;
                    $currentStageLabel = '2. Manager Review';
                } else {
                    $progressPercent = 0;
                    $currentStageLabel = '1. Self-Evaluation';
                }
            } else {
                // 3 Stages: Self-Evaluation -> HOD Review & Moderation -> Final Sign-off
                $s1Done = in_array($appStatus, ['submitted_self_eval', 'pending_hod_eval', 'completed']) || !empty($appraisal->self_submitted_at);
                $s1Active = ($appStatus === 'pending_self_eval');

                $s2Done = ($appStatus === 'completed') || !empty($appraisal->hod_submitted_at);
                $s2Active = in_array($appStatus, ['submitted_self_eval', 'pending_hod_eval', 'submitted_manager_eval', 'pending_manager_eval']);

                $s3Done = ($appStatus === 'completed');
                $s3Active = false;

                if ($s3Done) {
                    $progressPercent = 100;
                    $currentStageLabel = 'Completed & Finalized';
                } elseif ($s2Active) {
                    $progressPercent = 50;
                    $currentStageLabel = '2. HOD Review & Moderation';
                } else {
                    $progressPercent = 0;
                    $currentStageLabel = '1. Self-Evaluation';
                }
            }
        @endphp

        <!-- Progressive Review Stages Milestone Progress Bar -->
        <div class="relative bg-slate-50/50 dark:bg-slate-800/30 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5">
            <div class="mb-4 pb-2 border-b border-slate-200/50 dark:border-slate-700/50">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Progressive Review Workflow</span>
            </div>

            <div class="relative">
                <!-- Background Connecting Track Line -->
                <div class="absolute top-4 sm:top-4.5 {{ $hasReportingManager ? 'left-[12.5%] right-[12.5%]' : 'left-[16.67%] right-[16.67%]' }} h-1.5 bg-slate-200/90 dark:bg-slate-700/80 -translate-y-1/2 rounded-full overflow-hidden z-0">
                    <div class="h-full bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500 rounded-full transition-all duration-700 ease-out" 
                         style="width: {{ $progressPercent }}%"></div>
                </div>

                @if($hasReportingManager)
                    <!-- 4 Stages Grid -->
                    <div class="relative z-10 grid grid-cols-4 gap-2 sm:gap-4">
                        <!-- 1. Self-Evaluation -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s1Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : 'bg-blue-600 text-white shadow-blue-500/30 ring-blue-100 dark:ring-blue-900/40' }}">
                                @if($s1Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    1
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s1Active ? 'text-blue-600 dark:text-blue-400' : ($s1Done ? 'text-slate-900 dark:text-white' : 'text-slate-400') }} block">
                                    Self-Evaluation
                                </span>
                                <p class="text-[10px] font-bold {{ $s1Done ? 'text-emerald-600 dark:text-emerald-400' : ($s1Active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400') }}">
                                    @if($s1Done)
                                        {{ $appraisal->self_submitted_at ? 'Submitted ' . $appraisal->self_submitted_at->format('d M') : 'Submitted' }}
                                    @else
                                        Pending Submission
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    {{ $appraisal->employee?->user?->name ?? 'Employee' }}
                                </p>
                            </div>
                        </div>

                        <!-- 2. Manager Review -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s2Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : ($s2Active ? 'bg-blue-600 text-white shadow-blue-500/30 ring-blue-100 dark:ring-blue-900/40' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400') }}">
                                @if($s2Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    2
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s2Active ? 'text-blue-600 dark:text-blue-400' : ($s2Done ? 'text-slate-900 dark:text-white' : 'text-slate-400') }} block">
                                    Manager Review
                                </span>
                                <p class="text-[10px] font-bold {{ $s2Done ? 'text-emerald-600 dark:text-emerald-400' : ($s2Active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400') }}">
                                    @if($s2Done)
                                        {{ $appraisal->manager_submitted_at ? 'Reviewed ' . $appraisal->manager_submitted_at->format('d M') : 'Reviewed' }}
                                    @elseif($s2Active)
                                        In Review
                                    @else
                                        Awaiting Self-Eval
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    {{ $appraisal->reportingManager?->user?->name ?? 'Reporting Manager' }}
                                </p>
                            </div>
                        </div>

                        <!-- 3. HOD Moderation -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s3Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : ($s3Active ? 'bg-blue-600 text-white shadow-blue-500/30 ring-blue-100 dark:ring-blue-900/40' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400') }}">
                                @if($s3Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    3
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s3Active ? 'text-blue-600 dark:text-blue-400' : ($s3Done ? 'text-slate-900 dark:text-white' : 'text-slate-400') }} block">
                                    HOD Moderation
                                </span>
                                <p class="text-[10px] font-bold {{ $s3Done ? 'text-emerald-600 dark:text-emerald-400' : ($s3Active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400') }}">
                                    @if($s3Done)
                                        {{ $appraisal->hod_submitted_at ? 'Approved ' . $appraisal->hod_submitted_at->format('d M') : 'Approved' }}
                                    @elseif($s3Active)
                                        In Moderation
                                    @else
                                        Awaiting Manager
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    {{ $appraisal->hod?->user?->name ?? 'Head of Department' }}
                                </p>
                            </div>
                        </div>

                        <!-- 4. Final Sign-off -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s4Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                                @if($s4Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    4
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s4Done ? 'text-slate-900 dark:text-white' : 'text-slate-400' }} block">
                                    Final Sign-off
                                </span>
                                <p class="text-[10px] font-bold {{ $s4Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                    @if($s4Done)
                                        Finalized ({{ number_format($appraisal->total_weighted_score, 2) }}/5.00)
                                    @else
                                        Pending Finalization
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    HR / Admin
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- 3 Stages Grid (Direct HOD) -->
                    <div class="relative z-10 grid grid-cols-3 gap-2 sm:gap-4">
                        <!-- 1. Self-Evaluation -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s1Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : 'bg-blue-600 text-white shadow-blue-500/30 ring-blue-100 dark:ring-blue-900/40' }}">
                                @if($s1Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    1
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s1Active ? 'text-blue-600 dark:text-blue-400' : ($s1Done ? 'text-slate-900 dark:text-white' : 'text-slate-400') }} block">
                                    Self-Evaluation
                                </span>
                                <p class="text-[10px] font-bold {{ $s1Done ? 'text-emerald-600 dark:text-emerald-400' : ($s1Active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400') }}">
                                    @if($s1Done)
                                        {{ $appraisal->self_submitted_at ? 'Submitted ' . $appraisal->self_submitted_at->format('d M') : 'Submitted' }}
                                    @else
                                        Pending Submission
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    {{ $appraisal->employee?->user?->name ?? 'Employee' }}
                                </p>
                            </div>
                        </div>

                        <!-- 2. HOD Review & Moderation -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s2Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : ($s2Active ? 'bg-blue-600 text-white shadow-blue-500/30 ring-blue-100 dark:ring-blue-900/40' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400') }}">
                                @if($s2Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    2
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s2Active ? 'text-blue-600 dark:text-blue-400' : ($s2Done ? 'text-slate-900 dark:text-white' : 'text-slate-400') }} block">
                                    HOD Review & Moderation
                                </span>
                                <p class="text-[10px] font-bold {{ $s2Done ? 'text-emerald-600 dark:text-emerald-400' : ($s2Active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400') }}">
                                    @if($s2Done)
                                        {{ $appraisal->hod_submitted_at ? 'Approved ' . $appraisal->hod_submitted_at->format('d M') : 'Approved' }}
                                    @elseif($s2Active)
                                        In Review
                                    @else
                                        Awaiting Self-Eval
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    {{ $appraisal->hod?->user?->name ?? 'Head of Department' }}
                                </p>
                            </div>
                        </div>

                        <!-- 3. Final Sign-off -->
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm ring-4 ring-slate-50 dark:ring-slate-800 {{ $s3Done ? 'bg-emerald-500 text-white shadow-emerald-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                                @if($s3Done)
                                    <i class="ph-bold ph-check text-xs"></i>
                                @else
                                    3
                                @endif
                            </div>
                            <div class="mt-2 space-y-0.5">
                                <span class="text-xs font-black {{ $s3Done ? 'text-slate-900 dark:text-white' : 'text-slate-400' }} block">
                                    Final Sign-off
                                </span>
                                <p class="text-[10px] font-bold {{ $s3Done ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                    @if($s3Done)
                                        Finalized ({{ number_format($appraisal->total_weighted_score, 2) }}/5.00)
                                    @else
                                        Pending Finalization
                                    @endif
                                </p>
                                <p class="text-[9px] font-medium text-slate-400 dark:text-slate-500 truncate max-w-[110px] sm:max-w-none">
                                    HR / Admin
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-6 border-b border-slate-100 dark:border-slate-800 -mb-5">
            <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-clipboard-text"></i>
                <span>Evaluation Form</span>
            </button>

            @if($canSeeIncrement)
                <button @click="activeTab = 'increment'" :class="activeTab === 'increment' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-trend-up"></i>
                    <span>Increment & Promotion</span>
                    @if($appraisal->increment_percentage > 0 || $appraisal->is_promoted)
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    @endif
                </button>
            @endif

            @if(!$isEmployeeOnly || $appraisal->letters->isNotEmpty())
                <button @click="activeTab = 'letters'" :class="activeTab === 'letters' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                    <i class="ph ph-stamp"></i>
                    <span>Letters ({{ $appraisal->letters->count() }})</span>
                </button>
            @endif

            <button @click="activeTab = 'audit'" :class="activeTab === 'audit' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-extrabold' : 'border-transparent text-slate-500 font-semibold'" class="pb-3 text-xs border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Change Requests ({{ $appraisal->changeRequests->count() }})</span>
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
            <div class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800/80 rounded-2xl overflow-hidden shadow-xs">
                <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Performance Criteria &amp; Competency Ratings</h2>
                    </div>
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/60 px-2.5 py-1 rounded-xl">
                        Template: {{ $appraisal->template?->title }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 dark:bg-[#0b1019] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800/80">
                            <tr>
                                <th class="p-4 pl-6 w-1/4">Evaluation Criteria</th>
                                <th class="p-4 text-center w-20">Weightage</th>
                                <th class="p-4 w-1/3">Employee Self-Evaluation</th>
                                <th class="p-4 w-1/3 pr-6">Manager / HOD Final Review</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            @foreach($appraisal->criteriaScores as $idx => $criterion)
                                <tr class="hover:bg-slate-50/40 dark:hover:bg-[#161f30]/40 transition-colors">
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
                                        <span class="inline-block px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800/70 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/60 font-semibold text-xs">
                                            {{ number_format($criterion->weightage, 0) }}%
                                        </span>
                                    </td>

                                    <!-- Employee Self-Evaluation -->
                                    <td class="p-4 align-top space-y-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Rating (1-5)</label>
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
                                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Comments</label>
                                            <textarea name="scores[{{ $criterion->id }}][self_comments]" 
                                                      rows="2" 
                                                      {{ ($canEditSelf) ? '' : 'readonly' }}
                                                      @input="autoExpand($el)"
                                                      @focus="autoExpand($el)"
                                                      x-init="$nextTick(() => autoExpand($el))"
                                                      placeholder="Describe specific achievements, projects, or context..." 
                                                      class="auto-expand w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-80 read-only:bg-slate-50/50 resize-none overflow-hidden leading-relaxed min-h-[38px]">{{ $criterion->self_comments }}</textarea>
                                        </div>
                                    </td>

                                    <!-- Manager / HOD Final Review -->
                                    <td class="p-4 pr-6 align-top space-y-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Rating (1-5)</label>
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
                                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Comments</label>
                                            <textarea name="scores[{{ $criterion->id }}][manager_comments]" 
                                                      rows="2" 
                                                      {{ ($canEditManager || $canEditHod) ? '' : 'readonly' }}
                                                      @input="autoExpand($el)"
                                                      @focus="autoExpand($el)"
                                                      x-init="$nextTick(() => autoExpand($el))"
                                                      placeholder="Evaluation rationale and feedback..." 
                                                      class="auto-expand w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-2.5 focus:ring-2 focus:ring-blue-500/20 disabled:opacity-80 read-only:bg-slate-50/50 resize-none overflow-hidden leading-relaxed min-h-[38px]">{{ $criterion->manager_comments }}</textarea>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Live Score Calculation Footer (Without Math Formula) -->
                <div class="p-5 sm:p-6 bg-slate-50/80 dark:bg-[#0b1019] border-t border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Computed Weighted Score</span>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-3xl font-black text-slate-900 dark:text-white" x-text="calculatedScore.toFixed(2)">
                                {{ number_format($appraisal->total_weighted_score, 2) }}
                            </span>
                            <span class="text-sm font-semibold text-slate-400">/ 5.00</span>
                            <span :class="calculatedBand.color" class="px-2.5 py-0.5 rounded-full text-xs font-bold border" x-text="calculatedBand.badge">
                                {{ $appraisal->performance_band ?: 'Pending Evaluation' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Overall Comments & Feedback Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-2">
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                        Employee General Self-Reflection
                    </label>
                    <textarea name="general_employee_comments" rows="3" {{ $canEditSelf ? '' : 'readonly' }} @input="autoExpand($el)" @focus="autoExpand($el)" x-init="$nextTick(() => autoExpand($el))" placeholder="Highlights of achievements, career aspirations, and support needed..." class="auto-expand w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-3 read-only:bg-slate-50/40 resize-none overflow-hidden leading-relaxed min-h-[50px]">{{ $appraisal->general_employee_comments }}</textarea>
                </div>

                <div class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xs space-y-2">
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                        Manager / HOD General Feedback
                    </label>
                    <textarea name="general_manager_comments" rows="3" {{ ($canEditManager || $canEditHod) ? '' : 'readonly' }} @input="autoExpand($el)" @focus="autoExpand($el)" x-init="$nextTick(() => autoExpand($el))" placeholder="Summary of overall strengths, areas for development, and leadership review..." class="auto-expand w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white p-3 read-only:bg-slate-50/40 resize-none overflow-hidden leading-relaxed min-h-[50px]">{{ $appraisal->general_manager_comments }}</textarea>
                </div>
            </div>

            <!-- Sticky Bottom Action Bar -->
            <div class="sticky bottom-0 z-20 -mx-4 sm:-mx-6 px-4 sm:px-6 py-3.5 bg-white dark:bg-[#111927] border-t border-slate-200/80 dark:border-slate-800/90 shadow-lg mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3 max-w-7xl mx-auto">
                    <div>
                        @if(!$appraisal->is_locked || in_array($role, ['Super Admin', 'HR Lead']))
                            <button type="submit" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-floppy-disk text-base text-slate-400"></i>
                                <span>Save Draft Scores</span>
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-2.5 flex-wrap">
                        @if($canEditSelf)
                            <button type="button" 
                                    onclick="if(confirm('Are you sure you want to submit your self-evaluation? The form will lock and forward to your manager/HOD.')) { document.getElementById('evaluationForm').action = '{{ route('appraisal.submit-self', $appraisal->id) }}'; document.getElementById('evaluationForm').submit(); }"
                                    class="{{ $appStatus === 'pending_self_eval' ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-semibold' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium' }} px-5 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-paper-plane-tilt text-sm"></i>
                                <span>Submit Self-Evaluation</span>
                            </button>
                        @endif

                        @if($canEditManager)
                            <button type="button" 
                                    onclick="if(confirm('Submit manager evaluation? This will forward the evaluation to HOD for final approval.')) { document.getElementById('evaluationForm').action = '{{ route('appraisal.submit-manager', $appraisal->id) }}'; document.getElementById('evaluationForm').submit(); }"
                                    class="{{ in_array($appStatus, ['submitted_self_eval', 'pending_manager_eval']) ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-semibold' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium' }} px-5 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-check-square-offset text-sm"></i>
                                <span>Submit Review</span>
                            </button>
                        @endif

                        @if($canEditHod)
                            <button type="button" 
                                    onclick="if(confirm('Finalize appraisal? This will complete the performance evaluation and record final score.')) { document.getElementById('evaluationForm').action = '{{ route('appraisal.submit-hod', $appraisal->id) }}'; document.getElementById('evaluationForm').submit(); }"
                                    class="{{ in_array($appStatus, ['submitted_manager_eval', 'pending_hod_eval']) ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-semibold' : 'border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium' }} px-5 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                <i class="ph ph-seal-check text-sm"></i>
                                <span>Approve &amp; Finalize</span>
                            </button>
                        @endif
                    </div>
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
    <!-- TAB 3: LETTERS & E-SIGNATURES (Management & HR Only) -->
    <!-- ========================================== -->
    @if(!$isEmployeeOnly || $appraisal->letters->isNotEmpty())
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
    @endif

    <!-- ========================================== -->
    <!-- TAB 4: CHANGE REQUESTS & AUDIT HISTORY -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'audit'" class="space-y-6">
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
                <div>
                    <span class="text-[10px] font-black uppercase text-amber-600 dark:text-amber-400 tracking-wider">
                        {{ $isEmployeeOnly ? 'Edit & Unlock Requests' : 'Audit Trail' }}
                    </span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">
                        {{ $isEmployeeOnly ? 'Changes Requested' : 'Change Requests & Unlock History' }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $isEmployeeOnly ? 'Track all formal edit requests submitted for this evaluation form and HR decisions.' : 'Audit log of all change requests submitted by employees or managers and HR unlock actions.' }}
                    </p>
                </div>

                @if($canRequestChange)
                    <button type="button" @click="showChangeRequestModal = true" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-amber-500/20 cursor-pointer self-start sm:self-auto">
                        <i class="ph ph-arrow-counter-clockwise text-base"></i>
                        <span>Request New Change</span>
                    </button>
                @endif
            </div>

            @if($appraisal->changeRequests->isEmpty())
                <div class="text-center py-10 space-y-2">
                    <i class="ph ph-shield-check text-3xl text-emerald-500"></i>
                    <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">
                        {{ $isEmployeeOnly ? 'No Changes Requested' : 'Clean Audit Trail' }}
                    </h4>
                    <p class="text-[11px] text-slate-400 max-w-sm mx-auto">
                        {{ $isEmployeeOnly ? 'No edit requests or unlock tickets have been submitted for this appraisal form.' : 'No change requests or unlock disputes have been recorded for this appraisal form.' }}
                    </p>
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
                    <textarea name="reason" rows="3" required minlength="10" 
                              @input="autoExpand($el)"
                              @focus="autoExpand($el)"
                              x-init="$nextTick(() => autoExpand($el))"
                              placeholder="State clearly which criteria or scores need to be adjusted and why..." 
                              class="auto-expand w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs resize-none overflow-hidden leading-relaxed min-h-[50px]"></textarea>
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
                    Unlocking restores edit permissions for the selected stage and sets a temporary extension window:
                </p>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Unlock Stage</label>
                    <select name="target_stage" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="pending_self_eval" {{ $appraisal->status === 'pending_self_eval' ? 'selected' : '' }}>Pending Self-Evaluation (Revert to Employee)</option>
                        @if($appraisal->workflow_type === 'three_tier' && $appraisal->reporting_manager_id)
                            <option value="pending_manager_eval" {{ in_array($appraisal->status, ['submitted_self_eval', 'pending_manager_eval']) ? 'selected' : '' }}>Pending Manager Review (Revert to Manager)</option>
                        @endif
                        <option value="pending_hod_eval" {{ $appraisal->status === 'pending_hod_eval' ? 'selected' : '' }}>Pending HOD Review (Revert to HOD)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Unlock Extension Duration (Days)</label>
                    <select name="extension_days" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="3">3 Days Extension</option>
                        <option value="5">5 Days Extension</option>
                        <option value="7" selected>7 Days Extension (Default)</option>
                        <option value="14">14 Days Extension</option>
                        <option value="30">30 Days Extension</option>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">HR Admin Note</label>
                    <textarea name="notes" rows="2" 
                              @input="autoExpand($el)"
                              @focus="autoExpand($el)"
                              x-init="$nextTick(() => autoExpand($el))"
                              placeholder="Audit note explaining the unlock reason..." 
                              class="auto-expand w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs resize-none overflow-hidden leading-relaxed min-h-[42px]"></textarea>
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
