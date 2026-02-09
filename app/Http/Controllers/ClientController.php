<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\BonusCard;
use App\Models\BonusHistory;
use App\Models\Sale;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with(['bonusCard', 'sales']);
        
        // Поиск по имени или телефону
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Загружаем всех клиентов и вычисляем дополнительные данные
        $clients = $query->latest()->get()->map(function($client) {
            // Вычисляем общую сумму покупок
            $totalSpent = $client->sales()->sum('total');
            // Вычисляем количество посещений (продаж)
            $visitsCount = $client->sales()->count();
            
            // Добавляем вычисленные поля как динамические свойства
            $client->total_spent = $totalSpent ?? 0;
            $client->visits_count = $visitsCount ?? 0;
            
            return $client;
        });
        
        // Сортировка на уровне коллекции
        if ($sortBy === 'total_spent') {
            $clients = $sortOrder === 'asc' 
                ? $clients->sortBy('total_spent') 
                : $clients->sortByDesc('total_spent');
        } elseif ($sortBy === 'visits_count') {
            $clients = $sortOrder === 'asc' 
                ? $clients->sortBy('visits_count') 
                : $clients->sortByDesc('visits_count');
        } elseif ($sortBy === 'bonus_points') {
            $clients = $sortOrder === 'asc' 
                ? $clients->sortBy('bonus_points') 
                : $clients->sortByDesc('bonus_points');
        } elseif ($sortBy === 'name') {
            $clients = $sortOrder === 'asc' 
                ? $clients->sortBy('name') 
                : $clients->sortByDesc('name');
        }
        
        // Преобразуем обратно в коллекцию с сохранением ключей
        $clients = $clients->values();
        
        $bonusCards = BonusCard::all();
        
        return view('clients.index', compact('clients', 'bonusCards'));
    }
    
    // Остальные методы остаются без изменений...
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
        // Загружаем продажи для отображения деталей
        $client->load(['bonusCard', 'sales']);
        
        // Вычисляем дополнительные данные
        $totalSpent = $client->sales()->sum('total');
        $visitsCount = $client->sales()->count();
        $averageCheck = $visitsCount > 0 ? $totalSpent / $visitsCount : 0;
        
        return view('clients.show', compact('client', 'totalSpent', 'visitsCount', 'averageCheck'));
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
        $client->refresh();
        
        // Создаем запись в истории
        BonusHistory::create([
            'client_id' => $client->id,
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
            'client_id' => $client->id,
            'amount' => $validated['amount'],
            'operation_type' => 'debit',
            'balance_after' => $client->bonus_points,
            'reason' => $validated['reason'] ?? 'Списание бонусов',
            'sale_id' => null,
        ]);
        
        return redirect()->route('clients.index')
            ->with('success', '⚠️ Списано ' . $validated['amount'] . ' бонусов у клиента ' . $client->name);
    }
    
    public function exportExcel()
    {
        $filename = 'clients_' . date('Y-m-d_H-i') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ClientsExport(), 
            $filename
        );
    }
    
    /**
     * История бонусов клиента
     */
    public function bonusHistory(Client $client)
    {
        $bonusHistory = BonusHistory::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('clients.bonus-history', compact('client', 'bonusHistory'));
    }
}