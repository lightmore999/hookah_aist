<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource with calendar view.
     */
    public function index(Request $request)
    {
        // Определяем месяц для отображения
        $month = $request->get('month', now()->format('Y-m'));
        $focusDate = $request->get('focus'); // Новый параметр

        $currentMonth = Carbon::parse($month)->startOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        if ($focusDate) {
            $focusCarbon = Carbon::parse($focusDate);
            
            // Проверяем, есть ли смена на эту дату
            $shiftExists = Shift::whereDate('date', $focusCarbon)->exists();
            
            // Если смены нет и дата не в будущем (или можно и будущее - решите)
            if (!$shiftExists) {
                // Автоматически создаем смену
                $newShift = Shift::create([
                    'date' => $focusCarbon,
                    'status' => 'planned', // или 'open' в зависимости от логики
                    'created_by' => auth()->id(),
                ]);
                
                // Можно добавить флаг, что смена создана автоматически
                session()->flash('info', 'Смена на сегодня создана автоматически');
            }
            
            // Убедимся, что месяц отображает нужную дату
            if (!$focusCarbon->isSameMonth($currentMonth)) {
                $currentMonth = $focusCarbon->copy()->startOfMonth();
                $prevMonth = $currentMonth->copy()->subMonth();
                $nextMonth = $currentMonth->copy()->addMonth();
                $month = $currentMonth->format('Y-m');
            }
                
        }
        
        // Получаем все смены за месяц с сотрудниками
        $shifts = Shift::with(['employees'])
            ->whereBetween('date', [
                $currentMonth->format('Y-m-01'),
                $currentMonth->endOfMonth()->format('Y-m-d')
            ])
            ->get()
            ->keyBy(function($shift) {
                return $shift->date->format('Y-m-d');
            });
        
        // Создаем календарь на месяц
        $weeks = [];
        $firstDayOfMonth = $currentMonth->copy()->startOfMonth();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth();
        
        // Начинаем с понедельника первой недели месяца
        $currentDay = $firstDayOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $lastDay = $lastDayOfMonth->copy()->endOfWeek(Carbon::SUNDAY);
        
        while ($currentDay <= $lastDay) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = $currentDay->copy();
                $currentDay->addDay();
            }
            $weeks[] = $week;
        }
        
        // Все сотрудники для добавления
        $allEmployees = Employee::all();
        
        return view('shifts.index', compact(
            'shifts', 
            'allEmployees',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'focusDate',
            'weeks'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date|unique:shifts,date',
        ]);

        if (Shift::whereDate('date', $request->date)->exists()) {
            return redirect()->route('shifts.index')
                ->with('error', 'Смена на эту дату уже существует.');
        }

        Shift::create([ 
            'date' => $request->date,
            'status' => 'planned',
        ]);

        return redirect()->route('shifts.index')
            ->with('success', 'Смена успешно создана.');
    }

    /**
     * Автоматическое создание смен на месяц
     */
    public function generateMonthly(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);
        
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();
        
        $created = 0;
        $skipped = 0;
        
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Проверяем, существует ли уже смена на эту дату
            if (!Shift::whereDate('date', $date)->exists()) {
                Shift::create([
                    'date' => $date->format('Y-m-d'),
                    'status' => 'planned',
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }
        
        return redirect()->route('shifts.index', ['month' => $month])
            ->with('success', "Создано $created смен, пропущено $skipped (уже существуют).");
    }

    /**
     * Open the shift.
     */
    public function open(Shift $shift)
    {
        if ($shift->isOpen()) {
            return redirect()->route('shifts.index')->with('error', 'Смена уже открыта.');
        }
        
        if ($shift->isClosed()) {
            return redirect()->route('shifts.index')->with('error', 'Нельзя открыть закрытую смену.');
        }

        $shift->open();

        return redirect()->route('shifts.index')->with('success', 'Смена открыта.');
    }

    public function close(Shift $shift)
    {
        if ($shift->isClosed()) {
            return redirect()->route('shifts.index')->with('error', 'Смена уже закрыта.');
        }

        $shift->close();

        return redirect()->route('shifts.index')->with('success', 'Смена закрыта.');
    }

    /**
     * Add employee to shift.
     */
    public function addEmployee(Request $request, Shift $shift)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        // Проверяем, не добавлен ли уже сотрудник
        if ($shift->employees()->where('employees.id', $request->employee_id)->exists()) {
            return back()->with('error', 'Сотрудник уже добавлен в смену.');
        }

        $shift->employees()->attach($request->employee_id);

        return back()->with('success', 'Сотрудник добавлен в смену.');
    }

    /**
     * Remove employee from shift.
     */
    public function removeEmployee(Shift $shift, Employee $employee)
    {
        $shift->employees()->detach($employee->id);

        return back()->with('success', 'Сотрудник удален из смены.');
    }

    /**
     * Bulk add employees to shift
     */
    public function bulkAddEmployees(Request $request, Shift $shift)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $added = 0;
        $skipped = 0;

        foreach ($request->employee_ids as $employeeId) {
            if (!$shift->employees()->where('employees.id', $employeeId)->exists()) {
                $shift->employees()->attach($employeeId);
                $added++;
            } else {
                $skipped++;
            }
        }

        return back()->with('success', "Добавлено $added сотрудников, пропущено $skipped (уже в смене).");
    }

    /**
     * Clear all employees from shift
     */
    public function clearEmployees(Shift $shift)
    {
        $shift->employees()->detach();
        
        return back()->with('success', 'Все сотрудники удалены из смены.');
    }
    /**
     * Обновить сотрудников в смене
     */
    public function updateEmployees(Request $request, Shift $shift)
    {
        $request->validate([
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Получаем выбранных сотрудников (или пустой массив если ничего не выбрано)
        $employeeIds = $request->input('employee_ids', []);
        
        // Синхронизируем сотрудников
        $shift->employees()->sync($employeeIds);
        
        return back()->with('success', 'Список сотрудников обновлен.');
    }

    public function getEmployeesData(Shift $shift)
    {
        $allEmployees = Employee::all();
        $shiftEmployees = $shift->employees()->pluck('employees.id')->toArray();
        
        return view('shifts.partials.employees-list', compact('allEmployees', 'shiftEmployees', 'shift'));
    }

    // В ShiftController.php
    public function jsonData(Shift $shift)
    {
        $allEmployees = Employee::select(['id', 'name', 'position'])->get();
        $shiftEmployees = $shift->employees()->select(['employees.id', 'name', 'position'])->get();
        
        return response()->json([
            'employees' => $allEmployees,
            'shiftEmployees' => $shiftEmployees,
        ]);
    }
    /**
     * Получить текущую смену для хедера
     */
    public function getCurrentShiftForHeader()
    {
        $today = Carbon::parse('2025-12-25');
        return Shift::with(['employees'])
            ->whereDate('date', $today)
            ->first();
    }

}