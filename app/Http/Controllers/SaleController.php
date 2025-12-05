<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Client;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sales = Sale::with('product', 'warehouse', 'client')->latest('sold_at')->get();
        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        return view('sales.create', compact('products', 'warehouses', 'clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'client_id' => 'nullable|exists:clients,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card',
            'sold_at' => 'required|date',
            'total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $stock = Stock::where([
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $validated['product_id'],
            ])->first();

            if (!$stock || $stock->quantity < $validated['quantity']) {
                DB::rollBack();
                return back()->withInput()
                    ->with('error', 'Недостаточно товара на складе. Доступно: ' . ($stock ? $stock->quantity : 0));
            }

            $sale = Sale::create($validated);

            $stock->quantity -= $validated['quantity'];
            $stock->last_updated = now();
            $stock->save();

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Продажа успешно создана!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Ошибка при создании продажи: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        return view('sales.edit', compact('sale', 'products', 'warehouses', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'client_id' => 'nullable|exists:clients,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card',
            'sold_at' => 'required|date',
            'total' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $oldQuantity = $sale->quantity;
            $oldWarehouseId = $sale->warehouse_id;
            $oldProductId = $sale->product_id;

            $newQuantity = $validated['quantity'];
            $newWarehouseId = $validated['warehouse_id'];
            $newProductId = $validated['product_id'];

            if ($oldWarehouseId != $newWarehouseId || $oldProductId != $newProductId) {
                if ($oldWarehouseId && $oldProductId) {
                    $oldStock = Stock::where([
                        'warehouse_id' => $oldWarehouseId,
                        'product_id' => $oldProductId,
                    ])->first();

                    if ($oldStock) {
                        $oldStock->quantity += $oldQuantity;
                        $oldStock->last_updated = now();
                        $oldStock->save();
                    }
                }
            }

            $newStock = Stock::where([
                'warehouse_id' => $newWarehouseId,
                'product_id' => $newProductId,
            ])->first();

            if ($oldWarehouseId == $newWarehouseId && $oldProductId == $newProductId) {

                $availableQuantity = ($newStock ? $newStock->quantity : 0) + $oldQuantity;
                if ($availableQuantity < $newQuantity) {
                    DB::rollBack();
                    return back()->withInput()
                        ->with('error', 'Недостаточно товара на складе. Доступно: ' . $availableQuantity);
                }
            } else {
                if (!$newStock || $newStock->quantity < $newQuantity) {
                    DB::rollBack();
                    return back()->withInput()
                        ->with('error', 'Недостаточно товара на складе. Доступно: ' . ($newStock ? $newStock->quantity : 0));
                }
            }

            $sale->update($validated);

            if ($oldWarehouseId == $newWarehouseId && $oldProductId == $newProductId) {
                $quantityDiff = $newQuantity - $oldQuantity;
                $newStock->quantity -= $quantityDiff;
            } else {
                $newStock->quantity -= $newQuantity;
            }

            $newStock->last_updated = now();
            $newStock->save();

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Продажа успешно обновлена! Остатки скорректированы.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Ошибка при обновлении продажи: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        DB::beginTransaction();

        try {
            $warehouseId = $sale->warehouse_id;
            $productId = $sale->product_id;
            $quantity = $sale->quantity;

            $sale->delete();

            if ($warehouseId && $productId && $quantity > 0) {
                $stock = Stock::where([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                ])->first();

                if ($stock) {
                    $stock->quantity += $quantity;
                    $stock->last_updated = now();
                    $stock->save();
                }
            }

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Продажа успешно удалена! Остатки скорректированы.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('sales.index')
                ->with('error', 'Ошибка при удалении продажи: ' . $e->getMessage());
        }
    }
}
