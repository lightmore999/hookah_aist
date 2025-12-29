<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItemRecipe;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeOrderItemController extends Controller
{
    // Добавить рецепт в заказ
    public function store(Request $request, Order $order)
    {
        // Проверяем, что заказ не завершен
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя добавлять рецепты в завершенный заказ!');
        }
        
        $validated = $request->validate([
            'IDRecipes' => 'required|exists:recipes,id',
            'Quantity' => 'required|integer|min:1',
            'UnitPrice' => 'required|numeric|min:0',
        ]);
        
        $validated['IDOrder'] = $order->IDOrder;
        
        OrderItemRecipe::create($validated);
        
        // Пересчитываем сумму через модель
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Рецепт успешно добавлен в заказ!');
    }
    
    // Обновить рецепт в заказе
    public function update(Request $request, Order $order, OrderItemRecipe $item)
    {
        // Проверяем, что заказ не завершен
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя редактировать рецепты в завершенном заказе!');
        }
        
        // Проверяем, что рецепт принадлежит этому заказу
        if ($item->IDOrder != $order->IDOrder) {
            abort(403, 'Рецепт не принадлежит этому заказу');
        }
        
        $validated = $request->validate([
            'Quantity' => 'required|integer|min:1',
            'UnitPrice' => 'required|numeric|min:0',
        ]);
        
        $item->update($validated);
        
        // Пересчитываем сумму через модель
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Рецепт успешно обновлен!');
    }
    
    // Удалить рецепт из заказа
    public function destroy(Order $order, OrderItemRecipe $item)
    {
        // Проверяем, что заказ не завершен
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя удалять рецепты из завершенного заказа!');
        }
        
        // Проверяем, что рецепт принадлежит этому заказу
        if ($item->IDOrder != $order->IDOrder) {
            abort(403, 'Рецепт не принадлежит этому заказу');
        }
        
        $item->delete();
        
        // Пересчитываем сумму через модель
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Рецепт удален из заказа!');
    }
}