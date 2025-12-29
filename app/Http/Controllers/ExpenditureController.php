<?php

namespace App\Http\Controllers;

use App\Models\Expenditure;
use App\Models\ExpenditureType;
use Illuminate\Http\Request;

class ExpenditureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenditures = Expenditure::with('expenditureType')->latest()->get();
        $expenditureTypes = ExpenditureType::all();
        
        // Статистика для отображения
        $totalAmount = $expenditures->sum('cost');
        $cashAmount = $expenditures->where('payment_method', 'cash')->sum('cost');
        $cardAmount = $expenditures->where('payment_method', 'card')->sum('cost');
        $monthlyAmount = $expenditures->where('is_monthly_expense', true)->sum('cost');
        
        return view('expenditures.index', compact(
            'expenditures', 
            'expenditureTypes',
            'totalAmount',
            'cashAmount',
            'cardAmount',
            'monthlyAmount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $expenditureTypes = ExpenditureType::all();
        return view('expenditures.create', compact('expenditureTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expenditure_type_id' => 'required|exists:expenditure_types,id',
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card',
            'comment' => 'nullable|string|max:1000',
            'expenditure_date' => 'required|date',
            'is_hidden_admin' => 'sometimes|boolean',
            'is_monthly_expense' => 'sometimes|boolean',
        ]);

        // Исправляем обработку boolean полей
        $validated['is_hidden_admin'] = $request->boolean('is_hidden_admin');
        $validated['is_monthly_expense'] = $request->boolean('is_monthly_expense');

        Expenditure::create($validated);

        return redirect()->route('expenditures.index')
            ->with('success', 'Расход успешно добавлен!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expenditure $expenditure)
    {
        return view('expenditures.show', compact('expenditure'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expenditure $expenditure)
    {
        $expenditureTypes = ExpenditureType::all();
        return view('expenditures.edit', compact('expenditure', 'expenditureTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expenditure $expenditure)
    {
        $validated = $request->validate([
            'expenditure_type_id' => 'required|exists:expenditure_types,id',
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card',
            'comment' => 'nullable|string|max:1000',
            'expenditure_date' => 'required|date',
            'is_hidden_admin' => 'sometimes|boolean',
            'is_monthly_expense' => 'sometimes|boolean',
        ]);

        // Исправляем обработку boolean полей
        $validated['is_hidden_admin'] = $request->boolean('is_hidden_admin');
        $validated['is_monthly_expense'] = $request->boolean('is_monthly_expense');

        $expenditure->update($validated);

        return redirect()->route('expenditures.index')
            ->with('success', 'Расход успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expenditure $expenditure)
    {
        $expenditure->delete();

        return redirect()->route('expenditures.index')
            ->with('success', 'Расход успешно удалён!');
    }
}