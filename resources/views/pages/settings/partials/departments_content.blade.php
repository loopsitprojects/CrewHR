<div class="space-y-4">
    <!-- 1. Organization Departments Overview -->
    <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-sm space-y-3.5">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-900/60 shrink-0">
                    <i class="ph ph-tree-structure text-lg"></i>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Organization Departments & Functional Teams ({{ count($departments) }})</h2>
                    <p class="text-[11px] font-semibold text-slate-400">Manage internal departmental units, department codes, and department heads (HODs)</p>
                </div>
            </div>
            <button type="button" @click="addDeptModal = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 active:scale-95 shadow-md shadow-indigo-500/25 transition-all cursor-pointer">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Add Department</span>
            </button>
        </div>

        <!-- Department Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
            @forelse($departments as $dept)
                <div class="group border border-slate-200/90 dark:border-slate-800 rounded-xl p-3 sm:p-3.5 bg-white dark:bg-[#1a2642] hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-sm transition-all flex flex-col justify-between space-y-2.5">
                    <div class="space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('settings.department.edit', $dept->id) }}" class="text-xs font-black text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-snug truncate block hover:underline">
                                    {{ $dept->name }}
                                </a>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate leading-tight">{{ $dept->description ?? 'Functional department unit within Loops HR.' }}</p>
                            </div>
                            <div class="flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-opacity shrink-0">
                                <a href="{{ route('settings.department.edit', $dept->id) }}" class="p-1 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Edit Department & Designations">
                                    <i class="ph ph-pencil-simple text-sm"></i>
                                </a>
                                <form action="{{ route('settings.department.destroy', $dept->id) }}" method="POST" onsubmit="return confirm('Delete department {{ addslashes($dept->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Delete Department">
                                        <i class="ph ph-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Footer: Head of Dept & Roles Count -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] font-semibold">
                        <span class="text-slate-400 flex items-center gap-1"><i class="ph ph-user text-[11px]"></i> Head of Dept:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-200 truncate max-w-[130px]">{{ $dept->hod_name ?? 'Super Admin' }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-8 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 space-y-1.5">
                    <i class="ph ph-tree-structure text-2xl text-slate-400"></i>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No departments configured yet.</p>
                    <button type="button" @click="addDeptModal = true" class="text-indigo-600 text-xs font-bold hover:underline">Add the first department</button>
                </div>
            @endforelse
        </div>
    </div>
</div>
