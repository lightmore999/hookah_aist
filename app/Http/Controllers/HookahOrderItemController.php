<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItemHookah;
use App\Models\Hookah;
use Illuminate\Http\Request;

class HookahOrderItemController extends Controller
{
    // Добавить кальян в заказ
    public function store(Request $request, Order $order)
    {
        // Проверяем, что заказ не завершен
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя добавлять кальяны в завершенный заказ!');
        }
        
        $validated = $request->validate([
            'IDHookah' => 'required|exists:hookahs,id',
        ]);
        
        // УБИРАЕМ проверку на уникальность - можно добавлять несколько одинаковых кальянов
        // $exists = OrderItemHookah::where('IDOrder', $order->IDOrder)
        //     ->where('IDHookah', $validated['IDHookah'])
        //     ->exists();
            
        // if ($exists) {
        //     return redirect()->back()
        //         ->with('error', 'Этот кальян уже добавлен в заказ!');
        // }
        
        $validated['IDOrder'] = $order->IDOrder;
        
        OrderItemHookah::create($validated);
        
        // Пересчитываем сумму через модель
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Кальян успешно добавлен в заказ!');
    }
    
    // Удалить кальян из заказа
    public function destroy(Order $order, OrderItemHookah $item)
    {
        // Проверяем, что заказ не завершен
        if ($order->Status == 'completed') {
            return redirect()->back()
                ->with('error', 'Нельзя удалять кальяны из завершенного заказа!');
        }
        
        // Проверяем, что кальян принадлежит этому заказу
        if ($item->IDOrder != $order->IDOrder) {
            abort(403, 'Кальян не принадлежит этому заказу');
        }
        
        $item->delete();
        
        // Пересчитываем сумму через модель
        $order->recalculateTotal();
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Кальян удален из заказа!');
    }
}