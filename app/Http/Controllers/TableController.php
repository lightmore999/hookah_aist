<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Sale;
use App\Models\Client;
use App\Models\Product;
use App\Models\Hookah;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\BonusHistory;
use App\Models\PaymentMethod;
use App\Models\OperationHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    public function index(Request $request)
    {
        // Получаем дату из запроса или используем сегодня
        $selectedDate = $request->has('date') 
            ? Carbon::parse($request->date)
            : Carbon::today();
        
        // Создаем временные границы
        $startOfDay = $selectedDate->copy()->startOfDay();
        $nextDay = $selectedDate->copy()->addDay()->startOfDay();
        $nightCutoff = $selectedDate->copy()->addDay()->setTime(3, 30, 0);
        
        // Получаем все столы, которые пересекаются с выбранной датой
        $tables = Table::where(function($query) use ($selectedDate, $startOfDay, $nextDay, $nightCutoff) {
            // Столы, которые начинаются в выбранный день (с 00:00 до 23:59)
            $query->whereDate('booking_date', $selectedDate)
                ->whereTime('booking_time', '>=', '00:00')
                ->whereTime('booking_time', '<', '23:59');
            
            // ИЛИ столы, которые начинаются после 00:00 предыдущего дня и заканчиваются после 00:00
            $query->orWhere(function($q) use ($selectedDate) {
                // Предыдущий день
                $prevDay = $selectedDate->copy()->subDay();
                $q->whereDate('booking_date', $prevDay)
                ->whereTime('booking_time', '>=', '14:00');
            });
        })
        ->orderBy('table_number')
        ->orderBy('booking_time')
        ->get()
        ->groupBy('table_number');
        
        $tableNumbers = [1, 2, 3, 4, 'Барная стойка', 6, 7];
        
        $tableIds = $tables->flatten()->pluck('id');
        
        $allSalesForTables = Sale::whereIn('table_id', $tableIds)
            ->with(['items.product', 'hookahs'])
            ->get()
            ->keyBy('table_id');
        
        $clients = Client::orderBy('name')->get();
        
        $products = Product::with('recipeComponents.component')
            ->orderBy('name')
            ->get();
        
        $hookahs = Hookah::orderBy('name')->get();
        
        $paymentMethods = PaymentMethod::orderBy('Name')->get();
    
        return view('tables.index', compact(
            'tables',
            'tableNumbers',
            'selectedDate',
            'allSalesForTables',
            'clients',
            'products',
            'hookahs',
            'paymentMethods'
        ));
    }
    
    // Создание стола
    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string|max:50',
            'booking_date' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'duration' => 'required|integer|min:30|max:720',
            'guest_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'guests_count' => 'nullable|integer|min:1',
            'comment' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'nullable|string|in:new,opened_without_hookah,opened_with_hookah,closed'
        ]);
        
        // Проверка на пересечение времени
        if (!$this->checkTimeAvailability($validated['table_number'], $validated['booking_date'], $validated['booking_time'], $validated['duration'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Этот стол уже занят на выбранное время!');
        }
        
        if (!isset($validated['status'])) {
            $validated['status'] = 'new';
        }
        
        if (!empty($validated['client_id'])) {
            $client = Client::find($validated['client_id']);
            $validated['guest_name'] = $client->name;
            $validated['phone'] = $client->phone;
        }
        
        // Создаем стол
        $table = Table::create($validated);
        
        // Логируем создание стола
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => OperationHistory::ACTION_CREATE,
            'new_data' => [
                'table_number' => $table->table_number,
                'booking_date' => $table->booking_date,
                'booking_time' => $table->booking_time,
                'guest_name' => $table->guest_name,
                'status' => $table->status,
            ],
            'comment' => "Создан стол #{$table->table_number} на {$table->booking_date} {$table->booking_time}",
        ]);
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол забронирован успешно!');
    }
    
    // Редактирование стола
    public function update(Request $request, Table $table)
    {
        $oldData = [
            'table_number' => $table->table_number,
            'booking_date' => $table->booking_date,
            'booking_time' => $table->booking_time,
            'guest_name' => $table->guest_name,
            'status' => $table->status,
        ];
        
        $validated = $request->validate([
            'table_number' => 'required|string|max:50',
            'booking_date' => 'required|date',
            'booking_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'guest_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'guests_count' => 'nullable|integer|min:1|max:50',
            'comment' => 'nullable|string|max:1000',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'nullable|string|in:new,opened_without_hookah,opened_with_hookah,closed'
        ]);
        
        // Рассчитываем длительность
        $startTime = \Carbon\Carbon::parse($validated['booking_time']);
        $endTime = \Carbon\Carbon::parse($validated['end_time']);
        
        if ($endTime->lt($startTime)) {
            $endTime->addDay();
        }
        
        $duration = $startTime->diffInMinutes($endTime);
        $validated['duration'] = $duration;
        
        // Проверка на пересечение времени
        if (!$this->checkTimeAvailability($validated['table_number'], $validated['booking_date'], $validated['booking_time'], $duration, $table->id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Этот стол уже занят на выбранное время!');
        }
        
        if (!empty($validated['client_id'])) {
            $client = Client::find($validated['client_id']);
            $validated['guest_name'] = $client->name;
            $validated['phone'] = $client->phone;
        } else {
            $validated['client_id'] = null;
        }
        
        // Обновляем стол
        $table->update($validated);
        
        // Логируем изменение стола
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => OperationHistory::ACTION_UPDATE,
            'old_data' => $oldData,
            'new_data' => [
                'table_number' => $table->table_number,
                'booking_date' => $table->booking_date,
                'booking_time' => $table->booking_time,
                'guest_name' => $table->guest_name,
                'status' => $table->status,
            ],
            'comment' => "Изменен стол #{$table->table_number}",
        ]);
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол обновлен успешно!');
    }

    private function checkTimeAvailability($tableNumber, $bookingDate, $bookingTime, $durationMinutes, $excludeTableId = null)
    {
        $newBookingDate = \Carbon\Carbon::parse($bookingDate);
        $newBookingTime = \Carbon\Carbon::parse($newBookingDate->format('Y-m-d') . ' ' . $bookingTime);
        $newBookingEnd = $newBookingTime->copy()->addMinutes($durationMinutes);
        
        $existingBookings = Table::where('table_number', $tableNumber)
            ->when($excludeTableId, function ($query) use ($excludeTableId) {
                return $query->where('id', '!=', $excludeTableId);
            })
            ->get();
        
        foreach ($existingBookings as $existingBooking) {
            $existingBookingTimeStr = is_string($existingBooking->booking_time) ? 
                $existingBooking->booking_time : 
                $existingBooking->booking_time->format('H:i:s');
            
            $existingBookingHour = (int)substr($existingBookingTimeStr, 0, 2);
            
            if ($existingBookingHour < 4) {
                $existingBookingTime = \Carbon\Carbon::parse(
                    $existingBooking->booking_date->copy()->subDay()->format('Y-m-d') . ' ' . 
                    substr($existingBookingTimeStr, 0, 8)
                );
            } else {
                $existingBookingTime = \Carbon\Carbon::parse(
                    $existingBooking->booking_date->format('Y-m-d') . ' ' . 
                    substr($existingBookingTimeStr, 0, 8)
                );
            }
            
            $existingBookingEnd = $existingBookingTime->copy()->addMinutes($existingBooking->duration);
            
            $overlaps = $this->timeRangesOverlap(
                $newBookingTime,
                $newBookingEnd,
                $existingBookingTime,
                $existingBookingEnd
            );
            
            if ($overlaps) {
                return false;
            }
        }
        
        return true;
    }

    private function timeRangesOverlap($start1, $end1, $start2, $end2)
    {
        return $start1->lt($end2) && $start2->lt($end1);
    }
    
    // Удаление стола
    public function destroy(Table $table)
    {
        $oldData = [
            'table_number' => $table->table_number,
            'booking_date' => $table->booking_date,
            'booking_time' => $table->booking_time,
            'guest_name' => $table->guest_name,
            'status' => $table->status,
        ];
        
        // Удаляем связанную продажу если она существует
        $sale = Sale::where('table_id', $table->id)->first();
        if ($sale) {
            SaleItem::where('sale_id', $sale->id)->delete();
            $sale->hookahs()->detach();
            $sale->delete();
        }
        
        // Удаляем стол
        $table->delete();
        
        // Логируем удаление стола
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => OperationHistory::ACTION_DELETE,
            'old_data' => $oldData,
            'comment' => "Удален стол #{$table->table_number}",
        ]);
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол и связанная продажа удалены успешно!');
    }
    
    // Изменение статуса стола
    public function changeStatus(Request $request, Table $table)
    {
        $oldStatus = $table->status;
        $newStatus = $request->status;
        
        $request->validate([
            'status' => 'required|string|in:new,opened_without_hookah,opened_with_hookah,closed'
        ]);
        
        // Если стол открывается
        if ($newStatus === 'opened_without_hookah' && $oldStatus === 'new') {
            $existingSale = Sale::where('table_id', $table->id)->first();
            if (!$existingSale) {
                Sale::create([
                    'client_id' => $table->client_id,
                    'table_id' => $table->id,
                    'total' => 0,
                    'discount' => 0,
                    'status' => 'active',
                    'sale_date' => now(),
                    'payment_method_id' => null,
                    'comment' => null,
                ]);
            }
            
            // Логируем открытие стола
            OperationHistory::create([
                'user_id' => Auth::id(),
                'entity_type' => OperationHistory::ENTITY_TABLE,
                'entity_id' => $table->id,
                'action_type' => OperationHistory::ACTION_OPEN,
                'old_data' => ['status' => $oldStatus],
                'new_data' => ['status' => $newStatus],
                'comment' => "Открыт стол #{$table->table_number}",
            ]);
        }
        
        $table->update(['status' => $newStatus]);
        
        // AJAX или редирект
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Статус стола изменен',
                'status' => $newStatus
            ]);
        }
        
        return redirect()->route('tables.index')
            ->with('success', 'Стол успешно открыт!');
    }
    
    // Модалка для товаров
    public function showSaleModal(Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        $sale->load('items.product');
        $products = Product::with('recipeComponents.component')
            ->orderBy('name')
            ->get();
        
        return view('tables.modals.sale-products', compact('sale', 'products', 'table'));
    }
    
    // Добавить товар в продажу стола
    public function addProductToSale(Request $request, Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя добавлять товары в завершенную продажу'
            ], 400);
        }
        
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0.01'
        ]);
        
        $product = Product::find($validated['product_id']);
        $requestedQuantity = $validated['quantity'];
        
        // Для штучных товаров проверяем целое число
        if ($product->unit === 'шт' && floor($requestedQuantity) != $requestedQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Для штучных товаров количество должно быть целым числом'
            ], 400);
        }
        
        // Проверка наличия на складе
        $availableQuantity = $this->getAvailableQuantity($product);

        if (!$this->checkStockAvailability($product, $requestedQuantity)) {
            $unitText = $product->unit === 'шт' ? 'штук' : $product->unit;
            
            $errorMessage = "Недостаточно товара '{$product->name}' на складе.\n";
            $errorMessage .= "Запрошено: {$requestedQuantity} {$unitText}\n";
            $errorMessage .= "Доступно: {$availableQuantity} {$unitText}";
            
            if ($product->is_composite) {
                $componentDetails = $this->getComponentAvailabilityDetails($product, $requestedQuantity);
                $errorMessage .= "\n\nНедостаточно компонентов:\n";
                
                foreach ($componentDetails as $detail) {
                    if (!$detail['is_available']) {
                        $errorMessage .= "- {$detail['component_name']}: нужно {$detail['required_total']} {$detail['unit']}, есть {$detail['available']} {$detail['unit']}\n";
                    }
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'details' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'requested' => $requestedQuantity,
                    'available' => $availableQuantity,
                    'can_add_max' => $availableQuantity > 0,
                    'is_composite' => $product->is_composite,
                    'components' => $product->is_composite ? $this->getComponentAvailabilityDetails($product, $requestedQuantity) : null
                ]
            ], 400);
        }
        
        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
        ]);
        
        // Логируем добавление товара
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => 'add_product', // Используем кастомный тип
            'new_data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'sale_id' => $sale->id,
            ],
            'comment' => "Добавлен товар '{$product->name}' к столу #{$table->table_number}",
        ]);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Товар добавлен успешно',
            'total' => $sale->total,
            'newTotal' => $sale->total,
            'saleId' => $sale->id
        ]);
    }

    private function getComponentAvailabilityDetails(Product $product, $requestedQuantity)
    {
        $details = [];
        
        foreach ($product->recipeComponents as $component) {
            $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                ->sum('quantity');

            $availableComponent = $totalComponentQuantity;
            $requiredComponent = $requestedQuantity * $component->quantity;
            
            $details[] = [
                'component_id' => $component->component_product_id,
                'component_name' => $component->componentProduct->name ?? 'Компонент #' . $component->component_product_id,
                'required_per_unit' => $component->quantity,
                'required_total' => $requiredComponent,
                'available' => $availableComponent,
                'unit' => $component->componentProduct->unit ?? 'шт',
                'is_available' => $requiredComponent <= $availableComponent,
                'shortage' => max(0, $requiredComponent - $availableComponent),
                'can_make_units' => floor($availableComponent / $component->quantity)
            ];
        }
        
        return $details;
    }
    
    // Добавить кальян в продажу стола
    public function addHookahToSale(Request $request, Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя добавлять кальяны в завершенную продажу'
            ], 400);
        }
        
        $validated = $request->validate([
            'hookah_id' => 'required|exists:hookahs,id'
        ]);
        
        $hookah = Hookah::find($validated['hookah_id']);
        
        // Добавляем кальян
        $sale->hookahs()->attach($validated['hookah_id']);
        
        // Логируем добавление кальяна
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => OperationHistory::ACTION_ADD_HOOKAH,
            'new_data' => [
                'hookah_id' => $hookah->id,
                'hookah_name' => $hookah->name,
                'hookah_price' => $hookah->price,
                'sale_id' => $sale->id,
            ],
            'comment' => "Добавлен кальян '{$hookah->name}' к столу #{$table->table_number}",
        ]);
        
        // Обновляем статус стола
        $table->update(['status' => 'opened_with_hookah']);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Кальян добавлен успешно',
            'total' => $sale->total,
            'newTotal' => $sale->total,
            'saleId' => $sale->id,
            'status' => 'opened_with_hookah'
        ]);
    }

    // Удалить кальян из продажи
    public function removeHookahFromSale(Table $table, $hookahId)
    {
        $sale = Sale::where('table_id', $table->id)->first();
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Продажа не найдена для этого стола'
            ], 404);
        }
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалять кальяны из завершенной продажи'
            ], 400);
        }
        
        $pivotRecord = \DB::table('sale_hookahs')
            ->where('sale_id', $sale->id)
            ->where('hookah_id', $hookahId)
            ->first();
        
        if (!$pivotRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Кальян не был найден в этой продаже'
            ], 404);
        }
        
        $hookah = Hookah::find($hookahId);
        
        // Удаляем кальян
        $deleted = \DB::table('sale_hookahs')
            ->where('sale_id', $sale->id)
            ->where('hookah_id', $hookahId)
            ->limit(1)
            ->delete();
        
        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Не удалось удалить кальян'
            ], 500);
        }
        
        // Логируем удаление кальяна
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => OperationHistory::ACTION_REMOVE_HOOKAH,
            'new_data' => [
                'hookah_id' => $hookah->id,
                'hookah_name' => $hookah->name,
                'sale_id' => $sale->id,
            ],
            'comment' => "Удален кальян '{$hookah->name}' из стола #{$table->table_number}",
        ]);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Кальян удален успешно',
            'total' => $sale->total,
            'newTotal' => $sale->total,
            'saleId' => $sale->id,
            'remainingHookahs' => $sale->hookahs()->count(),
            'removedHookahId' => $hookahId
        ]);
    }
    
    // Модалка для закрытия стола
    public function showCloseModal(Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        $sale->load(['items.product', 'hookahs', 'client.bonusCard']);
        
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $hookahsTotal = $sale->hookahs->sum('price');
        $subtotal = $productsTotal + $hookahsTotal;
        $finalTotal = $subtotal - $sale->discount - $sale->used_bonus_points;
        
        $clientMaxPercent = $sale->client && $sale->client->bonusCard 
            ? $sale->client->bonusCard->MaxSpendPercent 
            : 50;
        
        return view('tables.modals.close-sale', compact(
            'sale', 
            'table', 
            'productsTotal', 
            'hookahsTotal', 
            'subtotal', 
            'finalTotal',
            'clientMaxPercent'
        ));
    }
    
    // Завершить продажу и закрыть стол
    public function closeSaleAndTable(Request $request, Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return redirect()->back()->with('error', 'Продажа уже завершена');
        }
        
        $validated = $request->validate([
            'discount' => 'required|numeric|min:0',
            'discount_in_rubles' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:fixed,percent',
            'payment_method_id' => 'required|exists:payment_methods,IDPaymentMethod',
            'comment' => 'nullable|string|max:1000',
            'use_bonuses' => 'nullable|string',
            'bonus_points_to_use' => 'nullable|integer|min:0'
        ]);
        
        // Определяем клиента
        $clientId = $table->client_id ?? $sale->client_id;
        $client = $clientId ? Client::with('bonusCard')->find($clientId) : null;
        
        // Обработка использования бонусов
        $useBonuses = $request->has('use_bonuses') && $request->input('use_bonuses') === '1';
        $bonusPoints = (int) $request->input('bonus_points_to_use', 0);
        
        if ($useBonuses && $bonusPoints > 0) {
            if (!$clientId) {
                return back()->with('error', 'Для использования бонусов необходимо указать клиента');
            }
            
            if (!$client) {
                return back()->with('error', 'Клиент не найден');
            }
            
            $productsTotal = $sale->items->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });
            
            $hookahsTotal = $sale->hookahs->sum('price');
            $subtotal = $productsTotal + $hookahsTotal;
            
            $discount = $validated['discount'];
            if (isset($validated['discount_type']) && $validated['discount_type'] === 'percent') {
                $discount = $validated['discount_in_rubles'] ?? $discount;
            }
            
            $maxPercent = $client->bonusCard ? $client->bonusCard->MaxSpendPercent : 50;
            $percentage = $maxPercent / 100;
            $maxUsable = floor(($subtotal - $discount) * $percentage);
            $maxUsable = min($client->bonus_points, $maxUsable);
            
            if ($bonusPoints > $maxUsable) {
                return back()->with('error', "Можно использовать не более {$maxUsable} бонусов (макс. {$maxPercent}% от суммы)");
            }
            
            if ($client->bonus_points < $bonusPoints) {
                return back()->with('error', 'Недостаточно бонусов у клиента');
            }
            
            // Списываем бонусы у клиента
            $oldBalance = $client->bonus_points;
            $client->bonus_points -= $bonusPoints;
            $client->save();
            
            BonusHistory::create([
                'client_id' => $clientId,
                'amount' => $bonusPoints,
                'operation_type' => 'debit',
                'balance_after' => $client->bonus_points,
                'reason' => 'Списание бонусов при оплате продажи #' . $sale->id . ' (стол ' . $table->table_number . ')',
                'sale_id' => $sale->id,
            ]);
            
            $sale->used_bonus_points = $bonusPoints;
            $sale->save();
        }
        
        // Определение скидки
        $discount = $validated['discount'];
        if (isset($validated['discount_type']) && $validated['discount_type'] === 'percent') {
            $discount = $validated['discount_in_rubles'] ?? $discount;
        }
        
        // Проверка наличия товаров
        foreach ($sale->items as $item) {
            $product = $item->product;
            
            if ($product->is_composite) {
                foreach ($product->recipeComponents as $component) {
                    $requiredComponentQuantity = $item->quantity * $component->quantity;
                    
                    $stocks = Stock::where('product_id', $component->component_product_id)
                        ->where('quantity', '>', 0)
                        ->orderByDesc('quantity')
                        ->get();
                    
                    $remainingQuantity = $requiredComponentQuantity;
                    foreach ($stocks as $stock) {
                        if ($remainingQuantity <= 0) break;
                        
                        $quantityToDeduct = min($stock->quantity, $remainingQuantity);
                        $stock->quantity -= $quantityToDeduct;
                        $stock->save();
                        
                        $remainingQuantity -= $quantityToDeduct;
                    }
                }
            } else {
                $requiredQuantity = $item->quantity;
                
                $stocks = Stock::where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)
                    ->orderByDesc('quantity')
                    ->get();
                
                $remainingQuantity = $requiredQuantity;
                foreach ($stocks as $stock) {
                    if ($remainingQuantity <= 0) break;
                    
                    $quantityToDeduct = min($stock->quantity, $remainingQuantity);
                    $stock->quantity -= $quantityToDeduct;
                    $stock->save();
                    
                    $remainingQuantity -= $quantityToDeduct;
                }
            }
        }
        
        // Обновляем client_id в продаже если нужно
        if (!$sale->client_id && $table->client_id) {
            $sale->client_id = $table->client_id;
            $clientId = $table->client_id;
            $client = Client::with('bonusCard')->find($clientId);
        }
        
        // Обновляем продажу
        $sale->update([
            'discount' => $discount,
            'payment_method_id' => $validated['payment_method_id'],
            'comment' => $validated['comment'] ?? $sale->comment,
            'status' => 'completed',
        ]);
        
        // Пересчитываем итоговую сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        // Начисление бонусов
        $bonusMessage = '';
        $pointsAwarded = 0;
        
        if ($client) {
            if ($client->bonusCard) {
                $bonusPercent = $client->bonusCard->BonusPercent;
                $finalTotal = $sale->total;
                
                $pointsAwarded = floor($finalTotal * ($bonusPercent / 100));
                
                if ($pointsAwarded > 0) {
                    $oldBalance = $client->bonus_points;
                    $client->bonus_points += $pointsAwarded;
                    $client->save();
                    
                    BonusHistory::create([
                        'client_id' => $clientId,
                        'amount' => $pointsAwarded,
                        'operation_type' => 'credit',
                        'balance_after' => $client->bonus_points,
                        'reason' => "Начисление {$bonusPercent}% бонусов за продажу #" . $sale->id . ' (стол ' . $table->table_number . ')',
                        'sale_id' => $sale->id,
                    ]);
                    
                    $bonusMessage = " Начислено {$pointsAwarded} бонусов ({$bonusPercent}% от суммы).";
                }
            }
            $client->addPurchase($sale->total);
        }
        
        // Закрываем стол
        $table->update(['status' => 'closed']);
        
        // Логируем закрытие стола
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => OperationHistory::ACTION_CLOSE,
            'new_data' => [
                'sale_id' => $sale->id,
                'sale_total' => $sale->total,
                'discount' => $discount,
                'used_bonus_points' => $sale->used_bonus_points,
            ],
            'comment' => "Закрыт стол #{$table->table_number}, сумма: {$sale->total} руб.",
        ]);
        
        $successMessage = 'Стол закрыт и продажа завершена успешно!';
        
        if ($bonusMessage) {
            $successMessage .= $bonusMessage;
        }
        
        if ($sale->used_bonus_points > 0) {
            $successMessage .= " Использовано {$sale->used_bonus_points} бонусов.";
        }
        
        return redirect()->route('tables.index')
            ->with('success', $successMessage);
    }
    
    // Удалить товар из продажи
    public function removeProductFromSale(Table $table, SaleItem $item)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалять товары из завершенной продажи'
            ], 400);
        }
        
        if ($item->sale_id !== $sale->id) {
            return response()->json([
                'success' => false,
                'message' => 'Товар не принадлежит этой продаже'
            ], 400);
        }
        
        $product = $item->product;
        
        // Логируем удаление товара
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => 'remove_product', // Используем кастомный тип
            'old_data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $item->quantity,
                'sale_id' => $sale->id,
            ],
            'comment' => "Удален товар '{$product->name}' из стола #{$table->table_number}",
        ]);
        
        $item->delete();
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Товар удален успешно',
            'total' => $sale->total,
            'newTotal' => $sale->total,
            'saleId' => $sale->id
        ]);
    }
    
    // Обновить количество товара
    public function updateProductQuantity(Request $request, Table $table, SaleItem $item)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя изменять товары в завершенной продаже'
            ], 400);
        }
        
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.001'
        ]);
        
        $oldQuantity = $item->quantity;
        $newQuantity = $validated['quantity'];
        
        // Логируем изменение количества
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => 'update_product_quantity', // Используем кастомный тип
            'old_data' => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $oldQuantity,
                'sale_id' => $sale->id,
            ],
            'new_data' => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'quantity' => $newQuantity,
                'sale_id' => $sale->id,
            ],
            'comment' => "Изменено количество товара '{$item->product->name}' на столе #{$table->table_number}: {$oldQuantity} → {$newQuantity}",
        ]);
        
        $item->update(['quantity' => $newQuantity]);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Количество обновлено',
            'total' => $sale->total,
            'newTotal' => $sale->total
        ]);
    }
    
    // Вспомогательная функция для пересчета суммы
    private function recalculateSaleTotal(Sale $sale)
    {
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $hookahsTotal = $sale->hookahs->sum('price');
        
        $subtotal = $productsTotal + $hookahsTotal;
        
        $total = $subtotal - ($sale->discount ?? 0) - ($sale->used_bonus_points ?? 0);
        
        $total = max(0, $total);
        
        $sale->update(['total' => $total]);
        
        return $total;
    }

    public function getSaleItems(Table $table)
    {
        $sale = Sale::where('table_id', $table->id)->first();
        
        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Продажа не найдена',
                'items' => [],
                'total' => 0,
                'tableInfo' => [
                    'tableNumber' => $table->table_number,
                    'guestName' => $table->guest_name ?? ($table->client->name ?? 'Клиент')
                ]
            ]);
        }
        
        $sale->load('items.product');
        
        $items = $sale->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product->name,
                'quantity' => (float)$item->quantity,
                'unit' => $item->product->unit,
                'unit_price' => (float)$item->unit_price,
                'total' => (float)($item->quantity * $item->unit_price)
            ];
        });
        
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        return response()->json([
            'success' => true,
            'items' => $items,
            'total' => (float)$productsTotal,
            'saleId' => $sale->id,
            'tableInfo' => [
                'tableNumber' => $table->table_number,
                'guestName' => $table->guest_name ?? ($table->client->name ?? 'Клиент')
            ]
        ]);
    }

    public function getSaleHookahs($id)
    {
        try {
            $table = Table::findOrFail($id);
            
            $sale = Sale::where('table_id', $id)->first();
            
            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Продажа не найдена',
                    'hookahs' => [],
                    'total' => 0
                ]);
            }
            
            $sale->load('hookahs');
            
            $hookahs = $sale->hookahs->map(function($hookah) {
                return [
                    'id' => $hookah->id,
                    'name' => $hookah->name,
                    'price' => (float)$hookah->price
                ];
            });
            
            return response()->json([
                'success' => true,
                'hookahs' => $hookahs,
                'total' => (float)$sale->hookahs->sum('price'),
                'saleId' => $sale->id,
                'tableInfo' => [
                    'tableNumber' => $table->table_number,
                    'guestName' => $table->guest_name ?? ($table->client->name ?? 'Клиент')
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getSaleHookahs: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки кальянов: ' . $e->getMessage(),
                'hookahs' => [],
                'total' => 0
            ], 500);
        }
    }

    public function getSaleData($id) 
    {
        $table = Table::with('client.bonusCard')->findOrFail($id);
        $sale = Sale::where('table_id', $id)->first();
        
        if (!$sale) {
            return response()->json([
                'success' => true,
                'products' => [],
                'hookahs' => [],
                'productsTotal' => 0,
                'hookahsTotal' => 0,
                'subtotal' => 0,
                'discount' => 0,
                'bonusDiscount' => 0,
                'finalTotal' => 0,
                'paymentMethod' => null,
                'paymentMethodId' => null,
                'comment' => null,
                'clientId' => $table->client_id,
                'clientName' => $table->client ? $table->client->name : null,
                'clientBonusPoints' => $table->client ? $table->client->bonus_points : 0,
                'clientMaxSpendPercent' => $table->client && $table->client->bonusCard 
                    ? $table->client->bonusCard->MaxSpendPercent 
                    : 50,
                'clientBonusPercent' => $table->client && $table->client->bonusCard 
                    ? $table->client->bonusCard->BonusPercent 
                    : 5,
                'clientRequiredSpend' => $table->client && $table->client->bonusCard 
                    ? $table->client->bonusCard->RequiredSpendAmount 
                    : 0,
                'clientBonusCardName' => $table->client && $table->client->bonusCard 
                    ? $table->client->bonusCard->Name 
                    : null,
                'usedBonusPoints' => 0,
                'bonusEarned' => 0,
                'bonusEarnedPercent' => 0,
                'canEarnBonus' => false,
                'bonusCalculation' => 'Нет данных о бонусной карте',
                'hasBonusCard' => $table->client && $table->client->bonusCard ? true : false
            ]);
        }
        
        $sale->load(['items.product', 'hookahs', 'paymentMethod']);
        
        $clientId = $table->client_id ?? $sale->client_id;
        $clientName = null;
        $clientBonusPoints = 0;
        $clientMaxSpendPercent = 50;
        $clientBonusPercent = 5;
        $clientRequiredSpend = 0;
        $clientBonusCardName = null;
        $hasBonusCard = false;
        
        if ($clientId) {
            $client = Client::with('bonusCard')->find($clientId);
            if ($client) {
                $clientName = $client->name;
                $clientBonusPoints = $client->bonus_points;
                $hasBonusCard = $client->bonusCard ? true : false;
                
                if ($client->bonusCard) {
                    $clientMaxSpendPercent = $client->bonusCard->MaxSpendPercent;
                    $clientBonusPercent = $client->bonusCard->BonusPercent;
                    $clientRequiredSpend = $client->bonusCard->RequiredSpendAmount;
                    $clientBonusCardName = $client->bonusCard->Name;
                }
            }
        }
        
        $products = $sale->items->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->product->name,
                'quantity' => (float)$item->quantity,
                'unit' => $item->product->unit,
                'unit_price' => (float)$item->unit_price,
                'total' => (float)($item->quantity * $item->unit_price)
            ];
        });
        
        $hookahs = $sale->hookahs->map(function($hookah) {
            return [
                'id' => $hookah->id,
                'name' => $hookah->name,
                'price' => (float)$hookah->price
            ];
        });
        
        $productsTotal = $sale->items->sum(function($item) {
            return $item->quantity * $item->unit_price;
        });
        
        $hookahsTotal = $sale->hookahs->sum('price');
        $subtotal = $productsTotal + $hookahsTotal;
        
        $finalTotal = max(0, $subtotal - $sale->discount - $sale->used_bonus_points);
        
        $bonusEarned = 0;
        $bonusEarnedPercent = 0;
        $canEarnBonus = false;
        $bonusCalculation = '';
        
        if ($clientId) {
            if ($hasBonusCard) {
                $canEarnBonus = true;
                $bonusEarned = floor($finalTotal * ($clientBonusPercent / 100));
                $bonusEarnedPercent = $clientBonusPercent;
                $bonusCalculation = "{$clientBonusPercent}% от {$finalTotal} руб. = {$bonusEarned} бонусов";
            } else {
                $canEarnBonus = false;
                
                $totalClientSpent = Sale::where('client_id', $clientId)
                    ->where('status', 'completed')
                    ->sum('total');
                
                $totalAfterThis = $totalClientSpent + $finalTotal;
                
                if ($clientRequiredSpend > 0) {
                    if ($totalAfterThis >= $clientRequiredSpend) {
                        $bonusCalculation = "Клиент достиг необходимой суммы для получения карты!";
                    } else {
                        $remaining = $clientRequiredSpend - $totalAfterThis;
                        $bonusCalculation = "Для получения карты нужно потратить еще {$remaining} руб.";
                    }
                } else {
                    $bonusCalculation = "У клиента нет бонусной карты";
                }
            }
        } else {
            $bonusCalculation = "Клиент не указан";
        }
        
        return response()->json([
            'success' => true,
            'products' => $products,
            'hookahs' => $hookahs,
            'productsTotal' => (float)$productsTotal,
            'hookahsTotal' => (float)$hookahsTotal,
            'subtotal' => (float)$subtotal,
            'discount' => (float)$sale->discount,
            'bonusDiscount' => (float)$sale->used_bonus_points,
            'finalTotal' => (float)$finalTotal,
            'paymentMethod' => $sale->paymentMethod ? $sale->paymentMethod->Name : null,
            'paymentMethodId' => $sale->payment_method_id,
            'comment' => $sale->comment,
            
            'clientId' => $clientId,
            'clientName' => $clientName,
            'clientBonusPoints' => $clientBonusPoints,
            'hasBonusCard' => $hasBonusCard,
            
            'clientMaxSpendPercent' => $clientMaxSpendPercent,
            'clientBonusPercent' => $clientBonusPercent,
            'clientRequiredSpend' => $clientRequiredSpend,
            'clientBonusCardName' => $clientBonusCardName,
            
            'usedBonusPoints' => $sale->used_bonus_points,
            
            'bonusEarned' => $bonusEarned,
            'bonusEarnedPercent' => $bonusEarnedPercent,
            'canEarnBonus' => $canEarnBonus,
            'bonusCalculation' => $bonusCalculation,
        ]);
    }

    public function getOpenedAtAttribute()
    {
        if (in_array($this->status, ['opened_without_hookah', 'opened_with_hookah'])) {
            return $this->created_at;
        }
        return null;
    }

    // Проверяет наличие товара на складе
    private function checkStockAvailability(Product $product, $requestedQuantity)
    {
        if ($product->is_composite) {
            foreach ($product->recipeComponents as $component) {
                $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                    ->sum('quantity');
                
                $requiredQuantity = $requestedQuantity * $component->quantity;
                
                if ($totalComponentQuantity < $requiredQuantity) {
                    return false;
                }
            }
        } else {
            $totalProductQuantity = Stock::where('product_id', $product->id)
                ->sum('quantity');
            
            if ($totalProductQuantity < $requestedQuantity) {
                return false;
            }
        }
        
        return true;
    }

    // Получает доступное количество товара на складе
    private function getAvailableQuantity(Product $product)
    {
        if ($product->is_composite) {
            $minAvailable = PHP_INT_MAX;
            
            foreach ($product->recipeComponents as $component) {
                $totalComponentQuantity = Stock::where('product_id', $component->component_product_id)
                    ->sum('quantity');
                
                if ($totalComponentQuantity == 0) {
                    return 0;
                }
                
                $availableForComponent = floor($totalComponentQuantity / $component->quantity);
                $minAvailable = min($minAvailable, $availableForComponent);
            }
            
            return $minAvailable;
        } else {
            return Stock::where('product_id', $product->id)
                ->sum('quantity');
        }
    }

    // Обновить цену товара
    public function updateProductPrice(Request $request, Table $table, SaleItem $item)
    {
        $sale = Sale::where('table_id', $table->id)->firstOrFail();
        
        if ($sale->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя изменять товары в завершенной продаже'
            ], 400);
        }
        
        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0.01'
        ]);
        
        $oldPrice = $item->unit_price;
        $newPrice = $validated['unit_price'];
        
        // Логируем изменение цены
        OperationHistory::create([
            'user_id' => Auth::id(),
            'entity_type' => OperationHistory::ENTITY_TABLE,
            'entity_id' => $table->id,
            'action_type' => 'update_product_price', // Используем кастомный тип
            'old_data' => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'price' => $oldPrice,
                'sale_id' => $sale->id,
            ],
            'new_data' => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'price' => $newPrice,
                'sale_id' => $sale->id,
            ],
            'comment' => "Изменена цена товара '{$item->product->name}' на столе #{$table->table_number}: {$oldPrice} руб. → {$newPrice} руб.",
        ]);
        
        $item->update(['unit_price' => $newPrice]);
        
        // Пересчитываем сумму
        $this->recalculateSaleTotal($sale);
        $sale->refresh();
        
        return response()->json([
            'success' => true,
            'message' => 'Цена обновлена',
            'total' => $sale->total,
            'newTotal' => $sale->total
        ]);
    }
}