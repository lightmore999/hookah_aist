<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItemProduct; // ← Правильное имя модели!
use App\Models\Product;
use Illuminate\Http\Request;

class ProductOrderItemController extends Controller
{
    // Добавить товар в заказ (из модалки на странице заказа)
    public function store(Request $request, Order $order)
    {
        // Проверяем, что заказ не завершен
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя добавлять товары в завершенный заказ!');
        }
        
        $validated = $request->validate([
            'IDProduct' => 'required|exists:products,id',
            'Quantity' => 'required|integer|min:1',
            'UnitPrice' => 'required|numeric|min:0',
        ]);
        
        $validated['IDOrder'] = $order->IDOrder;
        
        OrderItemProduct::create($validated);
        
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Товар успешно добавлен в заказ!');
    }
    
    // Обновить товар в заказе (из модалки)
    public function update(Request $request, Order $order, OrderItemProduct $item)
    {
        // Проверяем, что товар принадлежит этому заказу
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя редактировать товары в завершенном заказе!');
        }

        if ($item->IDOrder != $order->IDOrder) {
            abort(403, 'Товар не принадлежит этому заказу');
        }
        
        $validated = $request->validate([
            'Quantity' => 'required|integer|min:1',
            'UnitPrice' => 'required|numeric|min:0',
        ]);
        
        $item->update($validated);
        
        // Пересчитываем общую сумму заказа
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Товар успешно обновлен!');
    }
    
    // Удалить товар из заказа (из модалки)
    public function destroy(Order $order, OrderItemProduct $item)
    {
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя удалять товары из завершенного заказа!');
        }
        // Проверяем, что товар принадлежит этому заказу
        if ($item->IDOrder != $order->IDOrder) {
            abort(403, 'Товар не принадлежит этому заказу');
        }
        
        $item->delete();
        
        // Пересчитываем общую сумму заказа
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Товар удален из заказа!');
    }


}