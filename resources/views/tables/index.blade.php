@extends('layouts.app')

@section('title', 'Столы')

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            @php
                $yesterday = $selectedDate->copy()->subDay()->format('Y-m-d');
                $today = now()->format('Y-m-d');
                $tomorrow = $selectedDate->copy()->addDay()->format('Y-m-d');
                $currentDate = $selectedDate->format('Y-m-d');
                $hookahsForModal = $hookahs;
            @endphp
            
            <a href="{{ route('tables.index', ['date' => $yesterday]) }}" 
               class="btn btn-outline-secondary">
                Вчера
            </a>
            <a href="{{ route('tables.index', ['date' => $today]) }}" 
               class="btn {{ $currentDate == $today ? 'btn-secondary' : 'btn-outline-secondary' }}">
                Сегодня
            </a>
            <a href="{{ route('tables.index', ['date' => $tomorrow]) }}" 
               class="btn btn-outline-secondary">
                Завтра 
            </a>
        </div>
        
        <div class="flex-grow-1 text-center">
            <form method="GET" action="{{ route('tables.index') }}" class="d-inline-block">
                <input type="date" 
                       name="date" 
                       value="{{ $currentDate }}" 
                       class="form-control d-inline-block" 
                       style="width: auto;"
                       onchange="this.form.submit()">
            </form>
        </div>
        
        <div>
            <form action="{{ route('sales.store') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Создать продажу
                </button>
            </form>
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createTableModal">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить стол
            </button>
        </div>
    </div>

    <!-- Основная таблица столов -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                <table class="table table-bordered mb-0" style="table-layout: fixed;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 120px;" class="text-center">Время</th>
                        @foreach($tableNames as $table)
                                <th class="text-center" data-table-id="{{ $table->id }}">
                                    @if(str_contains($table->name, 'Барная стойка'))
                                        <i class="bi bi-cup-hot-fill text-warning me-1"></i>{{ $table->name }}
                                    @else
                                        <i class="bi bi-table me-1"></i>{{ $table->name }}
                                    @endif
                                </th>
                            @endforeach
                            <th style="width: 120px;" class="text-center">Время</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $times = [];
                            $start = \Carbon\Carbon::createFromTime(14, 0);
                            $end = \Carbon\Carbon::createFromTime(3, 30);
                            
                            $current = $start->copy();
                            $day = 0;
                            while ($day < 2) {
                                $times[] = $current->copy();
                                $current->addMinutes(30);
                                
                                if ($current->format('H:i') == '00:00') {
                                    $day++;
                                }
                                if ($day == 1 && $current->format('H:i') == '03:30') {
                                    break;
                                }
                            }
                        @endphp
                        
                        @php
                            $renderedCells = [];
                        @endphp
                        
                        @foreach($times as $timeIndex => $time)
                            @php
                                $timeStr = $time->format('H:i');
                            @endphp
                            <tr>
                                <td class="text-center fw-bold bg-light" style="position: sticky; left: 0; z-index: 10;">
                                    {{ $timeStr }}
                                </td>
                                
                                @foreach($tableNames as $table)
                                    @php
                                        // Используем ID стола как ключ
                                        $tableKey = $table->id;
                                        $tableName = $table->name;
                                        $cellKey = $timeIndex . '_' . $tableKey;

                                        
                                        if (isset($renderedCells[$cellKey])) {
                                            continue;
                                        }
                                        
                                        $tableBookings = $tables[$tableKey] ?? [];
                                        $currentBooking = null;
                                        $isStart = false;
                                        $durationSlots = 0;
                                        
                                        foreach($tableBookings as $booking) {
                                            $bookingTimeStr = is_string($booking->booking_time) ? $booking->booking_time : (is_object($booking->booking_time) ? $booking->booking_time->format('H:i:s') : '00:00:00');
                                            
                                            // Время бронирования
                                            $bookingHour = (int)substr($bookingTimeStr, 0, 2);
                                            if ($bookingHour < 4) {
                                                // Время бронирования 00:00-03:30 - это продолжение предыдущего дня
                                                $bookingTime = \Carbon\Carbon::parse($booking->booking_date->copy()->subDay()->format('Y-m-d') . ' ' . substr($bookingTimeStr, 0, 8));
                                            } else {
                                                // Время бронирования 04:00-23:30 - это текущий день
                                                $bookingTime = \Carbon\Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . substr($bookingTimeStr, 0, 8));
                                            }
                                            
                                            $bookingEnd = $bookingTime->copy()->addMinutes($booking->duration);
                                            
                                            $timeHour = (int)substr($timeStr, 0, 2);
                                            $timeMin = (int)substr($timeStr, 3, 2);
                                            
                                            // Время из таблицы
                                            if ($timeHour < 4) {
                                                // Время 00:00-03:30 - это продолжение предыдущего дня
                                                $timeCarbon = \Carbon\Carbon::parse($selectedDate->copy()->subDay()->format('Y-m-d') . ' ' . $timeStr);
                                            } else {
                                                // Время 04:00-23:30 - это текущий день
                                                $timeCarbon = \Carbon\Carbon::parse($selectedDate->format('Y-m-d') . ' ' . $timeStr);
                                            }
                                            
                                            // Проверяем, попадает ли текущее время в интервал бронирования
                                            if ($timeCarbon->gte($bookingTime) && $timeCarbon->lt($bookingEnd)) {
                                                // Проверяем, совпадает ли время начала бронирования с текущим временем
                                                if ($bookingTime->format('H:i') == $timeStr) {
                                                    $currentBooking = $booking;
                                                    $isStart = true;
                                                    $durationSlots = max(1, ceil($booking->duration / 30));
                                                    
                                                    for ($i = 0; $i < $durationSlots; $i++) {
                                                        $renderedCells[($timeIndex + $i) . '_' . $tableKey] = true;
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    @if($isStart && $currentBooking)
                                        @php
                                            $hasSale = isset($allSalesForTables[$currentBooking->id]);
                                            $sale = $hasSale ? $allSalesForTables[$currentBooking->id] : null;
                                            $hasHookahs = $hasSale && $sale->hookahs->isNotEmpty();
                                            
                                            // Рассчитываем общую сумму
                                            $productsTotal = $sale ? $sale->items->sum(function($item) { return $item->quantity * $item->unit_price; }) : 0;
                                            $hookahsTotal = $sale ? $sale->hookahs->sum('price') : 0;
                                            $totalAmount = $productsTotal + $hookahsTotal - ($sale->discount ?? 0);
                                            
                                            // Получаем название стола
                                            $tableDisplayName = $currentBooking->tableName ? $currentBooking->tableName->name : 'Стол #' . $currentBooking->table_name_id;
                                        @endphp
                                        <td rowspan="{{ $durationSlots }}" class="p-2 align-top" 
                                            style="background-color: {{ $currentBooking->getStatusColor() }}; border: 2px solid #2196f3; vertical-align: top;">
                                            <div class="small">
                                                <!-- Заголовок с именем и статусом -->
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <strong class="text-truncate" style="max-width: 120px;">
                                                        {{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}  
                                                    </strong>
                                                    <span class="badge bg-{{ $currentBooking->getStatusBadgeColor() }}">
                                                        {{ $currentBooking->getStatusText() }}
                                                    </span>
                                                </div>
                                                
                                                <!-- Информация о столе -->
                                                <div class="mb-1"><i class="bi bi-table"></i> {{ $tableDisplayName }}</div>

                                                @if($currentBooking->phone || $currentBooking->client?->phone)
                                                    <div class="mb-1"><i class="bi bi-telephone"></i> {{ $currentBooking->phone ?? $currentBooking->client->phone }}</div>
                                                @endif

                                                @if($currentBooking->client)
                                                    <div class="badge bg-info text-dark mb-1">
                                                        <i class="bi bi-person-check"></i> Клиент из базы
                                                    </div>
                                                @endif

                                                @if($currentBooking->guests_count)
                                                    <div class="mb-1"><i class="bi bi-people"></i> {{ $currentBooking->guests_count }} чел.</div>
                                                @endif

                                                @if($currentBooking->comment)
                                                    <small class="text-muted d-block mb-2">{{ Str::limit($currentBooking->comment, 30) }}</small>
                                                @endif

                                                <!-- Кнопки управления -->
                                                <div class="mt-2">
                                                    @if($currentBooking->status === 'new')
                                                        <!-- СТАТУС: NEW (только создан) -->
                                                        <div class="d-flex flex-wrap gap-1">
                                                             <!-- Кнопка Открыть стол -->
                                                            <form action="{{ route('tables.change-status', $currentBooking->id) }}" method="POST" style="display: inline;">
                                                                @csrf
                                                                @method('POST')
                                                                <input type="hidden" name="status" value="opened_without_hookah">
                                                                <button type="submit" class="btn btn-sm btn-success">
                                                                    <i class="bi bi-door-open"></i> Открыть
                                                                </button>
                                                            </form>
                                                            
                                                            <!-- Кнопки редактирования и удаления стола -->
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-warning edit-table-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editTableModal"
                                                                    data-id="{{ $currentBooking->id }}"
                                                                    data-table-id="{{ $currentBooking->table_name_id }}"
                                                                    data-table-name="{{ $tableDisplayName }}"
                                                                    data-booking-date="{{ $currentBooking->booking_date->format('Y-m-d') }}"
                                                                    data-booking-time="{{ is_string($currentBooking->booking_time) ? $currentBooking->booking_time : $currentBooking->booking_time->format('H:i') }}"
                                                                    data-duration="{{ $currentBooking->duration }}"
                                                                    data-guest-name="{{ $currentBooking->guest_name }}"
                                                                    data-phone="{{ $currentBooking->phone }}"
                                                                    data-guests-count="{{ $currentBooking->guests_count }}"
                                                                    data-comment="{{ $currentBooking->comment }}"
                                                                    data-client-id="{{ $currentBooking->client_id }}"
                                                                    data-client-name="{{ $currentBooking->client->name ?? '' }}"
                                                                    data-client-phone="{{ $currentBooking->client->phone ?? '' }}"
                                                                    data-status="{{ $currentBooking->status }}">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger delete-table-btn"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteTableModal"
                                                                    data-id="{{ $currentBooking->id }}"
                                                                    data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}"
                                                                    data-table-name="{{ $tableDisplayName }}">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                        
                                                   @elseif($currentBooking->status === 'opened_without_hookah' && $hasSale)
                                                    <!-- СТАТУС: ОТКРЫТ БЕЗ КАЛЬЯНА -->
                                                    <div class="mb-2">
                                                        <!-- Таймер "Требуется кальян" сразу в HTML -->
                                                        <div class="timer-container">
                                                            <div class="hookah-requirement-timer" data-table-id="{{ $currentBooking->id }}">
                                                                <div class="alert alert-danger alert-dismissible fade show p-1 mb-2" role="alert" style="font-size: 0.8rem;">
                                                                    <div class="d-flex align-items-center justify-content-between">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="bi bi-alarm me-1"></i>
                                                                            <span>Поставьте кальян: <strong class="time-display">--:--</strong></span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Кнопки товаров и кальянов -->
                                                            <div class="d-flex gap-1 mb-2">
                                                                <!-- Кнопка Товары -->
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-primary"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#saleProductsModal"
                                                                        data-table-id="{{ $currentBooking->id }}"
                                                                        data-sale-id="{{ $sale->id ?? '' }}">
                                                                    <i class="bi bi-cart"></i> Товары
                                                                </button>
                                                                
                                                                <!-- Кнопка Кальяны -->
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-warning open-sale-hookahs-btn"
                                                                    data-bs-toggle="modal"                 
                                                                    data-bs-target="#saleHookahsModal"        
                                                                    data-table-id="{{ $currentBooking->id }}"
                                                                    data-table-name="{{ $tableDisplayName }}"
                                                                    data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}"
                                                                    data-sale-id="{{ $sale->id ?? '' }}">
                                                                <i class="bi bi-cup-straw"></i> Кальяны
                                                                </button>
                                                                
                                                                <!-- Кнопка редактирования стола -->
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-warning edit-table-btn"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editTableModal"
                                                                        data-id="{{ $currentBooking->id }}"
                                                                        data-table-id="{{ $currentBooking->table_name_id }}"
                                                                        data-table-name="{{ $tableDisplayName }}"
                                                                        data-booking-date="{{ $currentBooking->booking_date->format('Y-m-d') }}"
                                                                        data-booking-time="{{ is_string($currentBooking->booking_time) ? $currentBooking->booking_time : $currentBooking->booking_time->format('H:i') }}"
                                                                        data-duration="{{ $currentBooking->duration }}"
                                                                        data-guest-name="{{ $currentBooking->guest_name }}"
                                                                        data-phone="{{ $currentBooking->phone }}"
                                                                        data-guests-count="{{ $currentBooking->guests_count }}"
                                                                        data-comment="{{ $currentBooking->comment }}"
                                                                        data-client-id="{{ $currentBooking->client_id }}"
                                                                        data-client-name="{{ $currentBooking->client->name ?? '' }}"
                                                                        data-client-phone="{{ $currentBooking->client->phone ?? '' }}"
                                                                        data-status="{{ $currentBooking->status }}"
                                                                        title="Только для редактирования данных, не влияет на продажу">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                            </div>
                                                            
                                                            <!-- Кнопка закрытия и сумма -->
                                                            <div class="d-flex align-items-center justify-content-between border-top pt-2">
                                                                <!-- Таймер и сумма -->
                                                                <div class="d-flex justify-content-between align-items-center mb-2 bg-light rounded p-2">
                                                                    <!-- Таймер -->
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="bi bi-clock text-primary me-2"></i>
                                                                        <div>
                                                                            <small class="text-muted d-block">Осталось времени:</small>
                                                                            <div class="table-timer" 
                                                                                data-booking-date="{{ $currentBooking->booking_date->format('Y-m-d') }}"
                                                                                data-booking-time="{{ is_string($currentBooking->booking_time) ? $currentBooking->booking_time : $currentBooking->booking_time->format('H:i') }}"
                                                                                data-duration="{{ $currentBooking->duration }}">
                                                                                <span class="badge bg-warning text-dark fs-6">
                                                                                    <span class="timer-hours">00</span>:<span class="timer-minutes">00</span>:<span class="timer-seconds">00</span>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- Сумма -->
                                                                    <div class="text-end">
                                                                        <small class="text-muted d-block">Сумма заказа:</small>
                                                                        <span class="badge bg-success fs-6">
                                                                            {{ number_format($sale->total, 0) }} ₽
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Кнопка Закрыть стол -->
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-success"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#closeSaleModal"   
                                                                    data-table-id="{{ $currentBooking->id }}"
                                                                    data-table-name="{{ $tableDisplayName }}"
                                                                    data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}"
                                                                    data-sale-id="{{ $sale->id ?? '' }}">
                                                                    <i class="bi bi-door-closed"></i> Закрыть
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                    @elseif($currentBooking->status === 'opened_with_hookah' && $hasSale)
                                                        <!-- СТАТУС: ОТКРЫТ С КАЛЬЯНОМ -->
                                                        <div class="mb-2">
                                                            <!-- Кнопки товаров и кальянов -->
                                                            <div class="d-flex gap-1 mb-2">
                                                                <!-- Кнопка Товары -->
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-primary"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#saleProductsModal"
                                                                        data-table-id="{{ $currentBooking->id }}"
                                                                        data-sale-id="{{ $sale->id ?? '' }}">
                                                                    <i class="bi bi-cart"></i> Товары
                                                                </button>
                                                                
                                                                <!-- Кнопка Кальяны -->
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-warning open-sale-hookahs-btn"
                                                                    data-bs-toggle="modal"                 
                                                                    data-bs-target="#saleHookahsModal"        
                                                                    data-table-id="{{ $currentBooking->id }}"
                                                                    data-table-name="{{ $tableDisplayName }}"
                                                                    data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}"
                                                                    data-sale-id="{{ $sale->id ?? '' }}">
                                                                <i class="bi bi-cup-straw"></i> Кальяны
                                                                </button>
                                                                
                                                                <!-- Кнопка редактирования стола -->
                                                                <button type="button" 
                                                                        class="btn btn-sm btn-outline-warning edit-table-btn"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#editTableModal"
                                                                        data-id="{{ $currentBooking->id }}"
                                                                        data-table-id="{{ $currentBooking->table_name_id }}"
                                                                        data-table-name="{{ $tableDisplayName }}"
                                                                        data-booking-date="{{ $currentBooking->booking_date->format('Y-m-d') }}"
                                                                        data-booking-time="{{ is_string($currentBooking->booking_time) ? $currentBooking->booking_time : $currentBooking->booking_time->format('H:i') }}"
                                                                        data-duration="{{ $currentBooking->duration }}"
                                                                        data-guest-name="{{ $currentBooking->guest_name }}"
                                                                        data-phone="{{ $currentBooking->phone }}"
                                                                        data-guests-count="{{ $currentBooking->guests_count }}"
                                                                        data-comment="{{ $currentBooking->comment }}"
                                                                        data-client-id="{{ $currentBooking->client_id }}"
                                                                        data-client-name="{{ $currentBooking->client->name ?? '' }}"
                                                                        data-client-phone="{{ $currentBooking->client->phone ?? '' }}"
                                                                        data-status="{{ $currentBooking->status }}"
                                                                        title="Только для редактирования данных, не влияет на продажу">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                            </div>

                                                            <!-- МЕСТО ДЛЯ ТАЙМЕРА УГЛЕЙ (будет добавлен JavaScript) -->
                                                            <div id="coal-timer-placeholder-{{ $currentBooking->id }}" style="min-height: 50px;"></div>


                                                            <!-- Кнопка закрытия и сумма -->
                                                            <div class="d-flex align-items-center justify-content-between border-top pt-2">
                                                                <!-- Таймер и сумма -->
                                                                <div class="d-flex justify-content-between align-items-center mb-2 bg-light rounded p-2">
                                                                    <!-- Таймер -->
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="bi bi-clock text-primary me-2"></i>
                                                                        <div>
                                                                            <small class="text-muted d-block">Осталось времени:</small>
                                                                            <div class="table-timer" 
                                                                                data-booking-date="{{ $currentBooking->booking_date->format('Y-m-d') }}"
                                                                                data-booking-time="{{ is_string($currentBooking->booking_time) ? $currentBooking->booking_time : $currentBooking->booking_time->format('H:i') }}"
                                                                                data-duration="{{ $currentBooking->duration }}">
                                                                                <span class="badge bg-warning text-dark fs-6">
                                                                                    <span class="timer-hours">00</span>:<span class="timer-minutes">00</span>:<span class="timer-seconds">00</span>
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- Сумма -->
                                                                    <div class="text-end">
                                                                        <small class="text-muted d-block">Сумма заказа:</small>
                                                                        <span class="badge bg-success fs-6">
                                                                            {{ number_format($sale->total, 0) }} ₽
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Кнопка Закрыть стол -->
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-success"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#closeSaleModal"   
                                                                    data-table-id="{{ $currentBooking->id }}"
                                                                    data-table-name="{{ $tableDisplayName }}"
                                                                    data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}"
                                                                    data-sale-id="{{ $sale->id ?? '' }}">
                                                                    <i class="bi bi-door-closed"></i> Закрыть
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                     @elseif($currentBooking->status === 'closed')
                                                        <!-- СТАТУС: ЗАКРЫТ -->
                                                        <div class="d-flex flex-column gap-2">
                                                            
                                                             <div class="d-flex gap-1">
                                                                <!-- Кнопка Посмотреть заказ -->
                                                                @if($hasSale)
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-info view-order-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#viewOrderModal"
                                                                            data-sale-id="{{ $sale->id }}"
                                                                            data-table-id="{{ $currentBooking->id }}"
                                                                            data-table-name="{{ $tableDisplayName }}"
                                                                            data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}">
                                                                        <i class="bi bi-eye"></i> Заказ
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            <!-- Бейдж статуса -->
                                                            <div class="badge bg-secondary text-white p-2">
                                                                <i class="bi bi-door-closed"></i> Стол закрыт
                                                                @if($hasSale)
                                                                    <div class="small mt-1">
                                                                        Итого: <strong>{{ number_format($sale->total, 0) }} ₽</strong>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            
                                                            <!-- Кнопки для закрытого стола -->
                                                            
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    @else
                                        <td class="p-1" style="min-height: 50px;"></td>
                                    @endif
                                @endforeach
                                
                                <td class="text-center fw-bold bg-light" style="position: sticky; right: 0; z-index: 10;">
                                    {{ $timeStr }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Модальные окна -->
@include('tables.modals.sale-products')
@include('tables.modals.sale-hookahs')
@include('tables.modals.close-sale')
@include('tables.modals.create')
@include('tables.modals.edit')
@include('tables.modals.delete')
@include('tables.modals.view-order')

<script src="{{ asset('js/coal-timer.js') }}"></script>
<script src="{{ asset('js/tables/modules/hookah-timer.js') }}"></script>
<script src="{{ asset('js/tables/modules/product-manager.js') }}"></script>
<script src="{{ asset('js/tables/modules/hookah-manager.js') }}"></script>


<script>

window.tableProducts = window.tableProducts || {
    currentTableId: null,
    currentSaleId: null,
};

// Обновленный обработчик модального окна товаров:
const saleProductsModal = document.getElementById('saleProductsModal');
if (saleProductsModal) {
    saleProductsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button) {
            const tableId = button.getAttribute('data-table-id');
            const saleId = button.getAttribute('data-sale-id');
            const tableName = button.getAttribute('data-table-name');
            const guestName = button.getAttribute('data-guest-name');
            
            // ✅ Используем window.tableProducts
            window.tableProducts.currentTableId = tableId;
            window.tableProducts.currentSaleId = saleId;
            
            // Обновляем информацию в заголовке
            const infoElement = document.getElementById('currentTableInfo');
            if (infoElement) {
                infoElement.innerHTML = `<strong>Стол ${tableName}</strong> - ${guestName}`;
            }
            
            // Загружаем товары для этого стола
            if (window.TableProductManager && window.TableProductManager.loadTableProducts) {
                window.TableProductManager.loadTableProducts(tableId);
            }
            
            // Сбрасываем фильтры
            const categoryFilter = document.getElementById('categoryFilterProducts');
            const searchInput = document.getElementById('searchTableProduct');
            if (categoryFilter) categoryFilter.value = 'all';
            if (searchInput) searchInput.value = '';
            
            // Фильтруем товары
            setTimeout(() => {
                if (window.TableProductManager && window.TableProductManager.filterTableProducts) {
                    window.TableProductManager.filterTableProducts();
                }
            }, 100);
        }
    });
    
    saleProductsModal.addEventListener('hidden.bs.modal', function() {
        // Сбрасываем состояние
        if (window.tableProducts) {
            window.tableProducts.currentTableId = null;
            window.tableProducts.currentSaleId = null;
        }
        
        // Сбрасываем форму
        const productSelect = document.getElementById('productSelect');
        const productQuantity = document.getElementById('productQuantity');
        const productPrice = document.getElementById('productPrice');
        
        if (productSelect) productSelect.selectedIndex = 0;
        if (productQuantity) productQuantity.value = '1';
        if (productPrice) productPrice.value = '';
        
        // Скрываем предупреждения
        const availabilityInfo = document.getElementById('productAvailabilityInfo');
        if (availabilityInfo) availabilityInfo.style.display = 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {

    // =============== ИНИЦИАЛИЗАЦИЯ  ===============

    if (typeof window.TableSelectionManager !== 'undefined') {
        // Инициализируем с небольшой задержкой, чтобы таблица успела отрендериться
        setTimeout(() => {
            window.TableSelectionManager.init();
        }, 1000);
    }
    
    window.tableObserver = null;

    // Экспорт функций
    window.TableManager = {
        showToast,
        makeRequest,
        formatPrice,
        createToastContainer,
        calculateCloseTotal,
        getDiscountInRubles,
        initDiscountLogic,
        recalculateDiscount,
        setCurrentSubtotal,
        updateDiscountUI,
        updateTableTotal,
        numberFormat,
        updateClientBonusInfo,
        updateBonusCalculation,
        loadSaleDataForClosing,
        updateCloseModalData,
        fillProductsList,
        fillHookahsList,
        loadOrderData,
        updateOrderView,
        updateViewClientBonusInfo,
        calculateRemainingTime,
        formatTimeUnit,
        updateTimer,
        initTableTimers
    };
    
    console.log('Table Manager initialized');

    let productManager = null;
    let hookahManager = null;

    try {
        if (typeof ProductManager !== 'undefined') {
            productManager = new ProductManager();
            console.log('✅ ProductManager успешно инициализирован');
        } else {
            console.error('❌ ProductManager не загружен!');
        }
    } catch (error) {
        console.error('❌ Ошибка инициализации ProductManager:', error);
    }

    try {
        if (typeof HookahManager !== 'undefined') {
            hookahManager = new HookahManager();
            console.log('✅ HookahManager успешно инициализирован');
        } else {
            console.error('❌ HookahManager не загружен!');
        }
    } catch (error) {
        console.error('❌ Ошибка инициализации HookahManager:', error);
    }

    // Экспортируем в глобальную область
    window.productManager = productManager;
    window.hookahManager = hookahManager;

    // Инициализация таймеров
    if (typeof window.HookahTimerManager !== 'undefined') {
        console.log('🚀 Immediate initialization of HookahTimerManager...');
        window.HookahTimerManager.init();
    } else {
        console.error('❌ HookahTimerManager not found!');
    }

    if (typeof window.CoalTimerSystem !== 'undefined') {
        console.log('CoalTimerSystem found, initializing...');
        const system = window.CoalTimerSystem.init();
        
        if (system && system.restoreTimersForAllTablesWithHookah) {
            system.restoreTimersForAllTablesWithHookah();
        }
    } else {
        console.error('CoalTimerSystem NOT FOUND! Check file path.');
    }

    console.log('Table Manager initialized');

    // =============== ОБЩИЕ ФУНКЦИИ ===============
    
    function showToast(type, title, message) {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}:</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    function makeRequest(url, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        
        return fetch(url, { ...defaultOptions, ...options })
            .then(response => {
                return response.text().then(text => {
                    try {
                        const data = JSON.parse(text);
                        
                        if (!response.ok) {
                            const error = new Error(`HTTP error! status: ${response.status}`);
                            error.status = response.status;
                            error.data = data;
                            throw error;
                        }
                        
                        return data;
                    } catch (e) {
                        if (!response.ok) {
                            const error = new Error(`HTTP error! status: ${response.status}`);
                            error.status = response.status;
                            error.text = text;
                            throw error;
                        }
                        return { message: text };
                    }
                });
            })
            .catch(error => {
                console.error('Request error:', error);
                throw error;
            });
    }
    
    function formatPrice(price) {
        return parseFloat(price).toFixed(2) + ' ₽';
    }

    // Обработка кнопок удаления стола
    document.querySelectorAll('.delete-table-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-id');
            const tableName = this.getAttribute('data-table-name');
            const guestName = this.getAttribute('data-guest-name');
            
            // Устанавливаем action формы
            const form = document.getElementById('deleteTableForm');
            if (form) {
                form.action = `/tables/${tableId}`;
            }
            
            // Обновляем информацию в модалке
            const infoElement = document.getElementById('deleteTableInfo');
            if (infoElement && tableName && guestName) {
                infoElement.textContent = `Стол: "${tableName}", Гость: "${guestName}"`;
            }
            
            // Сбрасываем поле комментария
            const commentField = document.getElementById('deleteComment');
            if (commentField) {
                commentField.value = '';
            }
            
            // Автоматически фокусируемся на поле комментария
            setTimeout(() => {
                if (commentField) {
                    commentField.focus();
                }
            }, 500);
        });
    });
    const deleteTableForm = document.getElementById('deleteTableForm');
    if (deleteTableForm) {
        deleteTableForm.addEventListener('submit', function(e) {
            // Можно добавить валидацию если нужно
            const commentField = document.getElementById('deleteComment');
            if (commentField && commentField.value.trim().length > 500) {
                e.preventDefault();
                showToast('warning', 'Внимание', 'Комментарий не должен превышать 500 символов');
                return false;
            }
            
            // Показываем индикатор загрузки
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="bi bi-hourglass"></i> Удаление...';
            submitButton.disabled = true;
            
            // Форма будет отправлена обычным способом
            return true;
        });
    }


    // =============== ТАЙМЕР ДЛЯ СТОЛОВ ===============

    // Функция для расчета оставшегося времени
    function calculateRemainingTime(bookingDate, bookingTime, durationMinutes) {
        // Создаем объект времени начала
        const [hours, minutes] = bookingTime.split(':').map(Number);
        const startTime = new Date(bookingDate);
        startTime.setHours(hours, minutes, 0, 0);
        
        // Время окончания
        const endTime = new Date(startTime.getTime() + durationMinutes * 60000);
        
        // Текущее время
        const now = new Date();
        
        // Оставшееся время в миллисекундах
        let remainingMs = endTime - now;
        
        // Если время уже прошло
        if (remainingMs < 0) {
            return {
                hours: 0,
                minutes: 0,
                seconds: 0,
                isOverdue: true
            };
        }
        
        // Конвертируем в часы, минуты, секунды
        const hoursRemaining = Math.floor(remainingMs / (1000 * 60 * 60));
        remainingMs %= (1000 * 60 * 60);
        const minutesRemaining = Math.floor(remainingMs / (1000 * 60));
        remainingMs %= (1000 * 60);
        const secondsRemaining = Math.floor(remainingMs / 1000);
        
        return {
            hours: hoursRemaining,
            minutes: minutesRemaining,
            seconds: secondsRemaining,
            isOverdue: false
        };
    }

    // Функция для форматирования времени (добавляет ведущие нули)
    function formatTimeUnit(unit) {
        return unit < 10 ? '0' + unit : unit.toString();
    }

    // Функция обновления таймера
    function updateTimer(timerElement) {
        const bookingDate = timerElement.getAttribute('data-booking-date');
        const bookingTime = timerElement.getAttribute('data-booking-time');
        const duration = parseInt(timerElement.getAttribute('data-duration'));
        
        if (!bookingDate || !bookingTime || !duration) return;
        
        const remaining = calculateRemainingTime(bookingDate, bookingTime, duration);
        
        const hoursSpan = timerElement.querySelector('.timer-hours');
        const minutesSpan = timerElement.querySelector('.timer-minutes');
        const secondsSpan = timerElement.querySelector('.timer-seconds');
        
        if (remaining.isOverdue) {
            // Время вышло - показываем красный таймер
            if (hoursSpan) hoursSpan.textContent = '00';
            if (minutesSpan) minutesSpan.textContent = '00';
            if (secondsSpan) secondsSpan.textContent = '00';
            
            // Меняем цвет на красный
            timerElement.querySelector('.badge').classList.remove('bg-warning');
            timerElement.querySelector('.badge').classList.add('bg-danger');
            timerElement.querySelector('.badge').classList.add('text-white');
        } else {
            // Обновляем время
            if (hoursSpan) hoursSpan.textContent = formatTimeUnit(remaining.hours);
            if (minutesSpan) minutesSpan.textContent = formatTimeUnit(remaining.minutes);
            if (secondsSpan) secondsSpan.textContent = formatTimeUnit(remaining.seconds);
            
            // Меняем цвет в зависимости от оставшегося времени
            const badge = timerElement.querySelector('.badge');
            badge.classList.remove('bg-danger', 'bg-warning');
            badge.classList.remove('text-white', 'text-dark');
            
            if (remaining.hours === 0 && remaining.minutes < 30) {
                // Меньше 30 минут - красный
                badge.classList.add('bg-danger', 'text-white');
            } else if (remaining.hours === 0 && remaining.minutes < 60) {
                // Меньше часа - оранжевый
                badge.classList.add('bg-warning', 'text-dark');
            } else {
                // Больше часа - зеленый
                badge.classList.add('bg-success', 'text-white');
            }
        }
    }

    // Инициализация всех таймеров на странице
    function initTableTimers() {
        const timerElements = document.querySelectorAll('.table-timer');
        
        timerElements.forEach(timerElement => {
            // Первоначальное обновление
            updateTimer(timerElement);
            
            // Обновляем каждую секунду
            setInterval(() => updateTimer(timerElement), 1000);
        });
    }

    // Запускаем таймеры при загрузке страницы
    initTableTimers();

    


    // =============== МОДАЛКА КАЛЬЯНОВ ===============

    const saleHookahsModal = document.getElementById('saleHookahsModal');

    if (saleHookahsModal) {
        // Событие открытия модалки
        saleHookahsModal.addEventListener('show.bs.modal', function(event) {
            // Делегируем обработку HookahManager
            if (window.hookahManager && window.hookahManager.handleModalShow) {
                window.hookahManager.handleModalShow(event);
            }
        });
        
        // Событие закрытия модалки
        saleHookahsModal.addEventListener('hidden.bs.modal', function() {
            // Делегируем обработку HookahManager
            if (window.hookahManager && window.hookahManager.resetForm) {
                window.hookahManager.resetForm();
            }
        });
    }



    function updateTableTotal(tableId, newTotal) {
        console.log('🔄 Обновляем сумму для стола', tableId, 'новая сумма:', newTotal);
        
        // Находим ВСЕ ячейки стола с данным tableId
        const cells = document.querySelectorAll(`td`);
        
        cells.forEach(cell => {
            // Проверяем, есть ли в ячейке кнопка с нужным table-id
            const button = cell.querySelector(`button[data-table-id="${tableId}"]`);
            if (!button) return;
            
            console.log('✅ Найден стол в ячейке');
            
            // ВАЖНО: Находим КОНТЕЙНЕР с таймером и суммой
            const timerSumContainer = cell.querySelector('.d-flex.justify-content-between.align-items-center.mb-2.bg-light.rounded.p-2');
            
            if (timerSumContainer) {
                console.log('✅ Найден контейнер с таймером и суммой');
                
                // Находим элемент суммы ВНУТРИ контейнера
                const sumElement = timerSumContainer.querySelector('.text-end .badge.bg-success.fs-6');
                
                if (sumElement) {
                    sumElement.textContent = numberFormat(newTotal) + ' ₽';
                    console.log('✅ Сумма обновлена внутри контейнера:', sumElement.textContent);
                } else {
                    // Если элемента суммы нет, создаем его
                    console.log('⚠️ Элемент суммы не найден, создаем новый');
                    
                    // Находим блок с таймером внутри контейнера
                    const timerContainer = timerSumContainer.querySelector('.text-end');
                    if (timerContainer) {
                        // Добавляем сумму рядом с таймером
                        const newSumElement = document.createElement('div');
                        newSumElement.className = 'text-end';
                        newSumElement.innerHTML = `
                            <small class="text-muted d-block">Сумма заказа:</small>
                            <span class="badge bg-success fs-6">
                                ${numberFormat(newTotal)} ₽
                            </span>
                        `;
                        
                        // Вставляем перед кнопкой закрытия (если есть)
                        const closeButton = cell.querySelector('button[data-bs-target="#closeSaleModal"]');
                        if (closeButton && closeButton.parentElement) {
                            closeButton.parentElement.insertBefore(newSumElement, closeButton);
                        }
                    }
                }
            } else {
                // Если контейнера нет, ищем другие места для суммы
                console.log('⚠️ Контейнер с таймером не найден, ищем другие места');
                
                // 1. Ищем существующий бейдж с суммой
                let existingSum = cell.querySelector('.badge.bg-success.fs-6');
                if (existingSum) {
                    existingSum.textContent = numberFormat(newTotal) + ' ₽';
                    console.log('✅ Сумма обновлена в существующем бейдже');
                }
                
                // 2. Ищем сумму в статусе "закрытый стол"
                const closedTotalElement = cell.querySelector('.small.mt-1 strong');
                if (closedTotalElement) {
                    closedTotalElement.textContent = numberFormat(newTotal) + ' ₽';
                    console.log('✅ Сумма обновлена для закрытого стола');
                }
                
                // 3. Создаем новый элемент для суммы если нет
                if (!existingSum && !closedTotalElement) {
                    // Находим место для вставки - перед кнопкой закрытия
                    const closeButton = cell.querySelector('button[data-bs-target="#closeSaleModal"]');
                    if (closeButton && closeButton.parentElement) {
                        const sumContainer = document.createElement('div');
                        sumContainer.className = 'text-end mb-2';
                        sumContainer.innerHTML = `
                            <small class="text-muted d-block">Сумма заказа:</small>
                            <span class="badge bg-success fs-6">
                                ${numberFormat(newTotal)} ₽
                            </span>
                        `;
                        
                        closeButton.parentElement.insertBefore(sumContainer, closeButton);
                        console.log('✅ Создан новый элемент суммы');
                    }
                }
            }
            
            // Обновляем данные в кнопке "Закрыть стол"
            const closeButton = cell.querySelector('button[data-bs-target="#closeSaleModal"]');
            if (closeButton) {
                closeButton.setAttribute('data-total', newTotal);
            }
        });
    }

    // Вспомогательная функция для форматирования числа
    function numberFormat(number) {
        return parseFloat(number).toLocaleString('ru-RU', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }

    // =============== ФУНКЦИЯ ОБНОВЛЕНИЯ СУММЫ ВО ВСЕХ МОДАЛКАХ ===============

    function updateAllModalTotals(tableId, saleId, newTotal) {
        console.log('📊 Обновляем суммы во всех модалках для стола', tableId);
        
        // 1. Обновляем в модалке товаров
        if (currentTableId === tableId) {
            const totalElement = document.getElementById('totalAmount');
            if (totalElement) {
                totalElement.textContent = formatPrice(newTotal);
            }
        }
        
        // 2. Обновляем в модалке кальянов
        if (currentHookahsTableId === tableId) {
            const hookahsTotalElement = document.getElementById('hookahsTotalAmount');
            if (hookahsTotalElement) {
                // Если это только кальяны, получаем текущую сумму кальянов
                // Но лучше перезагрузить данные
                loadSaleHookahs();
            }
        }
        
        // 3. Обновляем в модалке закрытия (если она открыта)
        const closeSaleModal = document.getElementById('closeSaleModal');
        if (closeSaleModal && closeSaleModal.classList.contains('show')) {
            // Перезагружаем данные для закрытия
            loadSaleDataForClosing(tableId);
        }
    }

   // =============== МОДАЛКА ЗАКРЫТИЯ СТОЛА ===============

    const closeSaleModal = document.getElementById('closeSaleModal');
    if (closeSaleModal) {
        closeSaleModal.addEventListener('show.bs.modal', function() {
            // Через 200мс после открытия модалки рассчитываем бонусы
            setTimeout(calculateBonusAward, 200);
        });
        
        // При изменении скидки или бонусов тоже пересчитываем
        closeSaleModal.addEventListener('input', function(e) {
            if (e.target.id === 'closeDiscount' || e.target.id === 'bonusPointsToUse') {
                // Через небольшой таймаут, чтобы другие расчеты успели завершиться
                setTimeout(calculateBonusAward, 100);
            }
        });
    }

    if (closeSaleModal) {
        closeSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                const tableId = button.getAttribute('data-table-id');
                const tableName = button.getAttribute('data-table-name');
                const guestName = button.getAttribute('data-guest-name');
                const saleId = button.getAttribute('data-sale-id');
                
                // Обновляем заголовок
                document.getElementById('closeTableNumber').textContent = tableName;
                document.getElementById('closeGuestName').textContent = guestName;
                
                // Устанавливаем action формы
                const form = document.getElementById('closeSaleForm');
                if (form) {
                    form.action = `/tables/${tableId}/close-sale`;
                }
                
                // Загружаем данные о продаже
                loadSaleDataForClosing(tableId);
            }
        });
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'closeDiscount') {
                if (typeof calculateCloseTotal === 'function') {
                    calculateCloseTotal();  
                }
            }
        });
    }

    // Функция загрузки данных для закрытия стола
    function loadSaleDataForClosing(tableId) {
        console.log('🔄 Загрузка данных для закрытия стола:', tableId);
        
        if (!tableId) return;
        
        makeRequest(`/tables/${tableId}/get-sale-data`)
            .then(data => {
                console.log('📊 Получены данные продажи:', data);
                
                if (data.success) {
                    updateCloseModalData(data);
                } else {
                    showToast('danger', 'Ошибка', data.message || 'Не удалось загрузить данные');
                }
            })
            .catch(error => {
                console.error('❌ Ошибка загрузки данных продажи:', error);
                showToast('danger', 'Ошибка', 'Не удалось загрузить данные продажи');
            });
    }

    function updateCloseModalData(data) {
        // Обновляем суммы
        document.getElementById('closeItemsTotal').textContent = formatPrice(data.productsTotal);
        document.getElementById('closeHookahsTotal').textContent = formatPrice(data.hookahsTotal);
        document.getElementById('closeSubtotal').textContent = formatPrice(data.subtotal);
        document.getElementById('closeFinalTotal').textContent = formatPrice(data.finalTotal);
        
        // Устанавливаем скидку и отображаем ее
        const discountInput = document.getElementById('closeDiscount');
        const discountDisplay = document.getElementById('closeDiscountDisplay');
        
        if (discountInput) {
            discountInput.value = data.discount || 0;
        }

        if (discountDisplay) {
            discountDisplay.textContent = formatPrice(data.discount || 0);
        }
        setTimeout(() => {
            calculateCloseTotal();
        }, 100);

        // Заполняем списки товаров и кальянов
        fillProductsList(data.products || []);
        fillHookahsList(data.hookahs || []);
    }

    function fillProductsList(products) {
        const container = document.getElementById('closeProductsList');
        if (!container) return;
        
        if (products.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-cart-x me-2"></i>
                    Товары не добавлены
                </div>
            `;
        } else {
            let html = '<div class="list-group list-group-flush small">';
            products.forEach(product => {
                html += `
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="me-3 flex-grow-1">
                                <div class="fw-bold mb-1">${product.name}</div>
                                <div class="text-muted">${product.quantity} ${product.unit} × ${formatPrice(product.unit_price)}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">${formatPrice(product.total)}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    }

    function fillHookahsList(hookahs) {
        const container = document.getElementById('closeHookahsList');
        if (!container) return;
        
        if (hookahs.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-3">
                    <i class="bi bi-cup-straw me-2"></i>
                    Кальяны не добавлены
                </div>
            `;
        } else {
            let html = '<div class="list-group list-group-flush small">';
            
            // Группируем одинаковые кальяны
            const groupedHookahs = {};
            hookahs.forEach(hookah => {
                const key = `${hookah.id}_${hookah.created_at || ''}`;
                if (!groupedHookahs[key]) {
                    groupedHookahs[key] = {
                        ...hookah,
                        count: 1,
                        times: [hookah.created_at || '--:--']
                    };
                } else {
                    groupedHookahs[key].count++;
                    groupedHookahs[key].times.push(hookah.created_at || '--:--');
                }
            });
            
            Object.values(groupedHookahs).forEach(hookah => {
                const totalPrice = parseFloat(hookah.price) * hookah.count;
                
                // Формируем строку с временами добавления
                let timesHtml = '';
                if (hookah.times.length === 1) {
                    timesHtml = `<div class="text-muted small">${hookah.times[0]}</div>`;
                } else {
                    // Если несколько одинаковых кальянов, показываем список времен
                    timesHtml = '<div class="text-muted small">';
                    hookah.times.forEach((time, index) => {
                        timesHtml += `<div>${index + 1}. ${time}</div>`;
                    });
                    timesHtml += '</div>';
                }
                
                html += `
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="me-3 flex-grow-1">
                                <div class="fw-bold mb-1">${hookah.name}</div>
                                <div class="text-muted">${hookah.count > 1 ? hookah.count + ' × ' : ''}${formatPrice(hookah.price)}</div>
                                ${timesHtml}
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">${formatPrice(totalPrice)}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }
    }


    // =============== ИНИЦИАЛИЗАЦИЯ ОБРАБОТЧИКА СКИДКИ ===============

    document.addEventListener('DOMContentLoaded', function() {
        const closeDiscountInput = document.getElementById('closeDiscount');
        if (closeDiscountInput) {
            closeDiscountInput.addEventListener('input', calculateCloseTotal);
            
            // Также вызываем при изменении через клавиатуру
            closeDiscountInput.addEventListener('change', calculateCloseTotal);
        }
    });

    // =============== МОДАЛКА ПРОСМОТРА ЗАКАЗА ===============

    const viewOrderModal = document.getElementById('viewOrderModal');

    if (viewOrderModal) {
        // Событие открытия модалки
        viewOrderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            
            // Берем table_id, а не sale_id
            const tableId = button.getAttribute('data-table-id'); 
            const tableName = button.getAttribute('data-table-name');
            const guestName = button.getAttribute('data-guest-name');
            
            // Обновляем информацию
            document.getElementById('viewTableNumber').textContent = `Стол ${tableName}`;
            document.getElementById('viewGuestName').textContent = guestName;
            
            // Загружаем данные через tableId
            if (tableId) {
                loadOrderData(tableId);
            }
        });
    }

    function loadOrderData(tableId) {
        if (!tableId) return;
        
        // Используем существующий маршрут /tables/{table}/get-sale-data
        fetch(`/tables/${tableId}/get-sale-data`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateOrderView(data);
                } else {
                    console.error('Error loading order data:', data.message);
                    showToast('danger', 'Ошибка', 'Не удалось загрузить данные заказа');
                }
            })
            .catch(error => {
                console.error('Error loading order data:', error);
                showToast('danger', 'Ошибка', 'Ошибка загрузки данных');
            });
    }

    function updateOrderView(data) {
        // Обновляем информацию о клиенте и бонусах
        updateViewClientBonusInfo(data);
        
        // Товары
        const productsBody = document.getElementById('viewOrderProductsBody');
        if (productsBody) {
            productsBody.innerHTML = '';
            
            if (data.products && data.products.length > 0) {
                data.products.forEach(product => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${product.name}</td>
                        <td>${product.quantity} ${product.unit}</td>
                        <td>${parseFloat(product.unit_price).toFixed(2)} ₽</td>
                        <td>${parseFloat(product.total).toFixed(2)} ₽</td>
                    `;
                    productsBody.appendChild(row);
                });
            } else {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="4" class="text-center text-muted py-3">
                        <i class="bi bi-cart-x me-2"></i>Товары не добавлены
                    </td>
                `;
                productsBody.appendChild(emptyRow);
            }
        }
        
        // Кальяны
        const hookahsBody = document.getElementById('viewOrderHookahsBody');
        if (hookahsBody) {
            hookahsBody.innerHTML = '';
            
            if (data.hookahs && data.hookahs.length > 0) {
                data.hookahs.forEach(hookah => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${hookah.name}</td>
                        <td>${parseFloat(hookah.price).toFixed(0)} ₽</td>
                        <td>${hookah.created_at || '--:--'}</td>
                    `;
                    hookahsBody.appendChild(row);
                });
            } else {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="3" class="text-center text-muted py-3">
                        <i class="bi bi-cup-straw me-2"></i>Кальяны не добавлены
                    </td>
                `;
                hookahsBody.appendChild(emptyRow);
            }
        }
        
        // Комментарий
        const commentContainer = document.getElementById('viewCommentContainer');
        const commentElement = document.getElementById('viewOrderComment');
        
        if (commentContainer && commentElement && data.comment) {
            commentElement.textContent = data.comment;
            commentContainer.style.display = 'block';
        } else if (commentContainer) {
            commentContainer.style.display = 'none';
        }
        
        // Итоги
        document.getElementById('viewProductsTotal').textContent = 
            parseFloat(data.productsTotal || 0).toFixed(2) + ' ₽';
        document.getElementById('viewHookahsTotal').textContent = 
            parseFloat(data.hookahsTotal || 0).toFixed(2) + ' ₽';
        document.getElementById('viewDiscount').textContent = 
            parseFloat(data.discount || 0).toFixed(2) + ' ₽';
        
        // Бонусная скидка
        const bonusDiscountContainer = document.getElementById('viewBonusDiscountContainer');
        const bonusDiscountElement = document.getElementById('viewBonusDiscount');
        if (bonusDiscountContainer && bonusDiscountElement && data.usedBonusPoints > 0) {
            bonusDiscountElement.textContent = parseFloat(data.usedBonusPoints || 0).toFixed(2) + ' ₽';
            bonusDiscountContainer.style.display = 'block';
        } else if (bonusDiscountContainer) {
            bonusDiscountContainer.style.display = 'none';
        }
        
        // Начисленные бонусы
        const bonusEarnedContainer = document.getElementById('viewBonusEarnedContainer');
        const bonusEarnedElement = document.getElementById('viewBonusEarned');
        
        if (bonusEarnedContainer && bonusEarnedElement) {
            // Используем данные, которые пришли с сервера
            if (data.bonusEarned > 0 && data.hasBonusCard) {
                bonusEarnedContainer.innerHTML = `
                    <small class="text-muted">Начислено бонусов:</small>
                    <div class="fw-bold text-success">${data.bonusEarned} бонусов</div>
                    <div class="small text-muted mt-1">(${data.clientBonusPercent || 5}% от суммы)</div>
                `;
                bonusEarnedContainer.style.display = 'block';
            } else {
                bonusEarnedContainer.style.display = 'none';
                bonusEarnedContainer.innerHTML = ''; // Очищаем содержимое
            }
        }
        
        // Итоговая сумма
        document.getElementById('viewFinalTotal').textContent = 
            parseFloat(data.finalTotal || 0).toFixed(2) + ' ₽';
        
        // Способ оплаты
        const paymentMethod = document.getElementById('viewPaymentMethod');
        if (paymentMethod && data.paymentMethod) {
            paymentMethod.textContent = data.paymentMethod;
        } else if (paymentMethod) {
            paymentMethod.textContent = 'Не указано';
        }
        
        // Формируем строку разбивки
        const totalBreakdown = document.getElementById('viewTotalBreakdown');
        if (totalBreakdown) {
            let breakdown = '(Товары + Кальяны)';
            if (data.discount > 0) breakdown += ' - Скидка';
            if (data.usedBonusPoints > 0) breakdown += ' - Бонусы';
            totalBreakdown.textContent = breakdown;
        }
    }

    // =============== ФУНКЦИЯ ОБНОВЛЕНИЯ ИНФОРМАЦИИ О КЛИЕНТЕ И БОНУСАХ ===============

    function updateViewClientBonusInfo(data) {
        const clientBonusInfo = document.getElementById('viewClientBonusInfo');
        const clientNameElem = document.getElementById('viewClientName');
        const clientBonusPointsElem = document.getElementById('viewClientBonusPoints');
        const usedBonusesElem = document.getElementById('viewUsedBonuses');
        const maxSpendPercentElem = document.getElementById('viewMaxSpendPercent');
        
        if (!clientBonusInfo || !clientNameElem) return;
        
        if (!data.clientId || !data.clientName) {
            // Нет клиента - скрываем секцию
            clientBonusInfo.style.display = 'none';
            return;
        }
        
        // Показываем информацию о клиенте
        clientBonusInfo.style.display = 'block';
        clientNameElem.textContent = data.clientName;
        
        if (clientBonusPointsElem) {
            clientBonusPointsElem.textContent = data.clientBonusPoints?.toLocaleString() || '0';
        }
        
        if (usedBonusesElem) {
            usedBonusesElem.textContent = data.usedBonusPoints?.toLocaleString() || '0';
        }
        
        if (maxSpendPercentElem) {
            // Показываем процент и название карты если есть
            let percentText = data.clientMaxSpendPercent || '50';
            if (data.clientBonusCardName) {
                percentText += `% (${data.clientBonusCardName})`;
            } else if (data.hasBonusCard) {
                percentText += '%';
            }
            maxSpendPercentElem.textContent = percentText;
        }
    }


    // =============== ПЕРЕМЕННЫЕ ДЛЯ БОНУСОВ ===============

    // Элементы бонусов
    const clientBonusInfo = document.getElementById('clientBonusInfo');
    const clientNameElem = document.getElementById('clientName');
    const clientBonusPointsElem = document.getElementById('clientBonusPoints');
    const maxUsableBonusesElem = document.getElementById('maxUsableBonuses');
    const maxSpendPercentText = document.getElementById('maxSpendPercentText');
    const bonusSection = document.getElementById('bonusSection');
    const useBonusesCheckbox = document.getElementById('useBonuses');
    const bonusPointsToUseInput = document.getElementById('bonusPointsToUse');
    const bonusInputRow = document.getElementById('bonusInputRow');
    const useMaxBonusesBtn = document.getElementById('useMaxBonusesBtn');
    const bonusWarning = document.getElementById('bonusWarning');
    const bonusWarningText = document.getElementById('bonusWarningText');
    const bonusDiscountRow = document.getElementById('bonusDiscountRow');
    const closeBonusDiscountDisplay = document.getElementById('closeBonusDiscountDisplay');
    const finalTotalBreakdown = document.getElementById('finalTotalBreakdown');

    // Проверка элементов
    console.log('🔍 Проверка элементов бонусной системы:');
    console.log('clientBonusInfo:', clientBonusInfo);
    console.log('clientNameElem:', clientNameElem);
    console.log('clientBonusPointsElem:', clientBonusPointsElem);
    console.log('maxUsableBonusesElem:', maxUsableBonusesElem);
    console.log('maxSpendPercentText:', maxSpendPercentText);
    console.log('bonusSection:', bonusSection);

    let maxUsableBonuses = 0;
    let currentBonusDiscount = 0;
    let clientMaxSpendPercent = 50;


    // =============== ФУНКЦИЯ ОБНОВЛЕНИЯ ИНФОРМАЦИИ О БОНУСАХ ===============

    function updateClientBonusInfo(data) {
        console.log('📊 updateClientBonusInfo вызвана с данными:', data);
        
        // Проверяем наличие элементов
        if (!clientBonusInfo || !clientNameElem) {
            console.error('❌ Не найдены элементы бонусной системы');
            return;
        }
        
        // Проверяем, есть ли данные клиента
        if (!data.clientId || data.clientBonusPoints === undefined) {
            console.log('📊 Нет данных клиента или бонусов');
            
            // Нет клиента - скрываем секцию
            clientBonusInfo.style.display = 'none';
            if (bonusSection) bonusSection.style.display = 'none';
            if (bonusDiscountRow) bonusDiscountRow.style.display = 'none';
            currentBonusDiscount = 0;
            return;
        }
        
        console.log('✅ Обновляем информацию о клиенте:', data.clientName, 'бонусы:', data.clientBonusPoints);
        
        // Сохраняем данные для расчета бонусов
        window.currentClientRequiredSpend = data.clientRequiredSpend || 0;
        window.currentClientBonusPercent = data.clientBonusPercent || 0;
        window.currentClientBonusCardName = data.clientBonusCardName || null;
        window.currentClientHasBonusCard = data.hasBonusCard || false;
        
        // Показываем информацию о клиенте
        clientBonusInfo.style.display = 'block';
        clientNameElem.textContent = data.clientName || 'Клиент';
        clientBonusPointsElem.textContent = data.clientBonusPoints.toLocaleString();
        
        if (data.hasBonusCard) {
            // У клиента есть карта
            const cardInfo = data.clientBonusCardName ? ` (${data.clientBonusCardName})` : '';
            maxSpendPercentText.textContent = `${data.clientMaxSpendPercent}%${cardInfo}`;
            
            // Рассчитываем максимальное количество бонусов
            const totalAmount = parseFloat(data.subtotal) || 0;
            const discountInput = document.getElementById('closeDiscount');
            const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
            
            console.log('📊 Данные для расчета бонусов:', {
                totalAmount,
                discount,
                clientMaxSpendPercent: data.clientMaxSpendPercent
            });
            
            // Максимум бонусов = X% от (сумма товаров - скидка)
            const percentage = (data.clientMaxSpendPercent || 50) / 100;
            maxUsableBonuses = Math.floor((totalAmount - discount) * percentage);
            
            // Нельзя использовать больше, чем есть у клиента
            maxUsableBonuses = Math.min(data.clientBonusPoints, maxUsableBonuses);
            maxUsableBonuses = Math.max(0, maxUsableBonuses);
            
            console.log('📊 Максимально можно использовать бонусов:', maxUsableBonuses);
            
            maxUsableBonusesElem.textContent = maxUsableBonuses.toLocaleString() + ' бонусов';
            
            // Показываем секцию бонусов
            bonusSection.style.display = 'block';
            
            // Если уже были использованы бонусы
            if (data.usedBonusPoints > 0) {
                console.log('✅ Уже использованы бонусы:', data.usedBonusPoints);
                useBonusesCheckbox.checked = true;
                bonusPointsToUseInput.value = data.usedBonusPoints;
                currentBonusDiscount = data.usedBonusPoints;
                bonusPointsToUseInput.disabled = false;
                bonusInputRow.style.display = 'flex';
                bonusDiscountRow.style.display = 'flex';
                closeBonusDiscountDisplay.textContent = formatPrice(data.usedBonusPoints);
            } else {
                // Сбрасываем состояние бонусов
                console.log('📊 Бонусы не использовались ранее');
                useBonusesCheckbox.checked = false;
                bonusPointsToUseInput.value = 0;
                bonusPointsToUseInput.disabled = true;
                bonusInputRow.style.display = 'none';
                currentBonusDiscount = 0;
                bonusDiscountRow.style.display = 'none';
            }
            
            // Предупреждение если можно использовать бонусы
            bonusWarning.style.display = 'block';
            
            if (maxUsableBonuses > 0) {
                bonusWarningText.innerHTML = `
                    <div>Клиент может использовать до <strong>${maxUsableBonuses.toLocaleString()}</strong> бонусов</div>
                    <div class="small mt-1">Лимит из бонусной карты: <strong>${data.clientMaxSpendPercent || 50}%</strong> от суммы заказа</div>
                `;
            } else if (data.clientBonusPoints > 0) {
                bonusWarningText.innerHTML = `
                    <div>У клиента недостаточно бонусов для использования</div>
                    <div class="small mt-1">Лимит из бонусной карты: <strong>${data.clientMaxSpendPercent || 50}%</strong> от суммы заказа</div>
                `;
            } else {
                bonusWarningText.textContent = 'У клиента нет бонусов';
            }
        } else {
             // У клиента нет карты
            maxSpendPercentText.textContent = 'Нет карты';
            maxUsableBonusesElem.textContent = '0 бонусов';
            
            // Скрываем секцию использования бонусов
            bonusSection.style.display = 'none';
            useBonusesCheckbox.checked = false;
            bonusPointsToUseInput.disabled = true;
            bonusInputRow.style.display = 'none';
            currentBonusDiscount = 0;
            bonusDiscountRow.style.display = 'none';
            
            // Упрощаем сообщение
            bonusWarning.style.display = 'block';
            bonusWarningText.innerHTML = `
                <div><i class="bi bi-info-circle text-warning me-1"></i> У клиента нет бонусной карты</div>
            `;
        }
        
        setTimeout(calculateBonusAward, 100);
        
        // Инициализируем обработчики для бонусов
        initBonusHandlers();
    }

    function initBonusHandlers() {
        console.log('🔄 Инициализация обработчиков бонусов');
        
        // Обработчик для чекбокса "Использовать бонусы"
        if (useBonusesCheckbox) {
            useBonusesCheckbox.addEventListener('change', function() {
                console.log('✅ Чекбокс бонусов изменен:', this.checked);
                
                if (this.checked && maxUsableBonuses > 0) {
                    bonusPointsToUseInput.disabled = false;
                    bonusInputRow.style.display = 'flex';
                    bonusPointsToUseInput.max = maxUsableBonuses;
                    bonusPointsToUseInput.placeholder = `До ${maxUsableBonuses}`;
                    
                    // Автоматически ставим максимальное значение
                    if (parseInt(bonusPointsToUseInput.value) === 0) {
                        const suggestedValue = Math.min(100, maxUsableBonuses);
                        bonusPointsToUseInput.value = suggestedValue;
                        currentBonusDiscount = suggestedValue;
                        console.log('✅ Установлено значение бонусов:', suggestedValue);
                    }
                    
                    calculateCloseTotal();
                } else {
                    bonusPointsToUseInput.disabled = true;
                    bonusInputRow.style.display = 'none';
                    currentBonusDiscount = 0;
                    calculateCloseTotal();
                }
            });
        }
        
        // Обработчик для ввода количества бонусов
        if (bonusPointsToUseInput) {
            bonusPointsToUseInput.addEventListener('input', function() {
                const value = parseInt(this.value) || 0;
                console.log('✅ Ввод бонусов:', value, 'максимум:', maxUsableBonuses);
                
                if (value > maxUsableBonuses) {
                    this.value = maxUsableBonuses;
                    currentBonusDiscount = maxUsableBonuses;
                    console.log('⚠️ Значение скорректировано до максимума:', maxUsableBonuses);
                } else if (value < 0) {
                    this.value = 0;
                    currentBonusDiscount = 0;
                } else {
                    currentBonusDiscount = value;
                }
                
                calculateCloseTotal();
            });
        }
        
        // Обработчик для кнопки "Максимум"
        if (useMaxBonusesBtn) {
            useMaxBonusesBtn.addEventListener('click', function() {
                if (maxUsableBonuses > 0) {
                    console.log('✅ Использовать максимум бонусов:', maxUsableBonuses);
                    bonusPointsToUseInput.value = maxUsableBonuses;
                    currentBonusDiscount = maxUsableBonuses;
                    calculateCloseTotal();
                }
            });
        }
    }

    // =============== ОБНОВЛЕННАЯ ФУНКЦИЯ РАСЧЕТА ИТОГОВОЙ СУММЫ ===============

    function getDiscountInRubles() {
        const closeDiscountInput = document.getElementById('closeDiscount');
        if (!closeDiscountInput) return 0;
        
        const discountValue = parseFloat(closeDiscountInput.value) || 0;
        
        if (currentDiscountType === 'percent') {
            // Получаем промежуточную сумму
            const subtotalElement = document.getElementById('closeSubtotal');
            if (!subtotalElement) return discountValue;
            
            const subtotalText = subtotalElement.textContent;
            const subtotal = parseFloat(subtotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
            
            // Рассчитываем скидку в рублях: 20% от 1000 = 200 руб
            return (subtotal * discountValue / 100);
        }
        
        // Для фиксированной суммы просто возвращаем значение
        return discountValue;
    }

    function calculateCloseTotal() {
        console.log('🔄 calculateCloseTotal вызвана');
        
        // Получаем элементы
        const subtotalElement = document.getElementById('closeSubtotal');
        const finalTotalElement = document.getElementById('closeFinalTotal');
        
        if (!subtotalElement || !finalTotalElement) {
            console.error('❌ Не найдены элементы для расчета итога');
            return;
        }
        
        // Получаем промежуточную сумму
        const subtotalText = subtotalElement.textContent;
        const subtotal = parseFloat(subtotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
        
        // Рассчитываем скидку в рублях
        const discountInRubles = getDiscountInRubles();
        
        // Получаем бонусы (учитываем disabled состояние)
        const bonusPointsInput = document.getElementById('bonusPointsToUse');
        let bonusDiscount = 0;
        
        if (bonusPointsInput && !bonusPointsInput.disabled) {
            bonusDiscount = parseInt(bonusPointsInput.value) || 0;
        }
        
        console.log('📊 Данные для расчета:', {
            subtotal,
            discountInRubles,
            bonusDiscount
        });
        
        // Рассчитываем итог
        const finalTotal = Math.max(0, subtotal - discountInRubles - bonusDiscount);
        
        // Обновляем отображение
        finalTotalElement.textContent = formatPrice(finalTotal);
        
        // Обновляем отображение скидки
        const discountDisplay = document.getElementById('closeDiscountDisplay');
        if (discountDisplay) {
            discountDisplay.textContent = formatPrice(discountInRubles);
        }
        
        // Обновляем конвертацию для процентов
        if (currentDiscountType === 'percent' && discountConversion && discountAmount) {
            discountAmount.textContent = formatPrice(discountInRubles);
        }
        
        // Обновляем отображение бонусной скидки
        if (bonusDiscountRow && closeBonusDiscountDisplay) {
            if (bonusDiscount > 0) {
                bonusDiscountRow.style.display = 'flex';
                closeBonusDiscountDisplay.textContent = formatPrice(bonusDiscount);
                console.log('✅ Бонусная скидка отображена:', bonusDiscount);
            } else {
                bonusDiscountRow.style.display = 'none';
            }
        }
        
        // Формируем строку разбивки
        if (finalTotalBreakdown) {
            let breakdown = '(Товары + Кальяны)';
            if (discountInRubles > 0) {
                breakdown += ' - Скидка';
                if (currentDiscountType === 'percent') {
                    breakdown += ` (${closeDiscountInput.value}%)`;
                }
            }
            if (bonusDiscount > 0) breakdown += ' - Бонусы';
            
            finalTotalBreakdown.textContent = breakdown;
        }
        
        // Обновляем расчет доступных бонусов
        setTimeout(calculateBonusAward, 50);
        updateBonusCalculation();
        const paymentSelect = document.getElementById('closePaymentMethod');
        if (paymentSelect && paymentSelect.value) {
            const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
            const isCash = isCashPayment(paymentSelect.value, selectedOption?.text || '');
            if (isCash) {
                setTimeout(() => {
                    if (typeof window.updateCalculatorTotal === 'function') {
                        window.updateCalculatorTotal();
                    }
                    if (typeof window.calculateChange === 'function') {
                        window.calculateChange();
                    }
                }, 50);
            }
        }
    }

    // =============== ФУНКЦИЯ ОБНОВЛЕНИЯ РАСЧЕТА БОНУСОВ ===============

    function updateBonusCalculation() {
        // Проверяем, есть ли данные клиента
        const clientBonusPoints = parseInt(clientBonusPointsElem.textContent.replace(/\D/g, '')) || 0;
        if (clientBonusPoints === 0) return;
        
        // Получаем текущие суммы
        const subtotalText = document.getElementById('closeSubtotal').textContent;
        const subtotal = parseFloat(subtotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
        const discount = parseFloat(document.getElementById('closeDiscount').value) || 0;
        
        // Пересчитываем максимальные бонусы
        const percentage = clientMaxSpendPercent / 100;
        maxUsableBonuses = Math.floor((subtotal - discount) * percentage);
        maxUsableBonuses = Math.min(clientBonusPoints, maxUsableBonuses);
        maxUsableBonuses = Math.max(0, maxUsableBonuses);
        
        maxUsableBonusesElem.textContent = maxUsableBonuses.toLocaleString() + ' бонусов';
        
        // Обновляем максимальное значение в input
        if (bonusPointsToUseInput && !bonusPointsToUseInput.disabled) {
            bonusPointsToUseInput.max = maxUsableBonuses;
            
            // Если текущее значение больше нового максимума, уменьшаем его
            if (parseInt(bonusPointsToUseInput.value) > maxUsableBonuses) {
                bonusPointsToUseInput.value = maxUsableBonuses;
                currentBonusDiscount = maxUsableBonuses;
                calculateCloseTotal();
            }
        }
    }

    // =============== ОБРАБОТЧИКИ ДЛЯ БОНУСОВ ===============

    // Инициализация обработчиков для бонусов
    if (useBonusesCheckbox) {
        useBonusesCheckbox.addEventListener('change', function() {
            if (this.checked && maxUsableBonuses > 0) {
                bonusPointsToUseInput.disabled = false;
                bonusInputRow.style.display = 'flex';
                bonusPointsToUseInput.max = maxUsableBonuses;
                bonusPointsToUseInput.placeholder = `До ${maxUsableBonuses}`;
                
                // Автоматически ставим максимальное значение
                if (bonusPointsToUseInput.value == 0) {
                    bonusPointsToUseInput.value = Math.min(100, maxUsableBonuses);
                    currentBonusDiscount = bonusPointsToUseInput.value;
                }
                
                calculateCloseTotal();
            } else {
                bonusPointsToUseInput.disabled = true;
                bonusInputRow.style.display = 'none';
                currentBonusDiscount = 0;
                calculateCloseTotal();
            }
        });
    }

    if (bonusPointsToUseInput) {
        bonusPointsToUseInput.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            
            if (value > maxUsableBonuses) {
                this.value = maxUsableBonuses;
                currentBonusDiscount = maxUsableBonuses;
            } else if (value < 0) {
                this.value = 0;
                currentBonusDiscount = 0;
            } else {
                currentBonusDiscount = value;
            }
            
            calculateCloseTotal();
        });
    }

    if (useMaxBonusesBtn) {
        useMaxBonusesBtn.addEventListener('click', function() {
            if (maxUsableBonuses > 0) {
                bonusPointsToUseInput.value = maxUsableBonuses;
                currentBonusDiscount = maxUsableBonuses;
                calculateCloseTotal();
            }
        });
    }

    // =============== ИНИЦИАЛИЗАЦИЯ ОБРАБОТЧИКА СКИДКИ ===============

    document.addEventListener('DOMContentLoaded', function() {
        const closeDiscountInput = document.getElementById('closeDiscount');
        if (closeDiscountInput) {
            // Устанавливаем начальный тип скидки
            currentDiscountType = 'fixed'; // или 'percent'
            updateDiscountUI();
            
            // Обработчик изменения значения
            closeDiscountInput.addEventListener('input', function() {
                calculateCloseTotal();
            });
        }
    });

    // =============== ЛОГИКА ВЫБОРА ТИПА СКИДКИ ===============

    // Элементы для работы со скидкой
    let discountTypeSelect = document.getElementById('discountTypeSelect');
    let closeDiscountInput = document.getElementById('closeDiscount');
    let discountSuffix = document.getElementById('discountSuffix');
    let discountConversion = document.getElementById('discountConversion');
    let discountAmount = document.getElementById('discountAmount');
    let discountPercentInput = document.getElementById('discountPercent');

    // Текущий тип скидки
    let currentDiscountType = 'fixed'; // 'fixed' или 'percent'
    let currentSubtotal = 0; // Промежуточная сумма для расчета процентов

    // Инициализация обработчиков скидки
    function initDiscountLogic() {
        if (!discountTypeSelect || !closeDiscountInput) return;
        
        // Обработчик изменения типа скидки
        discountTypeSelect.addEventListener('change', function() {
            currentDiscountType = this.value;
            updateDiscountUI();
            recalculateDiscount();
        });
        
        // Обработчик ввода значения скидки
        closeDiscountInput.addEventListener('input', function() {
            recalculateDiscount();
        });
    }

    // Обновление интерфейса в зависимости от типа скидки
    function updateDiscountUI() {
        if (currentDiscountType === 'percent') {
            // Режим процентов
            discountSuffix.textContent = '%';
            discountConversion.style.display = 'block';
            
            // Обновляем placeholder и шаг
            closeDiscountInput.placeholder = '0';
            closeDiscountInput.step = '0.01';
            closeDiscountInput.max = '100'; // Максимум 100%
            
            // Показываем конвертацию
            updateDiscountConversion();
        } else {
            // Режим рублей
            discountSuffix.textContent = '₽';
            discountConversion.style.display = 'none';
            
            // Обновляем placeholder и шаг
            closeDiscountInput.placeholder = '0';
            closeDiscountInput.step = '0.01';
            closeDiscountInput.max = ''; // Снимаем ограничение
            
            // Скрываем конвертацию
            discountAmount.textContent = '0.00 ₽';
        }
    }

    // Пересчет скидки
    function recalculateDiscount() {
        if (!closeDiscountInput) return;
        
        const discountValue = parseFloat(closeDiscountInput.value) || 0;
        
        if (currentDiscountType === 'percent') {
            // Ограничиваем проценты 0-100
            const limitedValue = Math.min(100, Math.max(0, discountValue));
            if (discountValue !== limitedValue) {
                closeDiscountInput.value = limitedValue;
            }
            
            // Обновляем конвертацию
            if (discountConversion && discountAmount) {
                const discountInRubles = getDiscountInRubles();
                discountAmount.textContent = formatPrice(discountInRubles);
            }
            
            // Обновляем скрытое поле процентов
            if (discountPercentInput) {
                discountPercentInput.value = limitedValue;
            }
        } else {
            // Ограничиваем рубли промежуточной суммой
            const discountInRubles = getDiscountInRubles();
            const subtotalElement = document.getElementById('closeSubtotal');
            if (subtotalElement) {
                const subtotalText = subtotalElement.textContent;
                const subtotal = parseFloat(subtotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
                const limitedValue = Math.min(subtotal, Math.max(0, discountValue));
                if (discountValue !== limitedValue) {
                    closeDiscountInput.value = limitedValue;
                }
            }
            
            // Обнуляем поле с процентами
            if (discountPercentInput) {
                discountPercentInput.value = 0;
            }
        }
        
        // Всегда вызываем пересчет итога
        calculateCloseTotal();
    }

    // Обновление итоговой суммы после скидки
    function updateTotalAfterDiscount(discountInRubles) {
        // Обновляем отображение скидки
        const discountDisplay = document.getElementById('closeDiscountDisplay');
        if (discountDisplay) {
            discountDisplay.textContent = formatPrice(discountInRubles);
        }
        
        // Вызываем пересчет итоговой суммы
        if (typeof calculateCloseTotal === 'function') {
            calculateCloseTotal();
        }
    }

    // Обновление конвертации (вызывается при загрузке данных)
    function updateDiscountConversion() {
        if (currentDiscountType === 'percent') {
            const discountValue = parseFloat(closeDiscountInput.value) || 0;
            const discountInRubles = (currentSubtotal * discountValue) / 100;
            discountAmount.textContent = formatPrice(discountInRubles);
        }
    }

    // Функция для установки промежуточной суммы
    function setCurrentSubtotal(subtotal) {
        currentSubtotal = subtotal;
        if (currentDiscountType === 'percent') {
            updateDiscountConversion();
        }
    }

    // =============== ОБНОВЛЕНИЕ ФУНКЦИИ updateCloseModalData ===============

    function updateCloseModalData(data) {
        console.log('📊 updateCloseModalData вызвана с данными:', data);
        
        // Обновляем информацию о клиенте и бонусах
        updateClientBonusInfo(data);
        
        // Обновляем суммы
        document.getElementById('closeItemsTotal').textContent = formatPrice(data.productsTotal);
        document.getElementById('closeHookahsTotal').textContent = formatPrice(data.hookahsTotal);
        document.getElementById('closeSubtotal').textContent = formatPrice(data.subtotal);
        document.getElementById('closeFinalTotal').textContent = formatPrice(data.finalTotal);
        
        // Устанавливаем скидку и отображаем ее
        const discountInput = document.getElementById('closeDiscount');
        const discountDisplay = document.getElementById('closeDiscountDisplay');
        
        if (discountInput) {
            discountInput.value = data.discount || 0;
        }

        if (discountDisplay) {
            discountDisplay.textContent = formatPrice(data.discount || 0);
        }
        setTimeout(() => {
            calculateCloseTotal();
        }, 100);

        // Заполняем списки товаров и кальянов
        fillProductsList(data.products || []);
        fillHookahsList(data.hookahs || []);
        if (document.getElementById('cashCalculator').style.display !== 'none') {
            updateCalculatorTotal();
            calculateChange();
        }
    }


    // =============== ОБНОВЛЕНИЕ ФУНКЦИИ updateBonusCalculation ===============

    function updateBonusCalculation() {
        // Проверяем, есть ли данные клиента
        const clientBonusPoints = parseInt(clientBonusPointsElem.textContent.replace(/\D/g, '')) || 0;
        if (clientBonusPoints === 0) return;
        
        // Получаем текущие суммы
        const subtotalElement = document.getElementById('closeSubtotal');
        if (!subtotalElement) return;
        
        const subtotalText = subtotalElement.textContent;
        const subtotal = parseFloat(subtotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
        
        // Рассчитываем скидку для расчета бонусов
        let discountForBonusCalc = 0;
        if (currentDiscountType === 'percent') {
            const discountPercent = parseFloat(closeDiscountInput.value) || 0;
            discountForBonusCalc = (subtotal * discountPercent) / 100;
        } else {
            discountForBonusCalc = parseFloat(closeDiscountInput.value) || 0;
        }
        
        // Используем правильный процент из карты
        const maxPercentElement = document.getElementById('maxSpendPercentText');
        let actualPercent = clientMaxSpendPercent; // По умолчанию 50
        
        if (maxPercentElement) {
            // Извлекаем число из текста "30% (Платиновая)"
            const text = maxPercentElement.textContent;
            const percentMatch = text.match(/(\d+)%/);
            if (percentMatch) {
                actualPercent = parseInt(percentMatch[1]);
            }
        }
        
        console.log('📊 Используем процент:', actualPercent, '% вместо', clientMaxSpendPercent, '%');
        
        // Пересчитываем максимальные бонусы с правильным процентом
        const maxByPercent = Math.floor((subtotal - discountForBonusCalc) * (actualPercent / 100));
        
        // Берем МЕНЬШЕЕ из двух значений
        maxUsableBonuses = Math.min(maxByPercent, clientBonusPoints);
        maxUsableBonuses = Math.max(0, maxUsableBonuses);
        
        console.log('📊 Пересчет бонусов:', {
            subtotal,
            discountForBonusCalc,
            actualPercent,
            maxByPercent,
            clientBonusPoints,
            finalMax: maxUsableBonuses
        });
        
        if (maxUsableBonusesElem) {
            maxUsableBonusesElem.textContent = maxUsableBonuses.toLocaleString() + ' бонусов';
        }
        
        // Обновляем максимальное значение в input
        if (bonusPointsToUseInput && !bonusPointsToUseInput.disabled) {
            bonusPointsToUseInput.max = maxUsableBonuses;
            
            // Если текущее значение больше нового максимума, уменьшаем его
            if (parseInt(bonusPointsToUseInput.value) > maxUsableBonuses) {
                bonusPointsToUseInput.value = maxUsableBonuses;
                currentBonusDiscount = maxUsableBonuses;
                calculateCloseTotal();
            }
        }
    }

    
    // =============== ИНИЦИАЛИЗАЦИЯ ===============
    
    // Всплывающие подсказки
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            placement: 'top',
            trigger: 'hover'
        });
    });
    initDiscountLogic();

    
    // =============== ОБРАБОТЧИК ОТПРАВКИ ФОРМЫ ===============

    const closeSaleForm = document.getElementById('closeSaleForm');
    if (closeSaleForm) {
        closeSaleForm.addEventListener('submit', function(e) {
            // 1. Получаем скидку в рублях
            const discountInRubles = getDiscountInRubles();
            
            // 2. Устанавливаем значения в скрытые поля
            const discountInRublesHidden = document.getElementById('discountInRublesHidden');
            const discountTypeHidden = document.getElementById('discountTypeHidden');
            
            if (discountInRublesHidden) {
                discountInRublesHidden.value = discountInRubles.toFixed(2);
                console.log('✅ discount_in_rubles отправляется:', discountInRublesHidden.value);
            }
            
            if (discountTypeHidden) {
                discountTypeHidden.value = currentDiscountType;
                console.log('✅ discount_type отправляется:', discountTypeHidden.value);
            }
            
            // 3. Обрабатываем поле скидки
            const closeDiscountInput = document.getElementById('closeDiscount');
            if (closeDiscountInput) {
                // Сохраняем оригинальное значение
                const originalValue = closeDiscountInput.value;
                
                // Если это проценты, конвертируем в рубли
                if (currentDiscountType === 'percent') {
                    closeDiscountInput.value = discountInRubles.toFixed(2);
                    console.log('✅ Поле discount изменено с', originalValue, 'на', closeDiscountInput.value);
                }
            }
            
            // 4. Обрабатываем чекбокс бонусов
            const useBonusesCheckbox = document.getElementById('useBonuses');
            const bonusPointsInput = document.getElementById('bonusPointsToUse');
            
            // Если чекбокс отмечен, убеждаемся что value = '1'
            if (useBonusesCheckbox.checked) {
                useBonusesCheckbox.value = '1';
            }
            
            // Если чекбокс НЕ отмечен, добавляем скрытое поле с value = '0'
            if (!useBonusesCheckbox.checked) {
                // Удаляем старое скрытое поле если есть
                const existingHidden = document.getElementById('useBonusesHidden');
                if (existingHidden) {
                    existingHidden.remove();
                }
                
                // Создаем новое скрытое поле с value = '0'
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'useBonusesHidden';
                hiddenInput.name = 'use_bonuses';
                hiddenInput.value = '0';
                this.appendChild(hiddenInput);
                
                // Также обнуляем бонусы если чекбокс не отмечен
                if (bonusPointsInput) {
                    bonusPointsInput.value = 0;
                }
            }
            
            // 5. Убеждаемся что поле bonus_points_to_use всегда отправляется
            if (bonusPointsInput && !bonusPointsInput.hasAttribute('name')) {
                bonusPointsInput.setAttribute('name', 'bonus_points_to_use');
            }
            
            // 6. Логируем данные для отладки
            console.log('📤 Отправляемые данные:', {
                discount: document.getElementById('closeDiscount').value,
                discount_in_rubles: discountInRublesHidden?.value,
                discount_type: discountTypeHidden?.value,
                use_bonuses: useBonusesCheckbox.checked ? '1' : '0',
                bonus_points_to_use: bonusPointsInput?.value,
                payment_method_id: document.getElementById('closePaymentMethod').value,
                payment_method: document.getElementById('paymentMethodAlias').value
            });
        });
    }

    // =============== ФУНКЦИЯ РАСЧЕТА НАЧИСЛЯЕМЫХ БОНУСОВ ===============

    function calculateBonusAward() {
        const bonusAwardInfo = document.getElementById('bonusAwardInfo');
        const bonusAwardAmount = document.getElementById('bonusAwardAmount');
        const bonusAwardPercent = document.getElementById('bonusAwardPercent');
        const bonusAwardDetails = document.getElementById('bonusAwardDetails');
        
        if (!bonusAwardInfo || !clientBonusInfo) return;
        
        // Если нет информации о клиенте, скрываем блок
        if (clientBonusInfo.style.display === 'none') {
            bonusAwardInfo.style.display = 'none';
            return;
        }
        
        // Получаем данные клиента
        const hasBonusCard = window.currentClientHasBonusCard || false;
        const bonusPercent = window.currentClientBonusPercent || 0;
        
        // Получаем финальную сумму для расчета ИЗ ЭЛЕМЕНТА НА СТРАНИЦЕ
        const finalTotalElement = document.getElementById('closeFinalTotal');
        if (!finalTotalElement) {
            console.error('❌ Элемент closeFinalTotal не найден');
            bonusAwardInfo.style.display = 'none';
            return;
        }
        
        const finalTotalText = finalTotalElement.textContent || '0.00 ₽';
        const finalTotal = parseFloat(finalTotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
        
        if (hasBonusCard && bonusPercent > 0 && finalTotal > 0) {
            // Если у клиента ЕСТЬ карта, рассчитываем бонусы
            const awardAmount = Math.floor(finalTotal * (bonusPercent / 100));
            
            // Показываем блок
            bonusAwardInfo.style.display = 'block';
            bonusAwardAmount.textContent = awardAmount;
            bonusAwardPercent.textContent = `${bonusPercent}% от суммы`;
            
            // Полностью перезаписываем содержимое
            bonusAwardDetails.innerHTML = `
                <div class="row small mt-2">
                    <div class="col-6">Процент бонусов:</div>
                    <div class="col-6 text-end">${bonusPercent}%</div>
                </div>
                <div class="row small">
                    <div class="col-6">Сумма чека:</div>
                    <div class="col-6 text-end">${formatPrice(finalTotal)}</div>
                </div>
                <div class="row small fw-bold mt-1">
                    <div class="col-6">Будет начислено:</div>
                    <div class="col-6 text-end text-success">${awardAmount} бонусов</div>
                </div>
                <div class="small text-success mt-1">
                    <i class="bi bi-check-circle"></i> Бонусы будут начислены автоматически
                </div>
            `;
            
            console.log('✅ Начисляемые бонусы:', awardAmount, 'от суммы', finalTotal);
        } else {
            // У клиента нет карты или некорректные данные - скрываем блок
            bonusAwardInfo.style.display = 'none';
            // Очищаем содержимое чтобы не накапливалось
            if (bonusAwardDetails) {
                bonusAwardDetails.innerHTML = '';
            }
        }
    }

    // =============== ФУНКЦИЯ ПРЕДВАРИТЕЛЬНОЙ ПРОВЕРКИ ФОРМЫ ===============

    function validateCloseForm() {
        const paymentMethod = document.getElementById('closePaymentMethod');
        if (!paymentMethod || !paymentMethod.value) {
            showToast('warning', 'Внимание', 'Выберите способ оплаты');
            return false;
        }
        
        // Проверка бонусов
        const useBonusesCheckbox = document.getElementById('useBonuses');
        const bonusPointsInput = document.getElementById('bonusPointsToUse');
        
        if (useBonusesCheckbox && useBonusesCheckbox.checked) {
            const bonusValue = parseInt(bonusPointsInput.value) || 0;
            if (bonusValue <= 0) {
                showToast('warning', 'Внимание', 'Введите количество бонусов для использования');
                return false;
            }
        }
        
        return true;
    }
    
    console.log('Table Manager initialized');

    if (typeof window.CoalTimerSystem !== 'undefined') {
        console.log('CoalTimerSystem found, initializing...');
        const system = window.CoalTimerSystem.init();
        
        // Проверяем все столы с кальянами при загрузке
        if (system && system.restoreTimersForAllTablesWithHookah) {
            system.restoreTimersForAllTablesWithHookah();
        }
    } else {
        console.error('CoalTimerSystem NOT FOUND! Check file path.');
    }

    // =============== ПРОСТОЙ КАЛЬКУЛЯТОР СДАЧИ ===============

    window.updateCalculatorTotal = function() {
        console.log('🔄 updateCalculatorTotal вызвана');
        const calcTotalElement = document.getElementById('calcTotalAmount');
        if (!calcTotalElement) return;
        
        try {
            const finalTotalElement = document.getElementById('closeFinalTotal');
            if (!finalTotalElement) {
                console.error('❌ Элемент closeFinalTotal не найден');
                return;
            }
            
            const totalText = finalTotalElement.textContent || '0.00 ₽';
            const number = parseFloat(totalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
            calcTotalElement.textContent = number.toFixed(2) + ' ₽';
            
            // Если способ оплаты "наличные", обновляем расчет
            const paymentSelect = document.getElementById('closePaymentMethod');
            if (paymentSelect && paymentSelect.value === 'cash') {
                window.calculateChange();
            }
        } catch (err) {
            console.log('❌ Ошибка в updateCalculatorTotal:', err);
        }
    };

    window.calculateChange = function() {
        console.log('🔄 calculateChange вызвана');
        const cashReceivedInput = document.getElementById('cashReceived');
        const calcResult = document.getElementById('calcResult');
        const insufficientCash = document.getElementById('insufficientCash');
        
        if (!cashReceivedInput || !calcResult || !insufficientCash) return;
        
        try {
            const totalText = document.getElementById('closeFinalTotal')?.textContent || '0.00 ₽';
            const total = parseFloat(totalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
            const received = parseFloat(cashReceivedInput.value) || 0;
            
            // Очищаем предыдущие результаты
            calcResult.style.display = 'none';
            insufficientCash.style.display = 'none';
            
            if (received === 0) return;
            
            if (received >= total) {
                // Хватает денег
                const change = received - total;
                document.getElementById('changeAmount').textContent = change.toFixed(2) + ' ₽';
                calcResult.style.display = 'block';
            } else {
                // Не хватает денег
                const missing = total - received;
                document.getElementById('missingAmount').textContent = missing.toFixed(2) + ' ₽';
                insufficientCash.style.display = 'block';
            }
        } catch (err) {
            console.log('❌ Ошибка в calculateChange:', err);
        }
    };


    // Инициализация калькулятора при загрузке
    function initCashCalculator() {
        console.log('🔄 Инициализация калькулятора сдачи');
        
        const paymentSelect = document.getElementById('closePaymentMethod');
        const cashCalculator = document.getElementById('cashCalculator');
        const cashReceivedInput = document.getElementById('cashReceived');
        const calcTotalElement = document.getElementById('calcTotalAmount');
        const calcResult = document.getElementById('calcResult');
        const changeAmount = document.getElementById('changeAmount');
        const insufficientCash = document.getElementById('insufficientCash');
        const missingAmount = document.getElementById('missingAmount');
        
        if (!paymentSelect || !cashCalculator) {
            console.log('⚠️ Калькулятор сдачи не найден на странице');
            return;
        }
        
        // Функция для получения текущей итоговой суммы
        function getCurrentTotal() {
            try {
                const finalTotalElement = document.getElementById('closeFinalTotal');
                if (!finalTotalElement) {
                    console.error('❌ Элемент closeFinalTotal не найден');
                    return 0;
                }
                
                const totalText = finalTotalElement.textContent || '0.00 ₽';
                const number = parseFloat(totalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
                return number;
            } catch (err) {
                console.log('❌ Ошибка получения суммы:', err);
                return 0;
            }
        }
        
        // Функция обновления суммы в калькуляторе
        function updateCalculatorTotal() {
            if (!calcTotalElement) return;
            
            const total = getCurrentTotal();
            calcTotalElement.textContent = total.toFixed(2) + ' ₽';
            
            // Если способ оплаты "наличные", обновляем расчет
            if (paymentSelect.value === 'cash') {
                calculateChange();
            }
        }
        
        // Функция расчета сдачи
        function calculateChange() {
            if (!cashReceivedInput || !calcResult || !insufficientCash) return;
            
            const total = getCurrentTotal();
            const received = parseFloat(cashReceivedInput.value) || 0;
            
            // Очищаем предыдущие результаты
            calcResult.style.display = 'none';
            insufficientCash.style.display = 'none';
            
            if (received === 0) return;
            
            if (received >= total) {
                // Хватает денег
                const change = received - total;
                changeAmount.textContent = change.toFixed(2) + ' ₽';
                calcResult.style.display = 'block';
            } else {
                // Не хватает денег
                const missing = total - received;
                missingAmount.textContent = missing.toFixed(2) + ' ₽';
                insufficientCash.style.display = 'block';
            }
        }
        
        // 1. Обработчик изменения способа оплаты
       paymentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const paymentMethodName = selectedOption ? selectedOption.text : '';
            const isCash = isCashPayment(this.value, paymentMethodName);
            
            console.log('💰 Изменение способа оплаты в калькуляторе:', {
                value: this.value,
                name: paymentMethodName,
                isCash: isCash
            });
            
            if (cashCalculator) {
                cashCalculator.style.display = isCash ? 'block' : 'none';
                console.log(`💰 Калькулятор: ${isCash ? 'показан' : 'скрыт'}`);
            }
            
            if (calcResult) calcResult.style.display = 'none';
            if (insufficientCash) insufficientCash.style.display = 'none';
            
            if (isCash) {
                // Автоматически заполняем поле суммой к оплате
                const total = getCurrentTotal();
                if (cashReceivedInput) {
                    cashReceivedInput.value = Math.ceil(total);
                    console.log(`💰 Автозаполнение: ${cashReceivedInput.value} ₽`);
                }
                
                // Обновляем отображение и рассчитываем сдачу
                setTimeout(() => {
                    updateCalculatorTotal();
                    calculateChange();
                }, 100);
            }
        });
        
        // 2. Обработчик ввода суммы в поле "Получено"
       if (cashReceivedInput) {
            cashReceivedInput.addEventListener('input', window.calculateChange);
            cashReceivedInput.addEventListener('change', window.calculateChange);
            
            // Кнопки быстрого ввода
            cashReceivedInput.addEventListener('focus', function() {
                // При фокусе выделяем всё содержимое для удобного редактирования
                this.select();
            });
        }
        
        // 3. Вызываем обновление при любом изменении итоговой суммы
        // Используем MutationObserver для отслеживания изменений
        const totalObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (paymentSelect.value === 'cash') {
                    updateCalculatorTotal();
                    calculateChange();
                }
            });
        });
        
        const finalTotalElement = document.getElementById('closeFinalTotal');
        if (finalTotalElement) {
            totalObserver.observe(finalTotalElement, {
                characterData: true,
                childList: true,
                subtree: true
            });
        }
        
        // 4. Инициализация при открытии модалки
        const closeSaleModal = document.getElementById('closeSaleModal');
        if (closeSaleModal) {
            closeSaleModal.addEventListener('shown.bs.modal', function() {
                // Через небольшой таймаут, чтобы данные успели загрузиться
                setTimeout(function() {
                    if (paymentSelect.value === 'cash') {
                        updateCalculatorTotal();
                        calculateChange();
                    }
                }, 300);
            });
        }
        
        console.log('✅ Калькулятор сдачи инициализирован');
    }

    // =============== СПОСОБ ОПЛАТЫ ИЗ БАЗЫ ДАННЫХ ===============

    function isCashPayment(paymentMethodId, paymentMethodName = '') {
        if (!paymentMethodId && !paymentMethodName) return false;
        
        // Получаем полное название способа оплаты
        let fullPaymentMethodName = paymentMethodName;
        
        // Если передано только ID, получаем название из select
        if (paymentMethodId && !paymentMethodName) {
            const paymentSelect = document.getElementById('closePaymentMethod');
            if (paymentSelect && paymentSelect.value) {
                const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
                if (selectedOption) {
                    fullPaymentMethodName = selectedOption.text || '';
                    console.log('💰 Получено название оплаты из select:', fullPaymentMethodName);
                }
            }
        }
        
        console.log('💰 Проверка наличных:', {
            id: paymentMethodId,
            name: fullPaymentMethodName
        });
        
        // Проверяем по названию (все варианты)
        if (fullPaymentMethodName) {
            const cashKeywords = ['нал', 'cash', 'налич', 'наличк', 'руб', '₽', 'деньг', 'денеж', 'купюр'];
            const lowerName = fullPaymentMethodName.toLowerCase();
            
            // Проверяем все ключевые слова
            for (const keyword of cashKeywords) {
                if (lowerName.includes(keyword)) {
                    console.log(`💰 Наличные определены по ключевому слову: "${keyword}"`);
                    return true;
                }
            }
            
            // Проверяем полные совпадения (с учетом возможных пробелов)
            if (['наличные', 'наличка', 'cash', 'нал'].includes(lowerName.trim())) {
                console.log(`💰 Наличные определены по полному совпадению: "${lowerName}"`);
                return true;
            }
        }
        
        // Если есть конкретные ID для наличных (добавьте нужные ID)
        const cashPaymentIds = [1]; // Замените на реальные ID наличных из вашей БД
        
        if (paymentMethodId && cashPaymentIds.includes(parseInt(paymentMethodId))) {
            console.log(`💰 Наличные определены по ID: ${paymentMethodId}`);
            return true;
        }
        
        console.log('💰 Не является наличными:', {
            id: paymentMethodId,
            name: fullPaymentMethodName
        });
        return false;
    }

    function updatePaymentMethodInModal(paymentMethodId, paymentMethodName) {
        const paymentSelect = document.getElementById('closePaymentMethod');
        
        if (!paymentSelect) return;
        
        console.log('🔄 updatePaymentMethodInModal вызвана:', {
            id: paymentMethodId,
            name: paymentMethodName
        });
        
        // Если передан ID способа оплаты
        if (paymentMethodId) {
            // Ищем опцию с нужным value
            let optionFound = false;
            for (let i = 0; i < paymentSelect.options.length; i++) {
                if (paymentSelect.options[i].value == paymentMethodId) {
                    paymentSelect.selectedIndex = i;
                    optionFound = true;
                    console.log(`✅ Найден и выбран способ оплаты ID: ${paymentMethodId}`);
                    break;
                }
            }
            
            if (!optionFound) {
                console.log(`❌ Способ оплаты с ID ${paymentMethodId} не найден в select`);
            }
        } else {
            paymentSelect.selectedIndex = 0;
        }
        
        // Обновляем скрытое поле для обратной совместимости
        const paymentMethodAlias = document.getElementById('paymentMethodAlias');
        if (paymentMethodAlias) {
            // Используем переданное название или берем из select
            const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
            paymentMethodAlias.value = paymentMethodName || selectedOption.text || '';
        }
        
        // ОБЯЗАТЕЛЬНО: Вызываем событие change чтобы обработать показ калькулятора
        setTimeout(() => {
            const event = new Event('change');
            paymentSelect.dispatchEvent(event);
            
            // Дополнительно вызываем initCashCalculator
            if (typeof initCashCalculator === 'function') {
                initCashCalculator();
            }
        }, 50);
    }

    // Обновляем функцию updateCloseModalData
    function updateCloseModalData(data) {
        console.log('📊 updateCloseModalData вызвана с данными:', data);
        
        // Обновляем информацию о клиенте и бонусах
        updateClientBonusInfo(data);
        
        // Обновляем суммы
        document.getElementById('closeItemsTotal').textContent = formatPrice(data.productsTotal);
        document.getElementById('closeHookahsTotal').textContent = formatPrice(data.hookahsTotal);
        document.getElementById('closeSubtotal').textContent = formatPrice(data.subtotal);
        document.getElementById('closeFinalTotal').textContent = formatPrice(data.finalTotal);
        
        // ОБНОВЛЯЕМ СПОСОБ ОПЛАТЫ (передаем и ID и название)
        updatePaymentMethodInModal(data.paymentMethodId, data.paymentMethodName);
        
        // Устанавливаем скидку и отображаем ее
        const discountInput = document.getElementById('closeDiscount');
        const discountDisplay = document.getElementById('closeDiscountDisplay');
        
        if (discountInput) {
            discountInput.value = data.discount || 0;
        }

        if (discountDisplay) {
            discountDisplay.textContent = formatPrice(data.discount || 0);
        }
        setTimeout(() => {
            calculateCloseTotal();
        }, 100);

        // Заполняем списки товаров и кальянов
        fillProductsList(data.products || []);
        fillHookahsList(data.hookahs || []);
        
        // ОБЯЗАТЕЛЬНО: Инициализируем калькулятор после загрузки всех данных
        setTimeout(() => {
            if (typeof initCashCalculator === 'function') {
                initCashCalculator();
            }
        }, 200);
    }

    // Инициализация обработчика для способа оплаты
    function initPaymentMethodHandler() {
        const paymentSelect = document.getElementById('closePaymentMethod');
        if (!paymentSelect) return;
        
        console.log('💰 Инициализация обработчика способов оплаты');
        
        paymentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const paymentMethodId = this.value;
            const paymentMethodName = selectedOption ? selectedOption.text : '';
            
            console.log('💰 Способ оплаты изменен:', {
                id: paymentMethodId,
                name: paymentMethodName
            });
            
            const isCash = isCashPayment(paymentMethodId, paymentMethodName);
            
            // Обновляем скрытое поле для обратной совместимости
            const paymentMethodAlias = document.getElementById('paymentMethodAlias');
            if (paymentMethodAlias) {
                paymentMethodAlias.value = paymentMethodName || '';
            }
            
            // Показываем/скрываем калькулятор сдачи
            const cashCalculator = document.getElementById('cashCalculator');
            if (cashCalculator) {
                cashCalculator.style.display = isCash ? 'block' : 'none';
                console.log(`💰 Калькулятор сдачи: ${isCash ? 'ПОКАЗАН' : 'СКРЫТ'}`);
            }
            
            // Если это наличные, обновляем калькулятор
            if (isCash) {
                setTimeout(() => {
                    if (typeof updateCalculatorTotal === 'function') {
                        updateCalculatorTotal();
                        calculateChange();
                    }
                }, 100);
            }
        });
    }

    // =============== ВЫДЕЛЕНИЕ ЯЧЕЕК ДЛЯ СОЗДАНИЯ СТОЛА ===============

let selectionState = {
    isSelecting: false,
    startCell: null,
    startTableId: null,
    startRow: null,
    startCol: null,
    selectedCells: [],
    tempHighlighted: []
};

// Инициализация выделения ячеек
function initCellSelection() {
    console.log('Инициализация выделения ячеек...');
    
    // Удаляем старые обработчики если есть
    const table = document.querySelector('table.table-bordered tbody');
    if (table) {
        table.removeEventListener('mousedown', handleTableMouseDown);
        table.removeEventListener('mouseenter', handleTableMouseEnter);
        table.removeEventListener('mouseup', handleTableMouseUp);
    }
    
    // Очищаем старые классы
    document.querySelectorAll('.table-cell-selectable, .table-cell-selected, .table-cell-selecting')
        .forEach(cell => {
            cell.classList.remove('table-cell-selectable', 'table-cell-selected', 'table-cell-selecting');
        });
    
    // Сбрасываем состояние
    selectionState = {
        isSelecting: false,
        startCell: null,
        startTableId: null,
        startRow: null,
        startCol: null,
        selectedCells: [],
        tempHighlighted: []
    };
    
    // Назначаем уникальные идентификаторы ячейкам
    assignCellIds();
    
    // Добавляем обработчики событий
    if (table) {
        table.addEventListener('mousedown', handleTableMouseDown);
        table.addEventListener('mouseenter', handleTableMouseEnter);
        table.addEventListener('mouseup', handleTableMouseUp);
    }
    
    console.log('Выделение ячеек инициализировано');
}

// Назначаем уникальные идентификаторы ячейкам
function assignCellIds() {
    const rows = document.querySelectorAll('table.table-bordered tbody tr');
    const headerCells = document.querySelectorAll('table.table-bordered thead th');
    
    if (rows.length === 0 || headerCells.length === 0) return;
    
    // Определяем количество столбцов (исключая столбцы времени)
    const columnCount = headerCells.length - 2;
    
    console.log('Количество столбцов для назначения ID:', columnCount);
    
    // Сначала очищаем все data-атрибуты
    rows.forEach(row => {
        const cells = Array.from(row.cells);
        for (let i = 1; i < cells.length - 1; i++) {
            const cell = cells[i];
            delete cell.dataset.cellId;
            delete cell.dataset.rowIndex;
            delete cell.dataset.colIndex;
            delete cell.dataset.tableName;
            delete cell.dataset.coveredBy;
        }
    });
    
    rows.forEach((row, rowIndex) => {
        const cells = Array.from(row.cells);
        let currentCol = 0;
        
        // Пропускаем первый столбец (время) и последний (время)
        for (let i = 1; i < cells.length - 1; i++) {
            const cell = cells[i];
            
            // Пропускаем ячейки, которые уже являются частью rowspan
            while (currentCol < columnCount && 
                   document.querySelector(`td[data-cell-id="${rowIndex}-${currentCol}"]`)) {
                currentCol++;
            }
            
            if (currentCol >= columnCount) break;
            
            const rowspan = parseInt(cell.getAttribute('rowspan')) || 1;
            const colspan = parseInt(cell.getAttribute('colspan')) || 1;
            
            // Основная ячейка
            cell.dataset.cellId = `${rowIndex}-${currentCol}`;
            cell.dataset.rowIndex = rowIndex;
            cell.dataset.colIndex = currentCol;
            cell.dataset.tableName = getTableNameForColumn(currentCol);
            
            // Если ячейка пустая и не занята rowspan/colspan, делаем ее selectable
            const isEmpty = cell.textContent.trim() === '' && 
                           !cell.querySelector('.btn') && 
                           !cell.querySelector('.badge') &&
                           !cell.classList.contains('bg-') &&
                           rowspan === 1 && colspan === 1;
            
            if (isEmpty) {
                cell.classList.add('table-cell-selectable');
                console.log(`Сделана selectable: строка ${rowIndex}, колонка ${currentCol}`);
            } else {
                cell.classList.remove('table-cell-selectable');
            }
            
            // Заполняем ячейки, покрытые rowspan/colspan
            for (let r = 0; r < rowspan; r++) {
                for (let c = 0; c < colspan; c++) {
                    if (r === 0 && c === 0) continue; // Пропускаем основную ячейку
                    
                    const targetRow = rowIndex + r;
                    const targetCol = currentCol + c;
                    
                    if (targetRow < rows.length && targetCol < columnCount) {
                        // Ищем существующую ячейку в этой позиции
                        const targetRowCells = rows[targetRow].cells;
                        let targetCell = null;
                        
                        for (let j = 1; j < targetRowCells.length - 1; j++) {
                            const testCell = targetRowCells[j];
                            if (!testCell.dataset.cellId) {
                                targetCell = testCell;
                                break;
                            }
                        }
                        
                        if (targetCell) {
                            targetCell.dataset.cellId = `${targetRow}-${targetCol}`;
                            targetCell.dataset.rowIndex = targetRow;
                            targetCell.dataset.colIndex = targetCol;
                            targetCell.dataset.tableName = getTableNameForColumn(targetCol);
                            targetCell.dataset.coveredBy = `${rowIndex}-${currentCol}`;
                            targetCell.classList.remove('table-cell-selectable');
                        }
                    }
                }
            }
            
            currentCol += colspan;
        }
    });
    
    console.log('Назначение ID ячейкам завершено');
}

// Получаем название стола для колонки
function getTableNameForColumn(colIndex) {
    const headerCells = document.querySelectorAll('table.table-bordered thead th');
    if (colIndex < 0 || colIndex >= headerCells.length - 2) return '';
    
    const headerCell = headerCells[colIndex + 1]; // +1 чтобы пропустить первый столбец времени
    return headerCell.textContent.trim();
}

// Получаем ID стола для колонки (из data-атрибута)
function getTableIdForColumn(colIndex) {
    const headerCells = document.querySelectorAll('table.table-bordered thead th');
    if (colIndex < 0 || colIndex >= headerCells.length - 2) return null;
    
    const headerCell = headerCells[colIndex + 1];
    return headerCell.dataset.tableId || null;
}

// Обработчики событий
function handleTableMouseDown(e) {
    const cell = e.target.closest('td.table-cell-selectable');
    if (!cell) return;
    
    e.preventDefault();
    
    if (cell.classList.contains('table-cell-selectable')) {
        selectionState.isSelecting = true;
        selectionState.startCell = cell;
        selectionState.startRow = parseInt(cell.dataset.rowIndex);
        selectionState.startCol = parseInt(cell.dataset.colIndex);
        selectionState.startTableId = getTableIdForColumn(selectionState.startCol);
        selectionState.selectedCells = [cell];
        
        clearSelection();
        clearTempHighlight();
        
        cell.classList.add('table-cell-selected');
        
        console.log('Начало выделения:', {
            row: selectionState.startRow,
            col: selectionState.startCol,
            tableId: selectionState.startTableId,
            tableName: cell.dataset.tableName
        });
    }
}

function handleTableMouseEnter(e) {
    if (!selectionState.isSelecting) return;
    
    const cell = e.target.closest('td.table-cell-selectable');
    if (!cell) return;
    
    const currentRow = parseInt(cell.dataset.rowIndex);
    const currentCol = parseInt(cell.dataset.colIndex);
    const currentTableId = getTableIdForColumn(currentCol);
    
    // Проверяем, что выделение в пределах одного стола
    if (currentTableId !== selectionState.startTableId) {
        console.log('Попытка выделения другого стола:', currentTableId, 'ожидался:', selectionState.startTableId);
        return;
    }
    
    // Проверяем, что выделение в пределах одного столбца
    if (currentCol !== selectionState.startCol) {
        console.log('Попытка выделения другого столбца:', currentCol, 'ожидался:', selectionState.startCol);
        return;
    }
    
    highlightRange(selectionState.startRow, currentRow, currentCol);
}

function handleTableMouseUp(e) {
    if (!selectionState.isSelecting) return;
    
    const cell = e.target.closest('td.table-cell-selectable');
    if (cell && cell.classList.contains('table-cell-selectable')) {
        const currentRow = parseInt(cell.dataset.rowIndex);
        const currentCol = parseInt(cell.dataset.colIndex);
        
        // Сохраняем выделенные ячейки
        selectionState.tempHighlighted.forEach(cell => {
            cell.classList.remove('table-cell-selecting');
            cell.classList.add('table-cell-selected');
        });
        
        selectionState.selectedCells = getCellsInRange(
            selectionState.startRow, 
            currentRow, 
            selectionState.startCol
        );
        
        console.log('Выделено ячеек:', selectionState.selectedCells.length);
        
        if (selectionState.selectedCells.length > 0) {
            showCreateModalWithSelection();
        }
    }
    
    clearTempHighlight();
    selectionState.isSelecting = false;
}

// Подсветка диапазона ячеек
function highlightRange(startRow, endRow, col) {
    clearTempHighlight();
    
    const minRow = Math.min(startRow, endRow);
    const maxRow = Math.max(startRow, endRow);
    const rows = document.querySelectorAll('table.table-bordered tbody tr');
    
    console.log(`Подсветка диапазона: строки ${minRow}-${maxRow}, столбец ${col}`);
    
    for (let row = minRow; row <= maxRow; row++) {
        if (row >= 0 && row < rows.length) {
            // Находим ячейку в этой строке и столбце
            const cell = document.querySelector(`td[data-cell-id="${row}-${col}"]`);
            if (cell && cell.classList.contains('table-cell-selectable')) {
                // Проверяем, что ячейка не заблокирована rowspan сверху
                let isBlocked = false;
                for (let r = row - 1; r >= 0; r--) {
                    const cellAbove = document.querySelector(`td[data-cell-id="${r}-${col}"]`);
                    if (cellAbove) {
                        const rowspan = parseInt(cellAbove.getAttribute('rowspan')) || 1;
                        if (rowspan > 1 && (r + rowspan - 1) >= row) {
                            isBlocked = true;
                            console.log(`Ячейка ${row}-${col} заблокирована rowspan из ${r}-${col}`);
                            break;
                        }
                    }
                }
                
                if (!isBlocked) {
                    cell.classList.add('table-cell-selecting');
                    selectionState.tempHighlighted.push(cell);
                    console.log(`Подсвечена ячейка: ${row}-${col}`);
                }
            } else if (cell) {
                console.log(`Ячейка ${row}-${col} не selectable или не найдена`);
            }
        }
    }
}

// Получаем все ячейки в диапазоне
function getCellsInRange(startRow, endRow, col) {
    const cells = [];
    const minRow = Math.min(startRow, endRow);
    const maxRow = Math.max(startRow, endRow);
    
    for (let row = minRow; row <= maxRow; row++) {
        const cell = document.querySelector(`td[data-cell-id="${row}-${col}"]`);
        if (cell && cell.classList.contains('table-cell-selectable')) {
            cells.push(cell);
        }
    }
    
    return cells;
}

// Очистка временной подсветки
function clearTempHighlight() {
    selectionState.tempHighlighted.forEach(cell => {
        if (cell && cell.classList) {
            cell.classList.remove('table-cell-selecting');
        }
    });
    selectionState.tempHighlighted = [];
}

// Очистка выделения
function clearSelection() {
    document.querySelectorAll('.table-cell-selected, .table-cell-selecting').forEach(cell => {
        cell.classList.remove('table-cell-selected', 'table-cell-selecting');
    });
    selectionState.selectedCells = [];
}

// Показ модального окна с предзаполненными данными
function showCreateModalWithSelection() {
    if (selectionState.selectedCells.length === 0) {
        console.log('Нет выделенных ячеек для показа модалки');
        return;
    }
    
    // Сортируем ячейки по строкам
    const sortedCells = [...selectionState.selectedCells].sort((a, b) => {
        return parseInt(a.dataset.rowIndex) - parseInt(b.dataset.rowIndex);
    });
    
    const firstCell = sortedCells[0];
    const lastCell = sortedCells[sortedCells.length - 1];
    
    // Получаем время начала и конца
    const startRow = parseInt(firstCell.dataset.rowIndex);
    const endRow = parseInt(lastCell.dataset.rowIndex);
    
    const startTime = getTimeForRow(startRow);
    const endTime = calculateEndTime(getTimeForRow(endRow));
    
    // Рассчитываем длительность
    const startMinutes = timeToMinutes(startTime);
    const endMinutes = timeToMinutes(endTime);
    let durationMinutes;
    
    if (endMinutes < startMinutes) {
        durationMinutes = (24 * 60 - startMinutes) + endMinutes;
    } else {
        durationMinutes = endMinutes - startMinutes;
    }
    
    // Находим ближайшую стандартную длительность
    const durationOptions = [60, 90, 120, 150, 180, 210, 240, 270, 300, 330, 360];
    let closestDuration = 120;
    
    for (const option of durationOptions) {
        if (Math.abs(option - durationMinutes) < Math.abs(closestDuration - durationMinutes)) {
            closestDuration = option;
        }
    }
    
    const formattedStartTime = formatTimeForInput(startTime);
    
    console.log('Данные для модалки:', {
        startTime,
        endTime,
        durationMinutes,
        closestDuration,
        formattedStartTime,
        tableName: firstCell.dataset.tableName,
        tableId: selectionState.startTableId
    });
    
    // Предзаполняем форму
    const tableNameSelect = document.getElementById('table_name_id');
    if (tableNameSelect && selectionState.startTableId) {
        // Ищем опцию с нужным value
        let found = false;
        for (let i = 0; i < tableNameSelect.options.length; i++) {
            if (parseInt(tableNameSelect.options[i].value) === parseInt(selectionState.startTableId)) {
                tableNameSelect.selectedIndex = i;
                found = true;
                console.log('Установлен стол ID:', selectionState.startTableId, 'текст:', tableNameSelect.options[i].text);
                break;
            }
        }
        
        if (!found) {
            console.log('Не найден стол с ID:', selectionState.startTableId);
        }
    } else if (!tableNameSelect) {
        console.log('Селект table_name_id не найден');
    }
    
    // Устанавливаем время
    const bookingTimeSelect = document.getElementById('booking_time');
    if (bookingTimeSelect) {
        let timeFound = false;
        for (let i = 0; i < bookingTimeSelect.options.length; i++) {
            if (bookingTimeSelect.options[i].value === formattedStartTime) {
                bookingTimeSelect.selectedIndex = i;
                timeFound = true;
                console.log('Время установлено:', formattedStartTime);
                break;
            }
        }
        
        if (!timeFound) {
            console.log('Не найдено время:', formattedStartTime);
        }
    }
    
    // Устанавливаем длительность
    const durationSelect = document.getElementById('duration');
    if (durationSelect) {
        durationSelect.value = closestDuration;
        console.log('Длительность установлена:', closestDuration);
    }
    
    // Показываем модальное окно
    const modalElement = document.getElementById('createTableModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Показываем информацию о выборе
        showSelectionInfo(
            firstCell.dataset.tableName || 'Стол',
            startTime,
            endTime,
            durationMinutes
        );
    } else {
        console.log('Модальное окно createTableModal не найдено');
    }
}

// Вспомогательные функции
function getTimeForRow(rowIndex) {
    const row = document.querySelector(`table.table-bordered tbody tr:nth-child(${rowIndex + 1})`);
    if (!row) return '';
    
    const timeCell = row.cells[0];
    return timeCell ? timeCell.textContent.trim() : '';
}

function calculateEndTime(cellTime) {
    const [hours, minutes] = cellTime.split(':').map(Number);
    let newHours = hours;
    let newMinutes = minutes + 30;
    
    if (newMinutes >= 60) {
        newHours += Math.floor(newMinutes / 60);
        newMinutes = newMinutes % 60;
    }
    
    if (newHours >= 24) {
        newHours = newHours % 24;
    }
    
    return `${newHours.toString().padStart(2, '0')}:${newMinutes.toString().padStart(2, '0')}`;
}

function timeToMinutes(timeStr) {
    if (!timeStr) return 0;
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

function formatTimeForInput(timeStr) {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    return `${hours.padStart(2, '0')}:${minutes.padStart(2, '0')}`;
}

function showSelectionInfo(tableName, startTime, endTime, durationMinutes) {
    const oldInfo = document.getElementById('selectionInfo');
    if (oldInfo) {
        oldInfo.remove();
    }
    
    const infoDiv = document.createElement('div');
    infoDiv.id = 'selectionInfo';
    infoDiv.className = 'alert alert-info mb-3';
    
    const hours = Math.floor(durationMinutes / 60);
    const minutes = durationMinutes % 60;
    let durationText = `${hours} час`;
    
    if (hours === 1) {
        durationText = '1 час';
    } else if (hours >= 2 && hours <= 4) {
        durationText = `${hours} часа`;
    } else if (hours >= 5) {
        durationText = `${hours} часов`;
    }
    
    if (minutes > 0) {
        durationText += ` ${minutes} минут`;
    }
    
    infoDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-info-circle me-2"></i>
                <strong>Выбрано:</strong> Стол ${tableName}, время: ${startTime}-${endTime} (${durationText})
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    const modalBody = document.querySelector('#createTableModal .modal-body');
    if (modalBody) {
        modalBody.insertBefore(infoDiv, modalBody.firstChild);
    }
}

// Обработчик двойного клика
function handleDoubleClick(e) {
    const cell = e.target.closest('td.table-cell-selectable');
    if (!cell) return;
    
    clearSelection();
    cell.classList.add('table-cell-selected');
    
    selectionState.selectedCells = [cell];
    selectionState.startCell = cell;
    selectionState.startRow = parseInt(cell.dataset.rowIndex);
    selectionState.startCol = parseInt(cell.dataset.colIndex);
    selectionState.startTableId = getTableIdForColumn(selectionState.startCol);
    
    console.log('Двойной клик по ячейке:', {
        row: selectionState.startRow,
        col: selectionState.startCol,
        tableId: selectionState.startTableId
    });
    
    showCreateModalWithSelection();
}

// =============== ОБНОВЛЕНИЕ ПРИ ИЗМЕНЕНИЯХ В ТАБЛИЦЕ ===============

function initTableObserver() {
    const table = document.querySelector('table.table-bordered tbody');
    if (!table) return;
    
    // Отключаем старый observer если есть
    if (window.tableObserver) {
        window.tableObserver.disconnect();
    }
    
    // Создаем новый observer
    window.tableObserver = new MutationObserver(function(mutations) {
        let shouldUpdate = false;
        
        for (let mutation of mutations) {
            if (mutation.type === 'childList') {
                // Проверяем, были ли добавлены или удалены строки
                for (let node of mutation.addedNodes) {
                    if (node.nodeType === 1 && (node.tagName === 'TR' || node.querySelector('tr'))) {
                        shouldUpdate = true;
                        break;
                    }
                }
                
                for (let node of mutation.removedNodes) {
                    if (node.nodeType === 1 && (node.tagName === 'TR' || node.querySelector('tr'))) {
                        shouldUpdate = true;
                        break;
                    }
                }
            }
        }
        
        if (shouldUpdate) {
            console.log('Обнаружены изменения в таблице, обновляем выделение...');
            // Используем debounce чтобы избежать множественных обновлений
            clearTimeout(window.reinitTimeout);
            window.reinitTimeout = setTimeout(() => {
                initCellSelection();
            }, 300);
        }
    });
    
    // Начинаем наблюдение
    window.tableObserver.observe(table, {
        childList: true,
        subtree: true,
        attributes: false,
        characterData: false
    });
}

// =============== ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ ===============

// Добавляем в конец функции initializeAll() или в DOMContentLoaded:
function initializeCellSelection() {
    // Инициализируем выделение через 1 секунду после загрузки
    setTimeout(() => {
        console.log('Запуск инициализации выделения ячеек...');
        initCellSelection();
        initTableObserver();
    }, 1000);
    
    // Добавляем обработчик двойного клика
    document.addEventListener('dblclick', handleDoubleClick);
    
    // Очистка выделения при клике вне таблицы
    document.addEventListener('click', function(e) {
        if (!e.target.closest('table.table-bordered tbody td') && 
            selectionState.selectedCells.length > 0) {
            clearSelection();
        }
    });
    
    // Очистка выделения при закрытии модалки
    const createTableModal = document.getElementById('createTableModal');
    if (createTableModal) {
        createTableModal.addEventListener('hidden.bs.modal', function() {
            clearSelection();
        });
    }
}


initializeCellSelection();


});

</script>

<style>
    .table-cell-selectable {
        cursor: pointer;
        position: relative;
        transition: background-color 0.2s;
    }
    
    .table-cell-selectable:hover {
        background-color: rgba(0, 123, 255, 0.1) !important;
    }
    
    .table-cell-selected {
        background-color: rgba(0, 123, 255, 0.3) !important;
        border: 2px solid #007bff !important;
    }
    
    .table-cell-selecting {
        background-color: rgba(0, 123, 255, 0.2) !important;
    }
    .table-cell-selectable {
        cursor: pointer;
        position: relative;
        transition: background-color 0.2s;
    }

    .table-cell-selectable:hover {
        background-color: rgba(0, 123, 255, 0.1) !important;
        box-shadow: inset 0 0 0 1px rgba(0, 123, 255, 0.3);
    }

    .table-cell-selected {
        background-color: rgba(0, 123, 255, 0.3) !important;
        box-shadow: inset 0 0 0 2px #007bff;
    }

    .table-cell-selecting {
        background-color: rgba(0, 123, 255, 0.2) !important;
        box-shadow: inset 0 0 0 1px #007bff;
    }

    /* Для отладки - подсветка занятых ячеек */
    td[rowspan] {
        position: relative;
    }

    td[rowspan]::after {
        content: '✓';
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 10px;
        color: #28a745;
        opacity: 0.5;
    }
</style>

@endsection