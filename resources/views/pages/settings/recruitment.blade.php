<form action="{{ route('settings.save') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="active_module" value="recruitment">

    <!-- 1. Recruitment Pipeline & ATS -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-pink-50 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 flex items-center justify-center border border-pink-100 dark:border-pink-900/60">
                    <i class="ph ph-briefcase text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Applicant Tracking System (ATS) & Hiring Workflows</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Configure candidate recruitment pipelines, interview feedback deadlines & candidate communications</p>
                </div>
            </div>
            <span class="bg-pink-50 dark:bg-pink-950/60 text-pink-700 dark:text-pink-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-pink-200/60 dark:border-pink-900/60">ATS Pipeline</span>
        </div>

        <!-- Hiring Stages Visual -->
        <div class="space-y-2">
            <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">Default Candidate Pipeline Stages</h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 text-center text-xs font-bold">
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-black text-slate-400 block uppercase">Stage 1</span>
                    <span class="text-slate-900 dark:text-white">Applied</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-black text-blue-500 block uppercase">Stage 2</span>
                    <span class="text-slate-900 dark:text-white">Screening</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-black text-purple-500 block uppercase">Stage 3</span>
                    <span class="text-slate-900 dark:text-white">Technical Test</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-black text-amber-500 block uppercase">Stage 4</span>
                    <span class="text-slate-900 dark:text-white">Interview</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-black text-emerald-500 block uppercase">Stage 5</span>
                    <span class="text-slate-900 dark:text-white">Offer Letter</span>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] font-black text-cyan-500 block uppercase">Stage 6</span>
                    <span class="text-slate-900 dark:text-white">Hired / Onboard</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-semibold pt-2">
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Interview Feedback Deadline (Days)</label>
                <input type="number" name="interview_feedback_deadline_days" value="{{ $settingsRaw['interview_feedback_deadline_days'] ?? 3 }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
                <span class="text-[10px] text-slate-400 font-semibold mt-1 block">Maximum days for interview panel to submit rating & notes</span>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1 font-bold">Recruitment Notification Email</label>
                <input type="email" name="recruitment_auto_email" value="{{ $settingsRaw['recruitment_auto_email'] ?? 'careers@loops.lk' }}" class="w-full border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white p-2.5">
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3 text-xs font-bold text-slate-700 dark:text-slate-300">
            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="career_portal_public" value="1" {{ ($settingsRaw['career_portal_public'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Publish Open Vacancies to Careers Portal</span>
                    <span class="text-[11px] font-normal text-slate-400">Allow external applicants to browse job openings and submit CVs</span>
                </div>
            </label>

            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 dark:bg-[#1e2d4d] border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input type="checkbox" name="send_rejection_emails" value="1" {{ ($settingsRaw['send_rejection_emails'] ?? '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-pink-600 focus:ring-pink-500">
                <div>
                    <span class="text-slate-900 dark:text-white font-extrabold block">Auto-Send Respectful Candidate Feedback on Rejection</span>
                    <span class="text-[11px] font-normal text-slate-400">Send automated notification emails when an applicant is disqualified</span>
                </div>
            </label>
        </div>
    </div>

    <!-- Save Actions -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-pink-500/20 transition-all cursor-pointer">
            <i class="ph ph-floppy-disk text-base"></i>
            <span>Save Recruitment Settings</span>
        </button>
    </div>
</form>
