<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function create()
    {
        // Берем только НЕ составные товары (те, у которых нет рецепта)
        $products = Product::whereDoesntHave('recipeComponents')
            ->orderBy('name')
            ->get();
        
        $warehouses = Warehouse::orderBy('name')->get();
        return view('purchases.create', compact('products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.001', // количество в единицах товара
            'unit_price' => 'required|numeric|min:0', // цена за единицу товара
            'purchase_date' => 'required|date',
            'update_cost_price' => 'nullable|boolean',
        ]);
        
        $product = Product::find($validated['product_id']);
        
        // Создаем закупку
        $purchase = Purchase::create([
            'product_id' => $validated['product_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'quantity' => $validated['quantity'], // количество в указанных единицах
            'unit_price' => $validated['unit_price'], // цена за указанную единицу
            'purchase_date' => $validated['purchase_date'],
        ]);
        
        // Обновляем себестоимость товара, если отмечен чекбокс
        if ($request->has('update_cost_price') && $request->boolean('update_cost_price')) {
            $product->cost = $validated['unit_price'];
            $product->save();
        }
        
        // Добавляем количество на склад (используем новый метод из модели Purchase)
        $purchase->addToStock();
        
        $successMessage = 'Закупка успешно добавлена! ';
        if ($request->has('update_cost_price') && $request->boolean('update_cost_price')) {
            $successMessage .= 'Себестоимость товара обновлена. ';
        }
        
        return redirect()->route('warehouses.index')
            ->with('success', $successMessage);
    }

    public function edit(Purchase $purchase)
    {
        // Для редактирования показываем все товары, но выделяем составные
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        
        return view('purchases.edit', compact(
            'purchase', 
            'products', 
            'warehouses'
        ));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'update_cost_price' => 'nullable|boolean',
        ]);
        
        $product = Product::find($validated['product_id']);
        
        // Старое количество (для корректировки остатков)
        $oldQuantity = $purchase->quantity;
        $oldWarehouseId = $purchase->warehouse_id;
        $oldProductId = $purchase->product_id;
        
        // 1. Убираем старое количество со склада
        if ($oldQuantity > 0) {
            $oldStock = Stock::where([
                'warehouse_id' => $oldWarehouseId,
                'product_id' => $oldProductId,
            ])->first();
            
            if ($oldStock) {
                $oldStock->useQuantity($oldQuantity);
            }
        }
        
        // 2. Обновляем закупку
        $purchase->update([
            'product_id' => $validated['product_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'purchase_date' => $validated['purchase_date'],
        ]);
        
        // 3. Обновляем себестоимость товара, если отмечен чекбокс
        if ($request->has('update_cost_price') && $request->boolean('update_cost_price')) {
            $product->cost = $validated['unit_price'];
            $product->save();
        }
        
        // 4. Добавляем новое количество на склад
        $purchase->addToStock();
        
        $successMessage = 'Закупка успешно обновлена! ';
        if ($request->has('update_cost_price') && $request->boolean('update_cost_price')) {
            $successMessage .= 'Себестоимость товара обновлена. ';
        }
        $successMessage .= 'Остатки скорректированы.';
        
        return redirect()->route('warehouses.index')
            ->with('success', $successMessage);
    }

    public function destroy(Purchase $purchase)
    {
        // Убираем количество со склада
        $stock = Stock::where([
            'warehouse_id' => $purchase->warehouse_id,
            'product_id' => $purchase->product_id,
        ])->first();
        
        if ($stock) {
            $stock->useQuantity($purchase->quantity);
        }
        
        // Удаляем закупку
        $purchase->delete();
        
        return redirect()->route('warehouses.index')
            ->with('success', 'Закупка успешно удалена! Остатки скорректированы.');
    }
}