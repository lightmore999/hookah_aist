<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('purchases.create', compact('products', 'warehouses'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);
        
        DB::beginTransaction();

        try {
            $purchase = Purchase::create($validated);

            $stock = Stock::firstOrNew([
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $validated['product_id'],
            ]);

            if ($stock->exists) {
                $stock->quantity += $validated['quantity'];
            } else {
                $stock->quantity = $validated['quantity'];
            }

            $stock->last_updated = now();

            $stock->save();
            

            DB::commit();
            
        } catch (\Exception $e) {

            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Ошибка при создании закупки: ' . $e->getMessage());
        }

        return redirect()->route('warehouses.index')
            ->with('success', 'Закупка успешно добавлена!');
    }


    public function edit(Purchase $purchase)
    {
        $products = Product::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('purchases.edit', compact('purchase', 'products', 'warehouses'));
    }


   public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $oldQuantity = $purchase->quantity;
            $oldWarehouseId = $purchase->warehouse_id;
            $oldProductId = $purchase->product_id;
            
            $newQuantity = $validated['quantity'];
            $newWarehouseId = $validated['warehouse_id'];
            $newProductId = $validated['product_id'];


            $purchase->update($validated);

            if ($oldQuantity != $newQuantity || 
                $oldWarehouseId != $newWarehouseId || 
                $oldProductId != $newProductId) {
                
                if ($oldWarehouseId != $newWarehouseId || $oldProductId != $newProductId) {
                    
                    if ($oldWarehouseId && $oldProductId) {
                        Stock::where([
                            'warehouse_id' => $oldWarehouseId,
                            'product_id' => $oldProductId,
                        ])->decrement('quantity', $oldQuantity);
                    }
                    
                    Stock::updateOrCreate(
                        [
                            'warehouse_id' => $newWarehouseId,
                            'product_id' => $newProductId,
                        ],
                        [
                            'quantity' => DB::raw('COALESCE(quantity, 0) + ' . $newQuantity),
                            'last_updated' => now(),
                        ]
                    );
                }
                else {
                    $quantityDiff = $newQuantity - $oldQuantity;
                    
                    Stock::where([
                        'warehouse_id' => $newWarehouseId,
                        'product_id' => $newProductId,
                    ])->increment('quantity', $quantityDiff);
                    
                    Stock::where([
                        'warehouse_id' => $newWarehouseId,
                        'product_id' => $newProductId,
                    ])->update(['last_updated' => now()]);
                }
            }

            DB::commit();

            return redirect()->route('warehouses.index')
                ->with('success', 'Закупка успешно обновлена! Остатки скорректированы.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withInput()
                ->with('error', 'Ошибка при обновлении закупки: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();
        
        try {
            $warehouseId = $purchase->warehouse_id;
            $productId = $purchase->product_id;
            $quantity = $purchase->quantity;
            
            $purchase->delete();
            
            if ($warehouseId && $productId && $quantity > 0) {
                Stock::where([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                ])->decrement('quantity', $quantity);
                
                Stock::where([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                ])->update(['last_updated' => now()]);
            }
            
            DB::commit();
            
            return redirect()->route('warehouses.index') 
                ->with('success', 'Закупка успешно удалена! Остатки скорректированы.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('warehouses.index')
                ->with('error', 'Ошибка при удалении закупки: ' . $e->getMessage());
        }
    }
}
