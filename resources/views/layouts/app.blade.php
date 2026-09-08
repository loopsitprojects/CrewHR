<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'LOOPS HR - Enterprise Portal' }}</title>

    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" href="{{ asset('loops-icon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('loops-icon.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-screen overflow-hidden antialiased text-slate-800 bg-[#f1f5f9] dark:bg-[#0d1117] dark:text-slate-200 transition-colors duration-200"
      x-data="{ 
          role: '{{ session('current_role', 'Super (Admin)') }}', 
          theme: localStorage.getItem('theme') || 'light',
          sidebarCollapsed: localStorage.getItem('sidebarCollapsed') !== null ? (localStorage.getItem('sidebarCollapsed') === 'true') : false,
          activeContext: '{{ request()->routeIs('dashboard', 'leave.*', 'my-leaves.*', 'approvals.index', 'analytics.index') ? 'leave' : (request()->routeIs('appraisal.*') ? 'appraisal' : (request()->routeIs('employee.*', 'attendance.*', 'payroll.*', 'recruitment.*') ? 'employees' : 'none')) }}',
          openModules: {
              leave: {{ request()->routeIs('dashboard', 'leave.*', 'my-leaves.*', 'approvals.index', 'analytics.index') ? 'true' : 'false' }},
              employees: {{ request()->routeIs('employee.*', 'attendance.*') ? 'true' : 'false' }},
              payroll: {{ request()->routeIs('payroll.*') ? 'true' : 'false' }},
              recruitment: {{ request()->routeIs('recruitment.*') ? 'true' : 'false' }},
              performance: {{ request()->routeIs('appraisal.*') ? 'true' : 'false' }}
          },
          toggleModule(modKey) {
              this.openModules[modKey] = !this.openModules[modKey];
              this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
          },
          toggleTheme() {
              this.theme = this.theme === 'light' ? 'dark' : 'light';
              localStorage.setItem('theme', this.theme);
              if (this.theme === 'dark') {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
              this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
          },
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
              this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
          }
      }"
      x-init="if (theme === 'dark') document.documentElement.classList.add('dark'); else document.documentElement.classList.remove('dark');">

<div class="h-screen flex overflow-hidden">

    <!-- Collapsible Sidebar with Lucide Icons (Dual Theme: Light & Dark Mode Support) -->
    <aside :class="sidebarCollapsed ? 'w-16' : 'w-64'" class="bg-white dark:bg-[#0f172a] border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between h-screen shrink-0 z-20 shadow-2xl transition-all duration-300 ease-in-out">
        <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
            <!-- Sidebar Brand Header (Logo Click Navigates to Home Dashboard) -->
            <div class="h-16 flex items-center justify-center px-3 border-b border-slate-200 dark:border-slate-800/80 transition-all duration-300">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center justify-center w-full cursor-pointer group" 
                   title="Go to Home Dashboard">
                    <!-- Collapsed Mode: Icon Only -->
                    <img x-show="sidebarCollapsed" 
                         src="{{ asset('loops-icon.png') }}" 
                         class="w-9 h-9 object-contain shrink-0 group-hover:scale-105 transition-all" 
                         alt="LOOPS HR Icon">

                    <!-- Expanded Mode: Dark Logo in Light Mode, White Logo in Dark Mode -->
                    <img x-show="!sidebarCollapsed && theme !== 'dark'" x-cloak 
                         src="{{ asset('LoopsBlack.png') }}" 
                         class="h-11 w-auto max-w-[210px] object-contain shrink-0 group-hover:scale-105 transition-all" 
                         alt="LOOPS HR Logo">
                    <img x-show="!sidebarCollapsed && theme === 'dark'" x-cloak 
                         src="{{ asset('LoopsWhite.png') }}" 
                         class="h-11 w-auto max-w-[210px] object-contain shrink-0 group-hover:scale-105 transition-all" 
                         alt="LOOPS HR Logo">
                </a>
            </div>

@php
    $statusesPath = base_path('modules_statuses.json');
    $moduleStatuses = file_exists($statusesPath) ? json_decode(file_get_contents($statusesPath), true) : [];
    $isModuleActive = function($modName) use ($moduleStatuses) {
        return isset($moduleStatuses[$modName]) ? (bool)$moduleStatuses[$modName] : true;
    };

    $userSystemRole = session('current_role', Auth::user()?->employee?->system_role ?? 'Employee');
    $normalizedRole = match($userSystemRole) {
        'Manager (Team Approvals)', 'HOD / Manager' => 'Manager',
        'HR Lead' => 'HR Lead',
        'Employee' => 'Employee',
        default => 'Super Admin',
    };

    $permissionsPath = base_path('role_module_permissions.json');
    $rolePermissions = file_exists($permissionsPath) ? json_decode(file_get_contents($permissionsPath), true) : [];

    $canAccess = function($moduleKey) use ($normalizedRole, $rolePermissions) {
        if (empty($rolePermissions) || !isset($rolePermissions[$normalizedRole])) return true;
        return (bool)($rolePermissions[$normalizedRole][$moduleKey] ?? false);
    };
@endphp

            <!-- Prominent Grouped Navigation Links -->
            <nav class="p-2.5 space-y-3">
                <!-- Group 1: LEAVE MANAGEMENT SUITE -->
                @if(($isModuleActive('Leave') && $canAccess('Leave')) || $canAccess('Approvals') || $canAccess('Analytics'))
                @php $isLeaveActive = request()->routeIs('dashboard', 'leave.*', 'my-leaves.*', 'approvals.index', 'analytics.index'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('leave')" 
                            title="Leave Management"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isLeaveActive ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 shrink-0 {{ $isLeaveActive ? 'text-white' : 'text-blue-600 dark:text-sky-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-black uppercase tracking-wider {{ $isLeaveActive ? 'text-white' : 'text-slate-900 dark:text-white' }}">Leave Management</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isLeaveActive ? 'text-white' : 'text-slate-500 dark:text-slate-300' }}" :class="openModules.leave ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.leave" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-200 dark:border-slate-800/80 ml-3.5 mt-1'">
                        @if($isModuleActive('Leave') && $canAccess('Leave'))
                        <a href="{{ route('dashboard') }}" 
                           title="Leave Dashboard"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('dashboard', 'leave.dashboard') ? 'bg-blue-50 dark:bg-slate-800 text-blue-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 shrink-0 text-blue-600 dark:text-sky-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Dashboard</span>
                            @if(request()->routeIs('dashboard', 'leave.dashboard'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        <a href="{{ route('my-leaves.index') }}" 
                           title="My Leaves"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('my-leaves.*') ? 'bg-blue-50 dark:bg-slate-800 text-blue-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 shrink-0 text-blue-600 dark:text-sky-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">My Leaves & Calendar</span>
                            @if(request()->routeIs('my-leaves.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        @if($isModuleActive('Approvals') && $canAccess('Approvals'))
                        <a href="{{ route('approvals.index') }}" 
                           title="Approvals Workflow"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('approvals.index') ? 'bg-blue-50 dark:bg-slate-800 text-blue-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="check-square" class="w-3.5 h-3.5 shrink-0 text-blue-600 dark:text-sky-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Approvals</span>
                            @if(request()->routeIs('approvals.index'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        @if($isModuleActive('Analytics') && $canAccess('Analytics'))
                        <a href="{{ route('analytics.index') }}" 
                           title="Leave Analytics"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('analytics.index') ? 'bg-blue-50 dark:bg-slate-800 text-blue-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 shrink-0 text-blue-600 dark:text-sky-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Reports & Analytics</span>
                            @if(request()->routeIs('analytics.index'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 2: EMPLOYEES & WORKFORCE SUITE -->
                @if(($isModuleActive('Employee') && $canAccess('Employee')) || ($isModuleActive('Attendance') && $canAccess('Attendance')))
                @php $isEmployeesActive = request()->routeIs('employee.*', 'attendance.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('employees')" 
                            title="Employees & Workforce"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isEmployeesActive ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 shrink-0 {{ $isEmployeesActive ? 'text-white' : 'text-indigo-600 dark:text-cyan-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-black uppercase tracking-wider {{ $isEmployeesActive ? 'text-white' : 'text-slate-900 dark:text-white' }}">Employees & Workforce</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isEmployeesActive ? 'text-white' : 'text-slate-500 dark:text-slate-300' }}" :class="openModules.employees ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.employees" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-200 dark:border-slate-800/80 ml-3.5 mt-1'">
                        @if($isModuleActive('Employee') && $canAccess('Employee'))
                        <a href="{{ route('employee.index') }}" 
                           title="Employee Directory"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('employee.*') ? 'bg-indigo-50 dark:bg-slate-800 text-indigo-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="contact" class="w-3.5 h-3.5 shrink-0 text-indigo-600 dark:text-cyan-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Employee Directory</span>
                            @if(request()->routeIs('employee.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        @if($isModuleActive('Attendance') && $canAccess('Attendance'))
                        <a href="{{ route('attendance.index') }}" 
                           title="Attendance Tracking"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('attendance.*') ? 'bg-indigo-50 dark:bg-slate-800 text-indigo-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="clock" class="w-3.5 h-3.5 shrink-0 text-indigo-600 dark:text-cyan-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Attendance Tracking</span>
                            @if(request()->routeIs('attendance.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 3: PAYROLL & PAYSLIPS SUITE -->
                @if($isModuleActive('Payroll') && $canAccess('Payroll'))
                @php $isPayrollActive = request()->routeIs('payroll.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('payroll')" 
                            title="Payroll & Payslips"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isPayrollActive ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 shrink-0 {{ $isPayrollActive ? 'text-white' : 'text-emerald-600 dark:text-emerald-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-black uppercase tracking-wider {{ $isPayrollActive ? 'text-white' : 'text-slate-900 dark:text-white' }}">Payroll & Payslips</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isPayrollActive ? 'text-white' : 'text-slate-500 dark:text-slate-300' }}" :class="openModules.payroll ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.payroll" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-200 dark:border-slate-800/80 ml-3.5 mt-1'">
                        <a href="{{ route('payroll.index') }}" 
                           title="Payroll & Payslips"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.index') ? 'bg-emerald-50 dark:bg-slate-800 text-emerald-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Monthly Payroll Master</span>
                            @if(request()->routeIs('payroll.index'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('payroll.loans.index') }}" 
                           title="Loan Management Sub-Module"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.loans.*') ? 'bg-emerald-50 dark:bg-slate-800 text-emerald-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="landmark" class="w-3.5 h-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Loan Management</span>
                            @if(request()->routeIs('payroll.loans.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 4: RECRUITMENT ATS SUITE -->
                @if($isModuleActive('Recruitment') && $canAccess('Recruitment'))
                @php $isRecruitmentActive = request()->routeIs('recruitment.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('recruitment')" 
                            title="Recruitment (ATS)"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isRecruitmentActive ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="briefcase" class="w-4 h-4 shrink-0 {{ $isRecruitmentActive ? 'text-white' : 'text-purple-600 dark:text-fuchsia-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-black uppercase tracking-wider {{ $isRecruitmentActive ? 'text-white' : 'text-slate-900 dark:text-white' }}">Recruitment (ATS)</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isRecruitmentActive ? 'text-white' : 'text-slate-500 dark:text-slate-300' }}" :class="openModules.recruitment ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.recruitment" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-200 dark:border-slate-800/80 ml-3.5 mt-1'">
                        <a href="{{ route('recruitment.index') }}" 
                           title="Recruitment ATS"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('recruitment.*') ? 'bg-purple-50 dark:bg-slate-800 text-purple-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 shrink-0 text-purple-600 dark:text-fuchsia-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Recruitment (ATS)</span>
                            @if(request()->routeIs('recruitment.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 5: PERFORMANCE MANAGEMENT SUITE -->
                @if($isModuleActive('Performance') && $canAccess('Performance'))
                @php $isPerformanceActive = request()->routeIs('appraisal.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('performance')" 
                            title="Performance"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isPerformanceActive ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20' : 'hover:bg-slate-100 dark:hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="award" class="w-4 h-4 shrink-0 {{ $isPerformanceActive ? 'text-white' : 'text-amber-600 dark:text-yellow-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-black uppercase tracking-wider {{ $isPerformanceActive ? 'text-white' : 'text-slate-900 dark:text-white' }}">Performance</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isPerformanceActive ? 'text-white' : 'text-slate-500 dark:text-slate-300' }}" :class="openModules.performance ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.performance" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-200 dark:border-slate-800/80 ml-3.5 mt-1'">
                        <a href="{{ route('appraisal.index') }}" 
                           title="Appraisal System"
                           :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                           class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('appraisal.*') ? 'bg-amber-50 dark:bg-slate-800 text-amber-900 dark:text-white font-extrabold' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                            <i data-lucide="award" class="w-3.5 h-3.5 shrink-0 text-amber-600 dark:text-yellow-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Appraisal System</span>
                            @if(request()->routeIs('appraisal.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-emerald-500 dark:text-emerald-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
                @endif
            </nav>
        </div>

        <!-- Sidebar Footer Settings Button -->
        <div :class="sidebarCollapsed ? 'p-2 flex justify-center' : 'p-3'" class="border-t border-slate-200 dark:border-slate-800/80 shrink-0 bg-slate-50 dark:bg-[#0f172a]">
            <a href="{{ route('settings.index') }}" 
               title="Settings"
               :class="{ 'justify-center': sidebarCollapsed }"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 {{ request()->routeIs('settings.index') ? 'bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-200/70 dark:hover:bg-slate-800/70 hover:text-slate-900 dark:hover:text-white' }}">
                <i data-lucide="settings" class="w-5 h-5 shrink-0 {{ request()->routeIs('settings.index') ? 'text-white' : 'text-slate-700 dark:text-slate-200' }}"></i>
                <span x-show="!sidebarCollapsed" x-cloak class="truncate font-extrabold">Settings</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Canvas -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Header Bar (h-14, Deep Shadow) -->
        <header class="h-14 bg-white/90 dark:bg-[#0f172a]/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 px-5 flex items-center justify-between shrink-0 z-10 shadow-sm dark:shadow-none transition-colors duration-200">
            <!-- Left Controls: Sidebar Toggle + Welcome User Name + Dashboard Name -->
            <div class="flex items-center gap-3">
                <button @click="toggleSidebar()" class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all" title="Toggle Sidebar">
                    <i data-lucide="panel-left" class="w-5 h-5"></i>
                </button>

                <div class="flex items-center gap-2 text-xs font-bold">
                    <span class="text-slate-900 dark:text-white font-black text-sm tracking-tight">Welcome, {{ Auth::user()->name ?? 'User' }}</span>
                    <span class="text-slate-300 dark:text-slate-700">•</span>
                    
                    <!-- Structured Interactive Breadcrumbs Navigation -->
                    <nav class="flex items-center gap-1.5 bg-slate-100/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 px-3 py-1 rounded-xl border border-slate-200/80 dark:border-slate-700 text-xs font-semibold shadow-2xs">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-1 text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" title="Home Dashboard">
                            <i data-lucide="home" class="w-3.5 h-3.5"></i>
                        </a>

                        @hasSection('breadcrumbs')
                            @yield('breadcrumbs')
                        @else
                            @if(request()->routeIs('employee.create'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <a href="{{ route('employee.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400">Employees Directory</a> <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Add Employee</span>
                            @elseif(request()->routeIs('employee.edit'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <a href="{{ route('employee.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400">Employees Directory</a> <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Edit Profile</span>
                            @elseif(request()->routeIs('employee.show'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <a href="{{ route('employee.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400">Employees Directory</a> <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">View Profile</span>
                            @elseif(request()->routeIs('employee.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Employees Directory</span>
                            @elseif(request()->routeIs('my-leaves.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <a href="{{ route('dashboard') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400">Leave Management</a> <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">My Leave Records</span>
                            @elseif(request()->routeIs('approvals.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <a href="{{ route('dashboard') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400">Leave Management</a> <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Leave Approvals</span>
                            @elseif(request()->routeIs('analytics.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <a href="{{ route('dashboard') }}" class="text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400">Leave Management</a> <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Leave Analytics</span>
                            @elseif(request()->routeIs('attendance.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Attendance Tracking</span>
                            @elseif(request()->routeIs('payroll.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Payroll & Payslips</span>
                            @elseif(request()->routeIs('recruitment.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Recruitment ATS</span>
                            @elseif(request()->routeIs('appraisal.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Appraisal System</span>
                            @elseif(request()->routeIs('settings.*'))
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">System Settings</span>
                            @else
                                <span class="text-slate-300 dark:text-slate-600">/</span> <span class="text-blue-600 dark:text-blue-400 font-black">Leave Management</span>
                            @endif
                        @endif
                    </nav>
                </div>
            </div>

            <!-- Right Toolbar Controls: Dark/Light Mode Button + Notification Button + Sign Out Button -->
            <div class="flex items-center gap-2">
                <!-- Top Header Sign Out Button -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black transition-all shadow-md shadow-red-500/20 border border-red-500 group cursor-pointer"
                            title="Sign Out of Portal">
                        <i data-lucide="log-out" class="w-4 h-4 text-white group-hover:scale-110 transition-transform"></i>
                        <span>Sign Out</span>
                    </button>
                </form>

                <!-- Dark / Light Mode Toggle Button -->
                <button @click="toggleTheme()" 
                        class="p-2 text-slate-500 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800 hover:bg-slate-100 rounded-xl transition-all" 
                        title="Toggle Light/Dark Theme">
                    <i data-lucide="moon" class="w-5 h-5" x-show="theme === 'light'"></i>
                    <i data-lucide="sun" class="w-5 h-5 text-amber-500" x-show="theme === 'dark'" x-cloak></i>
                </button>
                <!-- Notification Bell Component with Slide-Over Panel -->
                <div class="relative" x-data="{ 
                    notificationsOpen: false, 
                    unreadCount: 0, 
                    notifications: [],
                    filterTab: 'all',
                    async loadNotifications() {
                        try {
                            const res = await fetch('{{ route('notifications.index') }}');
                            const data = await res.json();
                            this.unreadCount = data.unread_count;
                            this.notifications = data.notifications;
                        } catch (e) {}
                    },
                    async markAsRead(id, link) {
                        try {
                            await fetch(`/notifications/${id}/read`, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            await this.loadNotifications();
                            if (link) window.location.href = link;
                        } catch (e) {}
                    },
                    async markAllAsRead() {
                        try {
                            await fetch('{{ route('notifications.read-all') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            await this.loadNotifications();
                        } catch (e) {}
                    }
                }" x-init="loadNotifications()">
                    <button @click="notificationsOpen = true; loadNotifications(); $nextTick(() => lucide.createIcons())" 
                            class="p-2 text-slate-500 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all relative cursor-pointer"
                            title="Open Notifications Drawer">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <template x-if="unreadCount > 0">
                            <span class="absolute top-1 right-1 bg-rose-600 text-white font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center shadow-xs" x-text="unreadCount"></span>
                        </template>
                    </button>

                    <!-- Slide-Over Panel Teleported to Body Root for Full Viewport Drawer -->
                    <template x-teleport="body">
                        <div>
                            <!-- Slide-Over Backdrop Overlay -->
                            <div x-show="notificationsOpen" 
                                 x-transition:enter="transition opacity ease-out duration-300"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition opacity ease-in duration-200"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 @click="notificationsOpen = false" x-cloak
                                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[999]"></div>

                            <!-- Slide-Over Drawer (Sliding Smoothly from Right Edge) -->
                            <div x-show="notificationsOpen" 
                                 x-transition:enter="transition transform ease-out duration-300"
                                 x-transition:enter-start="translate-x-full"
                                 x-transition:enter-end="translate-x-0"
                                 x-transition:leave="transition transform ease-in duration-200"
                                 x-transition:leave-start="translate-x-0"
                                 x-transition:leave-end="translate-x-full"
                                 x-cloak 
                                 class="fixed inset-y-0 right-0 w-80 sm:w-[420px] bg-white dark:bg-[#0f172a] shadow-2xl z-[1000] flex flex-col border-l border-slate-200/90 dark:border-slate-800 overflow-hidden">
                                
                                <!-- Drawer Top Header -->
                                <div class="px-5 py-4 bg-[#0f172a] text-white flex items-center justify-between shrink-0 shadow-md border-b border-slate-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-600/30 border border-blue-500/40 text-blue-400 flex items-center justify-center font-bold">
                                            <i data-lucide="bell" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-black tracking-tight text-white">Notifications Center</h2>
                                            <template x-if="unreadCount > 0">
                                                <span class="text-[10px] text-blue-400 font-bold" x-text="unreadCount + ' Unread Messages'"></span>
                                            </template>
                                            <template x-if="unreadCount === 0">
                                                <span class="text-[10px] text-slate-400 font-medium">All caught up</span>
                                            </template>
                                        </div>
                                    </div>
                                    <button @click="notificationsOpen = false" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition-colors cursor-pointer">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <!-- Filter Tabs & Quick Action Bar -->
                                <div class="p-3 bg-slate-50 dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between shrink-0 text-xs">
                                    <div class="flex items-center gap-1 bg-white dark:bg-slate-800 p-1 rounded-full border border-slate-200/80 dark:border-slate-700">
                                        <button @click="filterTab = 'all'" :class="filterTab === 'all' ? 'bg-blue-600 text-white shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'" class="px-3.5 py-1 rounded-full transition-all text-[11px] cursor-pointer">All</button>
                                        <button @click="filterTab = 'unread'" :class="filterTab === 'unread' ? 'bg-blue-600 text-white shadow-2xs font-extrabold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold'" class="px-3.5 py-1 rounded-full transition-all text-[11px] cursor-pointer">Unread</button>
                                    </div>

                                    <button @click="markAllAsRead()" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center gap-1 hover:underline cursor-pointer">
                                        <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                                        <span>Mark all read</span>
                                    </button>
                                </div>

                                <!-- Drawer Body: Notification Items List -->
                                <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/80 p-3 space-y-1">
                                    <template x-for="item in notifications.filter(n => filterTab === 'all' || (filterTab === 'unread' && !n.is_read))" :key="item.id">
                                        <div @click="markAsRead(item.id, item.link)" 
                                             class="p-3.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/60 cursor-pointer transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-700 flex items-start gap-3 relative group"
                                             :class="!item.is_read ? 'bg-blue-50/40 dark:bg-blue-950/40 font-semibold border-blue-100/80 dark:border-blue-900/60 shadow-2xs' : 'opacity-80'">
                                            
                                            <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0" :class="!item.is_read ? 'bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-900/50' : 'bg-slate-300 dark:bg-slate-600'"></div>

                                            <div class="flex-1 space-y-1 min-w-0">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="text-xs font-black text-slate-900 dark:text-white truncate" x-text="item.title"></span>
                                                    <span class="text-[9px] text-slate-400 font-bold shrink-0" x-text="new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-snug" x-text="item.message"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="notifications.filter(n => filterTab === 'all' || (filterTab === 'unread' && !n.is_read)).length === 0">
                                        <div class="py-20 text-center space-y-2">
                                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                                                <i data-lucide="bell-off" class="w-6 h-6"></i>
                                            </div>
                                            <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">No Notifications</h4>
                                            <p class="text-[11px] text-slate-400">You are all caught up with your updates.</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </header>

        <!-- Notification Banner -->
        @if(session('success'))
            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-2.5 text-xs font-extrabold flex items-center justify-between shrink-0 shadow-sm">
                <div class="flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
        @endif

        <!-- Main Content Body -->
        <main class="flex-1 {{ request()->routeIs('dashboard') ? 'overflow-hidden p-3' : 'overflow-y-auto p-4 md:p-6' }} min-h-0 flex flex-col">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

</div>

<!-- Auto-initialize Lucide Icons -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (window.lucide) { lucide.createIcons(); }
    });
</script>

</body>
</html>
