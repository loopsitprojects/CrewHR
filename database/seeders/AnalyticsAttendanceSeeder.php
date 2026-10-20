<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Models\Employee;
use Carbon\Carbon;

class AnalyticsAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::with('user', 'department', 'designation')->get();
        if ($employees->isEmpty()) {
            return;
        }

        // Define persona behaviors for each employee
        $personas = [
            'Anjalie De Silva' => [
                'in_min' => '08:45', 'in_max' => '09:20', 'late_chance' => 0.08,
                'out_min' => '17:05', 'out_max' => '18:10', 'leave_chance' => 0.06,
            ],
            'Sahan Wickramasinghe' => [
                'in_min' => '09:32', 'in_max' => '10:15', 'late_chance' => 0.78,
                'out_min' => '18:35', 'out_max' => '20:30', 'leave_chance' => 0.05,
            ],
            'Supuni Ratnayake' => [
                'in_min' => '08:35', 'in_max' => '08:58', 'late_chance' => 0.02,
                'out_min' => '17:00', 'out_max' => '17:30', 'leave_chance' => 0.04,
            ],
            'Arosh' => [
                'in_min' => '08:55', 'in_max' => '09:25', 'late_chance' => 0.12,
                'out_min' => '18:50', 'out_max' => '20:45', 'leave_chance' => 0.05,
            ],
            'Shimal' => [
                'in_min' => '09:35', 'in_max' => '10:20', 'late_chance' => 0.70,
                'out_min' => '17:05', 'out_max' => '17:50', 'leave_chance' => 0.08,
            ],
            'Super Admin' => [
                'in_min' => '08:55', 'in_max' => '09:30', 'late_chance' => 0.15,
                'out_min' => '17:45', 'out_max' => '19:15', 'leave_chance' => 0.04,
            ],
        ];

        $startDate = Carbon::create(2026, 4, 1);
        $endDate = Carbon::create(2026, 8, 31);

        $records = [];
        $now = now();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Exclude Sinhala/Tamil New Year holidays (approx April 13-15)
            if ($date->month === 4 && in_array($date->day, [13, 14, 15])) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');

            foreach ($employees as $emp) {
                $name = $emp->user->name ?? 'Unknown';
                $persona = $personas[$name] ?? [
                    'in_min' => '08:50', 'in_max' => '09:25', 'late_chance' => 0.20,
                    'out_min' => '17:05', 'out_max' => '18:15', 'leave_chance' => 0.05,
                ];

                // Seed deterministically based on date and employee id
                $seed = crc32($dateStr . '_' . $emp->id);
                mt_srand($seed);

                $isAbsent = (mt_rand(1, 100) / 100.0) < $persona['leave_chance'];

                if ($isAbsent) {
                    $records[] = [
                        'employee_id' => $emp->id,
                        'date' => $dateStr,
                        'clock_in' => null,
                        'clock_out' => null,
                        'total_hours' => 0,
                        'regular_hours' => 0,
                        'early_ot_hours' => 0,
                        'late_ot_hours' => 0,
                        'total_ot_hours' => 0,
                        'status' => 'On Leave',
                        'clock_in_ip' => null,
                        'clock_out_ip' => null,
                        'notes' => 'Scheduled leave',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    continue;
                }

                // Determine Clock In
                $isLate = (mt_rand(1, 100) / 100.0) < $persona['late_chance'];
                if ($isLate) {
                    // Late arrival between 09:31 and 10:25
                    $inHour = 9;
                    $inMin = mt_rand(31, 59);
                    if (mt_rand(1, 10) > 7) {
                        $inHour = 10;
                        $inMin = mt_rand(0, 25);
                    }
                } else {
                    // On-time arrival between 08:35 and 09:28
                    $inHour = 8;
                    $inMin = mt_rand(35, 59);
                    if (mt_rand(1, 10) > 4) {
                        $inHour = 9;
                        $inMin = mt_rand(0, 28);
                    }
                }

                $clockInTime = sprintf('%02d:%02d:00', $inHour, $inMin);
                $clockIn = Carbon::parse("$dateStr $clockInTime");

                // Determine Clock Out
                [$outMinH, $outMinM] = explode(':', $persona['out_min']);
                [$outMaxH, $outMaxM] = explode(':', $persona['out_max']);
                $outStartMins = ((int)$outMinH * 60) + (int)$outMinM;
                $outEndMins = ((int)$outMaxH * 60) + (int)$outMaxM;

                $outMins = mt_rand($outStartMins, $outEndMins);
                $outHour = floor($outMins / 60);
                $outMinute = $outMins % 60;
                $clockOutTime = sprintf('%02d:%02d:00', $outHour, $outMinute);
                $clockOut = Carbon::parse("$dateStr $clockOutTime");

                $totalMinutes = abs($clockOut->diffInMinutes($clockIn));
                $totalHours = round($totalMinutes / 60.0, 2);
                $regularHours = min(8.0, $totalHours);
                $otHours = max(0.0, round($totalHours - 8.0, 2));

                $status = ($inHour > 9 || ($inHour === 9 && $inMin > 30)) ? 'Late' : 'Present';

                $records[] = [
                    'employee_id' => $emp->id,
                    'date' => $dateStr,
                    'clock_in' => $clockIn->toDateTimeString(),
                    'clock_out' => $clockOut->toDateTimeString(),
                    'total_hours' => $totalHours,
                    'regular_hours' => $regularHours,
                    'early_ot_hours' => 0,
                    'late_ot_hours' => $otHours,
                    'total_ot_hours' => $otHours,
                    'status' => $status,
                    'clock_in_ip' => '192.168.1.' . (10 + $emp->id),
                    'clock_out_ip' => '192.168.1.' . (10 + $emp->id),
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($records) >= 100) {
                    DB::table('attendances')->upsert(
                        $records,
                        ['employee_id', 'date'],
                        ['clock_in', 'clock_out', 'total_hours', 'regular_hours', 'early_ot_hours', 'late_ot_hours', 'total_ot_hours', 'status', 'clock_in_ip', 'clock_out_ip', 'notes', 'updated_at']
                    );
                    $records = [];
                }
            }
        }

        if (!empty($records)) {
            DB::table('attendances')->upsert(
                $records,
                ['employee_id', 'date'],
                ['clock_in', 'clock_out', 'total_hours', 'regular_hours', 'early_ot_hours', 'late_ot_hours', 'total_ot_hours', 'status', 'clock_in_ip', 'clock_out_ip', 'notes', 'updated_at']
            );
        }
    }
}
