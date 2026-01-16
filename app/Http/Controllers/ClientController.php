<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\BonusCard;
use App\Models\BonusHistory;
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

    /**
     * Начислить бонусы клиенту
     */
    public function addBonus(Request $request, Client $client)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);
        
        // Начисляем бонусы
        $oldBalance = $client->bonus_points;
        $client->increment('bonus_points', $validated['amount']);
        $client->refresh(); // обновляем объект
        
        // Создаем запись в истории
        BonusHistory::create([
            'client_id' => $client->id, // теперь client_id вместо user_id
            'amount' => $validated['amount'],
            'operation_type' => 'credit',
            'balance_after' => $client->bonus_points,
            'reason' => $validated['reason'] ?? 'Начисление бонусов',
            'sale_id' => null,
        ]);
        
        return redirect()->route('clients.index')
            ->with('success', '✅ Начислено ' . $validated['amount'] . ' бонусов клиенту ' . $client->name);
    }

    public function subtractBonus(Request $request, Client $client)
    {
        $validated = $request->validate([
            'amount' => [
                'required', 
                'integer', 
                'min:1',
                function ($attribute, $value, $fail) use ($client) {
                    if ($value > $client->bonus_points) {
                        $fail('Недостаточно бонусов для списания. Доступно: ' . $client->bonus_points);
                    }
                }
            ],
            'reason' => 'nullable|string|max:255',
        ]);
        
        // Списываем бонусы
        $oldBalance = $client->bonus_points;
        $client->decrement('bonus_points', $validated['amount']);
        $client->refresh();
        
        // Создаем запись в истории
        BonusHistory::create([
            'client_id' => $client->id, // теперь client_id вместо user_id
            'amount' => $validated['amount'],
            'operation_type' => 'debit',
            'balance_after' => $client->bonus_points,
            'reason' => $validated['reason'] ?? 'Списание бонусов',
            'sale_id' => null,
        ]);
        
        return redirect()->route('clients.index')
            ->with('success', '⚠️ Списано ' . $validated['amount'] . ' бонусов у клиента ' . $client->name);
    }
}