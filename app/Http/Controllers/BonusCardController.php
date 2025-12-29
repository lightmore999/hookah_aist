<?php

namespace App\Http\Controllers;

use App\Models\BonusCard;
use Illuminate\Http\Request;

class BonusCardController extends Controller
{
    public function index()
    {
        $bonusCards = BonusCard::withCount('clients')->get();
        return view('bonus_cards.index', compact('bonusCards'));
    }

    public function create()
    {
        return view('bonus_cards.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100',
            'RequiredSpendAmount' => 'required|integer|min:0',
            'EarntRantTable' => 'required|integer|min:0|max:100',
            'EarntRantTakeaway' => 'required|integer|min:0|max:100',
            'MaxSpendPercent' => 'required|integer|min:0|max:100',
            'TableCloseDiscountPercent' => 'required|integer|min:0|max:100',
        ]);

        BonusCard::create($validated);

        return redirect()->route('bonus-cards.index')
            ->with('success', 'Бонусная карта успешно создана!');
    }

    public function show($id) // Изменено: принимаем ID
    {
        $bonusCard = BonusCard::findOrFail($id);
        $bonusCard->load('clients');
        return view('bonus_cards.show', compact('bonusCard'));
    }

    public function edit($id) // Изменено: принимаем ID
    {
        $bonusCard = BonusCard::findOrFail($id);
        return view('bonus_cards.edit', compact('bonusCard'));
    }

    public function update(Request $request, $id) // Изменено: принимаем ID
    {
        $bonusCard = BonusCard::findOrFail($id);
        
        $validated = $request->validate([
            'Name' => 'required|string|max:100',
            'RequiredSpendAmount' => 'required|integer|min:0',
            'EarntRantTable' => 'required|integer|min:0|max:100',
            'EarntRantTakeaway' => 'required|integer|min:0|max:100',
            'MaxSpendPercent' => 'required|integer|min:0|max:100',
            'TableCloseDiscountPercent' => 'required|integer|min:0|max:100',
        ]);

        $bonusCard->update($validated);

        return redirect()->route('bonus-cards.index')
            ->with('success', 'Бонусная карта успешно обновлена!');
    }

    public function destroy($id) // Изменено: принимаем ID
    {
        $bonusCard = BonusCard::findOrFail($id);
        
        if ($bonusCard->clients()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Нельзя удалить карту, так как есть клиенты с этой картой!');
        }

        $bonusCard->delete();

        return redirect()->route('bonus-cards.index')
            ->with('success', 'Бонусная карта успешно удалена!');
    }
}