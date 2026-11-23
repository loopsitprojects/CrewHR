@extends('layouts.app', ['title' => 'Loops HR - Appraisal KPI Templates', 'breadcrumb' => 'KPI Templates'])

@section('content')
@php
    $templateMap = [];
    foreach ($templates as $tmpl) {
        $templateMap[$tmpl->id] = [
            'id' => $tmpl->id,
            'title' => $tmpl->title,
            'description' => $tmpl->description,
            'applicable_type' => $tmpl->applicable_type,
            'designation_id' => $tmpl->designation_id,
            'department_id' => $tmpl->department_id,
            'designation_name' => $tmpl->designation?->name,
            'department_name' => $tmpl->department?->name,
            'is_active' => (bool)$tmpl->is_active,
            'toggle_url' => route('appraisal.templates.toggle', $tmpl->id),
            'criteria' => $tmpl->criteria->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'weightage' => (float)$c->weightage,
                ];
            })->values()->all(),
        ];
    }
@endphp

<div class="space-y-6" x-data="{
    templateMap: @js($templateMap),
    currentView: 'list', // 'list', 'view', 'edit', 'create'
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

    openView(tmpl) {
        const data = (typeof tmpl === 'object' && tmpl !== null) ? tmpl : (this.templateMap[tmpl] || null);
        if (!data) return;
        this.selectedTemplate = data;
        this.currentView = 'view';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    openViewModal(tmpl) {
        this.openView(tmpl);
    },

    openEdit(tmpl) {
        const data = (typeof tmpl === 'object' && tmpl !== null) ? tmpl : (this.templateMap[tmpl] || null);
        if (!data) return;
        this.selectedTemplate = data;
        this.editForm = {
            id: data.id,
            title: data.title,
            description: data.description || '',
            applicable_type: data.applicable_type || 'Designation',
            designation_id: data.designation_id ? String(data.designation_id) : '',
            department_id: data.department_id ? String(data.department_id) : '',
            criteria: (data.criteria || []).map(c => ({
                name: c.name,
                description: c.description || '',
                weightage: Number(c.weightage)
            }))
        };
        if (this.editForm.criteria.length === 0) {
            this.editForm.criteria.push({ name: '', description: '', weightage: 0 });
        }
        this.currentView = 'edit';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    openEditModal(tmpl) {
        this.openEdit(tmpl);
    },

    openCreate() {
        this.currentView = 'create';
        window.scrollTo({ top: 0, behavior: 'smooth' });
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

    <!-- ======================================================== -->
    <!-- VIEW 1: FULL SCREEN TEMPLATES LIST / HUB (DEFAULT)       -->
    <!-- ======================================================== -->
    <div x-show="currentView === 'list'" class="space-y-6">
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

                <button type="button" @click="openCreate()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 cursor-pointer">
                    <i class="ph ph-plus-circle text-base"></i>
                    <span>Create New Template</span>
                </button>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($templates as $tmpl)
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
                                <button type="button" @click="openView({{ $tmpl->id }})" class="p-1.5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="View Full Screen">
                                    <i class="ph ph-eye text-sm"></i>
                                </button>
                                <button type="button" @click="openEdit({{ $tmpl->id }})" class="p-1.5 text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Edit Full Screen">
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
                            <h3 @click="openView({{ $tmpl->id }})" class="text-base font-black text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer transition-colors">
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
                            <button type="button" @click="openView({{ $tmpl->id }})" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer flex items-center gap-1.5 shadow-sm">
                                <i class="ph ph-eye"></i> View
                            </button>
                            <button type="button" @click="openEdit({{ $tmpl->id }})" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-colors cursor-pointer flex items-center gap-1.5 shadow-sm">
                                <i class="ph ph-pencil-simple"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- VIEW 2: FULL SCREEN TEMPLATE VIEW                        -->
    <!-- ======================================================== -->
    <div x-show="currentView === 'view'" x-cloak class="space-y-6">
        <!-- Top Action & Navigation Bar -->
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="currentView = 'list'" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                        <i class="ph ph-arrow-left text-sm"></i>
                        <span>Back to Templates</span>
                    </button>
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-black text-slate-900 dark:text-white" x-text="selectedTemplate?.title"></h1>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                                  x-text="(selectedTemplate?.applicable_type || 'General') + ' Format'"></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider flex items-center gap-1"
                                  :class="selectedTemplate?.is_active ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="selectedTemplate?.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                <span x-text="selectedTemplate?.is_active ? 'Active & Assigned' : 'Disabled (Hidden)'"></span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 font-medium mt-1" x-text="selectedTemplate?.description || 'Standard performance criteria and competency evaluation matrix.'"></p>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <button type="button" @click="openEdit(selectedTemplate)" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="ph ph-pencil-simple text-sm"></i>
                        <span>Edit Template</span>
                    </button>
                </div>
            </div>

            <!-- Quick Info Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs pt-4 border-t border-slate-100 dark:border-slate-800">
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Classification</span>
                    <span class="font-extrabold text-slate-900 dark:text-white text-sm" x-text="selectedTemplate?.applicable_type + ' Format'"></span>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Target Scope</span>
                    <span class="font-extrabold text-blue-600 dark:text-blue-400 text-sm" x-text="selectedTemplate?.designation_name || selectedTemplate?.department_name || 'Organization-wide'"></span>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Criteria</span>
                    <span class="font-extrabold text-indigo-600 dark:text-indigo-400 text-sm" x-text="(selectedTemplate?.criteria?.length || 0) + ' Evaluation Areas'"></span>
                </div>
                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200/80 dark:border-slate-800 space-y-1">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Total Weightage</span>
                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-sm flex items-center gap-1.5">
                        <i class="ph ph-check-circle"></i>
                        <span>100% Balanced</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Detailed Evaluation Criteria Table (Full Width) -->
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">KPI Evaluation Criteria & Competency Weights</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Scored performance areas and percentage weightings applied during employee appraisal cycles.</p>
                </div>
                <span class="text-xs font-extrabold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-3.5 py-1.5 rounded-xl border border-blue-200 dark:border-blue-900/60 self-start sm:self-auto" x-text="(selectedTemplate?.criteria?.length || 0) + ' Competencies Configured'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] font-black uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200/80 dark:border-slate-800">
                        <tr>
                            <th class="p-4 pl-6 w-14 text-center">#</th>
                            <th class="p-4 w-1/3">Criteria & Competency Name</th>
                            <th class="p-4 w-1/2">Evaluation Description & Qualitative Standards</th>
                            <th class="p-4 pr-6 w-40 text-right">Weightage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(crit, idx) in (selectedTemplate?.criteria || [])" :key="idx">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 pl-6 text-center font-bold text-slate-400" x-text="idx + 1"></td>
                                <td class="p-4 align-top">
                                    <span class="font-extrabold text-slate-900 dark:text-white text-xs block" x-text="crit.name"></span>
                                </td>
                                <td class="p-4 align-top text-slate-500 dark:text-slate-400 leading-relaxed font-normal">
                                    <span x-text="crit.description || 'No qualitative description specified.'"></span>
                                </td>
                                <td class="p-4 pr-6 align-top text-right space-y-1.5">
                                    <span class="inline-block px-3 py-1 rounded-lg text-xs font-black bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-900/60" x-text="crit.weightage + '%'"></span>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-blue-600 h-full rounded-full" :style="'width: ' + crit.weightage + '%'"></div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-slate-50/80 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <span class="font-bold text-slate-500 dark:text-slate-400">Total Sum of Criteria Weightages:</span>
                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                    <i class="ph ph-check-circle text-base"></i>
                    <span>100% Balanced Formula</span>
                </span>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div class="flex items-center justify-between pt-2">
            <button type="button" @click="currentView = 'list'" class="p-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                <i class="ph ph-arrow-left text-sm"></i>
                <span>Back to All Templates</span>
            </button>

            <button type="button" @click="openEdit(selectedTemplate)" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                <i class="ph ph-pencil-simple text-sm"></i>
                <span>Edit This Template</span>
            </button>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- VIEW 3: FULL SCREEN TEMPLATE EDIT FORM                   -->
    <!-- ======================================================== -->
    <div x-show="currentView === 'edit'" x-cloak class="space-y-6">
        <!-- Top Action & Navigation Bar -->
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="currentView = 'list'" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                        <i class="ph ph-arrow-left text-sm"></i>
                        <span>Back to Templates</span>
                    </button>
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="ph ph-pencil-simple text-amber-500"></i>
                            <span>Edit Appraisal KPI Template</span>
                        </h1>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Update template configuration, applicable scope, and individual criteria weightages.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <button type="button" @click="currentView = 'list'" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="editTemplateForm" :disabled="Math.abs(editTotalWeightage - 100) > 0.01" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="ph ph-check"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Form Container -->
        <form id="editTemplateForm" :action="'{{ url('/appraisal-templates') }}/' + editForm.id" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Template Meta Card -->
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5 text-xs font-bold">
                <h2 class="text-sm font-black text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i class="ph ph-sliders text-blue-600"></i>
                    <span>Template Identity & Scope</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Template Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" x-model="editForm.title" required placeholder="e.g. Senior Software Engineer KPI Template" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Applicable Classification <span class="text-rose-500">*</span></label>
                        <select name="applicable_type" x-model="editForm.applicable_type" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                            <option value="Designation">Designation Specific</option>
                            <option value="Department">Department Specific</option>
                            <option value="Probation">Probationary Period</option>
                            <option value="General">General / Organization-wide</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Target Designation (Optional)</label>
                        <select name="designation_id" x-model="editForm.designation_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                            <option value="">Any Designation</option>
                            @foreach($designations as $des)
                                <option value="{{ $des->id }}">{{ $des->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Target Department (Optional)</label>
                        <select name="department_id" x-model="editForm.department_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                            <option value="">Any Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Template Description & Guidelines</label>
                    <textarea name="description" x-model="editForm.description" rows="3" placeholder="Context, applicability notes, and instructions for reviewers..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-normal"></textarea>
                </div>
            </div>

            <!-- Criteria Section Card -->
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="ph ph-scales text-indigo-600"></i>
                            <span>Evaluation Criteria & Weightage Allocation</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Specify performance dimensions and percentage weightages summing to exactly 100%.</p>
                    </div>
                    <button type="button" @click="addEditCriteria()" class="bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer self-start sm:self-auto">
                        <i class="ph ph-plus-circle text-base"></i>
                        <span>Add Criteria Area</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(row, idx) in editForm.criteria" :key="idx">
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/40 rounded-2xl border border-slate-200/80 dark:border-slate-800 flex flex-col md:flex-row items-start md:items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs font-black text-slate-500 shrink-0" x-text="idx + 1"></div>
                            
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Criteria Name <span class="text-rose-500">*</span></label>
                                    <input type="text" :name="'criteria['+idx+'][name]'" x-model="row.name" required placeholder="e.g. Code Quality & Performance" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Evaluation Standards / Description</label>
                                    <input type="text" :name="'criteria['+idx+'][description]'" x-model="row.description" placeholder="Description of expectations and standards..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-normal">
                                </div>
                            </div>

                            <div class="w-full md:w-36 flex items-center justify-between md:justify-end gap-2 pt-2 md:pt-0">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Weight %</label>
                                    <div class="relative">
                                        <input type="number" :name="'criteria['+idx+'][weightage]'" x-model="row.weightage" required min="1" max="100" class="w-24 border-slate-200 dark:border-slate-700 rounded-xl p-2.5 pr-7 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-black text-center">
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">%</span>
                                    </div>
                                </div>
                                <button type="button" @click="removeEditCriteria(idx)" :disabled="editForm.criteria.length <= 1" class="p-2.5 mt-4 text-slate-400 hover:text-rose-600 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Remove criteria">
                                    <i class="ph ph-trash text-base"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Weightage Indicator Bar -->
                <div class="p-4 rounded-2xl flex items-center justify-between text-xs font-black"
                     :class="Math.abs(editTotalWeightage - 100) < 0.01 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800'">
                    <div class="flex items-center gap-2">
                        <i :class="Math.abs(editTotalWeightage - 100) < 0.01 ? 'ph ph-check-circle text-lg text-emerald-600' : 'ph ph-warning-circle text-lg text-rose-600'"></i>
                        <span x-text="Math.abs(editTotalWeightage - 100) < 0.01 ? 'Weightage Balanced: Sum equals exactly 100%' : 'Weightage Imbalance: Total weightage must equal exactly 100% (currently ' + editTotalWeightage + '%)'"></span>
                    </div>
                    <div class="text-sm font-black" x-text="editTotalWeightage + '%'"></div>
                </div>

                <!-- Bottom Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="currentView = 'list'" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="Math.abs(editTotalWeightage - 100) > 0.01" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="ph ph-check"></i>
                        <span>Update KPI Template</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ======================================================== -->
    <!-- VIEW 4: FULL SCREEN CREATE NEW TEMPLATE FORM             -->
    <!-- ======================================================== -->
    <div x-show="currentView === 'create'" x-cloak class="space-y-6">
        <!-- Top Action & Navigation Bar -->
        <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" @click="currentView = 'list'" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer shadow-sm">
                        <i class="ph ph-arrow-left text-sm"></i>
                        <span>Back to Templates</span>
                    </button>
                    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 hidden sm:block"></div>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="ph ph-plus-circle text-blue-600"></i>
                            <span>Create Appraisal KPI Template</span>
                        </h1>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Define new criteria, map to roles, and assign percentage weightages.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <button type="button" @click="currentView = 'list'" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" form="createTemplateForm" :disabled="Math.abs(totalWeightage - 100) > 0.01" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="ph ph-check"></i>
                        <span>Save KPI Template</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create Form Container -->
        <form id="createTemplateForm" action="{{ route('appraisal.templates.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Template Meta Card -->
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5 text-xs font-bold">
                <h2 class="text-sm font-black text-slate-900 dark:text-white border-b border-slate-100 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i class="ph ph-sliders text-blue-600"></i>
                    <span>Template Identity & Scope</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Template Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Senior Software Engineer KPI Template" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Applicable Classification <span class="text-rose-500">*</span></label>
                        <select name="applicable_type" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                            <option value="Designation">Designation Specific</option>
                            <option value="Department">Department Specific</option>
                            <option value="Probation">Probationary Period</option>
                            <option value="General">General / Organization-wide</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Target Designation (Optional)</label>
                        <select name="designation_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                            <option value="">Any Designation</option>
                            @foreach($designations as $des)
                                <option value="{{ $des->id }}">{{ $des->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Target Department (Optional)</label>
                        <select name="department_id" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-bold">
                            <option value="">Any Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1.5">Template Description & Guidelines</label>
                    <textarea name="description" rows="3" placeholder="Context, applicability notes, and instructions for reviewers..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-3 bg-slate-50/80 dark:bg-slate-800/60 text-slate-900 dark:text-white text-xs font-normal"></textarea>
                </div>
            </div>

            <!-- Criteria Section Card -->
            <div class="bg-white dark:bg-[#111927] border border-slate-200/90 dark:border-slate-800 rounded-3xl p-6 sm:p-7 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="ph ph-scales text-indigo-600"></i>
                            <span>Evaluation Criteria & Weightage Allocation</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Specify performance dimensions and percentage weightages summing to exactly 100%.</p>
                    </div>
                    <button type="button" @click="addCriteria()" class="bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer self-start sm:self-auto">
                        <i class="ph ph-plus-circle text-base"></i>
                        <span>Add Criteria Area</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(row, idx) in criteriaRows" :key="idx">
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/40 rounded-2xl border border-slate-200/80 dark:border-slate-800 flex flex-col md:flex-row items-start md:items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs font-black text-slate-500 shrink-0" x-text="idx + 1"></div>
                            
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3 w-full">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Criteria Name <span class="text-rose-500">*</span></label>
                                    <input type="text" :name="'criteria['+idx+'][name]'" x-model="row.name" required placeholder="e.g. Code Quality & Architecture" class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Evaluation Standards / Description</label>
                                    <input type="text" :name="'criteria['+idx+'][description]'" x-model="row.description" placeholder="Description of expectations and standards..." class="w-full border-slate-200 dark:border-slate-700 rounded-xl p-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-normal">
                                </div>
                            </div>

                            <div class="w-full md:w-36 flex items-center justify-between md:justify-end gap-2 pt-2 md:pt-0">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Weight %</label>
                                    <div class="relative">
                                        <input type="number" :name="'criteria['+idx+'][weightage]'" x-model="row.weightage" required min="1" max="100" class="w-24 border-slate-200 dark:border-slate-700 rounded-xl p-2.5 pr-7 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-black text-center">
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">%</span>
                                    </div>
                                </div>
                                <button type="button" @click="removeCriteria(idx)" :disabled="criteriaRows.length <= 1" class="p-2.5 mt-4 text-slate-400 hover:text-rose-600 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" title="Remove criteria">
                                    <i class="ph ph-trash text-base"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Total Weightage Indicator Bar -->
                <div class="p-4 rounded-2xl flex items-center justify-between text-xs font-black"
                     :class="Math.abs(totalWeightage - 100) < 0.01 ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800'">
                    <div class="flex items-center gap-2">
                        <i :class="Math.abs(totalWeightage - 100) < 0.01 ? 'ph ph-check-circle text-lg text-emerald-600' : 'ph ph-warning-circle text-lg text-rose-600'"></i>
                        <span x-text="Math.abs(totalWeightage - 100) < 0.01 ? 'Weightage Balanced: Sum equals exactly 100%' : 'Weightage Imbalance: Total weightage must equal exactly 100% (currently ' + totalWeightage + '%)'"></span>
                    </div>
                    <div class="text-sm font-black" x-text="totalWeightage + '%'"></div>
                </div>

                <!-- Bottom Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="currentView = 'list'" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="Math.abs(totalWeightage - 100) > 0.01" class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        <i class="ph ph-check"></i>
                        <span>Save KPI Template</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

</div>
@endsection
