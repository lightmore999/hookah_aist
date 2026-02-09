<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\Stock;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $warehouses = Warehouse::latest()->get();
        $purchases = Purchase::with('product', 'warehouse')->latest()->get();
        return view('warehouses.index', compact('warehouses', 'purchases'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('warehouses.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Warehouse::create($validated);

        return redirect()->route('warehouses.index')
            ->with('success', 'Склад успешно добавлен!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function show(Warehouse $warehouse)
    {
        $stocks = \App\Models\Stock::where('warehouse_id', $warehouse->id)
            ->with('product')
            ->latest('last_updated')
            ->get();
        return view('warehouses.show', compact('warehouse', 'stocks'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Warehouse $warehouse)
    {
         $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $warehouse->update($validated);

        return redirect()->route('warehouses.index')
            ->with('success', 'Склад успешно обновлён!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Warehouse  $warehouse
     * @return \Illuminate\Http\Response
     */
    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Склад успешно удалён!');
    }

    public function transferStock(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        try {
            // Находим товар на исходном складе
            $fromStock = Stock::where('warehouse_id', $request->from_warehouse_id)
                ->where('product_id', $request->product_id)
                ->firstOrFail();

            // Проверяем наличие достаточного количества
            if ($fromStock->quantity < $request->quantity) {
                return back()->with('error', 'Недостаточно товара на исходном складе');
            }

            // Находим или создаем запись на целевом складе
            $toStock = Stock::firstOrCreate(
                [
                    'warehouse_id' => $request->to_warehouse_id,
                    'product_id' => $request->product_id,
                ],
                [
                    'quantity' => 0,
                    'last_updated' => now(),
                ]
            );

            // Используем транзакцию для гарантии целостности данных
            \DB::transaction(function () use ($fromStock, $toStock, $request) {
                // Списываем с исходного склада
                $fromStock->quantity -= $request->quantity;
                $fromStock->save();

                // Добавляем на целевой склад
                $toStock->quantity += $request->quantity;
                $toStock->save();
            });

            return back()->with('success', 'Товар успешно перенесен!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Товар не найден на исходном складе');
        } catch (\Exception $e) {
            return back()->with('error', 'Произошла ошибка при переносе товара: ' . $e->getMessage());
        }
    }

    public function removeStock(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        try {
            // Находим запись о товаре на складе
            $stock = Stock::where('warehouse_id', $warehouse->id)
                ->where('product_id', $request->product_id)
                ->firstOrFail();

            // Проверяем, что товар есть на складе
            if ($stock->quantity > 0) {
                return back()->with('error', 'Нельзя удалить товар с ненулевым остатком. Сначала списайте остатки.');
            }

            // Удаляем запись
            $stock->delete();

            return back()->with('success', 'Товар удален со склада!');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Товар не найден на складе');
        } catch (\Exception $e) {
            return back()->with('error', 'Произошла ошибка при удалении товара: ' . $e->getMessage());
        }
    }

}
