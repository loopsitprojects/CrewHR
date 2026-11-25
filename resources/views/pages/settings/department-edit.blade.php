@extends('layouts.app', [
    'title' => 'Loops HR - ' . $department->name . ' Department',
    'breadcrumb' => 'Settings • Organization • ' . $department->name
])

@section('content')
<div class="space-y-6 w-full" x-data="{
    editingDesigId: null,
    editingDesigName: ''
}">

    @if(isset($errors) && $errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 rounded-2xl text-xs font-bold space-y-1 shadow-xs">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="ph ph-warning-circle text-base text-rose-600 dark:text-rose-400"></i>
                    <span>Please correct the errors below:</span>
                </div>
                <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75"><i class="ph ph-x text-sm"></i></button>
            </div>
            <ul class="list-disc list-inside pl-4 text-[11px] font-medium space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-[#152038] border border-slate-200/90 dark:border-slate-800 p-5 rounded-2xl shadow-sm">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('settings.index', ['module' => 'organization', 'tab' => 'general']) }}" 
               class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer">
                <i class="ph ph-arrow-left text-base"></i>
                <span>Back to Departments</span>
            </a>

            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>

            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ $department->name }}</h1>
                    <span class="bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 text-[10px] font-black px-2 py-0.5 rounded-md border border-indigo-200/80 dark:border-indigo-800/80">
                        {{ $department->code }}
                    </span>
                </div>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">
                    HOD: <strong class="text-slate-700 dark:text-slate-300">{{ $department->hod_name ?: 'Not Assigned' }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-xs font-extrabold px-3 py-1.5 rounded-xl border border-blue-200 dark:border-blue-900/60 flex items-center gap-1.5">
                <i class="ph ph-identification-badge text-sm"></i>
                <span>{{ $department->designations->count() }} Designations</span>
            </span>
            <span class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-extrabold px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
                <i class="ph ph-users text-sm"></i>
                <span>{{ $department->employees->count() }} Staff Members</span>
            </span>
        </div>
    </div>

    <!-- Main Content: Each section is a full-width horizontal row -->
    <div class="space-y-6">

        <!-- Row 1: Department Information -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-900/60">
                        <i class="ph ph-tree-structure text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Department Information</h2>
                        <p class="text-[11px] font-semibold text-slate-400">Configure organizational unit attributes</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('settings.department.update', $department->id) }}" method="POST" class="space-y-4 text-xs font-bold">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Department Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $department->name) }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-3 focus:ring-2 focus:ring-indigo-500/20" required>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Department Code <span class="text-rose-500">*</span></label>
                        <input type="text" name="code" value="{{ old('code', $department->code) }}" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold uppercase p-3 focus:ring-2 focus:ring-indigo-500/20" required>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Head of Department (HOD)</label>
                        <input type="text" name="hod_name" value="{{ old('hod_name', $department->hod_name) }}" placeholder="e.g. Arosh Perera" class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold p-3 focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Department responsibilities, functions, and mandate..." class="w-full border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-medium p-3 resize-none leading-relaxed">{{ old('description', $department->description) }}</textarea>
                </div>

                <div class="pt-2 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl shadow-md shadow-indigo-500/20 cursor-pointer transition-all">
                        Save Department
                    </button>

                    <button type="button" 
                            onclick="if(confirm('Delete department {{ addslashes($department->name) }}? All data will be permanently removed.')) { document.getElementById('delete-dept-form').submit(); }"
                            class="text-xs font-bold text-rose-500 hover:text-rose-700 hover:underline cursor-pointer">
                        Delete Department
                    </button>
                </div>
            </form>

            <form id="delete-dept-form" action="{{ route('settings.department.destroy', $department->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>

        <!-- Row 2: Department Designations (Full CRUD) -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-900/60">
                        <i class="ph ph-identification-badge text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">
                            Department Designations ({{ $department->designations->count() }})
                        </h2>
                        <p class="text-[11px] font-semibold text-slate-400">Add, rename, and manage roles within {{ $department->name }}</p>
                    </div>
                </div>

                <!-- Create / Add Designation Form in Row Header -->
                <form action="{{ route('settings.designation.store') }}" method="POST" class="flex items-center gap-2 sm:max-w-md w-full">
                    @csrf
                    <input type="hidden" name="department_id" value="{{ $department->id }}">
                    <input type="text" 
                           name="name" 
                           placeholder="Add new designation (e.g. Senior Developer)..." 
                           class="flex-1 min-w-0 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold px-3 py-2.5 focus:ring-2 focus:ring-blue-500/20" 
                           required>
                    <button type="submit" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-xl shadow-sm flex items-center gap-1.5 shrink-0 cursor-pointer transition-all">
                        <i class="ph ph-plus-circle text-sm"></i>
                        <span>Add Role</span>
                    </button>
                </form>
            </div>

            <!-- Designations List: Displayed in horizontal grid across the row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @forelse($department->designations as $desig)
                    <div>
                        <!-- Normal Display Card -->
                        <div x-show="editingDesigId !== {{ $desig->id }}" class="flex items-center justify-between gap-2.5 p-3.5 bg-slate-50/70 dark:bg-slate-800/40 rounded-xl border border-slate-200/70 dark:border-slate-800/80 hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></div>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white block truncate" title="{{ $desig->name }}">{{ $desig->name }}</span>
                                    <span class="text-[10px] text-slate-400">ID: #{{ $desig->id }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ ($desig->employees_count ?? $desig->employees->count()) > 0 ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900/60' : 'bg-slate-100 dark:bg-slate-800 text-slate-400' }}">
                                    {{ $desig->employees_count ?? $desig->employees->count() }} Staff
                                </span>

                                <button type="button" 
                                        @click="editingDesigId = {{ $desig->id }}; editingDesigName = '{{ addslashes($desig->name) }}'" 
                                        class="p-1.5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors" 
                                        title="Rename Designation">
                                    <i class="ph ph-pencil-simple text-sm"></i>
                                </button>

                                <form action="{{ route('settings.designation.destroy', $desig->id) }}" method="POST" onsubmit="return confirm('Delete designation {{ addslashes($desig->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer transition-colors" 
                                            title="Delete Designation">
                                        <i class="ph ph-trash text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Inline Edit Card -->
                        <div x-show="editingDesigId === {{ $desig->id }}" x-cloak class="p-3 bg-blue-50/40 dark:bg-blue-950/20 rounded-xl border border-blue-300 dark:border-blue-800">
                            <form action="{{ route('settings.designation.update', $desig->id) }}" method="POST" class="flex items-center gap-1.5">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="department_id" value="{{ $department->id }}">
                                <input type="text" 
                                       name="name" 
                                       x-model="editingDesigName" 
                                       class="flex-1 min-w-0 border-slate-300 dark:border-slate-700 bg-white dark:bg-[#1e2d4d] text-slate-900 dark:text-white rounded-xl text-xs font-bold px-2.5 py-1.5" 
                                       required>
                                <button type="submit" class="px-2.5 py-1.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 cursor-pointer shadow-xs shrink-0">
                                    Save
                                </button>
                                <button type="button" @click="editingDesigId = null" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-slate-200 cursor-pointer shrink-0">
                                    Cancel
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 space-y-1">
                        <i class="ph ph-identification-card text-2xl text-slate-400"></i>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No designations in this department yet.</p>
                        <p class="text-[11px] text-slate-400">Use the form above to add the first job title.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Row 3: Department Assigned Staff -->
        <div class="bg-white dark:bg-[#152038] rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-900/60">
                        <i class="ph ph-users text-base"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">
                            Assigned Team Members ({{ $department->employees->count() }})
                        </h2>
                        <p class="text-[11px] font-semibold text-slate-400">Active personnel assigned to {{ $department->name }}</p>
                    </div>
                </div>
                <a href="{{ route('employee.index') }}" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 shrink-0">
                    <span>View in Employee Directory</span>
                    <i class="ph ph-arrow-up-right text-xs"></i>
                </a>
            </div>

            <!-- Team Members Grid across the row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                @forelse($department->employees as $emp)
                    <div class="p-3 bg-slate-50/70 dark:bg-slate-800/40 rounded-xl border border-slate-200/70 dark:border-slate-800 flex items-center justify-between gap-3 hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-xs">
                                {{ strtoupper(substr($emp->user?->name ?? 'U', 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <span class="text-xs font-bold text-slate-900 dark:text-white block truncate">{{ $emp->user?->name ?? 'Staff' }}</span>
                                <span class="text-[10px] font-semibold text-slate-400 truncate block">{{ $emp->designation?->name ?? 'No Designation' }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 shrink-0">
                            {{ $emp->employee_id_number }}
                        </span>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 space-y-1">
                        <i class="ph ph-user-minus text-2xl text-slate-400"></i>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300">No employees currently assigned to {{ $department->name }}.</p>
                        <p class="text-[11px] text-slate-400">Assign employees to this department in the Employee Directory.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
