<?php

namespace Database\Seeders;

use App\Models\BonusCard;
use Illuminate\Database\Seeder;

class BonusCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'Name' => 'Стандартная',
                'RequiredSpendAmount' => 0,
                'EarntRantTable' => 5,
                'EarntRantTakeaway' => 2,
                'MaxSpendPercent' => 10,
                'TableCloseDiscountPercent' => 0,
            ],
            [
                'Name' => 'Золотая',
                'RequiredSpendAmount' => 10000,
                'EarntRantTable' => 7,
                'EarntRantTakeaway' => 3,
                'MaxSpendPercent' => 20,
                'TableCloseDiscountPercent' => 5,
            ],
            [
                'Name' => 'Платиновая',
                'RequiredSpendAmount' => 30000,
                'EarntRantTable' => 10,
                'EarntRantTakeaway' => 5,
                'MaxSpendPercent' => 30,
                'TableCloseDiscountPercent' => 10,
            ],
        ];

        foreach ($cards as $card) {
            BonusCard::create($card);
        }
    }
}