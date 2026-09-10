<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaveDashboardController;
use App\Http\Controllers\MyLeavesController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ApprovalController;

// Notification System API
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AppraisalController;
use Modules\Employee\Http\Controllers\EmployeeController;
use Modules\Attendance\Http\Controllers\AttendanceController;
use Modules\Payroll\Http\Controllers\PayrollController;
use Modules\Recruitment\Http\Controllers\RecruitmentController;

// Homepage redirects to Leave Management Dashboard
Route::get('/', [LeaveDashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [LeaveDashboardController::class, 'index'])->name('dashboard');
Route::get('/leave-management', [LeaveDashboardController::class, 'index'])->name('leave.dashboard');
Route::post('/leave/store', [LeaveDashboardController::class, 'storeLeaveRequest'])->name('leave.store');
Route::post('/leave/company-holiday', [LeaveDashboardController::class, 'storeCompanyLeave'])->name('leave.company_holiday');

// My Leaves Module
Route::get('/my-leaves', [MyLeavesController::class, 'index'])->name('my-leaves.index');
Route::post('/my-leaves/{id}/cancel', [MyLeavesController::class, 'cancel'])->name('my-leaves.cancel');

// Employees Directory
Route::resource('employees', EmployeeController::class)->names('employee');

// Attendance Tracking
Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendance.index');
Route::post('/attendance/clock', [AttendanceController::class, 'clockToggle'])->name('attendance.clock_toggle');

use Modules\Payroll\Http\Controllers\LoanController;
use Modules\Payroll\Http\Controllers\OvertimeController;
use Modules\Payroll\Http\Controllers\SalaryAdvanceController;

// Payroll & Payslips
Route::get('/payrolls', [PayrollController::class, 'index'])->name('payroll.index');
Route::post('/payrolls/process', [PayrollController::class, 'process'])->name('payroll.process');
Route::post('/payrolls/advances', [PayrollController::class, 'storeAdvance'])->name('payroll.advances.store');
Route::get('/payrolls/export-master-register/{id}', [PayrollController::class, 'exportMasterRegister'])->name('payroll.export_master_register');
Route::get('/payrolls/export-epf-cform/{id}', [PayrollController::class, 'exportEpfCForm'])->name('payroll.export_epf_cform');
Route::get('/payrolls/export-journal/{id}', [PayrollController::class, 'exportJournalEntry'])->name('payroll.export_journal');
Route::get('/payrolls/export-bank-advice/{id}', [PayrollController::class, 'exportBankAdvice'])->name('payroll.export_bank_advice');
Route::get('/payrolls/export-cash-denomination/{id}', [PayrollController::class, 'exportCashDenomination'])->name('payroll.export_cash_denomination');
Route::get('/payrolls/payslip-details/{id}', [PayrollController::class, 'getPayslipDetails'])->name('payroll.payslip_details');
Route::get('/my-payslips', [PayrollController::class, 'myPayslips'])->name('payroll.my_payslips');

// Loan Management Sub-Module
Route::get('/payrolls/loans', [LoanController::class, 'index'])->name('payroll.loans.index');
Route::post('/payrolls/loans/apply', [LoanController::class, 'apply'])->name('payroll.loans.apply');
Route::post('/payrolls/loans/{id}/approve', [LoanController::class, 'approve'])->name('payroll.loans.approve');
Route::post('/payrolls/loans/{id}/reject', [LoanController::class, 'reject'])->name('payroll.loans.reject');
Route::post('/payrolls/loans/types', [LoanController::class, 'storeType'])->name('payroll.loans.types.store');
Route::post('/payrolls/loans/{id}/payment', [LoanController::class, 'recordPayment'])->name('payroll.loans.payment');
Route::get('/payrolls/loans/export-report', [LoanController::class, 'exportReport'])->name('payroll.loans.export');

// Overtime Management Sub-Module
Route::get('/payrolls/overtime', [OvertimeController::class, 'index'])->name('payroll.overtime.index');
Route::post('/payrolls/overtime', [OvertimeController::class, 'store'])->name('payroll.overtime.store');
Route::post('/payrolls/overtime/{id}/approve', [OvertimeController::class, 'approve'])->name('payroll.overtime.approve');
Route::post('/payrolls/overtime/{id}/reject', [OvertimeController::class, 'reject'])->name('payroll.overtime.reject');
Route::post('/payrolls/overtime/{id}/cancel', [OvertimeController::class, 'cancel'])->name('payroll.overtime.cancel');
Route::get('/payrolls/overtime/export', [OvertimeController::class, 'export'])->name('payroll.overtime.export');

// Salary Advances Sub-Module
Route::get('/payrolls/advances', [SalaryAdvanceController::class, 'index'])->name('payroll.advances.index');
Route::post('/payrolls/advances/apply', [SalaryAdvanceController::class, 'apply'])->name('payroll.advances.apply');
Route::post('/payrolls/advances/{id}/approve', [SalaryAdvanceController::class, 'approve'])->name('payroll.advances.approve');
Route::post('/payrolls/advances/{id}/reject', [SalaryAdvanceController::class, 'reject'])->name('payroll.advances.reject');
Route::post('/payrolls/advances/{id}/cancel', [SalaryAdvanceController::class, 'cancel'])->name('payroll.advances.cancel');
Route::get('/payrolls/advances/export', [SalaryAdvanceController::class, 'export'])->name('payroll.advances.export');

// Recruitment ATS
Route::get('/recruitments', [RecruitmentController::class, 'index'])->name('recruitment.index');

// Approvals & Workflow
Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
Route::post('/approvals/{id}/action', [ApprovalController::class, 'action'])->name('approvals.action');

// Analytics & Reports
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');

// System Settings
Route::get('/settings/{module?}', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings/save', [SettingsController::class, 'save'])->name('settings.save');
Route::post('/settings/cache-clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
Route::post('/settings/leave-type', [SettingsController::class, 'storeLeaveType'])->name('settings.leave_type.store');
Route::put('/settings/leave-type/{id}', [SettingsController::class, 'updateLeaveType'])->name('settings.leave_type.update');
Route::post('/settings/leave-type/{id}/toggle', [SettingsController::class, 'toggleLeaveType'])->name('settings.leave_type.toggle');
Route::delete('/settings/leave-type/{id}', [SettingsController::class, 'destroyLeaveType'])->name('settings.leave_type.destroy');

// Holiday & Day Types Calendar Engine
Route::get('/settings/holidays/json', [SettingsController::class, 'getHolidaysJson'])->name('settings.holidays.json');
Route::post('/settings/holidays/assign', [SettingsController::class, 'assignHoliday'])->name('settings.holidays.assign');
Route::delete('/settings/holidays/{id}', [SettingsController::class, 'destroyHoliday'])->name('settings.holidays.destroy');

// Day Types Custom CRUD
Route::get('/settings/day-types/json', [SettingsController::class, 'getDayTypesJson'])->name('settings.day_types.json');
Route::post('/settings/day-types', [SettingsController::class, 'storeDayType'])->name('settings.day_types.store');
Route::put('/settings/day-types/{id}', [SettingsController::class, 'updateDayType'])->name('settings.day_types.update');
Route::delete('/settings/day-types/{id}', [SettingsController::class, 'destroyDayType'])->name('settings.day_types.destroy');

Route::post('/settings/allowance-type', [SettingsController::class, 'storeAllowanceType'])->name('settings.allowance_type.store');
Route::put('/settings/allowance-type/{id}', [SettingsController::class, 'updateAllowanceType'])->name('settings.allowance_type.update');
Route::post('/settings/allowance-type/{id}/toggle', [SettingsController::class, 'toggleAllowanceType'])->name('settings.allowance_type.toggle');
Route::delete('/settings/allowance-type/{id}', [SettingsController::class, 'destroyAllowanceType'])->name('settings.allowance_type.destroy');

Route::post('/settings/department', [SettingsController::class, 'storeDepartment'])->name('settings.department.store');
Route::put('/settings/department/{id}', [SettingsController::class, 'updateDepartment'])->name('settings.department.update');
Route::delete('/settings/department/{id}', [SettingsController::class, 'destroyDepartment'])->name('settings.department.destroy');
Route::post('/settings/module', [SettingsController::class, 'storeModule'])->name('settings.module.store');
Route::put('/settings/module/{key}', [SettingsController::class, 'updateModule'])->name('settings.module.update');
Route::delete('/settings/module/{key}', [SettingsController::class, 'destroyModule'])->name('settings.module.destroy');
Route::post('/role/switch', [SettingsController::class, 'switchRole'])->name('role.switch');

// Appraisal System
Route::get('/appraisal-system', [AppraisalController::class, 'index'])->name('appraisal.index');

require __DIR__.'/auth.php';
