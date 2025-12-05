<?php

namespace App\Http\Controllers;

use App\Models\Hookah;
use Illuminate\Http\Request;

class HookahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hookahs = Hookah::latest()->get();
        return view('hookahs.index', compact('hookahs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hookahs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'hookah_maker_rate' => 'required|numeric|min:0',
            'administrator_rate' => 'required|numeric|min:0',
        ]);

        Hookah::create($validated);

        return redirect()->route('hookahs.index')
            ->with('success', 'Кальян успешно добавлен!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Hookah $hookah)
    {
        return view('hookahs.show', compact('hookah'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hookah $hookah)
    {
        return view('hookahs.edit', compact('hookah'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Hookah $hookah)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'hookah_maker_rate' => 'required|numeric|min:0',
            'administrator_rate' => 'required|numeric|min:0',
        ]);

        $hookah->update($validated);

        return redirect()->route('hookahs.index')
            ->with('success', 'Кальян успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hookah $hookah)
    {
        $hookah->delete();

        return redirect()->route('hookahs.index')
            ->with('success', 'Кальян успешно удалён!');
    }
}