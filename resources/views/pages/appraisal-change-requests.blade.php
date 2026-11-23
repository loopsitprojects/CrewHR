@extends('layouts.app', ['title' => 'Loops HR - Appraisal Change Requests', 'breadcrumb' => 'Change Requests'])

@section('content')
<div class="space-y-6" x-data="{
    filterStatus: 'all',
    activeModal: false,
    selectedRequest: null,
    actionType: 'approve',
    notes: ''
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

    @if(session('info'))
        <div class="p-4 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-2xl flex items-center justify-between text-xs font-bold text-blue-800 dark:text-blue-300">
            <div class="flex items-center gap-2">
                <i class="ph ph-info text-lg text-blue-500"></i>
                <span>{{ session('info') }}</span>
            </div>
            <button @click="$el.parentElement.remove()" class="text-blue-500"><i class="ph ph-x"></i></button>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph ph-lock-key-open text-amber-500"></i>
                <span>{{ $role === 'Employee' ? 'My Changes Requested' : 'Appraisal Unlock & Change Requests' }}</span>
            </h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $role === 'Employee' ? 'Track formal edit and unlock requests submitted for your evaluation forms.' : 'Review formal edit requests submitted by employees or department managers for locked evaluation records.' }}
            </p>
        </div>

        <a href="{{ route('appraisal.index') }}" onclick="handleModuleBack(event, '{{ route('appraisal.index') }}')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5 self-start sm:self-auto cursor-pointer">
            <i class="ph ph-arrow-left"></i>
            <span>Back to Appraisal Hub</span>
        </a>
    </div>

    <!-- Requests Table / List -->
    <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        @if($requests->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 mx-auto flex items-center justify-center text-2xl">
                    <i class="ph ph-shield-check"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">No Pending Change Requests</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">There are currently no appraisal edit or unlock requests pending HR review.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="p-4 pl-6">Requester & Employee</th>
                            <th class="p-4">Appraisal Cycle</th>
                            <th class="p-4 w-1/3">Reason for Change</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Date</th>
                            <th class="p-4 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($requests as $req)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6">
                                    <div class="font-extrabold text-slate-900 dark:text-white">
                                        {{ $req->requestedBy?->name ?? 'User' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-semibold flex items-center gap-1 mt-0.5">
                                        <span>Role: <strong>{{ $req->role_at_request }}</strong></span>
                                        <span>&bull;</span>
                                        <span>For: {{ $req->appraisal?->employee?->user?->name }}</span>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                        {{ $req->appraisal?->cycle?->name }}
                                    </span>
                                </td>

                                <td class="p-4">
                                    <div class="bg-slate-50 dark:bg-slate-800/40 p-2.5 rounded-xl text-slate-700 dark:text-slate-300 font-normal leading-relaxed">
                                        {{ $req->reason }}
                                    </div>
                                    @if($req->action_notes)
                                        <div class="text-[10px] text-slate-400 mt-1 italic">
                                            HR Note: {{ $req->action_notes }} (by {{ $req->actionedBy?->name }})
                                        </div>
                                    @endif
                                </td>

                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black 
                                        {{ $req->status === 'Approved_Unlocked' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                        {{ $req->status === 'Rejected' ? 'bg-rose-100 text-rose-800' : '' }}
                                        {{ $req->status === 'Pending' ? 'bg-amber-100 text-amber-800 animate-pulse' : '' }}">
                                        {{ $req->status }}
                                    </span>
                                </td>

                                <td class="p-4 text-slate-400 font-semibold text-[11px]">
                                    {{ $req->created_at->format('d M Y, h:i A') }}
                                </td>

                                <td class="p-4 pr-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(in_array($role, ['Super Admin', 'HR Lead']) && $req->status === 'Pending')
                                            <button @click="selectedRequest = {{ $req->id }}; actionType = 'approve'; activeModal = true;" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1 shadow-sm cursor-pointer">
                                                <i class="ph ph-lock-key-open"></i>
                                                <span>Approve & Unlock</span>
                                            </button>

                                            <button @click="selectedRequest = {{ $req->id }}; actionType = 'reject'; activeModal = true;" class="bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 text-rose-600 border border-rose-200 dark:border-rose-900 px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1 cursor-pointer">
                                                <i class="ph ph-x"></i>
                                                <span>Decline</span>
                                            </button>
                                        @else
                                            <a href="{{ route('appraisal.show', $req->appraisal_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-bold text-xs">
                                                View Form &rarr;
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- MODAL: Action Change Request -->
    <div x-show="activeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div @click.away="activeModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-black text-slate-900 dark:text-white" x-text="actionType === 'approve' ? 'Approve Request & Unlock Appraisal' : 'Decline Change Request'"></h3>
                <button @click="activeModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x"></i></button>
            </div>

            <form :action="'/appraisal-change-requests/' + selectedRequest + '/action'" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                <input type="hidden" name="action" :value="actionType">

                <p class="text-slate-400 font-normal leading-relaxed" x-text="actionType === 'approve' ? 'Approving this request will immediately unlock the appraisal with an extension duration and notify the requester.' : 'Declining will keep the appraisal locked in its current submitted state.'"></p>

                <div x-show="actionType === 'approve'" class="space-y-3">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Unlock Duration (Days)</label>
                        <select name="extension_days" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="3">3 Days Extension</option>
                            <option value="5">5 Days Extension</option>
                            <option value="7" selected>7 Days Extension (Default)</option>
                            <option value="14">14 Days Extension</option>
                            <option value="30">30 Days Extension</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Revert to Stage</label>
                        <select name="target_stage" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="pending_self_eval" selected>Employee Self-Evaluation (Pending Self-Eval)</option>
                            <option value="pending_manager_eval">Manager Review (Pending Manager Eval)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">HR Feedback / Notes</label>
                    <textarea name="notes" rows="2" placeholder="Provide note for the requester..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-normal"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="activeModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" :class="actionType === 'approve' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'" class="text-white px-5 py-2 rounded-xl text-xs font-bold shadow-sm" x-text="actionType === 'approve' ? 'Confirm & Unlock' : 'Decline Request'"></button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
