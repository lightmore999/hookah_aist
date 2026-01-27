<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Expenditure;
use App\Models\Hookah;
use App\Models\Fine;
use App\Models\Product;
use App\Models\PaymentMethod;
use App\Models\User; // Добавляем User
use App\Models\Shift; // Добавляем Shift
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    /**
     * Главная страница бухгалтерии
     */
    public function index(Request $request)
    {
        // Получаем тип отчета: день, неделя, месяц
        $type = $request->get('type', 'day');
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $month = $request->get('month', now()->format('Y-m'));
        
        // Количество периодов для отображения
        $daysCount = $request->get('days_count', 30);
        $weeksCount = $request->get('weeks_count', 8);
        $monthsCount = $request->get('months_count', 6);
        
        // Если фильтр по датам не выбран, показываем от сегодня вниз
        if ($type === 'day' && empty($startDate) && empty($endDate)) {
            $startDate = now()->subDays($daysCount - 1)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');
        } elseif ($type === 'week' && empty($startDate) && empty($endDate)) {
            $startDate = now()->subWeeks($weeksCount)->startOfWeek()->format('Y-m-d');
            $endDate = now()->endOfWeek()->format('Y-m-d');
        } elseif ($type === 'month' && empty($month)) {
            $month = now()->format('Y-m');
        }
        
        // Получаем данные для таблицы
        $tableData = $this->getTableData($type, $startDate, $endDate, $month, $daysCount, $weeksCount, $monthsCount);
        
        // Общая статистика за все время
        $totalStats = $this->getTotalStats();
        $paymentMethods = PaymentMethod::orderBy('Name')->get();
        
        // Добавляем расчет зарплаты за выбранный период
        $salaryData = $this->calculateSalaryForPeriod($type, $date, $startDate, $endDate, $month);
        
        return view('accounting.index', [
            'tableData' => $tableData,
            'type' => $type,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'month' => $month,
            'daysCount' => $daysCount,
            'weeksCount' => $weeksCount,
            'monthsCount' => $monthsCount,
            'totalStats' => $totalStats,
            'paymentMethods' => $paymentMethods,
            'salaryData' => $salaryData,
        ]);
    }
    
    // ... ВСЕ СУЩЕСТВУЮЩИЕ МЕТОДЫ БЕЗ ИЗМЕНЕНИЙ ...
    // getTableData(), getDailyStats(), getPeriodStats(), getTotalStats(),
    // hookahStats(), paymentStats(), bonusStats(), export(),
    // exportToCsv(), getDailyReport(), getPeriodReport(),
    // costReport(), getPaymentMethodsStats()
    // ... они остаются полностью как есть ...
    
    /**
     * ============================================
     * НОВЫЕ МЕТОДЫ ДЛЯ РАСЧЕТА ЗАРПЛАТЫ СОТРУДНИКОВ
     * ============================================
     */
    
    /**
     * Рассчитать зарплату для периода
     */
    private function calculateSalaryForPeriod($type, $date, $startDate, $endDate, $month)
    {
        if ($type === 'day') {
            return $this->calculateDailySalary($date);
        } elseif ($type === 'week') {
            if (!$startDate || !$endDate) {
                $startDate = now()->startOfWeek()->format('Y-m-d');
                $endDate = now()->endOfWeek()->format('Y-m-d');
            }
            return $this->calculatePeriodSalary($startDate, $endDate);
        } elseif ($type === 'month') {
            if (!$month) {
                $month = now()->format('Y-m');
            }
            $monthStart = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
            $monthEnd = Carbon::parse($month)->endOfMonth()->format('Y-m-d');
            return $this->calculatePeriodSalary($monthStart, $monthEnd);
        }
        
        return [];
    }
    
    /**
     * Рассчитать зарплату за день с учетом смен
     */
    private function calculateDailySalary($date)
    {
        // Находим смену на эту дату
        $shift = Shift::whereDate('date', $date)->first();
        
        if (!$shift) {
            return [
                'total_salary' => 0,
                'total_revenue' => 0,
                'employees' => [],
                'shift_exists' => false,
                'shift_date' => $date,
                'shift_start' => null,
                'shift_end' => null,
                'sales_count' => 0,
                'shift_status' => null,
            ];
        }
        
        // Получаем сотрудников на этой смене
        $employees = $shift->employees()->get();
        
        // Определяем временные рамки смены (учитываем ночные смены)
        $shiftDate = Carbon::parse($date);
        $shiftStart = $shiftDate->copy()->setTime(20, 0, 0); // Смена начинается в 20:00
        $shiftEnd = $shiftDate->copy()->addDay()->setTime(4, 0, 0); // Заканчивается в 4:00 следующего дня
        
        // Если смена уже открыта/закрыта, используем реальные времена
        if ($shift->closed_at) {
            $shiftEnd = Carbon::parse($shift->closed_at);
        }
        if ($shift->opened_at) {
            $shiftStart = Carbon::parse($shift->opened_at);
            // Если нет времени закрытия, считаем стандартную 8-часовую смену
            if (!$shift->closed_at) {
                $shiftEnd = $shiftStart->copy()->addHours(8);
            }
        }
        
        // Получаем все продажи за период смены
        $sales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$shiftStart, $shiftEnd])
            ->get();
        
        $totalRevenue = $sales->sum('total');
        
        // Рассчитываем зарплату для каждого сотрудника
        $employeeSalaries = [];
        $totalSalary = 0;
        
        foreach ($employees as $employee) {
            // Ставка за смену
            $shiftSalary = $employee->shift_salary ?? 0;
            
            // Процент с выручки
            $revenuePercentage = $employee->revenue_percentage ?? 0;
            $percentageSalary = $totalRevenue * ($revenuePercentage / 100);
            
            // Общая зарплата за смену
            $totalEmployeeSalary = $shiftSalary + $percentageSalary;
            
            // Штрафы сотрудника за этот день
            $fines = Fine::where('user_id', $employee->id)
                ->whereDate('created_at', $date)
                ->sum('amount');
            
            // Чистая зарплата (после вычета штрафов)
            $netSalary = max(0, $totalEmployeeSalary - $fines);
            
            // Часы в смене
            $shiftHours = $shiftStart->diffInHours($shiftEnd);
            
            $employeeSalaries[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'position' => $employee->position,
                'shift_salary' => $shiftSalary,
                'revenue_percentage' => $revenuePercentage,
                'percentage_salary' => $percentageSalary,
                'total_salary' => $totalEmployeeSalary,
                'fines' => $fines,
                'net_salary' => $netSalary,
                'shift_hours' => $shiftHours,
                'revenue_share' => $totalRevenue > 0 ? round(($percentageSalary / $totalRevenue) * 100, 2) : 0,
            ];
            
            $totalSalary += $netSalary;
        }
        
        return [
            'total_salary' => $totalSalary,
            'total_revenue' => $totalRevenue,
            'employees' => $employeeSalaries,
            'shift_exists' => true,
            'shift_date' => $date,
            'shift_start' => $shiftStart->format('H:i'),
            'shift_end' => $shiftEnd->format('H:i'),
            'sales_count' => $sales->count(),
            'shift_status' => $shift->status,
        ];
    }
    
    /**
     * Рассчитать зарплату за период (неделя, месяц)
     */
    private function calculatePeriodSalary($startDate, $endDate)
    {
        // Находим все смены в периоде
        $shifts = Shift::whereBetween('date', [$startDate, $endDate])
            ->with(['employees'])
            ->get();
        
        $totalSalary = 0;
        $totalRevenue = 0;
        $allEmployees = [];
        $shiftSalaries = [];
        
        foreach ($shifts as $shift) {
            // Определяем временные рамки смены
            $shiftDate = Carbon::parse($shift->date);
            $shiftStart = $shiftDate->copy()->setTime(20, 0, 0);
            $shiftEnd = $shiftDate->copy()->addDay()->setTime(4, 0, 0);
            
            if ($shift->closed_at) {
                $shiftEnd = Carbon::parse($shift->closed_at);
            }
            if ($shift->opened_at) {
                $shiftStart = Carbon::parse($shift->opened_at);
                if (!$shift->closed_at) {
                    $shiftEnd = $shiftStart->copy()->addHours(8);
                }
            }
            
            // Получаем продажи за эту смену
            $sales = Sale::where('status', 'completed')
                ->whereBetween('created_at', [$shiftStart, $shiftEnd])
                ->get();
            
            $shiftRevenue = $sales->sum('total');
            $totalRevenue += $shiftRevenue;
            
            // Рассчитываем зарплату для каждого сотрудника на этой смене
            foreach ($shift->employees as $employee) {
                $employeeId = $employee->id;
                
                // Инициализируем запись сотрудника
                if (!isset($allEmployees[$employeeId])) {
                    $allEmployees[$employeeId] = [
                        'employee' => $employee,
                        'shifts_worked' => 0,
                        'total_shift_salary' => 0,
                        'total_percentage_salary' => 0,
                        'total_fines' => 0,
                        'total_net_salary' => 0,
                        'shifts' => [],
                    ];
                }
                
                // Ставка за смену
                $shiftSalary = $employee->shift_salary ?? 0;
                
                // Процент с выручки за смену
                $revenuePercentage = $employee->revenue_percentage ?? 0;
                $percentageSalary = $shiftRevenue * ($revenuePercentage / 100);
                
                // Штрафы сотрудника за день смены
                $fines = Fine::where('user_id', $employee->id)
                    ->whereDate('created_at', $shift->date)
                    ->sum('amount');
                
                // Зарплата за смену
                $netSalary = max(0, ($shiftSalary + $percentageSalary) - $fines);
                
                // Обновляем данные сотрудника
                $allEmployees[$employeeId]['shifts_worked']++;
                $allEmployees[$employeeId]['total_shift_salary'] += $shiftSalary;
                $allEmployees[$employeeId]['total_percentage_salary'] += $percentageSalary;
                $allEmployees[$employeeId]['total_fines'] += $fines;
                $allEmployees[$employeeId]['total_net_salary'] += $netSalary;
                
                // Добавляем детали по смене
                $allEmployees[$employeeId]['shifts'][] = [
                    'date' => $shift->date,
                    'shift_salary' => $shiftSalary,
                    'revenue_percentage' => $revenuePercentage,
                    'percentage_salary' => $percentageSalary,
                    'fines' => $fines,
                    'net_salary' => $netSalary,
                    'shift_revenue' => $shiftRevenue,
                    'shift_status' => $shift->status,
                ];
                
                $totalSalary += $netSalary;
            }
            
            // Данные по смене
            $shiftSalaries[] = [
                'date' => $shift->date,
                'status' => $shift->status,
                'revenue' => $shiftRevenue,
                'employees_count' => $shift->employees->count(),
                'total_salary_paid' => 0,
            ];
        }
        
        // Формируем итоговый массив сотрудников
        $employeeSalaries = [];
        foreach ($allEmployees as $employeeId => $data) {
            $employeeSalaries[] = [
                'id' => $employeeId,
                'name' => $data['employee']->name,
                'position' => $data['employee']->position,
                'shifts_worked' => $data['shifts_worked'],
                'total_shift_salary' => $data['total_shift_salary'],
                'total_percentage_salary' => $data['total_percentage_salary'],
                'total_fines' => $data['total_fines'],
                'total_net_salary' => $data['total_net_salary'],
                'avg_salary_per_shift' => $data['shifts_worked'] > 0 ? 
                    round($data['total_net_salary'] / $data['shifts_worked'], 2) : 0,
                'shifts' => $data['shifts'],
            ];
        }
        
        // Сортируем по убыванию зарплаты
        usort($employeeSalaries, function($a, $b) {
            return $b['total_net_salary'] <=> $a['total_net_salary'];
        });
        
        return [
            'total_salary' => $totalSalary,
            'total_revenue' => $totalRevenue,
            'employees' => $employeeSalaries,
            'shifts' => $shiftSalaries,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'shifts_count' => $shifts->count(),
            'unique_employees' => count($allEmployees),
        ];
    }
    
    /**
     * Отдельная страница отчета по зарплате
     */
    public function salaryReport(Request $request)
    {
        $type = $request->get('type', 'day');
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $month = $request->get('month', now()->format('Y-m'));
        
        if ($type === 'day') {
            $salaryData = $this->calculateDailySalary($date);
            $periodText = Carbon::parse($date)->format('d.m.Y');
        } elseif ($type === 'week') {
            if (!$startDate || !$endDate) {
                $startDate = now()->startOfWeek()->format('Y-m-d');
                $endDate = now()->endOfWeek()->format('Y-m-d');
            }
            $salaryData = $this->calculatePeriodSalary($startDate, $endDate);
            $periodText = Carbon::parse($startDate)->format('d.m.Y') . ' - ' . Carbon::parse($endDate)->format('d.m.Y');
        } else {
            if (!$month) {
                $month = now()->format('Y-m');
            }
            $monthStart = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
            $monthEnd = Carbon::parse($month)->endOfMonth()->format('Y-m-d');
            $salaryData = $this->calculatePeriodSalary($monthStart, $monthEnd);
            $periodText = Carbon::parse($month)->translatedFormat('F Y');
        }
        
        // Все сотрудники для фильтра
        $allEmployees = User::where('role', 'employee')->orderBy('name')->get();
        
        // Все смены за период
        if ($type === 'day') {
            $shifts = Shift::whereDate('date', $date)->get();
        } elseif ($type === 'week') {
            $shifts = Shift::whereBetween('date', [$startDate, $endDate])->get();
        } else {
            $shifts = Shift::whereBetween('date', [$monthStart, $monthEnd])->get();
        }
        
        return view('accounting.salary-report', [
            'salaryData' => $salaryData,
            'type' => $type,
            'date' => $date,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'month' => $month,
            'periodText' => $periodText,
            'allEmployees' => $allEmployees,
            'shifts' => $shifts,
        ]);
    }
    
    /**
     * Экспорт отчета по зарплате в CSV
     */
    public function exportSalaryReport(Request $request)
    {
        $type = $request->get('type', 'day');
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $month = $request->get('month', now()->format('Y-m'));
        
        if ($type === 'day') {
            $salaryData = $this->calculateDailySalary($date);
            $filename = 'зарплата_' . $date . '.csv';
        } elseif ($type === 'week') {
            if (!$startDate || !$endDate) {
                $startDate = now()->startOfWeek()->format('Y-m-d');
                $endDate = now()->endOfWeek()->format('Y-m-d');
            }
            $salaryData = $this->calculatePeriodSalary($startDate, $endDate);
            $filename = 'зарплата_' . $startDate . '_' . $endDate . '.csv';
        } else {
            if (!$month) {
                $month = now()->format('Y-m');
            }
            $monthStart = Carbon::parse($month)->startOfMonth()->format('Y-m-d');
            $monthEnd = Carbon::parse($month)->endOfMonth()->format('Y-m-d');
            $salaryData = $this->calculatePeriodSalary($monthStart, $monthEnd);
            $filename = 'зарплата_' . $month . '.csv';
        }
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($salaryData, $type) {
            $file = fopen('php://output', 'w');
            
            if ($type === 'day') {
                // Заголовки для дневного отчета
                fputcsv($file, [
                    'ID',
                    'ФИО',
                    'Должность',
                    'Ставка за смену',
                    'Процент с выручки',
                    'Зарплата с процента',
                    'Штрафы',
                    'Итого к выплате',
                    'Часы в смене',
                    'Доля в выручке (%)'
                ]);
                
                foreach ($salaryData['employees'] as $employee) {
                    fputcsv($file, [
                        $employee['id'],
                        $employee['name'],
                        $employee['position'],
                        $employee['shift_salary'],
                        $employee['revenue_percentage'],
                        $employee['percentage_salary'],
                        $employee['fines'],
                        $employee['net_salary'],
                        $employee['shift_hours'],
                        $employee['revenue_share']
                    ]);
                }
                
                fputcsv($file, []);
                fputcsv($file, ['Итого зарплата:', '', '', '', '', '', '', $salaryData['total_salary']]);
                fputcsv($file, ['Выручка за смену:', '', '', '', '', '', '', $salaryData['total_revenue']]);
                
            } else {
                // Заголовки для периода
                fputcsv($file, [
                    'ID',
                    'ФИО',
                    'Должность',
                    'Отработано смен',
                    'Ставки за смены',
                    'Проценты с выручки',
                    'Всего штрафов',
                    'Итого к выплате',
                    'Средняя за смену'
                ]);
                
                foreach ($salaryData['employees'] as $employee) {
                    fputcsv($file, [
                        $employee['id'],
                        $employee['name'],
                        $employee['position'],
                        $employee['shifts_worked'],
                        $employee['total_shift_salary'],
                        $employee['total_percentage_salary'],
                        $employee['total_fines'],
                        $employee['total_net_salary'],
                        $employee['avg_salary_per_shift']
                    ]);
                }
                
                fputcsv($file, []);
                fputcsv($file, ['Итого зарплата:', '', '', '', '', '', '', $salaryData['total_salary']]);
                fputcsv($file, ['Выручка за период:', '', '', '', '', '', '', $salaryData['total_revenue']]);
                fputcsv($file, ['Количество смен:', '', '', $salaryData['shifts_count']]);
                fputcsv($file, ['Уникальных сотрудников:', '', '', $salaryData['unique_employees']]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}