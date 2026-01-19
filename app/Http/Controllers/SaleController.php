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
use App\Models\PaymentMethod; 
use Illuminate\Http\Request;
use App\Models\BonusHistory;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['client', 'table', 'hookahs', 'paymentMethod']) // Убрано warehouse
            ->latest('sale_date')
            ->paginate(20);
        
        $clients = Client::all();
        
        return view('sales.index', compact('sales', 'clients'));
    }

     public function create()
    {
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

    public function store(Request $request)
    {
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

    public function complete(Request $request, Sale $sale)
    {
        if ($sale->status === 'completed') {
            return back()->with('error', 'Продажа уже завершена');
        }

        $paymentMethods = PaymentMethod::pluck('Name', 'IDPaymentMethod')->toArray();
        $paymentMethodIds = array_keys($paymentMethods);
        
        $validated = $request->validate([
            'discount' => 'numeric|min:0',
            'use_bonuses' => 'boolean',
            'bonus_points_to_use' => 'nullable|integer|min:0',
            'payment_method_id' => 'required|exists:payment_methods,IDPaymentMethod',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Обработка бонусов
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
                // Для составных товаров проверяем компоненты на всех складах
                foreach ($product->recipeComponents as $component) {
                    // Суммируем количество компонента на всех складах
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
                // Для обычных товаров суммируем количество на всех складах
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

        // Списываем товары со складов (по принципу FIFO - сначала со складов, где больше всего товара)
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                // Для составных товаров списываем компоненты
                foreach ($product->recipeComponents as $component) {
                    $requiredComponentQuantity = $item->quantity * $component->quantity;
                    
                    // Ищем склады, где есть этот компонент, отсортированные по убыванию количества
                    $stocks = Stock::where('product_id', $component->component_product_id)
                        ->where('quantity', '>', 0)
                        ->orderByDesc('quantity')
                        ->get();
                    
                    // Списываем по очереди с каждого склада
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
                // Для обычных товаров списываем с любого склада
                $requiredQuantity = $item->quantity;
                
                // Ищем склады, где есть этот товар, отсортированные по убыванию количества
                $stocks = Stock::where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)
                    ->orderByDesc('quantity')
                    ->get();
                
                // Списываем по очереди с каждого склада
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

        // Сначала обновляем скидку
        $sale->update([
            'discount' => $validated['discount'] ?? 0,
        ]);

        // Затем пересчитываем сумму с учетом скидки
        $this->recalculateSaleTotal($sale);
        $sale->refresh();

        // Обновляем статус продажи на "completed" ПЕРЕД начислением бонусов
        $sale->update([
            'status' => 'completed',
            'payment_method_id' => $validated['payment_method_id'],
            'comment' => $validated['comment'] ?? $sale->comment,
        ]);

        // Перезагружаем модель, чтобы получить обновленный статус
        $sale->refresh();

        // Начисляем бонусы клиенту по правилам карты
        $bonusMessage = '';
        
        if ($sale->client_id) {
            $pointsAwarded = $sale->awardBonusPoints();
            if ($pointsAwarded > 0) {
                $bonusMessage = " Начислено {$pointsAwarded} бонусов.";
            }
            
            $client = Client::find($sale->client_id);
            if ($client) {
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

}