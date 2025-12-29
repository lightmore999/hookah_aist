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
        
        $query = Inventory::with(['warehouse', 'creator', 'completer'])->latest();
        
        // Фильтр по названию
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Фильтр по дате начала
        if ($request->filled('date_from')) {
            $query->whereDate('inventory_date', '>=', $request->date_from);
        }
        
        // Фильтр по дате окончания
        if ($request->filled('date_to')) {
            $query->whereDate('inventory_date', '<=', $request->date_to);
        }
        
        $inventories = $query->paginate(20);
        
        return view('inventories.index', compact('inventories', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        
        return view('inventories.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'nullable|string|max:255',
            'inventory_date' => 'nullable|date',
        ]);
        
        $inventory = Inventory::create($validated);
        
        return redirect()->route('inventories.show', $inventory)
            ->with('success', 'Инвентаризация успешно создана! Теперь добавьте товары.');
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['warehouse', 'creator', 'completer', 'items.product']);
        
        $stockItems = Stock::where('warehouse_id', $inventory->warehouse_id)
            ->with('product')
            ->get();
        
        $addedProductIds = $inventory->items->pluck('product_id')->toArray();
        
        $availableProducts = $stockItems->filter(function ($stock) use ($addedProductIds) {
            return !in_array($stock->product_id, $addedProductIds) && $stock->product;
        })->map(function ($stock) {
            return [
                'id' => $stock->product_id,
                'name' => $stock->product->name,
                'unit' => $stock->product->unit,
                'system_quantity' => (int)$stock->quantity,
                'current_stock' => (int)$stock->quantity,
            ];
        })->values();
        
        return view('inventories.show', compact('inventory', 'availableProducts'));
    }

    public function edit(Inventory $inventory)
    {
        // Можно редактировать только название, если инвентаризация не закрыта
        if ($inventory->isClosed()) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', 'Закрытую инвентаризацию можно редактировать только в особых случаях.');
        }
        
        return view('inventories.edit', compact('inventory'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        // Если инвентаризация закрыта, можно менять только название
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
        ]);
        
        $inventory->update($validated);
        
        return redirect()->route('inventories.show', $inventory)
            ->with('success', 'Инвентаризация обновлена!');
    }

    public function destroy(Inventory $inventory)
    {
        try {
            // Всегда можно удалить инвентаризацию, даже закрытую
            // Но нужно предупредить пользователя
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
        
        $stock = Stock::where('warehouse_id', $inventory->warehouse_id)
            ->where('product_id', $validated['product_id'])
            ->first();
        
        $systemQuantity = $stock ? (int)$stock->quantity : 0;
        
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
        
        try {
            DB::transaction(function () use ($inventory) {
                $inventory->close();
            });
            
            return redirect()->route('inventories.show', $inventory)
                ->with('success', 'Инвентаризация успешно закрыта! Остатки на складе обновлены.');
        } catch (\Exception $e) {
            return redirect()->route('inventories.show', $inventory)
                ->with('error', 'Ошибка при закрытии инвентаризации: ' . $e->getMessage());
        }
    }
}