<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ShiftSalary;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
   /**
 * Показать зарплату сотрудника с группировкой по месяцам
 */
public function showEmployeeSalary(Request $request, User $user)
{
    // Проверяем, что это сотрудник
    if ($user->role !== 'employee') {
        abort(404, 'Пользователь не является сотрудником');
    }
    
    // Год для фильтрации (по умолчанию текущий)
    $year = $request->get('year', now()->year);
    
    // Получаем ВСЕ записи зарплаты сотрудника с информацией о сменах
    $salaries = ShiftSalary::with(['shift' => function($query) {
            $query->select('id', 'date', 'opened_at', 'closed_at', 'status');
        }])
        ->where('user_id', $user->id)
        ->whereHas('shift', function($query) use ($year) {
            $query->whereYear('date', $year);
        })
        ->orderByDesc('created_at')
        ->get()
        ->sortByDesc(function($salary) {
            return $salary->shift ? $salary->shift->date : null;
        });
    
    // Группируем по месяцам
    $monthlyData = [];
    $yearlyTotal = 0;
    
    // Инициализируем все месяцы
    for ($month = 1; $month <= 12; $month++) {
        $monthlyData[$month] = [
            'month_number' => $month,
            'month_name' => Carbon::create()->month($month)->translatedFormat('F'),
            'total_amount' => 0,
            'shifts_count' => 0,
            'salaries' => [],
        ];
    }
    
    // Заполняем данные
    foreach ($salaries as $salary) {
        if ($salary->shift) {
            $month = Carbon::parse($salary->shift->date)->month;
            
            $monthlyData[$month]['total_amount'] += $salary->amount;
            $monthlyData[$month]['shifts_count']++;
            $monthlyData[$month]['salaries'][] = [
                'shift_date' => $salary->shift->date->format('d.m.Y'),
                'amount' => $salary->amount,
                'shift_id' => $salary->shift_id,
                'status' => $salary->shift->status,
                'date_sort' => $salary->shift->date, // для сортировки
            ];
            
            $yearlyTotal += $salary->amount;
        }
    }
    
    // Сортируем зарплаты внутри каждого месяца по дате
    foreach ($monthlyData as &$month) {
        if (!empty($month['salaries'])) {
            usort($month['salaries'], function($a, $b) {
                return $b['date_sort'] <=> $a['date_sort']; // от новых к старым
            });
        }
    }
    
    // Убираем пустые месяцы
    $monthlyData = array_filter($monthlyData, function($month) {
        return $month['shifts_count'] > 0;
    });
    
    // Сортируем месяцы от нового к старому
    krsort($monthlyData);
    
    // Также получаем все смены сотрудника за год для информации
    $allShifts = Shift::whereHas('employees', function($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->whereYear('date', $year)
        ->orderByDesc('date')
        ->get();
    
    // Годы для фильтра
    $currentYear = now()->year;
    $years = range($currentYear - 2, $currentYear + 1);
    
    return view('salaries.employee', [
        'user' => $user,
        'monthlyData' => $monthlyData,
        'yearlyTotal' => $yearlyTotal,
        'year' => $year,
        'years' => $years,
        'allShifts' => $allShifts,
        'totalShifts' => $allShifts->count(),
        'closedShifts' => $allShifts->where('status', 'closed')->count(),
    ]);
}
}