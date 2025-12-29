<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Sale;
use App\Models\Client;
use App\Models\Product;
use App\Models\Hookah;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TableController extends Controller
{

    public function index(Request $request)
    {
        // Получаем дату из запроса или используем сегодня
        $selectedDate = $request->has('date') 
            ? Carbon::parse($request->date)
            : Carbon::today();
        
        // Получаем все столы на выбранную дату
        $tables = Table::whereDate('booking_date', $selectedDate)
            ->orderBy('table_number')
            ->orderBy('booking_time')
            ->get()
            ->groupBy('table_number');
        
        // Номера столов: 1, 2, 3, 4, "Барная стойка", 6, 7
        $tableNumbers = [1, 2, 3, 4, 'Барная стойка', 6, 7];
        
        // Получаем продажи для этих столов
        $tableIds = Table::whereDate('booking_date', $selectedDate)->pluck('id');
        $allSalesForTables = Sale::whereIn('table_id', $tableIds)
            ->with(['items.product', 'hookahs'])
            ->get()
            ->keyBy('table_id');
        
        // Получаем клиентов для выпадающего списка
        $clients = Client::orderBy('name')->get();
        
        // Получаем товары для модальных окон
        $products = Product::with('recipeComponents.component')
            ->orderBy('name')
            ->get();
        
        // Получаем кальяны для модальных окон
        $hookahs = Hookah::orderBy('name')->get();
        
        return view('tables.index', compact(
            'tables',
            'tableNumbers',
            'selectedDate',
            'allSalesForTables',
            'clients',
            'products',
            'hookahs'
        ));
    }
    
    // Создание стола
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|integer|min:1|max:50',
            'booking_date' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:30|max:720',
            'guest_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'guests_count' => 'nullable|integer|min:1',
            'comment' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'nullable|string|in:new,opened_without_hookah,opened_with_hookah,closed'
        ]);
        
        // Если статус не указан, устанавливаем по умолчанию 'new' (забронирован)
        if (!isset($validated['status'])) {
            $validated['status'] = 'new';
        }
        
        // Если выбран клиент, берем его данные
        if (!empty($validated['client_id'])) {
            $client = Client::find($validated['client_id']);
            $validated['guest_name'] = $client->name;
            $validated['phone'] = $client->phone;
        }
        
        // Создаем только стол, продажа НЕ создается
        $table = Table::create($validated);
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол забронирован успешно! Продажа будет создана при открытии стола.');
    }
    
    // Редактирование стола
    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'table_number' => 'required|integer|min:1|max:50',
            'booking_date' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i', // Добавили end_time
            'guest_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'guests_count' => 'nullable|integer|min:1|max:50',
            'comment' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'nullable|string|in:new,opened_without_hookah,opened_with_hookah,closed'
        ]);
        
        // Рассчитываем длительность на основе времени начала и окончания
        $startTime = \Carbon\Carbon::parse($validated['booking_time']);
        $endTime = \Carbon\Carbon::parse($validated['end_time']);
        
        // Если время окончания меньше времени начала, значит это на следующий день
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }
        
        $duration = $startTime->diffInMinutes($endTime);
        
        // Добавляем рассчитанную длительность в массив данных
        $validated['duration'] = $duration;
        
        // Если выбран клиент, берем его данные
        if (!empty($validated['client_id'])) {
            $client = Client::find($validated['client_id']);
            $validated['guest_name'] = $client->name;
            $validated['phone'] = $client->phone;
        } else {
            // Если клиент удален, сбрасываем ID клиента
            $validated['client_id'] = null;
        }
        
        $table->update($validated);
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол обновлен успешно!');
    }
    
    // Удаление стола
    public function destroy(Table $table)
    {
        // Удаляем связанную продажу если она существует
        $sale = Sale::where('table_id', $table->id)->first();
        if ($sale) {
            // Удаляем товары продажи
            SaleItem::where('sale_id', $sale->id)->delete();
            // Удаляем кальяны
            $sale->hookahs()->detach();
            // Удаляем продажу
            $sale->delete();
        }
        
        // Удаляем стол
        $table->delete();
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол и связанная продажа удалены успешно!');
    }
    
    // Изменение статуса стола (если еще нужно)
    public function changeStatus(Request $request, Table $table)
    {
        $request->validate([
            'status' => 'required|string|in:new,opened_without_hookah,opened_with_hookah,closed'
        ]);
        
        $oldStatus = $table->status;
        $newStatus = $request->status;
        
        // Если стол открывается (из статуса 'new' в любой opened_*)
        if (($newStatus === 'opened_without_hookah' || $newStatus === 'opened_with_hookah') && 
            $oldStatus === 'new') {
            
            // Проверяем, есть ли уже продажа для этого стола
            $existingSale = Sale::where('table_id', $table->id)->first();
            if (!$existingSale) {
                // Создаем продажу для этого стола
                $warehouse = Warehouse::first();
                
                if (!$warehouse) {
                    $warehouse = Warehouse::create([
                        'name' => 'Основной склад',
                        'address' => 'Основной адрес',
                        'phone' => '+79999999999'
                    ]);
                }
                
                Sale::create([
                    'client_id' => $table->client_id,
                    'warehouse_id' => $warehouse->id,
                    'table_id' => $table->id,
                    'total' => 0,
                    'discount' => 0,
                    'status' => 'new',
                    'sale_date' => now(),
                    'payment_method' => null,
                    'comment' => null,
                ]);
            }
        }
        
        // Обновляем статус стола
        $table->update(['status' => $newStatus]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Статус стола изменен',
                'status' => $newStatus
            ]);
        }
        
        // Для обычного POST запроса - редирект
        return redirect()->route('tables.index')
            ->with('success', 'Стол успешно открыт!');
    }
    
    // Модалка для товаров
    public function showSaleModal(Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        $sale->load('items.product');
        $products = Product::with('recipeComponents.component')
            ->orderBy('name')
            ->get();
        
        return view('tables.modals.sale-products', compact('sale', 'products', 'table'));
    }
    
    // Добавить товар в продажу стола
    public function addProductToSale(Request $request, Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя добавлять товары в завершенную продажу'
            ], 400);
        }
        
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0.01'
        ]);
        
        $product = Product::find($validated['product_id']);
        
        // Для штучных товаров проверяем целое число
        if ($product->unit === 'шт' && floor($validated['quantity']) != $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Для штучных товаров количество должно быть целым числом'
            ], 400);
        }
        
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
        ]);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        
        return response()->json([
            'success' => true,
            'message' => 'Товар добавлен успешно',
            'total' => $sale->fresh()->total
        ]);
    }

    
    // Добавить кальян в продажу стола
    public function addHookahToSale(Request $request, Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя добавлять кальяны в завершенную продажу'
            ], 400);
        }
        
        $validated = $request->validate([
            'hookah_id' => 'required|exists:hookahs,id'
        ]);
        
        $sale->hookahs()->attach($validated['hookah_id']);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        
        return response()->json([
            'success' => true,
            'message' => 'Кальян добавлен успешно',
            'total' => $sale->fresh()->total
        ]);
    }
    
    // Удалить кальян из продажи
    public function removeHookahFromSale(Table $table, Hookah $hookah)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалять кальяны из завершенной продажи'
            ], 400);
        }
        
        $sale->hookahs()->detach($hookah->id);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        
        return response()->json([
            'success' => true,
            'message' => 'Кальян удален успешно',
            'total' => $sale->fresh()->total
        ]);
    }
    
    // Модалка для закрытия стола и завершения продажи
   public function showCloseModal(Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        $sale->load(['items.product', 'hookahs', 'client.bonusCard']);
        
        // Считаем суммы
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $hookahsTotal = $sale->hookahs->sum('price');
        $subtotal = $productsTotal + $hookahsTotal;
        $finalTotal = $subtotal - $sale->discount - $sale->used_bonus_points;
        
        // Данные клиента для бонусов
        $clientMaxPercent = $sale->client && $sale->client->bonusCard 
            ? $sale->client->bonusCard->MaxSpendPercent 
            : 50;
        
        return view('tables.modals.close-sale', compact(
            'sale', 
            'table', 
            'productsTotal', 
            'hookahsTotal', 
            'subtotal', 
            'finalTotal',
            'clientMaxPercent'
        ));
    }
    
    // Завершить продажу и закрыть стол
    public function closeSaleAndTable(Request $request, Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return redirect()->back()->with('error', 'Продажа уже завершена');
        }
        
        $validated = $request->validate([
            'discount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,online,terminal',
            'comment' => 'nullable|string|max:1000',
            'use_bonuses' => 'boolean',
            'bonus_points_to_use' => 'nullable|integer|min:0'
        ]);
        
        // Обработка бонусов
        if (isset($validated['use_bonuses']) && $validated['use_bonuses'] && !empty($validated['bonus_points_to_use'])) {
            if (!$sale->client_id) {
                return back()->with('error', 'Для использования бонусов необходимо указать клиента');
            }
            
            // Загружаем клиента с бонусной картой
            $sale->load(['client.bonusCard']);
            
            // Проверяем максимальное количество бонусов
            $maxUsable = $sale->getMaxUsableBonuses();
            
            if ($validated['bonus_points_to_use'] > $maxUsable) {
                return back()->with('error', "Можно использовать не более {$maxUsable} бонусов");
            }
            
            $bonusResult = $sale->applyBonuses($validated['bonus_points_to_use']);
            if (!$bonusResult['success']) {
                return back()->with('error', $bonusResult['message']);
            }
        }
        
        // Проверяем наличие товаров на складе
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                // Проверка для составных товаров
                foreach ($product->recipeComponents as $component) {
                    $stock = Stock::where('warehouse_id', $sale->warehouse_id)
                                ->where('product_id', $component->component_product_id)
                                ->first();
                    
                    $requiredQuantity = $item->quantity * $component->quantity;
                    
                    if (!$stock || $stock->quantity < $requiredQuantity) {
                        // Отменяем бонусы если были применены
                        if ($sale->used_bonus_points > 0) {
                            $sale->cancelBonuses();
                        }
                        
                        return back()->with('error', 
                            "Недостаточно компонента для товара '{$product->name}'"
                        );
                    }
                }
            } else {
                // Проверка для обычных товаров
                $stock = Stock::where('warehouse_id', $sale->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                
                if (!$stock || $stock->quantity < $item->quantity) {
                    // Отменяем бонусы если были применены
                    if ($sale->used_bonus_points > 0) {
                        $sale->cancelBonuses();
                    }
                    
                    return back()->with('error', 
                        "Недостаточно товара: {$product->name}"
                    );
                }
            }
        }
        
        // Списываем товары со склада
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                foreach ($product->recipeComponents as $component) {
                    $stock = Stock::where('warehouse_id', $sale->warehouse_id)
                                ->where('product_id', $component->component_product_id)
                                ->first();
                    
                    $requiredQuantity = $item->quantity * $component->quantity;
                    $stock->quantity -= $requiredQuantity;
                    $stock->save();
                }
            } else {
                $stock = Stock::where('warehouse_id', $sale->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                
                $stock->quantity -= $item->quantity;
                $stock->save();
            }
        }
        
        // Сначала обновляем скидку в продаже
        $sale->update([
            'discount' => $validated['discount'] ?? 0,
        ]);
        
        // Затем пересчитываем сумму с учетом скидки и бонусов
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        // Начисляем бонусы клиенту по правилам карты
        $bonusMessage = '';
        if ($sale->client_id) {
            $pointsAwarded = $sale->awardBonusPoints();
            if ($pointsAwarded > 0) {
                $bonusMessage = " Начислено {$pointsAwarded} бонусов.";
            }
        }
        
        // Обновляем остальные поля продажи
        $sale->update([
            'status' => 'completed',
            'payment_method' => $validated['payment_method'],
            'comment' => $validated['comment'] ?? $sale->comment,
        ]);
        
        // Закрываем стол
        $table->update([
            'status' => 'closed'
        ]);
        
        $successMessage = 'Стол закрыт и продажа завершена успешно!';
        
        if ($bonusMessage) {
            $successMessage .= $bonusMessage;
        }
        
        if ($sale->used_bonus_points > 0) {
            $successMessage .= " Использовано {$sale->used_bonus_points} бонусов.";
        }
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage
            ]);
        }
        
        return redirect()->route('tables.index')
            ->with('success', $successMessage);
    }
    
    // Удалить товар из продажи
    public function removeProductFromSale(Table $table, SaleItem $item)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалять товары из завершенной продажи'
            ], 400);
        }
        
        if ($item->sale_id !== $sale->id) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не принадлежит этой продаже'
            ], 400);
        }
        
        $item->delete();
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        
        return response()->json([
            'success' => true,
            'message' => 'Товар удален успешно',
            'total' => $sale->fresh()->total
        ]);
    }
    
    // Обновить количество товара
    public function updateProductQuantity(Request $request, Table $table, SaleItem $item)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя изменять товары в завершенной продаже'
            ], 400);
        }
        
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001'
        ]);
        
        $item->update(['quantity' => $validated['quantity']]);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        
        return response()->json([
            'success' => true,
            'message' => 'Количество обновлено',
            'total' => $sale->fresh()->total
        ]);
    }
    
    // Вспомогательная функция для пересчета суммы
    private function recalculateSaleTotal(Sale $sale)
    {
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $hookahsTotal = $sale->hookahs->sum('price');
        
        // Сумма без скидки и бонусов
        $subtotal = $productsTotal + $hookahsTotal;
        
        // Вычитаем скидку и бонусы
        $total = $subtotal - ($sale->discount ?? 0) - ($sale->used_bonus_points ?? 0);
        
        // Не даем уйти в минус
        $total = max(0, $total);
        
        $sale->update(['total' => $total]);
        
        return $total;
    }

    // TableController.php
    public function getSaleItems(Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->first();
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Продажа не найдена'
            ], 404);
        }
        
        $items = $sale->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit' => $item->product->unit,
                'unit_price' => $item->unit_price,
                'total' => $item->quantity * $item->unit_price
            ];
        });
        
        return response()->json([
            'success' => true,
            'items' => $items,
            'total' => $sale->total
        ]);
    }


    public function getSaleHookahs($id)
    {
        try {
            // Находим стол
            $table = Table::findOrFail($id);
            
            // Находим продажу для этого стола
            $sale = Sale::where('table_id', $id)->first();
            
            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Продажа не найдена',
                    'hookahs' => [],
                    'total' => 0
                ]);
            }
            
            // Загружаем кальяны
            $sale->load('hookahs');
            
            $hookahs = $sale->hookahs->map(function($hookah) {
                return [
                    'id' => $hookah->id,
                    'name' => $hookah->name,
                    'price' => (float)$hookah->price
                ];
            });
            
            return response()->json([
                'success' => true,
                'hookahs' => $hookahs,
                'total' => (float)$sale->hookahs->sum('price'),
                'saleId' => $sale->id,
                'tableInfo' => [
                    'tableNumber' => $table->table_number,
                    'guestName' => $table->guest_name ?? ($table->client->name ?? 'Клиент')
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getSaleHookahs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки кальянов: ' . $e->getMessage(),
                'hookahs' => [],
                'total' => 0
            ], 500);
        }
    }

    public function getSaleData($id) 
    {
        try {
            $table = Table::findOrFail($id);
            $sale = Sale::where('table_id', $id)->first();
            
            if (!$sale) {
                return response()->json([
                    'success' => true,
                    'products' => [],
                    'hookahs' => [],
                    'productsTotal' => 0,
                    'hookahsTotal' => 0,
                    'subtotal' => 0,
                    'discount' => 0,
                    'bonusDiscount' => 0,
                    'finalTotal' => 0,
                    'paymentMethod' => null,
                    'comment' => null,
                    'clientId' => null,
                    'clientName' => null,
                    'clientBonusPoints' => 0,
                    'clientMaxSpendPercent' => 50,
                    'usedBonusPoints' => 0,
                    'bonusEarned' => 0
                ]);
            }
            
            // Загружаем товары, кальяны и клиента с бонусной картой
            $sale->load(['items.product', 'hookahs', 'client.bonusCard']);
            
            $products = $sale->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->product->name,
                    'quantity' => (float)$item->quantity,
                    'unit' => $item->product->unit,
                    'unit_price' => (float)$item->unit_price,
                    'total' => (float)($item->quantity * $item->unit_price)
                ];
            });
            
            $hookahs = $sale->hookahs->map(function($hookah) {
                return [
                    'id' => $hookah->id,
                    'name' => $hookah->name,
                    'price' => (float)$hookah->price
                ];
            });
            
            $productsTotal = $sale->items->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });
            
            $hookahsTotal = $sale->hookahs->sum('price');
            $subtotal = $productsTotal + $hookahsTotal;
            
            // Данные клиента для бонусов
            $clientData = [
                'id' => $sale->client_id,
                'name' => $sale->client ? $sale->client->name : null,
                'bonusPoints' => $sale->client ? $sale->client->bonus_points : 0,
                'maxSpendPercent' => $sale->client && $sale->client->bonusCard 
                    ? $sale->client->bonusCard->MaxSpendPercent 
                    : 50
            ];
            
            // Итоговая сумма с учетом скидки и бонусов
            $finalTotal = max(0, $subtotal - $sale->discount - $sale->used_bonus_points);
            
            // Рассчитываем начисленные бонусы (5% от финальной суммы)
            $bonusEarned = floor($finalTotal * 0.05);
            
            return response()->json([
                'success' => true,
                'products' => $products,
                'hookahs' => $hookahs,
                'productsTotal' => (float)$productsTotal,
                'hookahsTotal' => (float)$hookahsTotal,
                'subtotal' => (float)$subtotal,
                'discount' => (float)$sale->discount,
                'bonusDiscount' => (float)$sale->used_bonus_points,
                'finalTotal' => (float)$finalTotal,
                'paymentMethod' => $sale->payment_method,
                'comment' => $sale->comment,
                'clientId' => $clientData['id'],
                'clientName' => $clientData['name'],
                'clientBonusPoints' => $clientData['bonusPoints'],
                'clientMaxSpendPercent' => $clientData['maxSpendPercent'],
                'usedBonusPoints' => $sale->used_bonus_points,
                'bonusEarned' => $bonusEarned
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getSaleData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки данных продажи'
            ]);
        }
    }
}