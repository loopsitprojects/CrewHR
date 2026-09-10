<?php

use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceAdjustment;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use App\Models\User;

beforeEach(function () {
    $this->seed();

    $dept = Department::firstOrCreate(['name' => 'Engineering']);
    $desig = Designation::firstOrCreate(['name' => 'Senior Developer']);

    $this->adminUser = User::create([
        'name' => 'Super Admin User',
        'email' => 'admin_test@loops.lk',
        'username' => 'admin_test',
        'password' => bcrypt('password'),
    ]);

    $this->adminEmployee = Employee::create([
        'user_id' => $this->adminUser->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_id_number' => 'EMP-TEST-01',
        'joined_date' => '2023-01-01',
        'job_category' => 'Permanent',
        'system_role' => 'Super (Admin)',
        'basic_salary' => 250000,
    ]);

    $this->regularUser = User::create([
        'name' => 'Regular Dev User',
        'email' => 'dev_test@loops.lk',
        'username' => 'dev_test',
        'password' => bcrypt('password'),
    ]);

    $this->regularEmployee = Employee::create([
        'user_id' => $this->regularUser->id,
        'department_id' => $dept->id,
        'designation_id' => $desig->id,
        'employee_id_number' => 'EMP-TEST-02',
        'joined_date' => '2023-02-01',
        'job_category' => 'Permanent',
        'system_role' => 'Staff / Employee',
        'basic_salary' => 180000,
    ]);
});

test('attendance dashboard loads successfully', function () {
    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->get(route('attendance.index'));

    $response->assertStatus(200);
    $response->assertSee('Attendance & Time Tracking');
    $response->assertSee('Daily Roster');
    $response->assertSee('Monthly Timesheet Matrix');
});

test('employee can clock in and clock out', function () {
    $response = $this->actingAs($this->regularUser)
        ->withSession(['active_role' => 'Employee'])
        ->post(route('attendance.clock_toggle'));

    $response->assertRedirect();
    
    $att = Attendance::where('employee_id', $this->regularEmployee->id)->first();
    expect($att)->not->toBeNull();
    expect($att->clock_in)->not->toBeNull();

    $response = $this->actingAs($this->regularUser)
        ->withSession(['active_role' => 'Employee'])
        ->post(route('attendance.clock_toggle'));

    $response->assertRedirect();

    $att->refresh();
    expect($att->clock_out)->not->toBeNull();
    expect((float)$att->total_hours)->toBeGreaterThanOrEqual(0);
});

test('admin can log manual attendance with clock in and clock out', function () {
    $testDate = '2026-09-05';

    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->post(route('attendance.manual_store'), [
            'employee_id' => $this->regularEmployee->id,
            'date' => $testDate,
            'clock_in' => '08:30',
            'clock_out' => '17:30',
            'notes' => 'Manual entry for site work',
        ]);

    $response->assertRedirect();

    $att = Attendance::where('employee_id', $this->regularEmployee->id)->first();
    expect($att)->not->toBeNull();
    expect($att->status)->toBe('Present');
    expect((float)$att->total_hours)->toBe(9.0);
});

test('employee regularization adjustment workflow', function () {
    $response = $this->actingAs($this->regularUser)
        ->withSession(['active_role' => 'Employee'])
        ->post(route('attendance.adjustments.store'), [
            'employee_id' => $this->regularEmployee->id,
            'date' => '2026-09-02',
            'clock_in' => '09:00',
            'clock_out' => '17:00',
            'reason' => 'Fingerprint scanner was offline in the morning',
        ]);

    $response->assertRedirect();

    $adj = AttendanceAdjustment::where('employee_id', $this->regularEmployee->id)->first();
    expect($adj)->not->toBeNull();
    expect($adj->status)->toBe('Pending');

    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->post(route('attendance.adjustments.review', $adj->id), [
            'action' => 'Approved',
            'review_remarks' => 'Verified with line manager',
        ]);

    $response->assertRedirect();
    $adj->refresh();
    expect($adj->status)->toBe('Approved');

    $att = Attendance::where('employee_id', $this->regularEmployee->id)->first();
    expect($att)->not->toBeNull();
    expect($att->status)->toBe('Present');
});

test('monthly attendance matrix and csv export', function () {
    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->get(route('attendance.export_monthly', [
            'month' => 9,
            'year' => 2026,
        ]));

    $response->assertStatus(200);
    expect((string)$response->headers->get('content-type'))->toContain('text/csv');
});

test('admin can download machine log sample csv templates', function () {
    $responseSummary = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->get(route('attendance.sample_csv', ['format' => 'summary']));

    $responseSummary->assertStatus(200);
    expect((string)$responseSummary->headers->get('content-type'))->toContain('text/csv');

    $responseRaw = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->get(route('attendance.sample_csv', ['format' => 'raw']));

    $responseRaw->assertStatus(200);
    expect((string)$responseRaw->headers->get('content-type'))->toContain('text/csv');
});

test('admin can upload and process summary machine logs csv', function () {
    $csvContent = "Employee_ID,Date,Clock_In,Clock_Out,Notes\n" .
                  "EMP-TEST-02,2026-09-08,08:50,17:10,Biometric Terminal 1\n";

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('machine_logs.csv', $csvContent);

    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->post(route('attendance.import'), [
            'csv_file' => $file,
        ]);

    $response->assertRedirect(route('attendance.index', ['tab' => 'monthly', 'month' => 9, 'year' => 2026]));

    $att = Attendance::where('employee_id', $this->regularEmployee->id)
        ->whereDate('date', '2026-09-08')
        ->first();

    expect($att)->not->toBeNull();
    expect($att->status)->toBe('Present');
    expect((float)$att->total_hours)->toBeGreaterThanOrEqual(8.0);
});

test('admin can upload and process raw punch dumps csv', function () {
    $csvContent = "Employee_ID,Timestamp,Punch_Type\n" .
                  "EMP-TEST-02,2026-09-09 08:45:00,Check-In\n" .
                  "EMP-TEST-02,2026-09-09 17:30:00,Check-Out\n";

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('raw_punches.csv', $csvContent);

    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->post(route('attendance.import'), [
            'csv_file' => $file,
            'target_month' => 9,
            'target_year' => 2026,
        ]);

    $response->assertRedirect(route('attendance.index', ['tab' => 'monthly', 'month' => 9, 'year' => 2026]));

    $att = Attendance::where('employee_id', $this->regularEmployee->id)
        ->whereDate('date', '2026-09-09')
        ->first();

    expect($att)->not->toBeNull();
    expect($att->clock_in)->not->toBeNull();
    expect($att->clock_out)->not->toBeNull();
    expect((float)$att->total_hours)->toBeGreaterThanOrEqual(8.5);
});

test('admin can upload and process excel spreadsheet (.xlsx) attendance file', function () {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Employee_ID');
    $sheet->setCellValue('B1', 'Date');
    $sheet->setCellValue('C1', 'Clock_In');
    $sheet->setCellValue('D1', 'Clock_Out');
    $sheet->setCellValue('E1', 'Notes');

    $sheet->setCellValue('A2', 'EMP-TEST-02');
    $sheet->setCellValue('B2', '2026-09-07');
    $sheet->setCellValue('C2', '08:50');
    $sheet->setCellValue('D2', '17:15');
    $sheet->setCellValue('E2', 'From Excel timesheet');

    $tempFile = tempnam(sys_get_temp_dir(), 'test_att_') . '.xlsx';
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($tempFile);

    $uploadedFile = new \Illuminate\Http\UploadedFile($tempFile, 'Employee Timesheet (1).xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->actingAs($this->adminUser)
        ->withSession(['active_role' => 'Super Admin'])
        ->post(route('attendance.import'), [
            'csv_file' => $uploadedFile,
            'target_month' => 9,
            'target_year' => 2026,
        ]);

    if (file_exists($tempFile)) {
        @unlink($tempFile);
    }

    $response->assertRedirect(route('attendance.index', ['tab' => 'monthly', 'month' => 9, 'year' => 2026]));

    $att = Attendance::where('employee_id', $this->regularEmployee->id)
        ->whereDate('date', '2026-09-07')
        ->first();

    expect($att)->not->toBeNull();
    expect($att->status)->toBe('Present');
    expect((float)$att->total_hours)->toBeGreaterThanOrEqual(8.0);
});

test('employees can only see personal attendance and cannot access admin operations', function () {
    $response = $this->actingAs($this->regularUser)
        ->withSession(['active_role' => 'Employee'])
        ->get(route('attendance.index'));

    $response->assertStatus(200);
    $response->assertSee('My Attendance');
    $response->assertDontSee('Daily Roster');
    $response->assertDontSee('Monthly Timesheet Matrix');
    $response->assertDontSee('Biometric Import');
    $response->assertDontSee('Upload Machine CSV');
    $response->assertDontSee('Manual Entry');

    // Attempting unauthorized manual entry should return 403
    $unauthManual = $this->actingAs($this->regularUser)
        ->withSession(['active_role' => 'Employee'])
        ->post(route('attendance.manual_store'), [
            'employee_id' => $this->regularEmployee->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00',
            'clock_out' => '17:00',
        ]);

    $unauthManual->assertStatus(403);

    // Attempting unauthorized bulk import should return 403
    $file = \Illuminate\Http\UploadedFile::fake()->create('unauth.csv', 10);
    $unauthImport = $this->actingAs($this->regularUser)
        ->withSession(['active_role' => 'Employee'])
        ->post(route('attendance.import'), [
            'csv_file' => $file,
        ]);

    $unauthImport->assertStatus(403);
});
