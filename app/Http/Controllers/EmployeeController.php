<?php

namespace App\Http\Controllers;

use App\Models\User; // Меняем Employee на User
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Берем только пользователей с ролью employee
        $employees = User::where('role', 'employee')->get();
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email', // Меняем employees на users
            'password' => 'required|string|min:8',
            'position' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:12',
            'shift_salary' => 'nullable|numeric|min:0',
            'revenue_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        // Создаем пользователя с ролью employee
        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'employee', // Добавляем роль
            'position' => $request->position,
            'social_network' => $request->social_network,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'shift_salary' => $request->shift_salary ?? 0,
            'revenue_percentage' => $request->revenue_percentage ?? 0,
            'inn' => $request->inn,
            'tips_link' => $request->tips_link,
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно создан.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $employee) // Меняем тип с Employee на User
    {
        // Проверяем, что это действительно сотрудник
        if ($employee->role !== 'employee') {
            abort(404, 'Пользователь не является сотрудником');
        }
        
        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $employee) // Меняем тип с Employee на User
    {
        // Проверяем, что это действительно сотрудник
        if ($employee->role !== 'employee') {
            abort(404, 'Пользователь не является сотрудником');
        }
        
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $employee) // Меняем тип
    {
        // Проверяем, что это действительно сотрудник
        if ($employee->role !== 'employee') {
            abort(404, 'Пользователь не является сотрудником');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->id, // Меняем employees на users
            'position' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:12',
            'shift_salary' => 'nullable|numeric|min:0',
            'revenue_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $data = $request->only([
            'name', 'email', 'position', 'social_network', 
            'phone', 'notes', 'shift_salary', 'revenue_percentage',
            'inn', 'tips_link'
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        return redirect()->route('employees.index')
            ->with('success', 'Данные сотрудника обновлены.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $employee) // Меняем тип
    {
        // Проверяем, что это действительно сотрудник
        if ($employee->role !== 'employee') {
            abort(404, 'Пользователь не является сотрудником');
        }
        
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник удален.');
    }
}