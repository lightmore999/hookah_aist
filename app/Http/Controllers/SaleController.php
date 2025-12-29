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
            'discount' => 'numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,online,terminal',
            'comment' => 'nullable|string|max:1000',
            'status' => 'required|string|in:new,in_progress,completed,cancelled',
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
            'quantity' => 'required|numeric|min:0.001', // quantity уже в правильной единице
            'unit_price' => 'required|numeric|min:0.01', // price за единицу
            'final_quantity' => 'nullable', // можно убрать если не используется
            'final_unit_price' => 'nullable', // можно убрать если не используется
        ]);

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
        
        // Вычитаем скидку
        $total -= $sale->discount;
        
        // Не даем уйти в минус
        $total = max(0, $total);
        
        $sale->update(['total' => $total]);
    }
}