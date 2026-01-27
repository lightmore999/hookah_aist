<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\User; // Меняем Employee на User
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
        // Запускаем автоматическое закрытие просроченных смен
        $this->autoCloseExpiredShifts();
        
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
        $shifts = Shift::with(['employees']) // employees() - это связь с User где role='employee'
            ->whereBetween('date', [
                $currentMonth->format('Y-m-01'),
                $currentMonth->endOfMonth()->format('Y-m-d')
            ])
            ->get()
            ->keyBy(function($shift) {
                return $shift->date->format('Y-m-d');
            })
            ->map(function($shift) {
                // Добавляем отформатированные заметки
                $shift->formatted_notes = $shift->formatted_notes;
                return $shift;
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
        
        // Все сотрудники для добавления (только с ролью employee)
        $allEmployees = User::where('role', 'employee')->get();
        
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
     * Автоматическое закрытие просроченных смен
     * Смены автоматически закрываются в 12:00 следующего дня
     */
    private function autoCloseExpiredShifts()
    {
        $now = now();
        $today = $now->toDateString();
        
        // Определяем дату "позавчера" (два дня назад)
        $dayBeforeYesterday = $now->copy()->subDays(2)->toDateString();
        
        // Закрываем ВСЕ открытые смены до позавчерашнего дня включительно
        $oldShifts = Shift::where('status', 'open')
            ->whereDate('date', '<=', $dayBeforeYesterday)
            ->get();
        
        $closedCount = 0;
        
        foreach ($oldShifts as $shift) {
            $shift->status = 'closed';
            $shift->closed_at = $now;
            $shift->addAutoCloseNote();
            $shift->save();
            $closedCount++;
        }
        
        // Обработка вчерашней смены
        $yesterday = $now->copy()->subDay()->toDateString();
        
        // Если сейчас после 12:00, закрываем вчерашнюю смену
        if ($now->hour >= 12) {
            $yesterdayShifts = Shift::where('status', 'open')
                ->whereDate('date', $yesterday)
                ->get();
            
            foreach ($yesterdayShifts as $shift) {
                $shift->status = 'closed';
                $shift->closed_at = $now;
                $shift->addAutoCloseNote();
                $shift->save();
                $closedCount++;
            }
        }
        
        if ($closedCount > 0) {
            \Log::info("Автоматически закрыто $closedCount просроченных смен");
        }
        
        return $closedCount;
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

        // Проверяем, есть ли сотрудники в смене
        $employeesCount = $shift->employees()->count();
        
        if ($employeesCount === 0) {
            return redirect()->route('shifts.index')
                ->with('error', 'Нельзя открыть смену без сотрудников. Добавьте хотя бы одного сотрудника.');
        }

        $shift->open();

        return redirect()->route('shifts.index')->with('success', 'Смена открыта.');
    }

    /**
     * Add employee to shift.
     */
    public function addEmployee(Request $request, Shift $shift)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id', // Меняем employees на users
        ]);

        // Проверяем, что это действительно сотрудник
        $user = User::where('id', $request->employee_id)
                   ->where('role', 'employee')
                   ->firstOrFail();

        // Проверяем, не добавлен ли уже сотрудник
        if ($shift->employees()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'Сотрудник уже добавлен в смену.');
        }

        $shift->employees()->attach($user->id);

        return back()->with('success', 'Сотрудник добавлен в смену.');
    }

    /**
     * Remove employee from shift.
     */
    public function removeEmployee(Shift $shift, User $employee) // Меняем тип Employee на User
    {
        // Проверяем, что это действительно сотрудник
        if ($employee->role !== 'employee') {
            abort(404, 'Пользователь не является сотрудником');
        }
        
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
            'employee_ids.*' => 'exists:users,id', // Меняем employees на users
        ]);

        $added = 0;
        $skipped = 0;

        foreach ($request->employee_ids as $employeeId) {
            // Проверяем, что это сотрудник
            $user = User::where('id', $employeeId)
                       ->where('role', 'employee')
                       ->first();
            
            if (!$user) {
                continue; // Пропускаем, если не сотрудник
            }
            
            if (!$shift->employees()->where('users.id', $employeeId)->exists()) {
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
            'employee_ids.*' => 'exists:users,id', // Меняем employees на users
        ]);

        // Получаем выбранных сотрудников (или пустой массив если ничего не выбрано)
        $employeeIds = $request->input('employee_ids', []);
        
        // Фильтруем - оставляем только ID сотрудников
        $employeeIds = User::whereIn('id', $employeeIds)
                          ->where('role', 'employee')
                          ->pluck('id')
                          ->toArray();
        
        // Синхронизируем сотрудников
        $shift->employees()->sync($employeeIds);
        
        return back()->with('success', 'Список сотрудников обновлен.');
    }

    public function getEmployeesData(Shift $shift)
    {
        $allEmployees = User::where('role', 'employee')->get();
        $shiftEmployees = $shift->employees()->pluck('users.id')->toArray();
        
        return view('shifts.partials.employees-list', compact('allEmployees', 'shiftEmployees', 'shift'));
    }

    // В ShiftController.php
    public function jsonData(Shift $shift)
    {
        $allEmployees = User::where('role', 'employee')
                          ->select(['id', 'name', 'position'])
                          ->get();
        $shiftEmployees = $shift->employees()
                              ->select(['users.id', 'name', 'position'])
                              ->get();
        
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

    public function updateNote(Request $request, Shift $shift)
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $shift->setNote($request->note);

        return back()->with('success', 'Комментарий обновлен.');
    }

    public function manageOrCreate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->input('date');
        
        // Ищем существующую смену
        $shift = Shift::whereDate('date', $date)->first();
        
        // Если смены нет - создаем
        if (!$shift) {
            $shift = Shift::create([
                'date' => $date,
                'status' => 'planned',
                'created_by' => auth()->id(),
            ]);
            
            // Можно добавить флаг в сессию, что смена создана автоматически
            session()->flash('info', 'Смена на ' . Carbon::parse($date)->format('d.m.Y') . ' создана автоматически');
        }
        
        // Возвращаем на календарь с фокусом на эту дату
        return redirect()->route('shifts.index', [
            'month' => Carbon::parse($date)->format('Y-m'),
            'focus' => $date
        ]);
    }
}