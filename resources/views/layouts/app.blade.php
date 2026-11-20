<!DOCTYPE html>
<html lang="en" class="h-full bg-[#EEF2F6] dark:bg-[#070B14] overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Crew HR - Enterprise Portal' }}</title>

    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/x-icon" href="{{ app_icon() }}">
    <link rel="icon" type="image/png" href="{{ app_icon() }}">
    <link rel="apple-touch-icon" href="{{ app_icon() }}">

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

    <!-- Canvas Confetti for Celebrations -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "Plus Jakarta Sans", system-ui, sans-serif;
            letter-spacing: -0.012em;
        }
        [x-cloak] { display: none !important; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="h-screen overflow-hidden antialiased text-slate-800 bg-[#EEF2F6] dark:bg-[#070B14] dark:text-slate-100 transition-colors duration-200"
      x-data="{ 
          role: '{{ session('current_role', 'Super (Admin)') }}', 
          theme: localStorage.getItem('theme') || 'light',
          sidebarCollapsed: localStorage.getItem('sidebarCollapsed') !== null ? (localStorage.getItem('sidebarCollapsed') === 'true') : false,
          activeContext: '{{ request()->routeIs('dashboard', 'leave.*', 'my-leaves.*', 'approvals.index', 'analytics.index') ? 'leave' : (request()->routeIs('appraisal.*') ? 'appraisal' : (request()->routeIs('employee.*', 'attendance.*', 'payroll.*', 'recruitment.*') ? 'employees' : 'none')) }}',
          openModules: {
              leave: {{ request()->routeIs('dashboard', 'leave.*', 'my-leaves.*', 'my-usage-records.*', 'approvals.index', 'analytics.index') ? 'true' : 'false' }},
              employees: {{ request()->routeIs('employee.*', 'attendance.*') ? 'true' : 'false' }},
              payroll: {{ request()->routeIs('payroll.*') ? 'true' : 'false' }},
              recruitment: {{ request()->routeIs('recruitment.*') ? 'true' : 'false' }},
              performance: {{ request()->routeIs('appraisal.*') ? 'true' : 'false' }},
              settings: {{ request()->routeIs('settings.*') ? 'true' : 'false' }}
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

<!-- Top Navigation & Page Loading Progress Indicator -->
<div id="top-progress-bar" aria-hidden="true"></div>

<div class="h-screen flex overflow-hidden">

    <!-- Collapsible Sidebar with Lucide Icons (Dual Theme: Light & Dark Mode Support) -->
    <aside :class="sidebarCollapsed ? 'w-16' : 'w-64'" class="bg-[#0b132b] dark:bg-[#070e1b] border-r border-slate-800/80 flex flex-col justify-between h-screen shrink-0 z-20 shadow-2xl transition-all duration-300 ease-in-out">
        <div class="flex-1 flex flex-col min-h-0 overflow-y-auto">
            <!-- Sidebar Brand Header (Logo Click Navigates to Home Dashboard) -->
            <div class="h-16 flex items-center justify-center px-4 border-b border-slate-800/80 transition-all duration-300">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center justify-center gap-2.5 w-full cursor-pointer group" 
                   title="Go to Home Dashboard">
                    <!-- Collapsed Mode: Icon Only -->
                    <img x-show="sidebarCollapsed" 
                         src="{{ app_icon() }}" 
                         class="w-8 h-8 rounded-xl object-contain shrink-0 group-hover:scale-105 transition-all mx-auto shadow-sm" 
                         alt="{{ app_setting('company_name', 'Loops HR') }} Icon">

                    <!-- Expanded Mode: Brand Logo -->
                    <div x-show="!sidebarCollapsed" class="flex items-center justify-center w-full">
                        <img src="{{ app_logo('dark') }}" 
                             class="h-9 w-auto max-w-[190px] object-contain shrink-0 group-hover:scale-105 transition-all mx-auto" 
                             alt="{{ app_setting('company_name', 'Loops HR') }} Logo">
                    </div>
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
                @php $isLeaveActive = request()->routeIs('dashboard', 'leave.*', 'my-leaves.*', 'my-usage-records.*', 'approvals.index', 'analytics.index'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('leave')" 
                            title="Leave Management"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isLeaveActive ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4 shrink-0 {{ $isLeaveActive ? 'text-white' : 'text-blue-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-bold uppercase tracking-wider {{ $isLeaveActive ? 'text-white' : 'text-slate-300' }}">Leave Management</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isLeaveActive ? 'text-white' : 'text-slate-400' }}" :class="openModules.leave ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.leave" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-800/80 ml-3.5 mt-1'">
                        @if($isModuleActive('Leave') && $canAccess('Leave'))
                        <a href="{{ route('dashboard') }}" 
                            title="Leave Dashboard"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('dashboard', 'leave.dashboard') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="layout-dashboard" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Dashboard</span>
                            @if(request()->routeIs('dashboard', 'leave.dashboard'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        <a href="{{ route('my-usage-records.index') }}" 
                            title="My Usage Records"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('my-usage-records.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="history" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">My Usage Records</span>
                            @if(request()->routeIs('my-usage-records.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        @if($isModuleActive('Approvals') && $canAccess('Approvals'))
                        <a href="{{ route('approvals.index') }}" 
                            title="Leave Approvals"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('approvals.index') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="check-square" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Approvals</span>
                            @if(request()->routeIs('approvals.index'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        @if($isModuleActive('Analytics') && $canAccess('Analytics'))
                        <a href="{{ route('analytics.index') }}" 
                            title="Leave Analytics"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('analytics.index') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Reports & Analytics</span>
                            @if(request()->routeIs('analytics.index'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
                <div class="border-t border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 2: EMPLOYEES & WORKFORCE SUITE -->
                @if(($isModuleActive('Employee') && $canAccess('Employee')) || ($isModuleActive('Attendance') && $canAccess('Attendance')))
                @php $isEmployeesActive = request()->routeIs('employee.*', 'attendance.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('employees')" 
                            title="Employees & Workforce"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isEmployeesActive ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 shrink-0 {{ $isEmployeesActive ? 'text-white' : 'text-indigo-400 dark:text-cyan-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-bold uppercase tracking-wider {{ $isEmployeesActive ? 'text-white' : 'text-slate-300' }}">Employees & Workforce</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isEmployeesActive ? 'text-white' : 'text-slate-400' }}" :class="openModules.employees ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.employees" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-800/80 ml-3.5 mt-1'">
                        @if($isModuleActive('Employee') && $canAccess('Employee'))
                        <a href="{{ route('employee.index') }}" 
                            title="Employee Directory"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('employee.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="contact" class="w-3.5 h-3.5 shrink-0 text-indigo-400 dark:text-cyan-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Employee Directory</span>
                            @if(request()->routeIs('employee.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        @if($isModuleActive('Attendance') && $canAccess('Attendance'))
                        <a href="{{ route('attendance.index') }}" 
                            title="Attendance Tracking"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('attendance.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="clock" class="w-3.5 h-3.5 shrink-0 text-indigo-400 dark:text-cyan-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Attendance Tracking</span>
                            @if(request()->routeIs('attendance.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif
                    </div>
                </div>
                <div class="border-t border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 3: PAYROLL & PAYSLIPS SUITE -->
                @if($isModuleActive('Payroll') && $canAccess('Payroll'))
                @php $isPayrollActive = request()->routeIs('payroll.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('payroll')" 
                            title="Payroll & Payslips"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isPayrollActive ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 shrink-0 {{ $isPayrollActive ? 'text-white' : 'text-blue-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-bold uppercase tracking-wider {{ $isPayrollActive ? 'text-white' : 'text-slate-300' }}">Payroll & Payslips</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isPayrollActive ? 'text-white' : 'text-slate-400' }}" :class="openModules.payroll ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.payroll" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-800/80 ml-3.5 mt-1'">
                        @if($normalizedRole !== 'Employee')
                        <a href="{{ route('payroll.index') }}" 
                            title="Monthly Payroll Master Register"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.index') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Payroll Master</span>
                            @if(request()->routeIs('payroll.index'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        <a href="{{ route('payroll.my_payslips') }}" 
                            title="My Monthly Payslips"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.my_payslips') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="receipt" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">My Payslips</span>
                            @if(request()->routeIs('payroll.my_payslips'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('payroll.advances.index') }}" 
                            title="Salary Advances & Applications"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.advances.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="hand-coins" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Salary Advances</span>
                            @if(request()->routeIs('payroll.advances.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('payroll.overtime.index') }}" 
                            title="Overtime Claims & Tracking"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.overtime.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="clock" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Overtime</span>
                            @if(request()->routeIs('payroll.overtime.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('payroll.loans.index') }}" 
                            title="Staff Loans & EMI Schedules"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('payroll.loans.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="landmark" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Loan Management</span>
                            @if(request()->routeIs('payroll.loans.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
                <div class="border-t border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 4: RECRUITMENT ATS SUITE -->
                @if($isModuleActive('Recruitment') && $canAccess('Recruitment'))
                @php $isRecruitmentActive = request()->routeIs('recruitment.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('recruitment')" 
                            title="Recruitment (ATS)"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isRecruitmentActive ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="briefcase" class="w-4 h-4 shrink-0 {{ $isRecruitmentActive ? 'text-white' : 'text-purple-400 dark:text-fuchsia-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-bold uppercase tracking-wider {{ $isRecruitmentActive ? 'text-white' : 'text-slate-300' }}">Recruitment (ATS)</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isRecruitmentActive ? 'text-white' : 'text-slate-400' }}" :class="openModules.recruitment ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.recruitment" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-800/80 ml-3.5 mt-1'">
                        <a href="{{ route('recruitment.index') }}" 
                            title="Recruitment ATS"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('recruitment.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 shrink-0 text-purple-400 dark:text-fuchsia-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Recruitment (ATS)</span>
                            @if(request()->routeIs('recruitment.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
                <div class="border-t border-slate-800/80 mx-2 my-1.5"></div>
                @endif

                <!-- Group 5: PERFORMANCE MANAGEMENT SUITE -->
                @if($isModuleActive('Performance') && $canAccess('Performance'))
                @php $isPerformanceActive = request()->routeIs('appraisal.*'); @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('performance')" 
                            title="Performance"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 {{ $isPerformanceActive ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <div class="flex items-center gap-2">
                            <i data-lucide="award" class="w-4 h-4 shrink-0 {{ $isPerformanceActive ? 'text-white' : 'text-amber-400 dark:text-yellow-400' }}"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-bold uppercase tracking-wider {{ $isPerformanceActive ? 'text-white' : 'text-slate-300' }}">Performance</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 {{ $isPerformanceActive ? 'text-white' : 'text-slate-400' }}" :class="openModules.performance ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.performance" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-800/80 ml-3.5 mt-1'">
                        <a href="{{ route('appraisal.index') }}" 
                            title="Appraisal System"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('appraisal.index', 'appraisal.show') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="award" class="w-3.5 h-3.5 shrink-0 text-amber-400 dark:text-yellow-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Appraisal System</span>
                            @if(request()->routeIs('appraisal.index', 'appraisal.show'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        <a href="{{ route('appraisal.templates.index') }}" 
                            title="KPI Templates"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('appraisal.templates.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="sliders" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">KPI Templates</span>
                            @if(request()->routeIs('appraisal.templates.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        <a href="{{ route('appraisal.change-requests.index') }}" 
                            title="Change Requests"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('appraisal.change-requests.*') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="lock" class="w-3.5 h-3.5 shrink-0 text-rose-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Change Requests</span>
                            @if(request()->routeIs('appraisal.change-requests.*'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        <a href="{{ route('appraisal.analytics') }}" 
                            title="Analytics & Reports"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ request()->routeIs('appraisal.analytics') ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="bar-chart-2" class="w-3.5 h-3.5 shrink-0 text-emerald-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Appraisal Analytics</span>
                            @if(request()->routeIs('appraisal.analytics'))
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
                @endif

                <div class="border-t border-slate-800/80 mx-2 my-1.5"></div>

                <!-- Group 6: SYSTEM & MODULE SETTINGS SUITE -->
                @php 
                    $isSettingsActive = request()->routeIs('settings.*'); 
                    $currSettingsModule = request()->route('module') ?? request()->query('module', ($normalizedRole === 'Employee' ? 'profile' : 'organization'));
                    $isSettingsAdminOrHr = in_array($normalizedRole, ['HR Lead', 'Super Admin']);
                    $isOrgSystemActive = $isSettingsActive && in_array($currSettingsModule, ['organization', 'general', 'system']);
                @endphp
                <div>
                    <button type="button" 
                            @click="toggleModule('settings')" 
                            title="Settings & System Hub"
                            :class="{ 'justify-center': sidebarCollapsed }"
                            class="w-full flex items-center justify-between px-2.5 py-2 rounded-xl transition-all text-left group cursor-pointer mb-1 text-slate-300 hover:text-white hover:bg-slate-800/60">
                        <div class="flex items-center gap-2">
                            <i data-lucide="settings" class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-white"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="text-[11px] font-bold uppercase tracking-wider text-slate-300 group-hover:text-white">Settings</span>
                        </div>
                        <i x-show="!sidebarCollapsed" x-cloak data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200 text-slate-400 group-hover:text-white" :class="openModules.settings ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="openModules.settings" x-collapse x-cloak :class="sidebarCollapsed ? 'space-y-1.5 py-1' : 'space-y-1 pl-2 border-l-2 border-slate-800/80 ml-3.5 mt-1'">
                        @if($isSettingsAdminOrHr)
                        <a href="{{ route('settings.index', ['module' => 'organization']) }}" 
                            title="Organization & System"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isOrgSystemActive ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="building-2" class="w-3.5 h-3.5 shrink-0 text-blue-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Organization & System</span>
                            @if($isOrgSystemActive)
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('settings.index', ['module' => 'leave']) }}" 
                            title="Leave Management"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'leave' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="calendar-check" class="w-3.5 h-3.5 shrink-0 text-emerald-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Leave Management</span>
                            @if($isSettingsActive && $currSettingsModule === 'leave')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('settings.index', ['module' => 'roles']) }}" 
                            title="Roles & Access Control"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'roles' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 shrink-0 text-purple-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Roles & Access Control</span>
                            @if($isSettingsActive && $currSettingsModule === 'roles')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('settings.index', ['module' => 'attendance']) }}" 
                            title="Attendance & Shifts"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'attendance' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="clock" class="w-3.5 h-3.5 shrink-0 text-cyan-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Attendance & Shifts</span>
                            @if($isSettingsActive && $currSettingsModule === 'attendance')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('settings.index', ['module' => 'payroll']) }}" 
                            title="Payroll & Tax"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'payroll' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="banknote" class="w-3.5 h-3.5 shrink-0 text-amber-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Payroll & Tax</span>
                            @if($isSettingsActive && $currSettingsModule === 'payroll')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('settings.index', ['module' => 'recruitment']) }}" 
                            title="Recruitment & ATS"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'recruitment' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 shrink-0 text-pink-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Recruitment & ATS</span>
                            @if($isSettingsActive && $currSettingsModule === 'recruitment')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>

                        <a href="{{ route('settings.index', ['module' => 'performance']) }}" 
                            title="Performance & Appraisal"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'performance' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="award" class="w-3.5 h-3.5 shrink-0 text-orange-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Performance & Appraisal</span>
                            @if($isSettingsActive && $currSettingsModule === 'performance')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                        @endif

                        <a href="{{ route('settings.index', ['module' => 'profile']) }}" 
                            title="My Account & Security"
                            :class="sidebarCollapsed ? 'w-9 h-9 mx-auto justify-center p-0 rounded-xl' : 'px-2.5 py-1.5 rounded-lg text-xs gap-2.5'"
                            class="flex items-center font-semibold transition-all duration-200 {{ $isSettingsActive && $currSettingsModule === 'profile' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                            <i data-lucide="user-cog" class="w-3.5 h-3.5 shrink-0 text-rose-400"></i>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">My Account & Security</span>
                            @if($isSettingsActive && $currSettingsModule === 'profile')
                                <i x-show="!sidebarCollapsed" x-cloak data-lucide="check" class="w-4 h-4 text-blue-400 ml-auto shrink-0 stroke-[3]"></i>
                            @endif
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </aside>

    <!-- Main Content Canvas -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        
        <!-- Header Bar (Apple Liquid Glass Header) -->
        <header class="h-16 bg-white/95 dark:bg-[#0B101D]/85 backdrop-blur-xl border-b border-slate-200/90 dark:border-white/[0.08] px-5 flex items-center justify-between shrink-0 z-10 transition-colors duration-200 shadow-[0_1px_3px_0_rgba(15,23,42,0.04)] dark:shadow-none">
            <!-- Left Controls: Sidebar Toggle Button & Page Title -->
            <div class="flex items-center gap-3.5 min-w-0">
                <button @click="toggleSidebar()" class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10 rounded-xl transition-all active:scale-95 cursor-pointer shrink-0" title="Toggle Sidebar">
                    <i data-lucide="panel-left" class="w-5 h-5"></i>
                </button>
                @hasSection('header_left')
                    @yield('header_left')
                @elseif(isset($pageTitle) || isset($breadcrumb))
                    <div class="h-5 w-px bg-slate-200 dark:bg-white/10 hidden sm:block shrink-0"></div>
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight leading-none truncate">
                        {{ $pageTitle ?? $breadcrumb }}
                    </h1>
                @endif
            </div>

            <!-- Right Toolbar Controls: User Avatar + Bell + Theme + Sign Out -->
            <div class="flex items-center gap-2.5 shrink-0">
                @hasSection('header_actions')
                    <div class="flex items-center gap-2.5 border-r border-slate-200 dark:border-white/[0.08] pr-4 mr-4">
                        @yield('header_actions')
                    </div>
                @endif
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
                    },
                    async clearAll() {
                        try {
                            await fetch('{{ route('notifications.clear-all') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            await this.loadNotifications();
                        } catch (e) {}
                    },
                    async clearNotification(id) {
                        try {
                            await fetch(`/notifications/${id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            await this.loadNotifications();
                        } catch (e) {}
                    }
                }" x-init="loadNotifications()">
                    <button @click="notificationsOpen = true; loadNotifications(); $nextTick(() => lucide.createIcons())" 
                            class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10 rounded-xl transition-all relative cursor-pointer active:scale-95"
                            title="Open Notifications Drawer">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <template x-if="unreadCount > 0">
                            <span class="absolute top-1 right-1 bg-[#FF3B30] text-white font-black text-[9px] w-4 h-4 rounded-full flex items-center justify-center shadow-xs" x-text="unreadCount"></span>
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
                                 class="fixed inset-y-0 right-0 w-80 sm:w-[420px] bg-white dark:bg-[#111827] shadow-2xl z-[1000] flex flex-col border-l border-slate-200/90 dark:border-white/[0.08] overflow-hidden">
                                
                                <!-- Drawer Top Header -->
                                <div class="px-5 py-4 bg-slate-50 dark:bg-[#0B101D] text-slate-900 dark:text-white flex items-center justify-between shrink-0 border-b border-slate-200/80 dark:border-white/[0.08]">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-500/15 border border-blue-500/25 text-[#007AFF] dark:text-[#0A84FF] flex items-center justify-center font-bold">
                                            <i data-lucide="bell" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-sm font-bold tracking-tight text-slate-900 dark:text-white">Notifications</h2>
                                            <template x-if="unreadCount > 0">
                                                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold" x-text="unreadCount + ' Unread'"></span>
                                            </template>
                                            <template x-if="unreadCount === 0">
                                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">All caught up</span>
                                            </template>
                                        </div>
                                    </div>
                                    <button @click="notificationsOpen = false" class="p-1.5 text-slate-400 hover:text-slate-800 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 transition-colors cursor-pointer">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <!-- Filter Tabs & Quick Action Bar -->
                                <div class="p-3 bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-200/80 dark:border-white/[0.08] flex items-center justify-between shrink-0 text-xs">
                                    <div class="flex items-center gap-1 bg-slate-200/70 dark:bg-white/10 p-0.5 rounded-xl border border-black/[0.04] dark:border-white/[0.06]">
                                        <button @click="filterTab = 'all'" :class="filterTab === 'all' ? 'bg-white dark:bg-[#1A2338] text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-3 py-1 rounded-lg transition-all text-[11px] cursor-pointer">All</button>
                                        <button @click="filterTab = 'unread'" :class="filterTab === 'unread' ? 'bg-white dark:bg-[#1A2338] text-slate-900 dark:text-white shadow-xs font-bold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-3 py-1 rounded-lg transition-all text-[11px] cursor-pointer">Unread</button>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button @click="markAllAsRead()" class="text-[11px] font-semibold text-[#007AFF] dark:text-[#0A84FF] hover:underline flex items-center gap-1 cursor-pointer transition-colors" title="Mark all as read">
                                            <i data-lucide="check-check" class="w-3.5 h-3.5"></i>
                                            <span>Mark read</span>
                                        </button>

                                        <button @click="clearAll()" 
                                                x-show="notifications.length > 0"
                                                class="text-[11px] font-semibold text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 flex items-center gap-1 hover:underline cursor-pointer transition-colors" 
                                                title="Clear all notifications">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            <span>Clear</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Drawer Body: Notification Items List -->
                                <div class="flex-1 overflow-y-auto divide-y divide-slate-100 dark:divide-white/[0.06] p-3 space-y-1">
                                    <template x-for="item in notifications.filter(n => filterTab === 'all' || (filterTab === 'unread' && !n.is_read))" :key="item.id">
                                        <div @click="markAsRead(item.id, item.link)" 
                                             class="p-3.5 rounded-xl hover:bg-slate-50 dark:hover:bg-white/[0.04] cursor-pointer transition-all border border-transparent hover:border-slate-200/80 dark:hover:border-white/10 flex items-start gap-3 relative group"
                                             :class="!item.is_read ? 'bg-blue-50/50 dark:bg-blue-950/20 font-semibold border-blue-100/80 dark:border-blue-900/40 shadow-xs' : 'opacity-80'">
                                            
                                            <div class="w-2 h-2 rounded-full mt-1.5 shrink-0" :class="!item.is_read ? 'bg-[#007AFF] ring-4 ring-blue-100 dark:ring-blue-900/40' : 'bg-slate-300 dark:bg-slate-600'"></div>

                                            <div class="flex-1 space-y-1 min-w-0">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.title"></span>
                                                    <div class="flex items-center gap-1.5 shrink-0">
                                                        <span class="text-[10px] text-slate-400 font-medium tabular-nums" x-text="new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                                        <button @click.stop="clearNotification(item.id)" 
                                                                class="opacity-0 group-hover:opacity-100 p-0.5 text-slate-400 hover:text-rose-500 rounded hover:bg-slate-200/50 dark:hover:bg-slate-700/50 transition-all cursor-pointer" 
                                                                title="Delete notification">
                                                            <i data-lucide="x" class="w-3 h-3"></i>
                                                        </button>
                                                    </div>
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

                @php
                    $fullName = Auth::user()->name ?? 'User';
                    $firstName = explode(' ', trim($fullName))[0] ?? $fullName;
                    $userAvatar = Auth::user()?->employee?->profile_picture;
                @endphp

                <!-- User Profile: Profile Picture & First Name Only -->
                <div class="flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-white/10" title="{{ $fullName }}">
                    @if($userAvatar)
                        <img src="{{ str_starts_with($userAvatar, 'http') ? $userAvatar : asset($userAvatar) }}" 
                             class="w-8 h-8 rounded-full object-cover ring-2 ring-blue-500/20 shrink-0 shadow-xs" 
                             alt="{{ $firstName }}">
                    @else
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs ring-2 ring-blue-500/20 shrink-0 shadow-xs">
                            {{ strtoupper(substr($firstName, 0, 2)) }}
                        </div>
                    @endif
                    <span class="hidden sm:inline-block text-xs font-bold text-slate-900 dark:text-white leading-none truncate max-w-[120px]">
                        {{ $firstName }}
                    </span>
                </div>

                <!-- Dark / Light Mode Toggle Button -->
                <button @click="toggleTheme()" 
                        class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100/80 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10 rounded-xl transition-all cursor-pointer active:scale-95" 
                        title="Toggle Light/Dark Theme">
                    <i data-lucide="moon" class="w-4 h-4" x-show="theme === 'light'"></i>
                    <i data-lucide="sun" class="w-4 h-4 text-amber-400" x-show="theme === 'dark'" x-cloak></i>
                </button>

                <!-- Sign Out Button -->
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" 
                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-rose-950/30 rounded-xl transition-all cursor-pointer active:scale-95"
                            title="Sign Out of Portal">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
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

        @if(session('error'))
            <div class="bg-gradient-to-r from-rose-500 to-red-600 text-white px-6 py-2.5 text-xs font-extrabold flex items-center justify-between shrink-0 shadow-sm">
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
        @endif

        <!-- Main Content Body with Smooth Page Entrance -->
        <main class="flex-1 overflow-y-auto p-3.5 md:p-5 min-h-0 flex flex-col page-enter">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

</div>

<!-- Auto-initialize Lucide Icons & Top Progress Bar -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (window.lucide) { lucide.createIcons(); }
    });

    // Smooth Top Navigation Progress Bar Driver
    (function () {
        const bar = document.getElementById('top-progress-bar');
        if (!bar) return;

        let timer = null;

        function startProgress() {
            if (timer) clearInterval(timer);
            bar.style.opacity = '1';
            bar.style.width = '20%';

            let current = 20;
            timer = setInterval(() => {
                if (current < 85) {
                    current += Math.random() * 12;
                    bar.style.width = Math.min(current, 85) + '%';
                }
            }, 180);
        }

        function completeProgress() {
            if (timer) clearInterval(timer);
            bar.style.width = '100%';
            setTimeout(() => {
                bar.style.opacity = '0';
                setTimeout(() => {
                    bar.style.width = '0%';
                }, 250);
            }, 150);
        }

        // Complete when current page finishes loading or restores from cache
        window.addEventListener('pageshow', () => {
            completeProgress();
        });

        // Trigger on internal navigation link clicks
        document.addEventListener('click', (e) => {
            const anchor = e.target.closest('a');
            if (!anchor) return;

            const href = anchor.getAttribute('href');
            const target = anchor.getAttribute('target');

            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:') || target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey || anchor.hasAttribute('download')) {
                return;
            }

            try {
                const url = new URL(href, window.location.origin);
                if (url.origin === window.location.origin && url.pathname !== window.location.pathname) {
                    startProgress();
                }
            } catch (err) {}
        });

        // Trigger on non-ajax form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form && !form.hasAttribute('data-ajax') && !form.target) {
                startProgress();
            }
        });
    })();
</script>

</body>
</html>
