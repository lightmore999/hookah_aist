<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Client;
use App\Models\Table;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\ProductOrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Показать список заказов (с модалками)
    public function index()
    {
        $orders = Order::with(['client', 'table'])
            ->latest()
            ->paginate(20);
        
        $clients = Client::all();
        $tables = Table::all();
        $warehouses = Warehouse::all();
        $users = User::all();
        
        return view('orders.index', compact('orders', 'clients', 'tables', 'warehouses', 'users'));
    }

   public function show(Order $order)
    {
        // Загружаем все связи, включая рецепты
        $order->load([
            'client', 
            'table', 
            'warehouse', 
            'user', 
            'orderItems.product',
            'hookahItems.hookah',
            'recipeItems.recipe'  // ← добавляем эту строку
        ]);
        
        // Получаем данные для всех модалок
        $products = \App\Models\Product::all();
        $recipes = \App\Models\Recipe::all();
        $clients = \App\Models\Client::all();
        $tables = \App\Models\Table::all();
        $warehouses = \App\Models\Warehouse::all();
        $users = \App\Models\User::all();
        
        return view('orders.show', compact(
            'order', 
            'products',
            'recipes',  // ← добавляем
            'clients',
            'tables', 
            'warehouses', 
            'users'
        ));
    }
    
    // Сохранить новый заказ (из модалки)
   // В методе store()
    public function store(Request $request)
    {
        $validated = $request->validate([
            'IDClient' => 'nullable|exists:clients,id',
            'IDTable' => 'nullable|exists:tables,id',
            'Status' => 'required|string|in:new,in_progress',
        ]);
        
        // Добавляем дефолтные значения
        $validated['Status'] = 'in_progress';
        $validated['Total'] = 0;
        $validated['Discount'] = 0;
        $validated['Tips'] = 0;
        $validated['On_loan'] = 0;
        
        $order = Order::create($validated);
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', 'Заказ успешно создан! Теперь вы можете добавить товары.');
    }

    // В методе update() для закрытия заказа
   public function update(Request $request, Order $order)
    {
        // Если статус меняется на completed - это закрытие заказа
        if ($request->has('Status') && $request->Status == 'completed') {
            // Валидация для закрытия заказа
            $validated = $request->validate([
                'Tips' => 'numeric|min:0',
                'Discount' => 'numeric|min:0',
                'PaymentMethod' => 'required|string|in:cash,card,online,terminal,split,corporate',
                'Comment' => 'nullable|string|max:1000',
            ]);
            
            // Загружаем все позиции для корректного расчета
            $order->load(['orderItems.product', 'hookahItems.hookah', 'recipeItems.recipe']);
            
            // Рассчитываем сумму товаров
            $productsTotal = $order->orderItems->sum(function($item) {
                return $item->Quantity * $item->UnitPrice;
            });
            
            // Рассчитываем сумму кальянов
            $hookahsTotal = $order->hookahItems->sum(function($item) {
                return $item->hookah->price ?? 0;
            });

            $recipeTotal = $order->recipeItems->sum(function($item) {
                return $item->Quantity * $item->UnitPrice;
            });
            
            // Общая сумма позиций
            $itemsTotal = $productsTotal + $hookahsTotal +  $recipeTotal;
            
            // ИТОГОВАЯ сумма с учетом скидки и чаевых
            $finalTotal = $itemsTotal - ($validated['Discount'] ?? 0) + ($validated['Tips'] ?? 0);
            
            $validated['Status'] = 'completed';
            $validated['Total'] = $finalTotal;
            
            $message = 'Заказ успешно закрыт!';
            
        } else {
            // Обычное редактирование
            if ($order->Status == 'completed') {
                return redirect()->back()
                    ->with('error', 'Нельзя редактировать завершенный заказ!');
            }
            
            $validated = $request->validate([
                'IDClient' => 'nullable|exists:clients,id',
                'IDTable' => 'nullable|exists:tables,id',
                'IDWarehouses' => 'nullable|exists:warehouses,id',
                'UserId' => 'nullable|exists:users,id',
                'Tips' => 'numeric|min:0',
                'Discount' => 'numeric|min:0',
                'On_loan' => 'numeric|min:0',
                'Total' => 'numeric|min:0',
                'PaymentMethod' => 'nullable|string|in:cash,card,online,terminal,split,corporate',
                'Comment' => 'nullable|string|max:1000',
                'Status' => 'required|string|in:new,in_progress,completed,cancelled',
            ]);
            
            $message = 'Заказ успешно обновлен!';
        }
        
        $order->update($validated);
        
        return redirect()->route('orders.show', $order->IDOrder)
            ->with('success', $message);
    }
    
    // Удалить заказ (из модалки)
    public function destroy(Order $order)
    {
        $order->delete();
        
        return redirect()->route('orders.index')
            ->with('success', 'Заказ успешно удален!');
    }
    
    // Методы для формы создания/редактирования не нужны, т.к. используем модалки
    // public function create() - НЕ НУЖЕН
    // public function edit() - НЕ НУЖЕН
}