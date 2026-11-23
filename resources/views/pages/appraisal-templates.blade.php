@extends('layouts.app', ['title' => 'Loops HR - Appraisal KPI Templates', 'breadcrumb' => 'KPI Templates'])

@section('content')
<div class="space-y-6" x-data="{
    showNewTemplateModal: false,
    showViewModal: false,
    showEditModal: false,
    selectedTemplate: null,
    editForm: {
        id: null,
        title: '',
        description: '',
        applicable_type: 'Designation',
        designation_id: '',
        department_id: '',
        criteria: []
    },
    criteriaRows: [
        { name: 'Core Technical Competencies', description: 'Domain knowledge and execution standards', weightage: 30 },
        { name: 'Quality of Deliverables', description: 'Accuracy, thoroughness, and reliability of output', weightage: 25 },
        { name: 'Problem Solving & Initiative', description: 'Proactive troubleshooting and solution engineering', weightage: 25 },
        { name: 'Communication & Teamwork', description: 'Collaboration, feedback receptiveness, and culture fit', weightage: 20 }
    ],

    get totalWeightage() {
        return this.criteriaRows.reduce((sum, r) => sum + (parseFloat(r.weightage) || 0), 0);
    },

    get editTotalWeightage() {
        return (this.editForm.criteria || []).reduce((sum, r) => sum + (parseFloat(r.weightage) || 0), 0);
    },

    addCriteria() {
        this.criteriaRows.push({ name: '', description: '', weightage: 0 });
    },

    removeCriteria(index) {
        if (this.criteriaRows.length > 1) {
            this.criteriaRows.splice(index, 1);
        }
    },

    openViewModal(tmpl) {
        this.selectedTemplate = tmpl;
        this.showViewModal = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    openEditModal(tmpl) {
        this.selectedTemplate = tmpl;
        this.editForm = {
            id: tmpl.id,
            title: tmpl.title,
            description: tmpl.description || '',
            applicable_type: tmpl.applicable_type || 'Designation',
            designation_id: tmpl.designation_id ? String(tmpl.designation_id) : '',
            department_id: tmpl.department_id ? String(tmpl.department_id) : '',
            criteria: (tmpl.criteria || []).map(c => ({
                name: c.name,
                description: c.description || '',
                weightage: Number(c.weightage)
            }))
        };
        if (this.editForm.criteria.length === 0) {
            this.editForm.criteria.push({ name: '', description: '', weightage: 0 });
        }
        this.showViewModal = false;
        this.showEditModal = true;
        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    addEditCriteria() {
        this.editForm.criteria.push({ name: '', description: '', weightage: 0 });
    },

    removeEditCriteria(index) {
        if (this.editForm.criteria.length > 1) {
            this.editForm.criteria.splice(index, 1);
        }
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

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <i class="ph ph-sliders-horizontal text-blue-600"></i>
                <span>Appraisal Criteria & KPI Templates</span>
            </h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">
                Define customized appraisal formats per designation, department, or probation status with weighted criteria summing to 100%.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('appraisal.index') }}" onclick="handleModuleBack(event, '{{ route('appraisal.index') }}')" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                <i class="ph ph-arrow-left"></i>
                <span>Back to Hub</span>
            </a>

            <button @click="showNewTemplateModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer">
                <i class="ph ph-plus-circle text-base"></i>
                <span>Create New Template</span>
            </button>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $tmpl)
            @php
                $tmplData = [
                    'id' => $tmpl->id,
                    'title' => $tmpl->title,
                    'description' => $tmpl->description,
                    'applicable_type' => $tmpl->applicable_type,
                    'designation_id' => $tmpl->designation_id,
                    'department_id' => $tmpl->department_id,
                    'designation_name' => $tmpl->designation?->name,
                    'department_name' => $tmpl->department?->name,
                    'criteria' => $tmpl->criteria->map(function($c) {
                        return [
                            'id' => $c->id,
                            'name' => $c->name,
                            'description' => $c->description,
                            'weightage' => (float)$c->weightage,
                        ];
                    })->values()->all(),
                ];
                $tmplJson = htmlspecialchars(json_encode($tmplData), ENT_QUOTES, 'UTF-8');
            @endphp
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4 hover:border-blue-300 dark:hover:border-blue-600/50 transition-all flex flex-col justify-between group">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                                {{ $tmpl->applicable_type === 'Designation' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200' : '' }}
                                {{ $tmpl->applicable_type === 'Department' ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200' : '' }}
                                {{ $tmpl->applicable_type === 'Probation' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200' : '' }}
                                {{ $tmpl->applicable_type === 'General' ? 'bg-slate-100 text-slate-700 border border-slate-200' : '' }}">
                                {{ $tmpl->applicable_type }} Format
                            </span>

                            <form action="{{ route('appraisal.templates.toggle', $tmpl->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-black tracking-wider transition-all cursor-pointer flex items-center gap-1 {{ $tmpl->is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300 hover:bg-emerald-100' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300 hover:bg-slate-200' }}" title="{{ $tmpl->is_active ? 'Enabled - Click to disable' : 'Disabled - Click to enable & populate for employees' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $tmpl->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $tmpl->is_active ? 'Active' : 'Disabled' }}</span>
                                </button>
                            </form>
                        </div>

                        <!-- Action Icons on Card Header (View, Edit, Delete) -->
                        <div class="flex items-center gap-1">
                            <button type="button" @click="openViewModal({{ $tmplJson }})" class="p-1.5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="View Template Details">
                                <i class="ph ph-eye text-sm"></i>
                            </button>
                            <button type="button" @click="openEditModal({{ $tmplJson }})" class="p-1.5 text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Edit Template">
                                <i class="ph ph-pencil-simple text-sm"></i>
                            </button>
                            <form action="{{ route('appraisal.templates.destroy', $tmpl->id) }}" method="POST" onsubmit="return confirm('Delete this template?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Delete Template">
                                    <i class="ph ph-trash text-sm"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div>
                        <h3 @click="openViewModal({{ $tmplJson }})" class="text-base font-black text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer transition-colors">
                            {{ $tmpl->title }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-1 line-clamp-2">{{ $tmpl->description ?? 'Standard performance criteria template.' }}</p>
                    </div>

                    @if($tmpl->designation || $tmpl->department)
                        <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 flex items-center gap-2">
                            <i class="ph ph-tag"></i>
                            <span>Mapped to: <strong>{{ $tmpl->designation?->name ?? $tmpl->department?->name }}</strong></span>
                        </div>
                    @endif

                    <!-- Criteria list -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                        <div class="text-[10px] font-black uppercase text-slate-400 flex items-center justify-between">
                            <span>Evaluation Criteria ({{ $tmpl->criteria->count() }})</span>
                            <span>Weightage</span>
                        </div>
                        @foreach($tmpl->criteria as $crit)
                            <div class="flex items-center justify-between text-xs py-1 border-b border-dashed border-slate-100 dark:border-slate-800/60">
                                <span class="font-semibold text-slate-700 dark:text-slate-300 truncate max-w-[200px]" title="{{ $crit->name }}">{{ $crit->name }}</span>
                                <span class="font-extrabold text-blue-600 dark:text-blue-400 shrink-0">{{ number_format($crit->weightage, 0) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] font-bold">
                    <form action="{{ route('appraisal.templates.toggle', $tmpl->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center gap-1.5 transition-colors cursor-pointer {{ $tmpl->is_active ? 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700' : 'text-slate-400 hover:text-slate-600' }}">
                            <i class="ph {{ $tmpl->is_active ? 'ph-toggle-right text-base text-emerald-500' : 'ph-toggle-left text-base text-slate-400' }}"></i>
                            <span>{{ $tmpl->is_active ? 'Active & Assigned' : 'Disabled (Hidden)' }}</span>
                        </button>
                    </form>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="openViewModal({{ $tmplJson }})" class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer flex items-center gap-1">
                            <i class="ph ph-eye"></i> View
                        </button>
                        <button type="button" @click="openEditModal({{ $tmplJson }})" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-colors cursor-pointer flex items-center gap-1">
                            <i class="ph ph-pencil-simple"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- MODAL 1: VIEW TEMPLATE DETAILS -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div @click.away="showViewModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-5 my-8">
            <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <i class="ph ph-sliders-horizontal text-xl"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black text-slate-900 dark:text-white" x-text="selectedTemplate?.title"></h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                  x-text="(selectedTemplate?.applicable_type || 'General') + ' Format'"></span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium mt-0.5" x-text="selectedTemplate?.description || 'Standard performance criteria template.'"></p>
                    </div>
                </div>
                <button @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"><i class="ph ph-x text-lg"></i></button>
            </div>

            <!-- Mapping Metadata Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Classification</span>
                    <span class="font-extrabold text-slate-900 dark:text-white" x-text="selectedTemplate?.applicable_type"></span>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Mapped Scope</span>
                    <span class="font-extrabold text-blue-600 dark:text-blue-400" x-text="selectedTemplate?.designation_name || selectedTemplate?.department_name || 'Organization-wide'"></span>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 col-span-2 sm:col-span-1">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Criteria Count</span>
                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400" x-text="(selectedTemplate?.criteria?.length || 0) + ' Evaluation Areas'"></span>
                </div>
            </div>

            <!-- Detailed Criteria Breakdown List -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-black uppercase tracking-wider text-slate-400 pb-1">
                    <span>Evaluation Criteria</span>
                    <span>Weight</span>
                </div>

                <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <template x-for="(crit, idx) in (selectedTemplate?.criteria || [])" :key="idx">
                        <div class="p-3 bg-slate-50/80 dark:bg-slate-800/40 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 space-y-1.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white" x-text="crit.name"></span>
                                    <p class="text-[11px] text-slate-400 font-normal mt-0.5" x-text="crit.description || 'No specific description provided.'"></p>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-black bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200/70 dark:border-blue-800 shrink-0" x-text="crit.weightage + '%'"></span>
                            </div>

                            <!-- Weight Share Bar -->
                            <div class="w-full bg-slate-200/70 dark:bg-slate-700/60 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full rounded-full" :style="'width: ' + crit.weightage + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer Bar with Total and Action -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <i class="ph ph-check-circle text-base"></i>
                    <span>Total Weight: 100% (Balanced)</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showViewModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Close
                    </button>
                    <button type="button" @click="openEditModal(selectedTemplate)" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="ph ph-pencil-simple"></i>
                        <span>Edit Template</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: EDIT KPI TEMPLATE -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div @click.away="showEditModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-5 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i class="ph ph-pencil-simple text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Edit Appraisal KPI Template</h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Update criteria names, descriptions, and weightages</p>
                    </div>
                </div>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form :action="'{{ url('/appraisal-templates') }}/' + editForm.id" method="POST" class="space-y-5 text-xs font-bold">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Template Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" x-model="editForm.title" required placeholder="e.g. Senior Frontend Engineer Template" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Applicable Classification <span class="text-rose-500">*</span></label>
                        <select name="applicable_type" x-model="editForm.applicable_type" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="Designation">Designation Specific</option>
                            <option value="Department">Department Specific</option>
                            <option value="Probation">Probationary Period</option>
                            <option value="General">General / Standard</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Designation (Optional)</label>
                        <select name="designation_id" x-model="editForm.designation_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">Any Designation</option>
                            @foreach($designations as $des)
                                <option value="{{ $des->id }}">{{ $des->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Department (Optional)</label>
                        <select name="department_id" x-model="editForm.department_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">Any Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Template Description</label>
                    <textarea name="description" x-model="editForm.description" rows="2" placeholder="Brief context or guidelines for this appraisal template..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-normal resize-none"></textarea>
                </div>

                <!-- Dynamic Criteria Rows in Edit Modal -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-slate-700 dark:text-slate-300 text-xs font-black uppercase tracking-wider">Evaluation Criteria & Weightages</label>
                        <button type="button" @click="addEditCriteria()" class="text-blue-600 dark:text-blue-400 hover:underline text-xs font-extrabold flex items-center gap-1">
                            <i class="ph ph-plus"></i> Add Criteria
                        </button>
                    </div>

                    <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                        <template x-for="(row, idx) in editForm.criteria" :key="idx">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 flex items-start gap-2.5">
                                <div class="flex-1 space-y-2">
                                    <input type="text" :name="'criteria['+idx+'][name]'" x-model="row.name" required placeholder="Criteria Name (e.g. Code Quality)" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                                    <input type="text" :name="'criteria['+idx+'][description]'" x-model="row.description" placeholder="Description / Evaluation standards" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-[11px] font-normal">
                                </div>
                                <div class="w-24">
                                    <label class="block text-[10px] text-slate-400 uppercase">Weight %</label>
                                    <input type="number" :name="'criteria['+idx+'][weightage]'" x-model="row.weightage" required min="1" max="100" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-black text-center">
                                </div>
                                <button type="button" @click="removeEditCriteria(idx)" class="text-slate-400 hover:text-rose-600 p-2 mt-4"><i class="ph ph-x"></i></button>
                            </div>
                        </template>
                    </div>

                    <!-- Total Weightage Live Validation Indicator -->
                    <div class="p-3 rounded-2xl flex items-center justify-between text-xs font-black"
                         :class="Math.abs(editTotalWeightage - 100) < 0.01 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800'">
                        <div class="flex items-center gap-1.5">
                            <i :class="Math.abs(editTotalWeightage - 100) < 0.01 ? 'ph ph-check-circle' : 'ph ph-warning-circle'" class="text-base"></i>
                            <span x-text="Math.abs(editTotalWeightage - 100) < 0.01 ? 'Weightage Balanced Exactly 100%' : 'Total weightage must equal 100%'"></span>
                        </div>
                        <div>
                            <span>Current Total: </span>
                            <span x-text="editTotalWeightage + '%'"></span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" :disabled="Math.abs(editTotalWeightage - 100) > 0.01" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md shadow-blue-500/20">
                        Update KPI Template
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: CREATE NEW KPI TEMPLATE -->
    <div x-show="showNewTemplateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div @click.away="showNewTemplateModal = false" class="bg-white dark:bg-[#111927] border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-5 my-8">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center">
                        <i class="ph ph-sliders-horizontal text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Create Appraisal KPI Template</h3>
                        <p class="text-[11px] text-slate-400 font-semibold">Define criteria and assign percentage weightages</p>
                    </div>
                </div>
                <button @click="showNewTemplateModal = false" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form action="{{ route('appraisal.templates.store') }}" method="POST" class="space-y-5 text-xs font-bold">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Template Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Senior Frontend Engineer Template" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Applicable Classification <span class="text-rose-500">*</span></label>
                        <select name="applicable_type" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="Designation">Designation Specific</option>
                            <option value="Department">Department Specific</option>
                            <option value="Probation">Probationary Period</option>
                            <option value="General">General / Standard</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Designation (Optional)</label>
                        <select name="designation_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">Any Designation</option>
                            @foreach($designations as $des)
                                <option value="{{ $des->id }}">{{ $des->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Department (Optional)</label>
                        <select name="department_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">Any Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Template Description</label>
                    <textarea name="description" rows="2" placeholder="Brief context or guidelines for this appraisal template..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-normal resize-none"></textarea>
                </div>

                <!-- Dynamic Criteria Rows -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-slate-700 dark:text-slate-300 text-xs font-black uppercase tracking-wider">Evaluation Criteria & Weightages</label>
                        <button type="button" @click="addCriteria()" class="text-blue-600 dark:text-blue-400 hover:underline text-xs font-extrabold flex items-center gap-1">
                            <i class="ph ph-plus"></i> Add Criteria
                        </button>
                    </div>

                    <div class="space-y-2.5 max-h-64 overflow-y-auto pr-1">
                        <template x-for="(row, idx) in criteriaRows" :key="idx">
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 flex items-start gap-2.5">
                                <div class="flex-1 space-y-2">
                                    <input type="text" :name="'criteria['+idx+'][name]'" x-model="row.name" required placeholder="Criteria Name (e.g. Code Quality)" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                                    <input type="text" :name="'criteria['+idx+'][description]'" x-model="row.description" placeholder="Description / Evaluation standards" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-[11px] font-normal">
                                </div>
                                <div class="w-24">
                                    <label class="block text-[10px] text-slate-400 uppercase">Weight %</label>
                                    <input type="number" :name="'criteria['+idx+'][weightage]'" x-model="row.weightage" required min="1" max="100" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-black text-center">
                                </div>
                                <button type="button" @click="removeCriteria(idx)" class="text-slate-400 hover:text-rose-600 p-2 mt-4"><i class="ph ph-x"></i></button>
                            </div>
                        </template>
                    </div>

                    <!-- Total Weightage Live Validation Indicator -->
                    <div class="p-3 rounded-2xl flex items-center justify-between text-xs font-black"
                         :class="Math.abs(totalWeightage - 100) < 0.01 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800'">
                        <div class="flex items-center gap-1.5">
                            <i :class="Math.abs(totalWeightage - 100) < 0.01 ? 'ph ph-check-circle' : 'ph ph-warning-circle'" class="text-base"></i>
                            <span x-text="Math.abs(totalWeightage - 100) < 0.01 ? 'Weightage Balanced Exactly 100%' : 'Total weightage must equal 100%'"></span>
                        </div>
                        <div>
                            <span>Current Total: </span>
                            <span x-text="totalWeightage + '%'"></span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showNewTemplateModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" :disabled="Math.abs(totalWeightage - 100) > 0.01" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md shadow-blue-500/20">
                        Save KPI Template
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
