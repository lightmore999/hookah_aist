<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Client;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Table;
use App\Models\Hookah;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['client', 'warehouse', 'table', 'hookahs'])
            ->latest('sale_date')
            ->paginate(20);
        
        $clients = Client::all();
        $warehouses = Warehouse::all();
        
        return view('sales.index', compact('sales', 'clients', 'warehouses'));
    }

    public function create()
    {
        $warehouse = Warehouse::first();
        
        if (!$warehouse) {
            return redirect()->route('sales.index')
                ->with('error', 'Невозможно создать заказ. Сначала добавьте склад в системе.');
        }
        
        $sale = Sale::create([
            'client_id' => null,
            'warehouse_id' => $warehouse->id,
            'table_id' => null,
            'total' => 0,
            'discount' => 0,
            'status' => 'new',
            'sale_date' => now(),
            'payment_method' => null,
            'comment' => null,
        ]);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Заказ создан успешно! Добавьте товары.');
    }

    public function store(Request $request)
    {
        $warehouse = Warehouse::first();
        
        if (!$warehouse) {
            return redirect()->route('sales.index')
                ->with('error', 'Невозможно создать заказ. Сначала добавьте склад в системе.');
        }
        
        $sale = Sale::create([
            'client_id' => null,
            'warehouse_id' => $warehouse->id,
            'table_id' => null,
            'total' => 0,
            'discount' => 0,
            'status' => 'new',
            'sale_date' => now(),
            'payment_method' => null,
            'comment' => null,
        ]);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Заказ создан успешно! Добавьте товары.');
    }

    public function show(Sale $sale)
    {
        $sale->load([
            'client', 
            'warehouse', 
            'items.product',
            'hookahs', // это должно быть
            'table'
        ]);
        if ($sale->client) {
            $sale->client->load('bonusCard');
        }
        
        $products = Product::with('recipeComponents.component')
            ->orderBy('name')
            ->get();
            
        $hookahs = Hookah::orderBy('name')->get(); // это должно быть
        
        $clients = Client::all();
        $warehouses = Warehouse::all();
        
        return view('sales.show', compact(
            'sale', 
            'products',
            'hookahs', // это должно быть
            'clients',
            'warehouses'
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
        $warehouses = Warehouse::all();
        
        return view('sales.edit', compact('sale', 'products', 'hookahs', 'clients', 'warehouses'));
    }

    public function update(Request $request, Sale $sale)
    {
        if ($sale->status === 'completed') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Нельзя редактировать завершенную продажу');
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'comment' => 'nullable|string|max:1000',
            // Удалены: discount, payment_method, status
        ]);

        $sale->update($validated);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Продажа обновлена успешно!');
    }

    public function complete(Request $request, Sale $sale)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Продажа уже завершена');
        }

        $validated = $request->validate([
            'discount' => 'numeric|min:0',
            'use_bonuses' => 'boolean',
            'bonus_points_to_use' => 'nullable|integer|min:0',
            'payment_method' => 'required|string|in:cash,card,online,terminal',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Обработка бонусов (УПРОЩЕННАЯ)
        if (isset($validated['use_bonuses']) && $validated['use_bonuses'] && !empty($validated['bonus_points_to_use'])) {
            if (!$sale->client_id) {
                return back()->with('error', 'Для использования бонусов необходимо указать клиента');
            }
            
            $bonusResult = $sale->applyBonuses($validated['bonus_points_to_use']);
            if (!$bonusResult['success']) {
                return back()->with('error', $bonusResult['message']);
            }
        }

        // ВАЖНО: Пересчитываем total сразу после применения бонусов
        if ($sale->wasChanged('used_bonus_points')) {
            $sale->refresh();
            $sale->recalculateTotal();
            $sale->refresh(); // Обновляем данные
        }

        // Проверяем наличие всех товаров
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                foreach ($product->recipeComponents as $component) {
                    $stock = Stock::where('warehouse_id', $sale->warehouse_id)
                                ->where('product_id', $component->component_product_id)
                                ->first();

                    $requiredQuantity = $item->quantity * $component->quantity;
                    
                    if (!$stock || $stock->quantity < $requiredQuantity) {
                        // Отменяем бонусы, если были применены
                        if ($sale->used_bonus_points > 0) {
                            $sale->cancelBonuses();
                        }
                        
                        $componentName = $component->component->name ?? 'Неизвестный компонент';
                        $componentUnit = $component->component->unit ?? 'шт';
                        
                        return back()->with('error', 
                            "Недостаточно компонента '{$componentName}' для товара '{$product->name}'. " .
                            "Требуется: {$requiredQuantity} {$componentUnit}, " .
                            "Доступно: " . ($stock->quantity ?? 0));
                    }
                }
            } else {
                $stock = Stock::where('warehouse_id', $sale->warehouse_id)
                            ->where('product_id', $item->product_id)
                            ->first();

                if (!$stock || $stock->quantity < $item->quantity) {
                    // Отменяем бонусы, если были применены
                    if ($sale->used_bonus_points > 0) {
                        $sale->cancelBonuses();
                    }
                    
                    return back()->with('error', 
                        "Недостаточно товара: {$product->name}. " .
                        "Требуется: {$item->quantity} {$product->unit}, " .
                        "Доступно: " . ($stock->quantity ?? 0));
                }
            }
        }

        // Списываем товары
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

        // Закрываем стол, если заказ привязан к столу
        $tableClosed = false;
        if ($sale->table_id) {
            $table = Table::find($sale->table_id);
            if ($table && in_array($table->status, ['opened_without_hookah', 'opened_with_hookah'])) {
                $table->update(['status' => 'closed']);
                $tableClosed = true;
            }
        }

        // Сначала обновляем скидку
        $sale->update([
            'discount' => $validated['discount'] ?? 0,
        ]);

        // Затем пересчитываем сумму с учетом скидки
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

        // Обновляем остальные поля после пересчета
        $sale->update([
            'status' => 'completed',
            'payment_method' => $validated['payment_method'],
            'comment' => $validated['comment'] ?? $sale->comment,
        ]);

        // Формируем сообщение об успехе
        $successMessage = 'Продажа завершена успешно! Товары списаны со склада.' . $bonusMessage;
        
        if ($sale->used_bonus_points > 0) {
            $successMessage .= " Использовано {$sale->used_bonus_points} бонусов.";
        }

        if ($sale->table_id && $tableClosed) {
            $tableDate = $sale->created_at->format('Y-m-d');
            return redirect()->route('tables.index', ['date' => $tableDate])
                ->with('success', $successMessage . ' Стол закрыт.');
        } else {
            return redirect()->route('sales.show', $sale)
                ->with('success', $successMessage);
        }
    }

    public function destroy(Sale $sale)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя удалить завершенную продажу!');
        }

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

        // Проверка наличия на складе
        $availableQuantity = $this->getAvailableQuantity($sale->warehouse_id, $product);
        $requestedQuantity = $validated['quantity'];
        
        if (!$this->checkStockAvailability($sale->warehouse_id, $product, $requestedQuantity)) {
            $unitText = $product->unit === 'шт' ? 'штук' : $product->unit;
            
            // Формируем детальное сообщение об ошибке
            $errorMessage = "Недостаточно товара '{$product->name}' на складе.\n";
            $errorMessage .= "Запрошено: {$requestedQuantity} {$unitText}\n";
            $errorMessage .= "Доступно: {$availableQuantity} {$unitText}";
            
            // Для составных товаров добавляем детали по компонентам
            if ($product->is_composite) {
                $componentDetails = $this->getComponentAvailabilityDetails($sale->warehouse_id, $product, $requestedQuantity);
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

        $this->recalculateSaleTotal($sale);

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
        
        $this->recalculateSaleTotal($sale);

        return back()->with('success', 'Товар обновлен');
    }

    public function removeItem(Sale $sale, SaleItem $item)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя удалять товары из завершенной продажи');
        }

        $item->delete();
        
        $this->recalculateSaleTotal($sale);

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
        ]);

        $sale->hookahs()->attach($validated['hookah_id']);

        $this->recalculateSaleTotal($sale);

        return back()->with('success', 'Кальян добавлен в заказ');
    }

    public function removeHookah(Sale $sale, Hookah $hookah)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Нельзя удалять кальяны из завершенного заказа');
        }

        $sale->hookahs()->detach($hookah->id);
        
        $this->recalculateSaleTotal($sale);

        return back()->with('success', 'Кальян удален из заказа');
    }

    // Приватные методы
    private function recalculateSaleTotal(Sale $sale)
    {
        // Сумма товаров
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        // Сумма кальянов
        $hookahsTotal = $sale->hookahs->sum('price');
        
        $total = $productsTotal + $hookahsTotal;
        
        // Вычитаем скидку и бонусы
        $finalTotal = $total - $sale->discount - ($sale->used_bonus_points ?? 0);
        
        // Не даем уйти в минус
        $finalTotal = max(0, $finalTotal);
        
        $sale->update(['total' => $finalTotal]);
    }

    private function checkStockAvailability($warehouseId, Product $product, $requestedQuantity)
    {
        if (!$warehouseId) {
            return false;
        }
        
        if ($product->is_composite) {
            // Проверка для составных товаров
            foreach ($product->recipeComponents as $component) {
                $stock = Stock::where('warehouse_id', $warehouseId)
                            ->where('product_id', $component->component_product_id)
                            ->first();
                
                $requiredQuantity = $requestedQuantity * $component->quantity;
                
                if (!$stock || $stock->quantity < $requiredQuantity) {
                    return false;
                }
            }
        } else {
            // Проверка для обычных товаров
            $stock = Stock::where('warehouse_id', $warehouseId)
                        ->where('product_id', $product->id)
                        ->first();
            
            if (!$stock || $stock->quantity < $requestedQuantity) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Получает доступное количество товара на складе
     */
    private function getAvailableQuantity($warehouseId, Product $product)
    {
        if (!$warehouseId) {
            return 0;
        }
        
        if ($product->is_composite) {
            // Для составных товаров находим минимальное количество среди компонентов
            $minAvailable = PHP_INT_MAX;
            
            foreach ($product->recipeComponents as $component) {
                $stock = Stock::where('warehouse_id', $warehouseId)
                            ->where('product_id', $component->component_product_id)
                            ->first();
                
                if (!$stock) {
                    return 0;
                }
                
                // Сколько можно сделать из доступных компонентов
                $availableForComponent = floor($stock->quantity / $component->quantity);
                $minAvailable = min($minAvailable, $availableForComponent);
            }
            
            return $minAvailable;
        } else {
            // Для обычных товаров
            $stock = Stock::where('warehouse_id', $warehouseId)
                        ->where('product_id', $product->id)
                        ->first();
            
            return $stock ? $stock->quantity : 0;
        }
    }

    /**
     * Получает детальную информацию о доступности компонентов составного товара
     */
    private function getComponentAvailabilityDetails($warehouseId, Product $product, $requestedQuantity)
    {
        $details = [];
        
        foreach ($product->recipeComponents as $component) {
            $componentStock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $component->component_product_id)
                ->first();
            
            $availableComponent = $componentStock ? $componentStock->quantity : 0;
            $requiredComponent = $requestedQuantity * $component->quantity;
            
            $details[] = [
                'component_id' => $component->component_product_id,
                'component_name' => $component->componentProduct->name ?? 'Компонент #' . $component->component_product_id,
                'required_per_unit' => $component->quantity,
                'required_total' => $requiredComponent,
                'available' => $availableComponent,
                'unit' => $component->componentProduct->unit ?? 'шт',
                'is_available' => $requiredComponent <= $availableComponent,
                'shortage' => max(0, $requiredComponent - $availableComponent),
                'can_make_units' => floor($availableComponent / $component->quantity)
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
        
        // Проверка наличия на складе
        $availableQuantity = $this->getAvailableQuantity($sale->warehouse_id, $product);
        
        if (!$this->checkStockAvailability($sale->warehouse_id, $product, $requestedQuantity)) {
            $unitText = $product->unit === 'шт' ? 'штук' : $product->unit;
            
            return response()->json([
                'success' => false,
                'message' => "Недостаточно товара '{$product->name}' на складе. Запрошено: {$requestedQuantity} {$unitText}, Доступно: {$availableQuantity} {$unitText}",
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

}