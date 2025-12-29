<?php

namespace App\Http\Controllers;

use App\Models\RecipeItem;
use App\Models\Product;
use Illuminate\Http\Request;

class RecipeItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        // Проверяем, не добавлен ли уже этот продукт в рецепт
        $existingItem = RecipeItem::where('recipe_id', $request->recipe_id)
            ->where('product_id', $request->product_id)
            ->first();
            
        if ($existingItem) {
            return back()->with('error', 'Этот продукт уже есть в рецепте');
        }
        
        // Проверяем что продукт существует
        $product = Product::find($request->product_id);
        if (!$product) {
            return back()->with('error', 'Продукт не найден');
        }
        
        $item = RecipeItem::create([
            'recipe_id' => $request->recipe_id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
        ]);
        
        // Пересчитываем себестоимость рецепта (информационно)
        $item->recipe->calculateCost();

        return back()->with('success', 'Ингредиент добавлен.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecipeItem $recipeItem)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $recipeItem->update([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
        ]);
        
        // Пересчитываем себестоимость рецепта (информационно)
        $recipeItem->recipe->calculateCost();

        return back()->with('success', 'Ингредиент обновлен.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecipeItem $recipeItem)
    {
        $recipe = $recipeItem->recipe;
        $recipeItem->delete();
        
        // Пересчитываем себестоимость рецепта (информационно)
        $recipe->calculateCost();

        return back()->with('success', 'Ингредиент удален.');
    }
    
    /**
     * Получить информацию о продукте для рецепта
     */
    public function getProductInfo($productId)
    {
        $product = Product::with('category')->find($productId);
        
        if (!$product) {
            return response()->json(['error' => 'Продукт не найден'], 404);
        }
        
        // Рассчитываем цену за единицу
        $pricePerUnit = $product->packaging > 0 
            ? $product->price / $product->packaging 
            : 0;
        
        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit,
                'packaging' => $product->packaging,
                'price_per_unit' => $pricePerUnit,
                'formatted_price_per_unit' => number_format($pricePerUnit, 2) . ' ₽/' . $product->unit,
                'category' => $product->category->name ?? 'Без категории',
            ]
        ]);
    }
}