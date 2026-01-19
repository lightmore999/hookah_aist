<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{

    public function index(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        
        $query = Inventory::with(['warehouses', 'creator', 'completer'])->latest();
        
        // Фильтр по названию
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Фильтр по складу
        if ($request->filled('warehouse_id')) {
            $query->whereHas('warehouses', function ($q) use ($request) {
                $q->where('warehouses.id', $request->warehouse_id);
            });
        }
        
        $inventories = $query->paginate(20);
        
        return view('inventories.index', compact('inventories', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_ids' => 'required|array|min:1',
            'warehouse_ids.*' => 'exists:warehouses,id',
            'name' => 'nullable|string|max:255',
            'inventory_date' => 'nullable|date',
        ]);
        
        // Создаем инвентаризацию
        $inventoryData = [
            'name' => $validated['name'] ?? null,
            'inventory_date' => $validated['inventory_date'] ?? now(),
        ];
        
        $inventory = Inventory::create($inventoryData);
        
        // Привязываем несколько складов
        $inventory->warehouses()->sync($validated['warehouse_ids']);
        
        // Собираем уникальные товары со всех складов
        $productQuantities = [];
        
        foreach ($validated['warehouse_ids'] as $warehouseId) {
            $stockItems = Stock::where('warehouse_id', $warehouseId)
                ->with('product')
                ->get();
            
            foreach ($stockItems as $stock) {
                if ($stock->product) {
                    $productId = $stock->product_id;
                    
                    if (!isset($productQuantities[$productId])) {
                        $productQuantities[$productId] = [
                            'product_id' => $productId,
                            'system_quantity' => 0,
                        ];
                    }
                    
                    $productQuantities[$productId]['system_quantity'] += (int)$stock->quantity;
                }
            }
        }
        
        // Добавляем все товары в инвентаризацию
        foreach ($productQuantities as $productData) {
            InventoryItem::create([
                'inventory_id' => $inventory->id,
                'product_id' => $productData['product_id'],
                'system_quantity' => $productData['system_quantity'],
                'actual_quantity' => $productData['system_quantity'],
            ]);
        }
        
        return redirect()->route('inventories.show', $inventory)
            ->with('success', 'Инвентаризация успешно создана! Все товары со складов добавлены.');
    }

    public function update(Request $request, Inventory $inventory)
    {
        if ($inventory->isClosed()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            
            $inventory->update($validated);
            
            return redirect()->route('inventories.show', $inventory)
                ->with('success', 'Название инвентаризации обновлено!');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'inventory_date' => 'required|date',
            'warehouse_ids' => 'nullable|array',
            'warehouse_ids.*' => 'exists:warehouses,id',
        ]);
        
        $inventory->update([
            'name' => $validated['name'],
            'inventory_date' => $validated['inventory_date'],
        ]);
        
        // Обновляем склады если переданы
        if (isset($validated['warehouse_ids']) && !empty($validated['warehouse_ids'])) {
            $inventory->warehouses()->sync($validated['warehouse_ids']);
        }
        
        return redirect()->route('inventories.show', $inventory)
            ->with('success', 'Инвентаризация обновлена!');
    }

    public function close(Inventory $inventory)
    {
        if ($inventory->isClosed()) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', 'Эта инвентаризация уже закрыта');
        }
        
        if ($inventory->items()->count() === 0) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', 'Нельзя закрыть пустую инвентаризацию. Добавьте хотя бы один товар.');
        }
        
        $inventory->close();
        
        return redirect()->route('inventories.show', $inventory)
            ->with('success', 'Инвентаризация успешно закрыта! Остатки на складах обновлены.');
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['warehouses', 'creator', 'completer', 'items.product']);
        
        // Получаем все склады инвентаризации
        $warehouseIds = $inventory->warehouses->pluck('id')->toArray();
        
        // Получаем товары со всех складов инвентаризации, которые еще не добавлены
        $stockItems = Stock::whereIn('warehouse_id', $warehouseIds)
            ->with('product')
            ->get();
        
        // Группируем по product_id для уникальности
        $uniqueProducts = [];
        foreach ($stockItems as $stock) {
            if ($stock->product) {
                $productId = $stock->product_id;
                
                if (!isset($uniqueProducts[$productId])) {
                    $uniqueProducts[$productId] = [
                        'product_id' => $productId,
                        'product' => $stock->product,
                        'total_quantity' => 0,
                    ];
                }
                
                $uniqueProducts[$productId]['total_quantity'] += (int)$stock->quantity;
            }
        }
        
        $addedProductIds = $inventory->items->pluck('product_id')->toArray();
        
        $availableProducts = collect($uniqueProducts)
            ->filter(function ($productData) use ($addedProductIds) {
                return !in_array($productData['product_id'], $addedProductIds);
            })
            ->map(function ($productData) {
                return [
                    'id' => $productData['product_id'],
                    'name' => $productData['product']->name,
                    'unit' => $productData['product']->unit,
                    'system_quantity' => $productData['total_quantity'],
                    'current_stock' => $productData['total_quantity'],
                ];
            })
            ->values();
        
        return view('inventories.show', compact('inventory', 'availableProducts'));
    }

    public function edit(Inventory $inventory)
    {
        if ($inventory->isClosed()) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', 'Закрытую инвентаризацию нельзя редактировать.');
        }
        
        $warehouses = Warehouse::orderBy('name')->get();
        $selectedWarehouses = $inventory->warehouses->pluck('id')->toArray();
        
        return view('inventories.edit', compact('inventory', 'warehouses', 'selectedWarehouses'));
    }


    public function destroy(Inventory $inventory)
    {
        try {
            $inventory->delete();
            
            return redirect()->route('inventories.index')
                ->with('success', 'Инвентаризация успешно удалена!');
        } catch (\Exception $e) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', 'Ошибка при удалении: ' . $e->getMessage());
        }
    }

    public function addItem(Request $request, Inventory $inventory)
    {
        if ($inventory->isClosed()) {
            return redirect()->back()
                ->with('error', 'Нельзя добавлять товары в закрытую инвентаризацию');
        }
        
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'actual_quantity' => 'required|integer|min:0',
        ]);
        
        if ($inventory->items()->where('product_id', $validated['product_id'])->exists()) {
            return redirect()->back()
                ->with('error', 'Этот товар уже добавлен в инвентаризацию');
        }
        
        // Получаем общее количество товара на всех складах инвентаризации
        $warehouseIds = $inventory->warehouses->pluck('id')->toArray();
        
        $totalStock = Stock::whereIn('warehouse_id', $warehouseIds)
            ->where('product_id', $validated['product_id'])
            ->sum('quantity');
        
        $systemQuantity = (int)$totalStock;
        
        try {
            InventoryItem::create([
                'inventory_id' => $inventory->id,
                'product_id' => $validated['product_id'],
                'system_quantity' => $systemQuantity,
                'actual_quantity' => $validated['actual_quantity'],
            ]);
            
            return redirect()->route('inventories.show', $inventory)
                ->with('success', 'Товар успешно добавлен в инвентаризацию');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Ошибка при добавлении товара: ' . $e->getMessage());
        }
    }

    public function updateItem(Request $request, Inventory $inventory, InventoryItem $item)
    {
        if ($inventory->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя изменять товары в закрытой инвентаризации'
            ], 422);
        }
        
        if ($item->inventory_id != $inventory->id) {
            return response()->json([
                'success' => false,
                'message' => 'Этот товар не принадлежит данной инвентаризации'
            ], 403);
        }
        
        $validated = $request->validate([
            'actual_quantity' => 'required|integer|min:0',
        ]);
        
        try {
            $item->update(['actual_quantity' => $validated['actual_quantity']]);
            
            return response()->json([
                'success' => true,
                'message' => 'Количество товара обновлено',
                'item' => $item->fresh(),
                'difference' => $item->fresh()->difference,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeItem(Inventory $inventory, InventoryItem $item)
    {
        if ($inventory->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалять товары из закрытой инвентаризации'
            ], 422);
        }
        
        if ($item->inventory_id != $inventory->id) {
            return response()->json([
                'success' => false,
                'message' => 'Этот товар не принадлежит данной инвентаризации'
            ], 403);
        }
        
        try {
            $item->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Товар удален из инвентаризации'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении: ' . $e->getMessage()
            ], 500);
        }
    }

}