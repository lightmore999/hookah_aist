<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\BonusCard;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with('bonusCard')->latest()->get();
        $bonusCards = BonusCard::all();
        return view('clients.index', compact('clients', 'bonusCards'));
    }

    public function create()
    {
        $bonusCards = BonusCard::all();
        return view('clients.create', compact('bonusCards'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:clients,phone',
            'comment' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'bonus_card_id' => 'nullable|exists:bonus_cards,IDBonusCard',
            'bonus_points' => 'integer|min:0',
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Клиент успешно создан!');
    }

    public function show(Client $client)
    {
        $client->load('bonusCard');
        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        $bonusCards = BonusCard::all();
        return view('clients.edit', compact('client', 'bonusCards'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:clients,phone,' . $client->id,
            'comment' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'bonus_card_id' => 'nullable|exists:bonus_cards,IDBonusCard',
            'bonus_points' => 'integer|min:0',
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Данные клиента обновлены!');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Клиент успешно удалён!');
    }
}