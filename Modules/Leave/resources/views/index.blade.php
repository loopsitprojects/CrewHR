@extends('layouts.app')

@section('header_title', 'Dashboard')

@section('content')
<!-- Alpine Data for Calendar Navigation -->
<div class="max-w-7xl mx-auto space-y-6" x-data="{ 
    currentMonth: {{ $month }}, 
    currentYear: {{ $year }},
    goToPrevious() {
        let date = new Date(this.currentYear, this.currentMonth - 2, 1);
        window.location.href = `?month=${String(date.getMonth() + 1).padStart(2, '0')}&year=${date.getFullYear()}`;
    },
    goToNext() {
        let date = new Date(this.currentYear, this.currentMonth, 1);
        window.location.href = `?month=${String(date.getMonth() + 1).padStart(2, '0')}&year=${date.getFullYear()}`;
    },
    goToToday() {
        let date = new Date();
        window.location.href = `?month=${String(date.getMonth() + 1).padStart(2, '0')}&year=${date.getFullYear()}`;
    }
}">

    <!-- Leave Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @php
            $icons = [
                'Annual Leave' => 'ph-airplane',
                'Casual Leave' => 'ph-calendar-check',
                'Medical Leave' => 'ph-first-aid',
                'Short Leave' => 'ph-clock',
                'Duty Leave' => 'ph-briefcase',
                'Lieu Leave' => 'ph-arrows-counter-clockwise',
            ];
            $colors = [
                'blue' => 'text-blue-600 border-blue-600',
                'purple' => 'text-purple-500 border-purple-500',
                'green' => 'text-green-500 border-green-500',
                'yellow' => 'text-yellow-500 border-yellow-500',
                'red' => 'text-red-500 border-red-500',
                'teal' => 'text-teal-500 border-teal-500',
            ];
        @endphp
        
        @foreach($balances as $balance)
            @php 
                $icon = $icons[$balance->leaveType->name] ?? 'ph-calendar';
                $colorCls = $colors[$balance->leaveType->color_code] ?? 'text-gray-500 border-gray-500';
                $textColor = explode(' ', $colorCls)[0];
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between {{ $textColor }} mb-4">
                    <span class="text-sm font-bold text-gray-900">{{ str_replace(' Leave', '', $balance->leaveType->name) }} Leave</span>
                    <i class="ph {{ $icon }} text-lg"></i>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full border-4 {{ $colorCls }} flex items-center justify-center text-xs font-bold text-gray-700">
                        {{ round($balance->allocated_days - $balance->used_days) }}/{{ round($balance->allocated_days) }}
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 font-bold uppercase">Available</div>
                        <div class="text-sm font-bold text-gray-900">
                            {{ round($balance->allocated_days - $balance->used_days) }} 
                            @if($balance->leaveType->name == 'Short Leave') 
                                <span class="text-xs font-normal text-gray-500">hrs/m</span> 
                            @else 
                                days 
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Calendar -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <!-- Calendar Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <i class="ph ph-calendar-blank text-2xl text-blue-600"></i>
                    <h2 class="text-lg font-bold text-gray-900">{{ $date->format('F Y') }}</h2>
                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ $holidayCount }} Holidays</span>
                    
                    <button class="bg-blue-600 text-white text-xs font-bold px-3 py-1.5 rounded flex items-center gap-1 shadow-sm ml-2">
                        <i class="ph ph-user"></i> Personal Calendar <i class="ph ph-caret-down ml-1 text-[10px]"></i>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="goToToday()" class="px-3 py-1 text-sm font-medium text-blue-600 bg-blue-50 rounded border border-blue-100 hover:bg-blue-100 cursor-pointer">Today</button>
                    <button @click="goToPrevious()" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded text-gray-600 hover:bg-gray-50 cursor-pointer"><i class="ph ph-caret-left"></i></button>
                    <button @click="goToNext()" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded text-gray-600 hover:bg-gray-50 cursor-pointer"><i class="ph ph-caret-right"></i></button>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden text-sm">
                <!-- Days Header -->
                <div class="bg-white p-2 text-center text-xs font-bold text-gray-500">Mon</div>
                <div class="bg-white p-2 text-center text-xs font-bold text-gray-500">Tue</div>
                <div class="bg-white p-2 text-center text-xs font-bold text-gray-500">Wed</div>
                <div class="bg-white p-2 text-center text-xs font-bold text-gray-500">Thu</div>
                <div class="bg-white p-2 text-center text-xs font-bold text-gray-500">Fri</div>
                <div class="bg-white p-2 text-center text-xs font-bold text-red-500">Sat</div>
                <div class="bg-white p-2 text-center text-xs font-bold text-red-500">Sun</div>
                
                <!-- Grid Cells -->
                @foreach($calendarDays as $cday)
                    @if(!$cday['is_current_month'])
                        <div class="bg-white h-24 p-2 opacity-50"></div>
                    @else
                        @php
                            $bgClass = 'bg-white';
                            $textClass = 'text-gray-900';
                            $borderClass = '';
                            $isToday = $cday['date'] == date('Y-m-d');
                            
                            if ($cday['is_weekend']) {
                                $bgClass = 'bg-red-50/30';
                                $textClass = 'text-red-500';
                            }
                            
                            if ($cday['holiday']) {
                                $bgClass = 'bg-orange-50';
                                $borderClass = 'border border-orange-200';
                            }
                            
                            if ($isToday) {
                                $bgClass = 'bg-blue-50';
                                $borderClass = 'border-2 border-blue-500 rounded';
                                $textClass = 'text-blue-700 font-bold';
                            }
                        @endphp
                        
                        <div class="{{ $bgClass }} h-24 p-2 font-medium {{ $textClass }} {{ $borderClass }} flex flex-col justify-between">
                            <div class="flex justify-between w-full">
                                <span>{{ $cday['day'] }}</span>
                                @if($cday['holiday'])
                                    <i class="ph ph-briefcase text-orange-500 text-xs mt-1" title="{{ $cday['holiday']->title }}"></i>
                                @endif
                            </div>
                            
                            @if($cday['leave'])
                                @php
                                    $leaveColor = $cday['leave']->leaveType->color_code;
                                    $indicatorClass = [
                                        'blue' => 'bg-blue-600', 'purple' => 'bg-purple-500', 
                                        'green' => 'bg-green-500', 'yellow' => 'bg-yellow-500', 
                                        'red' => 'bg-red-500', 'teal' => 'bg-teal-500'
                                    ][$leaveColor] ?? 'bg-gray-500';
                                @endphp
                                <div class="w-full flex justify-end">
                                    <div class="w-2 h-2 rounded-full {{ $indicatorClass }}" title="{{ $cday['leave']->leaveType->name }} - {{ $cday['leave']->status }}"></div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
            
            <!-- Legend -->
            <div class="flex items-center justify-between mt-4 text-[10px] font-bold tracking-wider uppercase text-gray-500">
                <div class="flex gap-4">
                    <span>Leaves:</span>
                    <span class="text-blue-600 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-blue-600"></div> Annual</span>
                    <span class="text-purple-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div> Casual</span>
                    <span class="text-green-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> Medical</span>
                    <span class="text-yellow-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-yellow-500"></div> Short</span>
                    <span class="text-red-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> Duty</span>
                    <span class="text-teal-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-teal-500"></div> Lieu</span>
                </div>
                <div class="flex gap-4">
                    <span class="text-red-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-red-500"></div> Company</span>
                    <span class="text-orange-500 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-orange-500"></div> Public</span>
                    <span class="text-gray-400 flex items-center gap-1"><div class="w-1.5 h-1.5 rounded-full bg-gray-300"></div> Weekend</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Actions & Schedule -->
        <div class="space-y-4">
            <!-- Top Actions -->
            <div class="flex gap-3">
                <button class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-xl shadow-sm flex justify-center items-center gap-2 transition-colors cursor-pointer" onclick="alert('Apply for Leave modal coming soon!')">
                    <i class="ph ph-plus-circle text-lg"></i> Apply for Leave
                </button>
                <button class="flex-1 bg-rose-500 hover:bg-rose-600 text-white font-bold py-3 px-4 rounded-xl shadow-sm flex justify-center items-center gap-2 transition-colors cursor-pointer" onclick="alert('Add Company Leave modal coming soon!')">
                    <i class="ph ph-buildings text-lg"></i> Add Company Leave
                </button>
            </div>

            <!-- Schedule Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center gap-2 mb-4">
                    <i class="ph ph-calendar-check text-blue-600 text-xl"></i>
                    <h3 class="font-bold text-gray-900">Schedule on {{ date('F d, Y') }}</h3>
                    <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded ml-1">Today</span>
                </div>

                <div class="mb-4">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Department Filter</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button class="px-3 py-1 bg-blue-600 text-white text-[10px] font-bold rounded-full">All</button>
                        <button class="px-3 py-1 bg-white border border-gray-200 text-gray-600 text-[10px] font-bold rounded-full hover:bg-gray-50">IT</button>
                        <button class="px-3 py-1 bg-white border border-gray-200 text-gray-600 text-[10px] font-bold rounded-full hover:bg-gray-50">Creative</button>
                        <button class="px-3 py-1 bg-white border border-gray-200 text-gray-600 text-[10px] font-bold rounded-full hover:bg-gray-50">HR/Admin</button>
                        <button class="px-3 py-1 bg-white border border-gray-200 text-gray-600 text-[10px] font-bold rounded-full hover:bg-gray-50">Finance</button>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl border border-gray-100 p-8 text-center mt-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 text-green-600 rounded-full mb-3">
                        <i class="ph ph-shield-check text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">Full Team Attendance</h4>
                    <p class="text-xs text-gray-500">All team members in all departments are active and present on {{ date('F d') }}.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
