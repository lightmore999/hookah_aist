<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'comment',
        'birth_date',
        'bonus_card_id',
        'bonus_points',
        'total_spent',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'bonus_points' => 'integer',
        'total_spent' => 'decimal:2',
    ];

    /**
     * Получить бонусную карту клиента
     */
    public function bonusCard()
    {
        return $this->belongsTo(BonusCard::class, 'bonus_card_id', 'IDBonusCard');
    }

    /**
     * Получить заказы клиента
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'IDClient');
    }
    
    public function sales()
    {
        return $this->hasMany(Sale::class, 'client_id');
    }

    /**
     * Добавить покупку клиенту и проверить/обновить карту
     */
    public function addPurchase(float $amount): void
    {
        // 1. Увеличиваем сумму покупок
        $this->total_spent += $amount;
        $this->save();

        // 2. Получаем следующую карту
        $nextCard = $this->getNextCard();
        
        // 3. Проверяем, достиг ли клиент суммы для следующей карты
        if ($nextCard && $this->total_spent >= $nextCard->RequiredSpendAmount) {
            // 4. Обновляем карту клиента
            $this->bonus_card_id = $nextCard->IDBonusCard;
            $this->save();
        }
    }

    /**
     * Получить следующую доступную карту
     */
    private function getNextCard(): ?BonusCard
    {
        // Если у клиента нет карты - берем первую
        if (!$this->bonusCard) {
            return BonusCard::orderBy('RequiredSpendAmount')->first();
        }

        // Ищем карту с бОльшим RequiredSpendAmount
        return BonusCard::where('RequiredSpendAmount', '>', $this->bonusCard->RequiredSpendAmount)
            ->orderBy('RequiredSpendAmount')
            ->first();
    }

    /**
     * Вычесть сумму (при возврате или отмене)
     */
    public function subtractPurchase(float $amount): self
    {
        $this->total_spent = max(0, $this->total_spent - $amount);
        $this->save();

        return $this;
    }

    /**
     * Проверить, достиг ли клиент суммы для следующей карты
     */
    public function hasReachedNextCardThreshold(): bool
    {
        $nextCard = $this->getNextCard();
        
        if (!$nextCard) {
            return false;
        }

        return $this->total_spent >= $nextCard->RequiredSpendAmount;
    }

    /**
     * Получить оставшуюся сумму до следующей карты
     */
    public function getRemainingToNextCard(): float
    {
        $nextCard = $this->getNextCard();
        
        if (!$nextCard) {
            return 0;
        }

        $remaining = $nextCard->RequiredSpendAmount - $this->total_spent;
        return max(0, $remaining);
    }

    /**
     * Повысить карту клиента на следующую доступную
     */
    public function upgradeToNextCard(): bool
    {
        $nextCard = $this->getNextCard();
        
        if (!$nextCard) {
            return false;
        }

        $oldCardId = $this->bonus_card_id;
        
        // Меняем карту
        $this->bonus_card_id = $nextCard->IDBonusCard;
        $this->save();

        // Можно добавить бонусы за повышение
        $upgradeBonus = 100;
        $this->bonus_points += $upgradeBonus;
        $this->save();

        return true;
    }

    /**
     * Проверить и при необходимости повысить карту
     */
    public function checkAndUpgradeCard(): bool
    {
        if ($this->hasReachedNextCardThreshold()) {
            return $this->upgradeToNextCard();
        }

        return false;
    }
}