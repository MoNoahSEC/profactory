<?php
namespace App\Services;

use App\Models\Worker;

class SalaryCalculationService
{
    public function calculatePeriod(Worker $worker, string $startDate, string $endDate): array
    {
        $attendances = $worker->attendances()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $workingDays   = $attendances->whereIn('status', ['present', 'late'])->count();
        $halfDays      = $attendances->where('status', 'half_day')->count();
        $absentDays    = $attendances->where('status', 'absent')->count();
        $overtimeHours = $attendances->sum('overtime_hours');

        // السلف غير المخصومة خلال نفس الفترة أو قبلها
        $advances = \App\Models\WorkerAdvance::where('worker_id', $worker->id)
            ->where('is_deducted', false)
            ->where('date', '<=', $endDate)
            ->sum('amount');
            
        // إنتاج الموظف بالقطعة خلال الفترة
        $productionPay = \App\Models\WorkerProduction::where('worker_id', $worker->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('total_pay');

        // الأجر الأساسي يحسب فقط بناءً على الأيام، وإذا كان الموظف بالقطعة تحسب له الورديات
        $wage = $worker->applicable_wage;
        $baseSalary    = $wage * ($workingDays + ($halfDays * 0.5));
        
        $overtimePay   = ($wage > 0 ? ($wage / 8) : 0) * $overtimeHours * 1.5; // overtime rate
        $deductions    = $wage * $absentDays;
        
        // إجمالي الراتب يتضمن الأساسي (إن وجد) + أجر الإنتاج بالقطعة + الإضافي
        $netSalary = $baseSalary + $productionPay + $overtimePay - $deductions - $advances;

        return [
            'working_days'   => $workingDays,
            'absent_days'    => $absentDays,
            'overtime_hours' => $overtimeHours,
            'base_salary'    => round($baseSalary, 2),
            'production_pay' => round($productionPay, 2),
            'overtime_pay'   => round($overtimePay, 2),
            'deductions'     => round($deductions, 2),
            'bonuses'        => 0, // Manual input later
            'advances'       => round($advances, 2),
            'net_salary'     => round($netSalary, 2),
        ];
    }
}
