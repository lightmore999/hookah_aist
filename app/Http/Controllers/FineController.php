<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\User;
use Illuminate\Http\Request;

class FineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fines = Fine::with('user')->latest()->get();
        $users = User::all(); // Для селекта в модалке
        return view('fines.index', compact('fines', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all();
        return view('fines.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0',
        ]);

        Fine::create($validated);

        return redirect()->route('fines.index')
            ->with('success', 'Штраф успешно добавлен!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Fine $fine)
    {
        return view('fines.show', compact('fine'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fine $fine)
    {
        $users = User::all();
        return view('fines.edit', compact('fine', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fine $fine)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:0',
        ]);

        $fine->update($validated);

        return redirect()->route('fines.index')
            ->with('success', 'Штраф успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fine $fine)
    {
        $fine->delete();

        return redirect()->route('fines.index')
            ->with('success', 'Штраф успешно удалён!');
    }
}