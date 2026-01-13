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
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createTableModal">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить стол
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Основная таблица столов -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 80vh; overflow-y: auto;">
                <table class="table table-bordered mb-0" style="table-layout: fixed;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 120px;" class="text-center">Время</th>
                            @foreach($tableNumbers as $tableNum)
                                <th class="text-center">
                                    @if($tableNum == 'Барная стойка')
                                        <i class="bi bi-cup-hot-fill text-warning me-1"></i>{{ $tableNum }}
                                    @else
                                        <i class="bi bi-table me-1"></i>Стол {{ $tableNum }}
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
                                
                                @foreach($tableNumbers as $tableNum)
                                    @php
                                        // Для барной стойки используем специальный номер
                                        $tableKey = ($tableNum == 'Барная стойка') ? 'bar' : $tableNum;
                                        $cellKey = $timeIndex . '_' . $tableKey;
                                        
                                        if (isset($renderedCells[$cellKey])) {
                                            continue;
                                        }
                                        
                                        $tableBookings = $tables[$tableNum] ?? [];
                                        $currentBooking = null;
                                        $isStart = false;
                                        $durationSlots = 0;
                                        
                                        foreach($tableBookings as $booking) {
                                            $bookingTimeStr = is_string($booking->booking_time) ? $booking->booking_time : (is_object($booking->booking_time) ? $booking->booking_time->format('H:i:s') : '00:00:00');
                                            
                                            // =========== ИСПРАВЛЕНИЕ: Время бронирования ===========
                                            $bookingHour = (int)substr($bookingTimeStr, 0, 2);
                                            if ($bookingHour < 4) {
                                                // Время бронирования 00:00-03:30 - это продолжение предыдущего дня
                                                $bookingTime = \Carbon\Carbon::parse($booking->booking_date->copy()->subDay()->format('Y-m-d') . ' ' . substr($bookingTimeStr, 0, 8));
                                            } else {
                                                // Время бронирования 04:00-23:30 - это текущий день
                                                $bookingTime = \Carbon\Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . substr($bookingTimeStr, 0, 8));
                                            }
                                            // ======================================================
                                            
                                            $bookingEnd = $bookingTime->copy()->addMinutes($booking->duration);
                                            
                                            $timeHour = (int)substr($timeStr, 0, 2);
                                            $timeMin = (int)substr($timeStr, 3, 2);
                                            
                                            // =========== ИСПРАВЛЕНИЕ: Время из таблицы ===========
                                            if ($timeHour < 4) {
                                                // Время 00:00-03:30 - это продолжение предыдущего дня
                                                $timeCarbon = \Carbon\Carbon::parse($selectedDate->copy()->subDay()->format('Y-m-d') . ' ' . $timeStr);
                                            } else {
                                                // Время 04:00-23:30 - это текущий день
                                                $timeCarbon = \Carbon\Carbon::parse($selectedDate->format('Y-m-d') . ' ' . $timeStr);
                                            }
                                            // =====================================================
                                            
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
                                                                    data-table-number="{{ $currentBooking->table_number }}"
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
                                                                    data-guest-name="{{ $currentBooking->guest_name ?? ($currentBooking->client->name ?? 'Без имени') }}">
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
                                                                    data-table-number="{{ $currentBooking->table_number }}"
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
                                                                        data-table-number="{{ $currentBooking->table_number }}"
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
                                                                    data-table-number="{{ $currentBooking->table_number }}"
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
                                                                    data-table-number="{{ $currentBooking->table_number }}"
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
                                                                        data-table-number="{{ $currentBooking->table_number }}"
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
                                                                    data-table-number="{{ $currentBooking->table_number }}"
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
                                                                            data-table-number="{{ $currentBooking->table_number }}"
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
                // Пробуем получить ответ как текст
                return response.text().then(text => {
                    // Пробуем парсить как JSON
                    try {
                        const data = JSON.parse(text);
                        
                        // Если статус не успешный, бросаем ошибку
                        if (!response.ok) {
                            const error = new Error(`HTTP error! status: ${response.status}`);
                            error.status = response.status;
                            error.data = data;
                            throw error;
                        }
                        
                        return data;
                    } catch (e) {
                        // Если не JSON, возвращаем текст
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
            
            // Устанавливаем action формы
            const form = document.getElementById('deleteTableForm');
            if (form) {
                form.action = `/tables/${tableId}`; // Правильный URL с ID стола
            }
        });
    });
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

    
    // =============== ПЕРЕМЕННЫЕ ДЛЯ МОДАЛКИ ТОВАРОВ ===============
    
    let currentTableId = null;
    let currentSaleId = null;
    
    // =============== МОДАЛКА ТОВАРОВ ===============
    
    const saleProductsModal = document.getElementById('saleProductsModal');
    
    if (saleProductsModal) {
        // Событие открытия модалки
        saleProductsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            
            currentTableId = button.getAttribute('data-table-id');
            currentSaleId = button.getAttribute('data-sale-id');
            
            if (!currentTableId) {
                console.error('Table ID not found');
                showToast('warning', 'Внимание', 'ID стола не найден');
                return;
            }
            
            // Загружаем данные через AJAX
            loadSaleItems();
        });
        
        // Событие закрытия модалки
        saleProductsModal.addEventListener('hidden.bs.modal', function() {
            currentTableId = null;
            currentSaleId = null;
            resetProductForm();
        });
    }
    
    // Функция загрузки товаров
    function loadSaleItems() {
        if (!currentTableId) return;
        
        makeRequest(`/tables/${currentTableId}/get-sale-items`)
            .then(data => {
                if (data.success) {
                    updateSaleItemsTable(data.items, data.total);
                    
                    // Обновляем заголовок и информацию
                    updateModalInfo(data);
                } else {
                    showToast('danger', 'Ошибка', data.message || 'Не удалось загрузить товары');
                }
            })
            .catch(error => {
                console.error('Error loading sale items:', error);
                showToast('danger', 'Ошибка', 'Не удалось загрузить данные');
            });
    }
    
    function updateModalInfo(data) {
        const titleElement = document.getElementById('saleProductsModalLabel');
        const infoElement = document.getElementById('saleProductsInfo');
        
        if (titleElement && data.tableInfo) {
            titleElement.textContent = `Товары для стола #${data.tableInfo.tableNumber} - ${data.tableInfo.guestName}`;
        }
        
        if (infoElement) {
            infoElement.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                <strong>Продажа #${currentSaleId || data.saleId || 'Новая'}</strong> - ${data.tableInfo?.guestName || 'Клиент'}
            `;
        }
    }
    
    function updateSaleItemsTable(items, total) {
        const tbody = document.getElementById('productsTableBody');
        const totalElement = document.getElementById('totalAmount');
        
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (items.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-cart-x me-2"></i>
                    Товары не добавлены
                </td>
            `;
            tbody.appendChild(emptyRow);
        } else {
            items.forEach(item => {
                const row = document.createElement('tr');
                row.id = `productRow${item.id}`;
                // ✅ ИСПРАВЛЕНО: Убрали input для изменения количества
                row.innerHTML = `
                    <td>${item.product_name}</td>
                    <td>
                        <span class="fw-bold">${parseFloat(item.quantity).toLocaleString('ru-RU')}</span>
                        <small class="text-muted ms-1">${item.unit}</small>
                    </td>
                    <td>${parseFloat(item.unit_price).toFixed(2)} ₽</td>
                    <td>${parseFloat(item.total).toFixed(2)} ₽</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger remove-product-btn" 
                                data-item-id="${item.id}"
                                title="Удалить">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        if (totalElement) {
            totalElement.textContent = parseFloat(total).toFixed(2);
        }
    }
    
    // =============== ЛОГИКА ДОБАВЛЕНИЯ ТОВАРА ===============
    
    // Обработчик выбора товара
    const productSelect = document.getElementById('productSelect');
    if (productSelect) {
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const unit = selectedOption.dataset.unit;
            const price = selectedOption.dataset.price;
            
            // Устанавливаем цену по умолчанию
            const priceInput = document.getElementById('productPrice');
            if (priceInput && price) {
                priceInput.value = price;
            }
            
            // Обновляем подсказку
            const hint = document.getElementById('quantityHint');
            if (hint) {
                if (unit === 'шт') {
                    hint.textContent = 'Количество должно быть целым числом';
                    hint.className = 'text-info';
                } else {
                    hint.textContent = `Единица измерения: ${unit}`;
                    hint.className = 'text-muted';
                }
            }
            
            // Устанавливаем правильный step для input
            const quantityInput = document.getElementById('productQuantity');
            if (quantityInput) {
                quantityInput.step = unit === 'шт' ? '1' : '0.001';
                quantityInput.min = unit === 'шт' ? '1' : '0.001';
            }
        });
    }
    
    // Обработчик добавления товара
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'addProductBtn') {
            e.preventDefault();
            addProduct();
        }
    });
    
    function addProduct() {
        const productSelect = document.getElementById('productSelect');
        const quantityInput = document.getElementById('productQuantity');
        const priceInput = document.getElementById('productPrice');
        
        if (!currentTableId) {
            showToast('warning', 'Внимание', 'Стол не выбран');
            return;
        }
        
        const productId = productSelect.value;
        const quantity = parseFloat(quantityInput.value);
        const price = parseFloat(priceInput.value);
        
        // Валидация
        if (!productId) {
            showToast('warning', 'Внимание', 'Выберите товар');
            productSelect.focus();
            return;
        }
        
        if (!quantity || quantity <= 0) {
            showToast('warning', 'Внимание', 'Введите корректное количество');
            quantityInput.focus();
            return;
        }
        
        if (!price || price <= 0) {
            showToast('warning', 'Внимание', 'Введите корректную цену');
            priceInput.focus();
            return;
        }
        
        // Проверка для штучных товаров
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const unit = selectedOption.dataset.unit;
        
        if (unit === 'шт' && !Number.isInteger(quantity)) {
            showToast('warning', 'Внимание', 'Для штучных товаров количество должно быть целым числом');
            quantityInput.value = Math.round(quantity);
            return;
        }
        
        const requestData = {
            product_id: productId,
            quantity: quantity,
            unit_price: price
        };
        
        console.log('Adding product:', requestData);
        
        // Используем fetch напрямую с обработкой текста
        fetch(`/tables/${currentTableId}/add-product`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(requestData)
        })
        .then(response => {
            return response.text().then(text => {
                // Пробуем парсить JSON
                try {
                    const data = JSON.parse(text);
                    return { response, data };
                } catch {
                    // Если не JSON, возвращаем текст
                    return { response, text };
                }
            });
        })
        .then(({ response, data, text }) => {
            console.log('Response:', response.status, data || text);
            
            if (response.ok) {
                if (data && data.success) {
                    showToast('success', 'Успех', 'Товар добавлен');
                    loadSaleItems();
                    if (data.newTotal !== undefined) {
                        updateTableTotal(currentTableId, data.newTotal);
                    }
                    resetProductForm();
                } else {
                    showToast('danger', 'Ошибка', 'Некорректный ответ от сервера');
                }
            } else {
                // Обработка ошибки
                if (data) {
                    // Есть JSON данные
                    if (data.message && data.message.includes('Недостаточно товара')) {
                        const productName = selectedOption.text.split(' - ')[0];
                        const unit = selectedOption.dataset.unit;
                        
                        let errorMessage = `Недостаточно товара на складе:<br>`;
                        errorMessage += `<strong>${productName}</strong><br>`;
                        
                        if (data.details && data.details.requested) {
                            errorMessage += `Запрошено: <span class="text-danger">${data.details.requested} ${data.details.unit || unit}</span><br>`;
                            errorMessage += `Доступно: <span class="text-success">${data.details.available} ${data.details.unit || unit}</span>`;
                        } else if (data.requested !== undefined) {
                            errorMessage += `Запрошено: <span class="text-danger">${data.requested} ${unit}</span><br>`;
                            errorMessage += `Доступно: <span class="text-success">${data.available} ${unit}</span>`;
                        } else {
                            errorMessage += data.message;
                        }
                        
                        showCustomToast('danger', 'Недостаточно товара', errorMessage, true);
                    } else {
                        showToast('danger', 'Ошибка', data.message || 'Не удалось добавить товар');
                    }
                } else if (text) {
                    // Только текст
                    showToast('danger', 'Ошибка ' + response.status, text.substring(0, 200));
                } else {
                    showToast('danger', 'Ошибка', 'Не удалось добавить товар');
                }
            }
        })
        .catch(error => {
            console.error('Error adding product:', error);
            showToast('danger', 'Ошибка', 'Не удалось добавить товар');
        });
    }
    
    function resetProductForm() {
        const productSelect = document.getElementById('productSelect');
        const quantityInput = document.getElementById('productQuantity');
        const priceInput = document.getElementById('productPrice');
        const hint = document.getElementById('quantityHint');
        
        if (productSelect) productSelect.value = '';
        if (quantityInput) quantityInput.value = '1';
        if (priceInput) priceInput.value = '';
        if (hint) {
            hint.textContent = '';
            hint.className = 'text-muted';
        }
    }

    function showCustomToast(type, title, message, isHtml = false) {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body ${isHtml ? '' : 'text-bg-' + type}">
                    ${isHtml ? message : `<strong>${title}:</strong> ${message}`}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: isHtml ? false : true, // Для HTML сообщений не скрываем автоматически
            delay: isHtml ? 8000 : 3000 // Для HTML даем больше времени на чтение
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }

    // Добавьте эту функцию для модального окна с предупреждением
    function showStockWarningModal(productName, available, unit, requested) {
        // Создаем модальное окно
        const modalId = 'stockWarningModal';
        let modalElement = document.getElementById(modalId);
        
        if (!modalElement) {
            // Создаем модальное окно
            modalElement = document.createElement('div');
            modalElement.className = 'modal fade';
            modalElement.id = modalId;
            modalElement.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Недостаточно товара
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle me-2"></i>
                                На складе недостаточно товара для добавления в полном объеме.
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="card border-danger">
                                        <div class="card-body text-center">
                                            <h6 class="card-subtitle mb-2 text-muted">Запрошено</h6>
                                            <h4 class="text-danger">${requested} ${unit}</h4>
                                            <p class="small text-muted">${productName}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <h6 class="card-subtitle mb-2 text-muted">Доступно</h6>
                                            <h4 class="text-success">${available} ${unit}</h4>
                                            <p class="small text-muted">${productName}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="mb-0">Хотите добавить максимально доступное количество?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-1"></i> Отмена
                            </button>
                            <button type="button" class="btn btn-primary" id="confirmAddMaxBtn">
                                <i class="bi bi-check-circle me-1"></i> Добавить ${available} ${unit}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modalElement);
            
            // Инициализируем модальное окно
            const modal = new bootstrap.Modal(modalElement);
            
            // Обработчик подтверждения
            modalElement.addEventListener('shown.bs.modal', function() {
                const confirmBtn = document.getElementById('confirmAddMaxBtn');
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function() {
                        // Устанавливаем максимальное доступное количество
                        const quantityInput = document.getElementById('productQuantity');
                        if (quantityInput) {
                            quantityInput.value = available;
                        }
                        
                        // Закрываем модальное окно
                        modal.hide();
                        
                        // Показываем сообщение
                        showToast('info', 'Количество обновлено', `Установлено максимальное доступное количество: ${available} ${unit}`);
                    });
                }
            });
            
            // Удаляем обработчик при закрытии
            modalElement.addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }
        
        // Показываем модальное окно
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
    
    // =============== ЛОГИКА УДАЛЕНИЯ ТОВАРА ===============
    
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-product-btn')) {
            const button = e.target.closest('.remove-product-btn');
            const itemId = button.dataset.itemId;
            
            if (!itemId || !currentTableId) return;
            
            removeProduct(itemId);
        }
    });
    
    function removeProduct(itemId) {
        if (!confirm('Вы уверены, что хотите удалить этот товар?')) {
            return;
        }
        
        makeRequest(`/tables/${currentTableId}/remove-product/${itemId}`, {
            method: 'DELETE'
        })
        .then(data => {
            if (data.success) {
                showToast('success', 'Успех', 'Товар удален');
                
                // Удаляем строку из таблицы
                const row = document.getElementById(`productRow${itemId}`);
                if (row) {
                    row.remove();
                }
                
                // Обновляем итоговую сумму в модалке
                const totalElement = document.getElementById('totalAmount');
                if (totalElement && data.total !== undefined) {
                    totalElement.textContent = parseFloat(data.total).toFixed(2);
                }
                
                // ✅ ОБНОВЛЯЕМ СУММУ В ЯЧЕЙКЕ СТОЛА
                if (data.newTotal !== undefined) {
                    updateTableTotal(currentTableId, data.newTotal);
                }
                
                // Если товаров не осталось, показываем сообщение
                const tbody = document.getElementById('productsTableBody');
                if (tbody && tbody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-cart-x me-2"></i>
                            Товары не добавлены
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
            } else {
                showToast('danger', 'Ошибка', data.message || 'Не удалось удалить товар');
            }
        })
        .catch(error => {
            console.error('Error removing product:', error);
            showToast('danger', 'Ошибка', 'Не удалось удалить товар');
        });
    }
    

    // =============== ПЕРЕМЕННЫЕ ДЛЯ МОДАЛКИ КАЛЬЯНОВ ===============

    let currentHookahsTableId = null;
    let currentHookahsSaleId = null;

    // =============== МОДАЛКА КАЛЬЯНОВ ===============

    const saleHookahsModal = document.getElementById('saleHookahsModal');

    if (saleHookahsModal) {
        // Событие открытия модалки
        saleHookahsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            
            currentHookahsTableId = button.getAttribute('data-table-id');
            const tableNumber = button.getAttribute('data-table-number');
            const guestName = button.getAttribute('data-guest-name');
            currentHookahsSaleId = button.getAttribute('data-sale-id');
            
            if (!currentHookahsTableId) {
                console.error('Table ID not found');
                showToast('warning', 'Внимание', 'ID стола не найден');
                return;
            }
            
            // Показываем заголовок
            const titleElement = this.querySelector('#saleHookahsModalLabel');
            if (titleElement) {
                titleElement.textContent = `Кальяны для стола #${tableNumber} - ${guestName}`;
            }
            
            // Обновляем информацию
            const infoElement = this.querySelector('#saleHookahsInfo');
            if (infoElement) {
                infoElement.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Продажа #${currentHookahsSaleId || 'Новая'}</strong> - ${guestName}
                `;
            }
            
            // Загружаем кальяны через AJAX
            loadSaleHookahs();
        });
        
        // Событие закрытия модалки
        saleHookahsModal.addEventListener('hidden.bs.modal', function() {
            currentHookahsTableId = null;
            currentHookahsSaleId = null;
            resetHookahForm();
        });
    }

    // Функция загрузки кальянов
    function loadSaleHookahs() {
        if (!currentHookahsTableId) return;
        
        makeRequest(`/tables/${currentHookahsTableId}/get-sale-hookahs`)
            .then(data => {
                if (data.success) {
                    updateHookahsTable(data.hookahs, data.total);
                } else {
                    showToast('danger', 'Ошибка', data.message || 'Не удалось загрузить кальяны');
                }
            })
            .catch(error => {
                console.error('Error loading hookahs:', error);
                showToast('danger', 'Ошибка', 'Не удалось загрузить данные');
            });
    }

    function updateHookahsTable(hookahs, total) {
        const tbody = document.getElementById('hookahsTableBody');
        const totalElement = document.getElementById('hookahsTotalAmount');
        
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (hookahs.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="3" class="text-center text-muted py-4">
                    <i class="bi bi-cup-straw me-2"></i>
                    Кальяны не добавлены
                </td>
            `;
            tbody.appendChild(emptyRow);
        } else {
            hookahs.forEach(hookah => {
                const row = document.createElement('tr');
                row.id = `hookahRow${hookah.id}`;
                row.innerHTML = `
                    <td>${hookah.name}</td>
                    <td>${parseFloat(hookah.price).toFixed(0)} ₽</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger remove-hookah-btn" 
                                data-hookah-id="${hookah.id}"
                                title="Удалить">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        if (totalElement) {
            totalElement.textContent = parseFloat(total).toFixed(0);
        }
    }

    // =============== ЛОГИКА ДОБАВЛЕНИЯ КАЛЬЯНА ===============

    // Обработчик выбора кальяна
    const hookahSelect = document.getElementById('hookahSelect');
    if (hookahSelect) {
        hookahSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.dataset.price;
            // Для кальянов нет подсказок о единицах измерения
        });
    }

    // Обработчик добавления кальяна
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'addHookahBtn') {
            e.preventDefault();
            addHookah();
        }
    });

    function addHookah() {
        const hookahSelect = document.getElementById('hookahSelect');
        
        if (!currentHookahsTableId) {
            showToast('warning', 'Внимание', 'Стол не выбран');
            return;
        }
        
        const hookahId = hookahSelect.value;
        
        if (!hookahId) {
            showToast('warning', 'Внимание', 'Выберите кальян');
            hookahSelect.focus();
            return;
        }
        
        const requestData = {
            hookah_id: hookahId
        };
        
        console.log('Adding hookah to table:', currentHookahsTableId);
        
        makeRequest(`/tables/${currentHookahsTableId}/add-hookah`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(requestData)
        })
        .then(data => {
            console.log('Hookah add response:', data);
            
            if (data.success) {
                showToast('success', 'Успех', 'Кальян добавлен');
                
                // ❌ УДАЛИТЕ ЭТУ СТРОКУ (она вызывает старую несуществующую функцию):
                // stopHookahRequirementTimer(currentHookahsTableId);
                
                // ✅ ВМЕСТО НЕЕ ИСПОЛЬЗУЙТЕ НОВЫЙ МОДУЛЬ:
                if (window.HookahTimerManager && window.HookahTimerManager.stopTimer) {
                    window.HookahTimerManager.stopTimer(currentHookahsTableId);
                }
                
                console.log('Updating table status to: opened_with_hookah');
                
                // Обновляем интерфейс
                updateTableInterface(currentHookahsTableId, {
                    status: 'opened_with_hookah',
                    hasHookah: true
                });
                
                // ✅ ОБНОВЛЯЕМ СУММУ В ЯЧЕЙКЕ СТОЛА
                if (data.newTotal !== undefined) {
                    updateTableTotal(currentHookahsTableId, data.newTotal);
                }
                
                // Закрываем модальное окно
                const modalElement = document.getElementById('saleHookahsModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
                
                // Сбрасываем форму
                resetHookahForm();
                
                // Показываем подтверждение
                setTimeout(() => {
                    showToast('info', 'Статус обновлен', 'Стол теперь с кальяном');
                }, 500);
            } else {
                showToast('danger', 'Ошибка', data.message || 'Не удалось добавить кальян');
            }
        })
        .catch(error => {
            console.error('Error adding hookah:', error);
            showToast('danger', 'Ошибка', 'Не удалось добавить кальян');
        });
    }
    
    function resetHookahForm() {
        const hookahSelect = document.getElementById('hookahSelect');
        if (hookahSelect) hookahSelect.value = '';
    }

    // =============== ЛОГИКА УДАЛЕНИЯ КАЛЬЯНА ===============

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-hookah-btn')) {
            const button = e.target.closest('.remove-hookah-btn');
            const hookahId = button.dataset.hookahId;
            
            if (!hookahId || !currentHookahsTableId) return;
            
            removeHookah(hookahId);
        }
    });

    function removeHookah(hookahId) {
        if (!confirm('Вы уверены, что хотите удалить этот кальян?')) {
            return;
        }
        
        makeRequest(`/tables/${currentHookahsTableId}/remove-hookah/${hookahId}`, {
            method: 'DELETE'
        })
        .then(data => {
            if (data.success) {
                showToast('success', 'Успех', 'Кальян удален');
                
                // Удаляем строку из таблицы
                const row = document.getElementById(`hookahRow${hookahId}`);
                if (row) {
                    row.remove();
                }
                
                // Обновляем итоговую сумму в модалке
                const totalElement = document.getElementById('hookahsTotalAmount');
                if (totalElement && data.total !== undefined) {
                    totalElement.textContent = parseFloat(data.total).toFixed(0);
                }
                
                // ✅ ОБНОВЛЯЕМ СУММУ В ЯЧЕЙКЕ СТОЛА
                if (data.newTotal !== undefined) {
                    updateTableTotal(currentHookahsTableId, data.newTotal);
                }
                
                // Если кальянов не осталось, показываем сообщение
                const tbody = document.getElementById('hookahsTableBody');
                if (tbody && tbody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="bi bi-cup-straw me-2"></i>
                            Кальяны не добавлены
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
            } else {
                showToast('danger', 'Ошибка', data.message || 'Не удалось удалить кальян');
            }
        })
        .catch(error => {
            console.error('Error removing hookah:', error);
            showToast('danger', 'Ошибка', 'Не удалось удалить кальян');
        });
    }

    
    // ДИНАМИЧЕСКОЕ ОБНОВЛЕНИЕ ИНТЕРФЕЙСА СТОЛА
    function updateTableInterface(tableId, data) {
        console.log('🔄 Обновляем стол', tableId, 'статус:', data.status);
        
        // 1. Находим ячейку стола
        const button = document.querySelector(`button[data-table-id="${tableId}"]`);
        if (!button) {
            console.error('❌ Кнопка стола не найдена');
            return;
        }
        
        const cell = button.closest('td');
        if (!cell) {
            console.error('❌ Ячейка td не найдена');
            return;
        }
        
        console.log('✅ Ячейка найдена');
        
        // 2. МЕНЯЕМ ЦВЕТ ФОНА ЯЧЕЙКИ
        if (data.status === 'opened_with_hookah') {
            // Стол С кальяном - светло-голубой
            cell.style.backgroundColor = '#e0f7fa';
            console.log('🎨 Цвет ячейки изменен на #e0f7fa (стол с кальяном)');
            
        } else if (data.status === 'opened_without_hookah') {
            // Стол БЕЗ кальяна - светло-зеленый (как в PHP getStatusColor())
            cell.style.backgroundColor = '#e8f5e9';
            console.log('🎨 Цвет ячейки изменен на #e8f5e9 (стол без кальяна)');
        }
        
        // 3. МЕНЯЕМ БЕЙДЖ СТАТУСА
        const headerDiv = cell.querySelector('.d-flex.justify-content-between.align-items-start.mb-1');
        const badge = headerDiv ? headerDiv.querySelector('.badge') : null;
        
        if (badge) {
            console.log('✅ Бейдж найден');
            
            if (data.status === 'opened_with_hookah') {
                badge.textContent = 'Открытый стол (с кальяном)';
                badge.className = 'badge bg-info';
                console.log('📝 Бейдж: Открытый стол (с кальяном) + bg-info');
                
            } else if (data.status === 'opened_without_hookah') {
                badge.textContent = 'Открытый стол (без кальяна)';
                badge.className = 'badge bg-success';
                console.log('📝 Бейдж: Открытый стол (без кальяна) + bg-success');
            }
        }
        
        // 4. УДАЛЯЕМ таймер "требуется кальян" если есть
        // ❌ УДАЛИТЕ ЭТОТ БЛОК:
        // const timerContainer = cell.querySelector('.timer-container');
        // if (timerContainer) {
        //     console.log('🗑 Удаляем timer-container (таймер "требуется кальян")');
        //     timerContainer.remove();
        // }
        
        // ✅ ВМЕСТО НЕГО ИСПОЛЬЗУЙТЕ НОВЫЙ МОДУЛЬ:
        if (window.HookahTimerManager && window.HookahTimerManager.stopTimer) {
            window.HookahTimerManager.stopTimer(tableId);
        }
        
        // 5. ТАЙМЕР УГЛЕЙ - ИСПРАВЛЕННАЯ ВЕРСИЯ
        if (data.status === 'opened_with_hookah') {
            console.log('🔥 Добавляем таймер углей для стола', tableId);
            
            // ... остальной код без изменений ...
        }
        
        console.log('✅ Стол успешно обновлен!');
    }

    function updateTableTotal(tableId, newTotal) {
        console.log('🔄 Обновляем сумму для стола', tableId, 'новая сумма:', newTotal);
        
        // Находим ВСЕ кнопки с данным tableId
        const buttons = document.querySelectorAll(`button[data-table-id="${tableId}"]`);
        if (buttons.length === 0) {
            console.error('❌ Кнопки стола не найдены для обновления суммы');
            return;
        }
        
        // Обрабатываем каждую кнопку
        buttons.forEach(button => {
            const cell = button.closest('td');
            if (!cell) return;
            
            // 1. Находим сумму в открытом столе (зеленый бейдж)
            const totalBadge = cell.querySelector('.badge.bg-success.fs-6');
            if (totalBadge) {
                totalBadge.textContent = numberFormat(newTotal) + ' ₽';
                console.log('✅ Сумма обновлена в ячейке (открытый стол):', totalBadge.textContent);
            }
            
            // 2. Находим сумму в заголовке таблицы товаров (если есть)
            const productsBadge = cell.querySelector('.badge.bg-primary');
            if (productsBadge && productsBadge.textContent.includes('₽')) {
                productsBadge.textContent = numberFormat(newTotal) + ' ₽';
            }
            
            // 3. Обновляем сумму в статусе "закрытый стол"
            const closedTotalElement = cell.querySelector('.small.mt-1 strong');
            if (closedTotalElement) {
                closedTotalElement.textContent = numberFormat(newTotal) + ' ₽';
            }
            
            // 4. Обновляем данные в кнопке "Закрыть стол"
            const closeButton = cell.querySelector('button[data-bs-target="#closeSaleModal"]');
            if (closeButton) {
                closeButton.setAttribute('data-total', newTotal);
            }
        });
    }

    // Функция для форматирования числа
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
        closeSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                const tableId = button.getAttribute('data-table-id');
                const tableNumber = button.getAttribute('data-table-number');
                const guestName = button.getAttribute('data-guest-name');
                const saleId = button.getAttribute('data-sale-id');
                
                // Обновляем заголовок
                document.getElementById('closeTableNumber').textContent = tableNumber;
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
        if (!tableId) return;
        
        makeRequest(`/tables/${tableId}/get-sale-data`)
            .then(data => {
                if (data.success) {
                    updateCloseModalData(data);
                } else {
                    showToast('danger', 'Ошибка', data.message || 'Не удалось загрузить данные');
                }
            })
            .catch(error => {
                console.error('Error loading sale data:', error);
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
                if (!groupedHookahs[hookah.id]) {
                    groupedHookahs[hookah.id] = {
                        ...hookah,
                        count: 1
                    };
                } else {
                    groupedHookahs[hookah.id].count++;
                }
            });
            
            Object.values(groupedHookahs).forEach(hookah => {
                const totalPrice = parseFloat(hookah.price) * hookah.count;
                html += `
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="me-3 flex-grow-1">
                                <div class="fw-bold mb-1">${hookah.name}</div>
                                <div class="text-muted">${hookah.count > 1 ? hookah.count + ' × ' : ''}${formatPrice(hookah.price)}</div>
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
            const tableNumber = button.getAttribute('data-table-number');
            const guestName = button.getAttribute('data-guest-name');
            
            // Обновляем информацию
            document.getElementById('viewTableNumber').textContent = `Стол #${tableNumber}`;
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
                        <td>${parseFloat(hookah.price).toFixed(2)} ₽</td>
                    `;
                    hookahsBody.appendChild(row);
                });
            } else {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="2" class="text-center text-muted py-3">
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
        
        // Начисленные бонусы (рассчитываем - обычно 5% от финальной суммы)
        const bonusEarnedContainer = document.getElementById('viewBonusEarnedContainer');
        const bonusEarnedElement = document.getElementById('viewBonusEarned');
        if (bonusEarnedContainer && bonusEarnedElement) {
            // Рассчитываем начисленные бонусы (5% от финальной суммы)
            const finalTotal = parseFloat(data.finalTotal || 0);
            const bonusEarned = Math.floor(finalTotal * 0.05);
            if (bonusEarned > 0 && data.clientId) {
                bonusEarnedElement.textContent = bonusEarned + ' бонусов';
                bonusEarnedContainer.style.display = 'block';
            } else {
                bonusEarnedContainer.style.display = 'none';
            }
        }
        
        // Итоговая сумма
        document.getElementById('viewFinalTotal').textContent = 
            parseFloat(data.finalTotal || 0).toFixed(2) + ' ₽';
        
        // Способ оплаты
        const paymentMethod = document.getElementById('viewPaymentMethod');
        if (paymentMethod && data.paymentMethod) {
            const paymentMethods = {
                'cash': 'Наличные',
                'card': 'Карта',
                'online': 'Онлайн',
                'terminal': 'Терминал'
            };
            paymentMethod.textContent = paymentMethods[data.paymentMethod] || data.paymentMethod;
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
            maxSpendPercentElem.textContent = data.clientMaxSpendPercent || '50';
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

    let maxUsableBonuses = 0;
    let currentBonusDiscount = 0;
    let clientMaxSpendPercent = 50;


    // =============== ФУНКЦИЯ ОБНОВЛЕНИЯ ИНФОРМАЦИИ О БОНУСАХ ===============

    function updateClientBonusInfo(data) {
        if (!data.clientId || data.clientBonusPoints === undefined) {
            // Нет клиента - скрываем секцию бонусов
            clientBonusInfo.style.display = 'none';
            bonusSection.style.display = 'none';
            bonusDiscountRow.style.display = 'none';
            currentBonusDiscount = 0;
            return;
        }
        
        // Показываем информацию о клиенте
        clientBonusInfo.style.display = 'block';
        clientNameElem.textContent = data.clientName || 'Клиент';
        clientBonusPointsElem.textContent = data.clientBonusPoints.toLocaleString();
        clientMaxSpendPercent = data.clientMaxSpendPercent || 50;
        maxSpendPercentText.textContent = clientMaxSpendPercent;
        
        // Рассчитываем максимальное количество бонусов
        const totalAmount = parseFloat(data.subtotal);
        const discount = parseFloat(document.getElementById('closeDiscount').value) || 0;
        
        // Максимум бонусов = X% от (сумма товаров - скидка)
        const percentage = clientMaxSpendPercent / 100;
        maxUsableBonuses = Math.floor((totalAmount - discount) * percentage);
        
        // Нельзя использовать больше, чем есть у клиента
        maxUsableBonuses = Math.min(data.clientBonusPoints, maxUsableBonuses);
        maxUsableBonuses = Math.max(0, maxUsableBonuses);
        
        maxUsableBonusesElem.textContent = maxUsableBonuses.toLocaleString() + ' бонусов';
        
        // Показываем секцию бонусов
        bonusSection.style.display = 'block';
        
        // Если уже были использованы бонусы
        if (data.usedBonusPoints > 0) {
            useBonusesCheckbox.checked = true;
            bonusPointsToUseInput.value = data.usedBonusPoints;
            currentBonusDiscount = data.usedBonusPoints;
            bonusPointsToUseInput.disabled = false;
            bonusInputRow.style.display = 'flex';
            bonusDiscountRow.style.display = 'flex';
            closeBonusDiscountDisplay.textContent = formatPrice(data.usedBonusPoints);
        } else {
            // Сбрасываем состояние бонусов
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
                <div class="small mt-1">Лимит из бонусной карты: <strong>${clientMaxSpendPercent}%</strong> от суммы заказа</div>
            `;
        } else if (data.clientBonusPoints > 0) {
            bonusWarningText.innerHTML = `
                <div>У клиента недостаточно бонусов для использования</div>
                <div class="small mt-1">Лимит из бонусной карты: <strong>${clientMaxSpendPercent}%</strong> от суммы заказа</div>
            `;
        } else {
            bonusWarningText.textContent = 'У клиента нет бонусов';
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
        // Получаем элементы
        const subtotalElement = document.getElementById('closeSubtotal');
        const finalTotalElement = document.getElementById('closeFinalTotal');
        
        if (!subtotalElement || !finalTotalElement) return;
        
        // Получаем промежуточную сумму
        const subtotalText = subtotalElement.textContent;
        const subtotal = parseFloat(subtotalText.replace(' ₽', '').replace(/\s/g, '')) || 0;
        
        // Рассчитываем скидку в рублях (используем новую функцию)
        const discountInRubles = getDiscountInRubles();
        
        // Рассчитываем итог с учетом бонусов
        const finalTotal = Math.max(0, subtotal - discountInRubles - currentBonusDiscount);
        
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
            if (currentBonusDiscount > 0) {
                bonusDiscountRow.style.display = 'flex';
                closeBonusDiscountDisplay.textContent = formatPrice(currentBonusDiscount);
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
            if (currentBonusDiscount > 0) breakdown += ' - Бонусы';
            
            finalTotalBreakdown.textContent = breakdown;
        }
        
        // Обновляем расчет доступных бонусов
        if (typeof updateBonusCalculation === 'function') {
            updateBonusCalculation();
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
        // Обновляем промежуточную сумму
        let subtotal = 0;
        
        // Проверяем формат данных
        if (typeof data.subtotal === 'string') {
            // Старый формат: строка с "₽"
            subtotal = parseFloat(data.subtotal.replace(' ₽', '').replace(/\s/g, '')) || 0;
        } else if (typeof data.subtotal === 'number') {
            // Новый формат: число
            subtotal = data.subtotal;
        } else {
            // Если непонятный формат
            subtotal = 0;
            console.warn('Unexpected subtotal format:', data.subtotal);
        }
        
        // Устанавливаем текущую промежуточную сумму
        setCurrentSubtotal(subtotal);
        
        // Обновляем суммы в формате для отображения
        document.getElementById('closeItemsTotal').textContent = formatPrice(data.productsTotal || 0);
        document.getElementById('closeHookahsTotal').textContent = formatPrice(data.hookahsTotal || 0);
        document.getElementById('closeSubtotal').textContent = formatPrice(subtotal);
        
        // Устанавливаем скидку (если она есть в данных)
        if (closeDiscountInput) {
            closeDiscountInput.value = data.discount || 0;
        }
        
        // Обновляем информацию о клиенте и бонусах
        updateClientBonusInfo(data);
        
        // Заполняем списки товаров и кальянов
        fillProductsList(data.products || []);
        fillHookahsList(data.hookahs || []);
        
        // Пересчитываем итоговую сумму
        recalculateDiscount();
    }


    // =============== ОБНОВЛЕНИЕ ФУНКЦИИ updateBonusCalculation ===============

    function updateBonusCalculation() {
        // Проверяем, есть ли элементы
        if (!clientBonusPointsElem || !maxUsableBonusesElem || !closeDiscountInput) return;
        
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
        
        // Пересчитываем максимальные бонусы
        const percentage = clientMaxSpendPercent / 100;
        maxUsableBonuses = Math.floor((subtotal - discountForBonusCalc) * percentage);
        maxUsableBonuses = Math.min(clientBonusPoints, maxUsableBonuses);
        maxUsableBonuses = Math.max(0, maxUsableBonuses);
        
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

    // 1. Инициализируем таймер "требуется кальян"
    setTimeout(() => {
        if (typeof window.HookahTimerManager !== 'undefined') {
            console.log('🚀 Initializing HookahTimerManager...');
            window.HookahTimerManager.init();
        } else {
            console.error('❌ HookahTimerManager not found!');
        }
    }, 1000); // Даем время на загрузку DOM
    
    // =============== ОБРАБОТЧИК ОТПРАВКИ ФОРМЫ ===============

    const closeSaleForm = document.getElementById('closeSaleForm');
    if (closeSaleForm) {
        closeSaleForm.addEventListener('submit', function(e) {
            // 1. Получаем скидку в рублях
            const discountInRubles = getDiscountInRubles();
            console.log('✅ Рассчитанная скидка в рублях:', discountInRubles, 'тип скидки:', currentDiscountType);
            
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
            
            // 3. ДЛЯ СОВМЕСТИМОСТИ: также устанавливаем в основное поле discount
            // (чтобы не ломать старый контроллер)
            const closeDiscountInput = document.getElementById('closeDiscount');
            if (closeDiscountInput) {
                // Сохраняем оригинальное значение (для отладки)
                const originalValue = closeDiscountInput.value;
                
                // Если это проценты, конвертируем в рубли
                if (currentDiscountType === 'percent') {
                    closeDiscountInput.value = discountInRubles.toFixed(2);
                    console.log('✅ Поле discount изменено с', originalValue, 'на', closeDiscountInput.value);
                }
            }
            
            // 4. Можно показать информационное сообщение (опционально)
            // showToast('info', 'Отправка', `Скидка: ${discountInRubles.toFixed(2)} руб.`);
        });
    }
    
    console.log('Table Manager initialized');
    

    // =============== ИНИЦИАЛИЗАЦИЯ ТАЙМЕРА УГЛЕЙ ===============

    // setTimeout(() => {
    //     // Проверяем, загрузился ли файл coal-timer.js
    //     if (typeof window.CoalTimerSystem !== 'undefined') {
    //         console.log('CoalTimerSystem found, initializing...');
    //         const system = window.CoalTimerSystem.init();
            
    //         // ДОПОЛНИТЕЛЬНО: Проверяем все столы с кальянами при загрузке
    //         setTimeout(() => {
    //             if (system && system.restoreTimersForAllTablesWithHookah) {
    //                 system.restoreTimersForAllTablesWithHookah();
    //             }
    //         }, 1500);
    //     } else {
    //         console.error('CoalTimerSystem NOT FOUND! Check file path.');
    //     }
    // }, 1000); // Уменьшаем задержку


    if (typeof window.CoalTimerSystem !== 'undefined') {
        console.log('CoalTimerSystem found, initializing...');
        const system = window.CoalTimerSystem.init();
        
        // ДОПОЛНИТЕЛЬНО: Проверяем все столы с кальянами при загрузке
        if (system && system.restoreTimersForAllTablesWithHookah) {
            system.restoreTimersForAllTablesWithHookah();
        }
    } else {
        console.error('CoalTimerSystem NOT FOUND! Check file path.');
    }


    // Экспорт функций
    window.TableManager = {
        showToast,
        makeRequest,
        formatPrice,
        loadSaleItems,
        addProduct,
        removeProduct,
        loadSaleHookahs,     
        addHookah,          
        removeHookah,
        loadSaleDataForClosing,  
        updateCloseModalData,    
        fillProductsList,         
        fillHookahsList,         
        calculateCloseTotal,
        updateClientBonusInfo,
        calculateCloseTotal,
        updateBonusCalculation,
        initDiscountLogic,
        recalculateDiscount,
        setCurrentSubtotal,
        updateDiscountUI,      
    };
    
    console.log('Table Manager initialized');
});
</script>
@endsection