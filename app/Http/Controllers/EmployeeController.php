<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::all();
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
            'email' => 'required|email|unique:employees,email',
            'password' => 'required|string|min:8',
            'position' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:12',
            'hookah_percentage' => 'nullable|numeric|min:0|max:100',
            'hookah_rate' => 'nullable|numeric|min:0',
            'shift_rate' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $employee = Employee::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'position' => $request->position,
            'social_network' => $request->social_network,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'hookah_percentage' => $request->hookah_percentage ?? 0,
            'hookah_rate' => $request->hookah_rate ?? 0,
            'shift_rate' => $request->shift_rate ?? 0,
            'hourly_rate' => $request->hourly_rate ?? 0,
            'inn' => $request->inn,
            'tips_link' => $request->tips_link,
        ]);

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник успешно создан.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $employee->id,
            'position' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'inn' => 'nullable|string|max:12',
            'hookah_percentage' => 'nullable|numeric|min:0|max:100',
            'hookah_rate' => 'nullable|numeric|min:0',
            'shift_rate' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $data = $request->only([
            'name', 'email', 'position', 'social_network', 
            'phone', 'notes', 'hookah_percentage', 'hookah_rate',
            'shift_rate', 'hourly_rate', 'inn', 'tips_link'
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
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Сотрудник удален.');
    }
}