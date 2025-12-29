<?php

namespace App\Http\Controllers;

use App\Models\WriteOff;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;

class WriteOffController extends Controller
{
    public function index(Request $request)
    {
        $warehouseId = $request->query('warehouse_id') ?? $request->query(0);
        $warehouse = Warehouse::find($warehouseId);

        $writeOffs = WriteOff::where('warehouse_id', $warehouseId)
            ->with('product')
            ->latest('write_off_date')
            ->get();

        return view('write-offs.index', compact('warehouse', 'writeOffs'));
    }

    public function store(Request $request)
    {
        $warehouseId = $request->input('warehouse_id');
        
        if (!$warehouseId) {
            return back()->withErrors(['error' => 'ID склада не указан']);
        }
        
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'operation_type' => 'required|string|in:spoilage,damage,expired,other',
        ]);

        $stock = Stock::where('product_id', $validated['product_id'])
            ->where('warehouse_id', $warehouse->id)
            ->first();

        if (!$stock) {
            return back()->withInput()->with('error', 'Товар отсутствует на этом складе');
        }

        if ($stock->quantity < $validated['quantity']) {
            return back()->withInput()->with('error', 'Недостаточно товара на складе. Доступно: ' . $stock->quantity);
        }
        
        // Используем метод useQuantity из новой модели Stock
        $useResult = $stock->useQuantity($validated['quantity']);
        
        if (!$useResult['success']) {
            return back()->withInput()->with('error', $useResult['message']);
        }

        // Создаем запись о списании
        WriteOff::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'operation_type' => $validated['operation_type'],
            'write_off_date' => now(),
        ]);

        return redirect()->route('warehouses.show', $warehouse)
            ->with('success', 'Товар успешно списан! Использовано: ' . $validated['quantity'] . ' ' . ($stock->product->unit ?? 'ед.'));
    }

    public function show(WriteOff $writeOff)
    {
        return view('write-offs.show', compact('writeOff'));
    }

    public function edit(WriteOff $writeOff)
    {
        return view('write-offs.edit', compact('writeOff'));
    }

    public function update(Request $request, WriteOff $writeOff)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'operation_type' => 'required|string|in:spoilage,damage,expired,other',
            'write_off_date' => 'required|date',
        ]);

        $oldQuantity = $writeOff->quantity;
        $newQuantity = $validated['quantity'];
        
        // Находим товар на складе
        $stock = Stock::where('warehouse_id', $writeOff->warehouse_id)
            ->where('product_id', $writeOff->product_id)
            ->first();
            
        if (!$stock) {
            return back()->withInput()->with('error', 'Товар не найден на складе');
        }
        
        // Если количество изменилось, корректируем остатки
        if ($oldQuantity != $newQuantity) {
            $quantityDiff = $newQuantity - $oldQuantity;
            
            // Проверяем, достаточно ли товара для увеличения списания
            if ($quantityDiff > 0) {
                if ($stock->quantity < $quantityDiff) {
                    return back()->withInput()->with('error', 'Недостаточно товара для увеличения списания. Доступно: ' . $stock->quantity);
                }
                
                // Если увеличиваем списание - списываем дополнительное количество
                $useResult = $stock->useQuantity($quantityDiff);
                if (!$useResult['success']) {
                    return back()->withInput()->with('error', $useResult['message']);
                }
            } else {
                // Если уменьшаем списание - возвращаем товар на склад
                $returnQuantity = abs($quantityDiff);
                $stock->quantity += $returnQuantity;
                $stock->save();
            }
        }
        
        // Обновляем списание
        $writeOff->update($validated);
        
        return redirect()->route('write-offs.index', ['warehouse_id' => $writeOff->warehouse_id])
            ->with('success', 'Списание успешно обновлено!');
    }

    public function destroy(WriteOff $writeOff)
    {
        // Находим товар на складе
        $stock = Stock::where('warehouse_id', $writeOff->warehouse_id)
            ->where('product_id', $writeOff->product_id)
            ->first();
            
        if ($stock) {
            // Возвращаем списанное количество на склад
            $stock->quantity += $writeOff->quantity;
            $stock->save();
        }
        
        // Удаляем списание
        $writeOff->delete();
        
        return redirect()->route('write-offs.index', ['warehouse_id' => $writeOff->warehouse_id])
            ->with('success', 'Списание успешно удалено! Товар возвращен на склад.');
    }
}