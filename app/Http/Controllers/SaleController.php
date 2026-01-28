<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Table;
use App\Models\Hookah;
use App\Models\PaymentMethod; 
use Illuminate\Http\Request;
use App\Models\BonusHistory;
use App\Models\OperationHistory;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['client', 'table', 'hookahs', 'paymentMethod'])
            ->latest('sale_date')
            ->paginate(20);
        
        $clients = Client::all();
        
        return view('sales.index', compact('sales', 'clients'));
    }

    public function create()
    {
        // Проверяем, есть ли активная смена для создания продажи
        if (!\App\Models\Shift::canCreateSale()) {
            return redirect()->route('sales.index')
                ->with('error', 'Нет активной смены. Сначала откройте смену для создания продаж.');
        }
        
        // Получаем активную смену
        $activeShift = \App\Models\Shift::getActiveShift();
        
        $sale = Sale::create([
            'client_id' => null,
            'table_id' => null,
            'total' => 0,
            'discount' => 0,
            'status' => 'new',
            'sale_date' => now(),
            'payment_method_id' => null,
            'comment' => null,
            // shift_id не добавляем, так как его нет в модели
        ]);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Заказ создан успешно! Добавьте товары.');
    }

    public function store(Request $request)
    {
        // Проверяем, есть ли активная смена для создания продажи
        if (!\App\Models\Shift::canCreateSale()) {
            return redirect()->route('sales.index')
                ->with('error', 'Нет активной смены. Сначала откройте смену для создания продаж.');
        }
        
        $sale = Sale::create([
            'client_id' => null,
            'table_id' => null,
            'total' => 0,
            'discount' => 0,
            'status' => 'new',
            'sale_date' => now(),
            'payment_method_id' => null,
            'comment' => null,
        ]);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Заказ создан успешно! Добавьте товары.');
    }

    public function show(Sale $sale)
    {
        $sale->load([
            'client', 
            'items.product',
            'hookahs',
            'table',
            'paymentMethod'
        ]);
        
        if ($sale->client) {
            $sale->client->load('bonusCard');
        }
        
        $products = Product::with(['recipeComponents.component', 'category'])
            ->orderBy('name')
            ->get();
            
        $hookahs = Hookah::orderBy('name')->get();
        
        $clients = Client::all();
        $paymentMethods = PaymentMethod::orderBy('Name')->get();
        
        return view('sales.show', compact(
            'sale', 
            'products',
            'hookahs',
            'clients',
            'paymentMethods'
        ));
    }

    public function edit(Sale $sale)
    {
        if ($sale->status === 'completed') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Нельзя редактировать завершенную продажу');
        }

        $sale->load('items', 'hookahs');
        $products = Product::with('recipeComponents.component')
            ->orderBy('name')
            ->get();
        $hookahs = Hookah::orderBy('name')->get();
        $clients = Client::all();
        $paymentMethods = PaymentMethod::orderBy('Name')->get();
        
        return view('sales.edit', compact(
            'sale', 
            'products', 
            'hookahs', 
            'clients', 
            'paymentMethods'
        ));
    }

    public function update(Request $request, Sale $sale)
    {
        if ($sale->status === 'completed') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Нельзя редактировать завершенную продажу');
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'payment_method_id' => 'nullable|exists:payment_methods,IDPaymentMethod',
            'comment' => 'nullable|string|max:1000',
        ]);

        $sale->update($validated);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Продажа обновлена успешно!');
    }

    /**
     * Завершить продажу с логированием
     */
    public function complete(Request $request, Sale $sale)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Продажа уже завершена');
        }

        $validated = $request->validate([
            'discount' => 'numeric|min:0',
            'use_bonuses' => 'boolean',
            'bonus_points_to_use' => 'nullable|integer|min:0',
            'payment_method_id' => 'required|exists:payment_methods,IDPaymentMethod',
            'comment' => 'nullable|string|max:1000',
            'complete_comment' => 'nullable|string|max:500',
        ]);

        // Сначала обновляем скидку
        $sale->update([
            'discount' => $validated['discount'] ?? 0,
        ]);

        // Пересчитываем сумму с учетом скидки (но без учета бонусов)
        $this->recalculateSaleTotal($sale);
        $sale->refresh();

        // Обработка бонусов (ПОСЛЕ пересчета суммы)
        if (isset($validated['use_bonuses']) && $validated['use_bonuses'] && !empty($validated['bonus_points_to_use'])) {
            if (!$sale->client_id) {
                return back()->with('error', 'Для использования бонусов необходимо указать клиента');
            }
            
            $bonusResult = $sale->applyBonuses($validated['bonus_points_to_use']);
            if (!$bonusResult['success']) {
                return back()->with('error', $bonusResult['message']);
            }
        }

        // Проверяем наличие всех товаров ВО ВСЕХ СКЛАДАХ
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                foreach ($product->recipeComponents as $component) {
                    $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                        ->sum('quantity');
                    
                    $requiredQuantity = $item->quantity * $component->quantity;
                    
                    if ($totalComponentQuantity < $requiredQuantity) {
                        // Отменяем бонусы, если были применены
                        if ($sale->used_bonus_points > 0) {
                            $sale->cancelBonuses();
                        }
                        
                        $componentName = $component->component->name ?? 'Неизвестный компонент';
                        $componentUnit = $component->component->unit ?? 'шт';
                        
                        return back()->with('error', 
                            "Недостаточно компонента '{$componentName}' в системе. " .
                            "Требуется: {$requiredQuantity} {$componentUnit}, " .
                            "Доступно: {$totalComponentQuantity} {$componentUnit}");
                    }
                }
            } else {
                $totalProductQuantity = Stock::where('product_id', $item->product_id)
                    ->sum('quantity');

                if ($totalProductQuantity < $item->quantity) {
                    // Отменяем бонусы, если были применены
                    if ($sale->used_bonus_points > 0) {
                        $sale->cancelBonuses();
                    }
                    
                    return back()->with('error', 
                        "Недостаточно товара: {$product->name}. " .
                        "Требуется: {$item->quantity} {$product->unit}, " .
                        "Доступно: {$totalProductQuantity} {$product->unit}");
                }
            }
        }

        // Списываем товары со складов
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                foreach ($product->recipeComponents as $component) {
                    $requiredComponentQuantity = $item->quantity * $component->quantity;
                    
                    $stocks = Stock::where('product_id', $component->component_product_id)
                        ->where('quantity', '>', 0)
                        ->orderByDesc('quantity')
                        ->get();
                    
                    $remainingQuantity = $requiredComponentQuantity;
                    foreach ($stocks as $stock) {
                        if ($remainingQuantity <= 0) break;
                        
                        $quantityToDeduct = min($stock->quantity, $remainingQuantity);
                        $stock->quantity -= $quantityToDeduct;
                        $stock->save();
                        
                        $remainingQuantity -= $quantityToDeduct;
                    }
                }
            } else {
                $requiredQuantity = $item->quantity;
                
                $stocks = Stock::where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)
                    ->orderByDesc('quantity')
                    ->get();
                
                $remainingQuantity = $requiredQuantity;
                foreach ($stocks as $stock) {
                    if ($remainingQuantity <= 0) break;
                    
                    $quantityToDeduct = min($stock->quantity, $remainingQuantity);
                    $stock->quantity -= $quantityToDeduct;
                    $stock->save();
                    
                    $remainingQuantity -= $quantityToDeduct;
                }
            }
        }

        // Закрываем стол, если заказ привязан к столу
        $tableClosed = false;
        if ($sale->table_id) {
            $table = Table::find($sale->table_id);
            if ($table && in_array($table->status, ['opened_without_hookah', 'opened_with_hookah'])) {
                $table->update(['status' => 'closed']);
                $tableClosed = true;
            }
        }

        // Обновляем статус продажи
        $sale->update([
            'status' => 'completed',
            'payment_method_id' => $validated['payment_method_id'],
            'comment' => $validated['comment'] ?? $sale->comment,
        ]);

        // Перезагружаем модель
        $sale->refresh();

        // Начисляем бонусы клиенту по правилам карты
        $bonusMessage = '';
        $pointsAwarded = 0;
        
        if ($sale->client_id) {
            $client = Client::with('bonusCard')->find($sale->client_id);
            
            if ($client) {
                if ($client->bonusCard) {
                    // У клиента ЕСТЬ бонусная карта
                    $bonusPercent = $client->bonusCard->BonusPercent;
                    
                    // Рассчитываем сумму для начисления бонусов
                    // Бонусы начисляются от суммы ПОСЛЕ применения скидки, но ПЕРЕД вычетом бонусов
                    
                    // Сумма товаров
                    $productsTotal = $sale->items->sum(function($item) {
                        return $item->quantity * $item->unit_price;
                    });
                    
                    // Сумма кальянов
                    $hookahsTotal = $sale->hookahs->sum('price');
                    
                    // Итоговая сумма для расчета бонусов
                    $bonusableAmount = $productsTotal + $hookahsTotal - (float) $sale->discount;
                    
                    // Не даем уйти в минус
                    $bonusableAmount = max(0, $bonusableAmount);
                    
                    // Рассчитываем бонусы
                    $pointsAwarded = floor($bonusableAmount * ($bonusPercent / 100));
                    
                    if ($pointsAwarded > 0) {
                        $oldBalance = $client->bonus_points;
                        $client->bonus_points += $pointsAwarded;
                        $client->save();
                        
                        // Сохраняем в историю начисление бонусов
                        BonusHistory::create([
                            'client_id' => $client->id,
                            'amount' => $pointsAwarded,
                            'operation_type' => 'credit',
                            'balance_after' => $client->bonus_points,
                            'reason' => "Начисление {$bonusPercent}% бонусов за продажу #" . $sale->id,
                            'sale_id' => $sale->id,
                        ]);
                        
                        $bonusMessage = " Начислено {$pointsAwarded} бонусов ({$bonusPercent}% от суммы после скидки).";
                    }
                } else {
                    // У клиента НЕТ бонусной карты
                    // Проверяем, достиг ли клиент необходимой суммы для получения карты
                    $requiredSpend = $client->bonusCard ? $client->bonusCard->RequiredSpendAmount : 0;
                    
                    if ($requiredSpend > 0) {
                        // Сумма ВСЕХ покупок клиента (включая текущую)
                        $clientTotalSpent = Sale::where('client_id', $sale->client_id)
                            ->where('status', 'completed')
                            ->sum('total');
                        
                        // Прибавляем текущую продажу
                        $totalSpentAfterThis = $clientTotalSpent + $sale->total;
                        
                        if ($totalSpentAfterThis >= $requiredSpend) {
                            $bonusMessage .= " Клиент достиг необходимой суммы для получения бонусной карты!";
                        }
                    }
                }
                
                // Добавляем покупку клиенту
                $client->addPurchase($sale->total);
            }
        }

        // Получаем название способа оплаты для сообщения
        $paymentMethodName = $sale->paymentMethod ? $sale->paymentMethod->Name : 'Не указан';

        // Формируем сообщение об успехе
        $successMessage = 'Продажа завершена успешно! Товары списаны.' . $bonusMessage;
        $successMessage .= " Способ оплаты: {$paymentMethodName}.";
        
        if ($sale->used_bonus_points > 0) {
            $successMessage .= " Использовано {$sale->used_bonus_points} бонусов.";
        }

        // === ЛОГИРУЕМ ЗАВЕРШЕНИЕ ПРОДАЖИ ===
        $completeComment = $validated['complete_comment'] ?? null;
        $logComment = "Продажа #{$sale->id} завершена. ";
        $logComment .= "Сумма: {$sale->total} руб. ";
        $logComment .= "Скидка: {$sale->discount} руб. ";
        $logComment .= "Способ оплаты: {$paymentMethodName}. ";
        
        if ($sale->used_bonus_points > 0) {
            $logComment .= "Использовано бонусов: {$sale->used_bonus_points}. ";
        }
        
        if ($pointsAwarded > 0) {
            $logComment .= "Начислено бонусов: {$pointsAwarded}. ";
        }
        
        if ($completeComment) {
            $logComment .= "Комментарий: {$completeComment}";
        }
        
        OperationHistory::create([
            'user_id' => auth()->id(),
            'action_type' => OperationHistory::ACTION_CLOSE,
            'entity_type' => OperationHistory::ENTITY_SALE,
            'entity_id' => $sale->id,
            'comment' => $logComment,
        ]);

        if ($tableClosed) {
            $tableDate = $sale->created_at->format('Y-m-d');
            return redirect()->route('tables.index', ['date' => $tableDate])
                ->with('success', $successMessage . ' Стол закрыт.');
        } else {
            return redirect()->route('sales.show', $sale)
                ->with('success', $successMessage);
        }
    }

    /**
     * Удалить продажу с комментарием
     */
    public function destroy(Request $request, Sale $sale)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя удалить завершенную продажу!');
        }

        $request->validate([
            'delete_comment' => 'required|string|min:5|max:500',
        ]);

        // === ЛОГИРУЕМ УДАЛЕНИЕ ПРОДАЖИ ===
        OperationHistory::create([
            'user_id' => auth()->id(),
            'action_type' => OperationHistory::ACTION_DELETE,
            'entity_type' => OperationHistory::ENTITY_SALE,
            'entity_id' => $sale->id,
            'comment' => $request->delete_comment . 
                        " (Удалена продажа #{$sale->id}, сумма: {$sale->total} руб.)",
        ]);
        
        $sale->delete();
        
        return redirect()->route('sales.index')
            ->with('success', 'Продажа удалена успешно!');
    }

    // Методы для работы с товарами
    public function addItem(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0.01',
            'final_quantity' => 'nullable',
            'final_unit_price' => 'nullable',
        ]);

        $product = Product::find($validated['product_id']);
        
        // Для штучных товаров проверяем целое число
        if ($product->unit === 'шт' && floor($validated['quantity']) != $validated['quantity']) {
            return back()->with('error', 'Для штучных товаров количество должно быть целым числом');
        }

        // Проверка наличия ВО ВСЕХ СКЛАДАХ
        $availableQuantity = $this->getAvailableQuantity($product);
        $requestedQuantity = $validated['quantity'];
        
        if (!$this->checkStockAvailability($product, $requestedQuantity)) {
            $unitText = $product->unit === 'шт' ? 'штук' : $product->unit;
            
            // Формируем детальное сообщение об ошибке
            $errorMessage = "Недостаточно товара '{$product->name}' в системе.\n";
            $errorMessage .= "Запрошено: {$requestedQuantity} {$unitText}\n";
            $errorMessage .= "Доступно: {$availableQuantity} {$unitText}";
            
            // Для составных товаров добавляем детали по компонентам
            if ($product->is_composite) {
                $componentDetails = $this->getComponentAvailabilityDetails($product, $requestedQuantity);
                $errorMessage .= "\n\nНедостаточно компонентов:\n";
                
                foreach ($componentDetails as $detail) {
                    if (!$detail['is_available']) {
                        $componentName = $detail['component_name'] ?? 'Компонент #' . $detail['component_id'];
                        $errorMessage .= "- {$componentName}: нужно {$detail['required_total']} {$detail['unit']}, есть {$detail['available']} {$detail['unit']}\n";
                    }
                }
            }
            
            return back()->with('error', $errorMessage);
        }

        // Создаем запись с простыми данными
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
        ]);

        // Пересчитываем сумму (без логирования)
        $sale->recalculateTotal();

        return back()->with('success', 'Товар добавлен в продажу');
    }

    public function updateItem(Request $request, Sale $sale, SaleItem $item)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя редактировать товары в завершенной продаже');
        }

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0.01',
        ]);

        $item->update($validated);
        
        // Пересчитываем сумму (без логирования)
        $this->recalculateSaleTotal($sale);

        return back()->with('success', 'Товар обновлен');
    }

    public function removeItem(Request $request, Sale $sale, SaleItem $item)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя удалять товары из завершенной продажи');
        }

        $item->delete();
        
        // Пересчитываем сумму (без логирования)
        $sale->recalculateTotal();

        return back()->with('success', 'Товар удален из продажи');
    }

    // Методы для работы с кальянами
    public function addHookah(Request $request, Sale $sale)
    {
        if (!$sale->table_id) {
            return back()->with('error', 'Кальяны можно добавлять только к заказам со столами');
        }

        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя добавлять кальяны в завершенный заказ');
        }

        $validated = $request->validate([
            'hookah_id' => 'required|exists:hookahs,id',
            'comment' => 'nullable|string|max:500',
        ]);

        $hookah = Hookah::find($validated['hookah_id']);
        
        // Проверяем, не добавлен ли уже этот кальян
        if ($sale->hookahs()->where('hookah_id', $hookah->id)->exists()) {
            return back()->with('error', 'Этот кальян уже добавлен к продаже');
        }
        
        $sale->hookahs()->attach($hookah->id);
        
        // Пересчитываем сумму (без логирования)
        $sale->recalculateTotal();

        return back()->with('success', 'Кальян успешно добавлен');
    }

    public function removeHookah(Request $request, Sale $sale, Hookah $hookah)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя удалять кальяны из завершенного заказа');
        }

        // Проверяем, есть ли этот кальян в продаже
        if (!$sale->hookahs()->where('hookah_id', $hookah->id)->exists()) {
            return back()->with('error', 'Этот кальян не найден в продаже');
        }
        
        $sale->hookahs()->detach($hookah->id);
        
        // Пересчитываем сумму (без логирования)
        $sale->recalculateTotal();

        return back()->with('success', 'Кальян успешно удален');
    }

    /**
     * Отменить продажу с комментарием
     */
    public function cancel(Request $request, Sale $sale)
    {
        $request->validate([
            'cancel_comment' => 'required|string|min:5|max:500',
        ]);

        // Используем метод модели для отмены (без логирования внутри)
        $result = $sale->cancelSale($request->cancel_comment);
        
        if ($result['success']) {
            // === ЛОГИРУЕМ ОТМЕНУ ПРОДАЖИ ===
            OperationHistory::create([
                'user_id' => auth()->id(),
                'action_type' => OperationHistory::ACTION_DELETE,
                'entity_type' => OperationHistory::ENTITY_SALE,
                'entity_id' => $sale->id,
                'comment' => $request->cancel_comment . 
                            " (Продажа #{$sale->id} отменена, сумма: {$sale->total} руб.)",
            ]);
            
            return redirect()->route('sales.index')
                ->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    /**
     * Изменить статус продажи с комментарием
     */
    public function changeStatus(Request $request, Sale $sale)
    {
        $request->validate([
            'status' => 'required|in:new,in_progress,completed,cancelled',
            'status_comment' => 'nullable|string|max:500',
        ]);

        // Используем метод модели для изменения статуса (без логирования внутри)
        $result = $sale->changeStatus($request->status, $request->status_comment);
        
        if ($result['success']) {
            // Логируем только изменение статуса на "completed" или "cancelled"
            if (in_array($request->status, ['completed', 'cancelled'])) {
                $newStatus = $sale->fresh()->status_text ?? $request->status;
                $oldStatus = $sale->status_text ?? $sale->status;
                
                OperationHistory::create([
                    'user_id' => auth()->id(),
                    'action_type' => OperationHistory::ACTION_UPDATE,
                    'entity_type' => OperationHistory::ENTITY_SALE,
                    'entity_id' => $sale->id,
                    'comment' => ($request->status_comment ? $request->status_comment . ". " : "") . 
                                "Статус продажи #{$sale->id} изменен с '{$oldStatus}' на '{$newStatus}'",
                ]);
            }
            
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', 'Ошибка при изменении статуса');
        }
    }

    // Приватные методы
    private function recalculateSaleTotal(Sale $sale)
    {
        // Используем метод модели
        $sale->recalculateTotal();
    }

    private function checkStockAvailability(Product $product, $requestedQuantity)
    {
        if ($product->is_composite) {
            // Проверка для составных товаров - суммарно по всем складам
            foreach ($product->recipeComponents as $component) {
                $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                    ->sum('quantity');
                
                $requiredQuantity = $requestedQuantity * $component->quantity;
                
                if ($totalComponentQuantity < $requiredQuantity) {
                    return false;
                }
            }
        } else {
            // Проверка для обычных товаров - суммарно по всем складам
            $totalProductQuantity = Stock::where('product_id', $product->id)
                ->sum('quantity');
            
            if ($totalProductQuantity < $requestedQuantity) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Получает доступное количество товара на складе
     */
    private function getAvailableQuantity(Product $product)
    {
        if ($product->is_composite) {
            // Для составных товаров находим минимальное количество среди компонентов
            $minAvailable = PHP_INT_MAX;
            
            foreach ($product->recipeComponents as $component) {
                $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                    ->sum('quantity');
                
                if ($totalComponentQuantity == 0) {
                    return 0;
                }
                
                // Сколько можно сделать из доступных компонентов
                $availableForComponent = floor($totalComponentQuantity / $component->quantity);
                $minAvailable = min($minAvailable, $availableForComponent);
            }
            
            return $minAvailable;
        } else {
            // Для обычных товаров - сумма по всем складам
            return Stock::where('product_id', $product->id)
                ->sum('quantity');
        }
    }

    /**
     * Получает детальную информацию о доступности компонентов составного товара
     */
    private function getComponentAvailabilityDetails(Product $product, $requestedQuantity)
    {
        $details = [];
        
        foreach ($product->recipeComponents as $component) {
            $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                ->sum('quantity');
            
            $requiredComponent = $requestedQuantity * $component->quantity;
            
            $details[] = [
                'component_id' => $component->component_product_id,
                'component_name' => $component->componentProduct->name ?? 'Компонент #' . $component->component_product_id,
                'required_per_unit' => $component->quantity,
                'required_total' => $requiredComponent,
                'available' => $totalComponentQuantity,
                'unit' => $component->componentProduct->unit ?? 'шт',
                'is_available' => $requiredComponent <= $totalComponentQuantity,
                'shortage' => max(0, $requiredComponent - $totalComponentQuantity),
                'can_make_units' => floor($totalComponentQuantity / $component->quantity)
            ];
        }
        
        return $details;
    }

    public function checkStock(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0.01'
        ]);
        
        $product = Product::find($validated['product_id']);
        $requestedQuantity = $validated['quantity'];
        
        // Для штучных товаров проверяем целое число
        if ($product->unit === 'шт' && floor($requestedQuantity) != $requestedQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Для штучных товаров количество должно быть целым числом'
            ]);
        }
        
        // Проверка наличия ВО ВСЕХ СКЛАДАХ
        $availableQuantity = $this->getAvailableQuantity($product);
        
        if (!$this->checkStockAvailability($product, $requestedQuantity)) {
            $unitText = $product->unit === 'шт' ? 'штук' : $product->unit;
            
            return response()->json([
                'success' => false,
                'message' => "Недостаточно товара '{$product->name}' в системе. Запрошено: {$requestedQuantity} {$unitText}, Доступно: {$availableQuantity} {$unitText}",
                'available' => $availableQuantity,
                'unit' => $product->unit,
                'product_name' => $product->name,
                'requested' => $requestedQuantity
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Товар доступен для добавления'
        ]);
    }

    public function getClientTotalSpent($clientId)
    {
        $totalSpent = Sale::where('client_id', $clientId)
            ->where('status', 'completed')
            ->sum('total');
        
        return response()->json([
            'totalSpent' => (float) $totalSpent
        ]);
    }
    
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Методы, которые требуют активной смены
            $methodsRequiringShift = [
                'create', 'store', 'addItem', 'updateItem', 
                'addHookah', 'update', 'complete', 'changeStatus'
            ];
            
            if (in_array($request->route()->getActionMethod(), $methodsRequiringShift)) {
                // Для методов, которые работают с существующей продажей
                // (addItem, updateItem и т.д.), получаем смену для времени этой продажи
                if (in_array($request->route()->getActionMethod(), ['addItem', 'updateItem', 'addHookah', 'update', 'complete', 'changeStatus'])) {
                    // Получаем продажу из маршрута
                    $saleId = $request->route('sale')->id ?? null;
                    if ($saleId) {
                        $sale = Sale::find($saleId);
                        if ($sale) {
                            // Проверяем, была ли смена активна во время создания продажи
                            $saleTime = $sale->sale_date ?? $sale->created_at;
                            $shiftForSale = \App\Models\Shift::getShiftForSaleTime($saleTime);
                            
                            if (!$shiftForSale) {
                                if ($request->ajax() || $request->wantsJson()) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Продажа создана вне активной смены.'
                                    ], 403);
                                }
                                return back()->with('error', 'Продажа была создана вне активной смены.');
                            }
                            
                            // Проверяем, не закрыта ли смена сейчас
                            if ($shiftForSale->status === 'closed') {
                                if ($request->ajax() || $request->wantsJson()) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'Смена уже закрыта. Невозможно изменить продажу.'
                                    ], 403);
                                }
                                return back()->with('error', 'Смена уже закрыта. Невозможно изменить продажу.');
                            }
                        }
                    }
                } else {
                    // Для создания новой продажи проверяем текущую активную смену
                    if (!\App\Models\Shift::canCreateSale()) {
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Нет активной смены. Сначала откройте смену.'
                            ], 403);
                        }
                        
                        return redirect()->route('sales.index')
                            ->with('error', 'Нет активной смены. Сначала откройте смену для работы с продажами.');
                    }
                }
            }
            
            return $next($request);
        });
    }
}