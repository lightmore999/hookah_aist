<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\BonusHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BonusHistoryController extends Controller
{
    /**
     * Показать историю бонусов клиента
     */
    public function index(Request $request, Client $client): View
    {
        // Проверяем, что клиент существует
        $client->load('bonusCard');

        // Получаем историю бонусов клиента
        $history = BonusHistory::where('client_id', $client->id)
            ->with(['sale']) // только sale, без items
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Статистика
        $totalCredits = BonusHistory::where('client_id', $client->id)
            ->where('operation_type', 'credit')
            ->sum('amount');
            
        $totalDebits = BonusHistory::where('client_id', $client->id)
            ->where('operation_type', 'debit')
            ->sum('amount');

        return view('bonus-history.index', [
            'history' => $history,
            'client' => $client,
            'totalCredits' => $totalCredits,
            'totalDebits' => $totalDebits,
            'currentBalance' => $client->bonus_points,
        ]);
    }
}