<?php

namespace App\Http\Controllers;

use App\Models\ExpenditureType;
use Illuminate\Http\Request;

class ExpenditureTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenditureTypes = ExpenditureType::latest()->get();
        return view('expenditure-types.index', compact('expenditureTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('expenditure-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expenditure_types,name',
        ]);

        ExpenditureType::create($validated);

        return redirect()->route('expenditure-types.index')
            ->with('success', 'Тип расхода успешно добавлен!');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenditureType $expenditureType)
    {
        return view('expenditure-types.show', compact('expenditureType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenditureType $expenditureType)
    {
        return view('expenditure-types.edit', compact('expenditureType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenditureType $expenditureType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expenditure_types,name,' . $expenditureType->id,
        ]);

        $expenditureType->update($validated);

        return redirect()->route('expenditure-types.index')
            ->with('success', 'Тип расхода успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenditureType $expenditureType)
    {
        if ($expenditureType->expenditures()->count() > 0) {
            return redirect()->route('expenditure-types.index')
                ->with('error', 'Нельзя удалить тип расхода, так как существуют связанные расходы!');
        }

        $expenditureType->delete();

        return redirect()->route('expenditure-types.index')
            ->with('success', 'Тип расхода успешно удалён!');
    }
}