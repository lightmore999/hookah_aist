@extends('layouts.app')

@section('title', 'Инвентаризация: ' . $inventory->name)

@section('content')
<div class="container py-4">
    
    <!-- Заголовок с информацией об инвентаризации -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">
                Инвентаризация: {{ $inventory->name }}
            </h1>
            <div class="text-muted small">
                <i class="bi bi-shop me-1"></i> Склад: {{ $inventory->warehouse->name }}
                <span class="mx-2">•</span>
                <i class="bi bi-calendar me-1"></i> Дата: {{ $inventory->inventory_date->format('d.m.Y H:i') }}
                <span class="mx-2">•</span>
                <i class="bi bi-person me-1"></i> Создал: {{ $inventory->creator->name }}
                
                @if($inventory->isClosed())
                    <span class="mx-2">•</span>
                    <i class="bi bi-check-circle me-1"></i> Закрыл: {{ $inventory->completer->name }}
                    <span class="mx-2">•</span>
                    <i class="bi bi-clock me-1"></i> {{ $inventory->updated_at->format('d.m.Y H:i') }}
                @endif
            </div>
            
            <!-- Статус -->
            <div class="mt-2">
                @if($inventory->isCreated())
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                        <i class="bi bi-pencil-square me-1"></i> Создана (можно редактировать)
                    </span>
                @elseif($inventory->isClosed())
                    <span class="badge bg-success fs-6 px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i> Закрыта (только просмотр)
                    </span>
                @endif
            </div>
        </div>
        
        <div class="d-flex flex-column gap-2">
            <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад к списку
            </a>
            
            @if($inventory->isCreated())
                <button type="button" 
                        class="btn btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#closeInventoryModal">
                    <i class="bi bi-check-circle me-1"></i> Завершить инвентаризацию
                </button>
            @endif
            
            @if($inventory->isCreated())
                <button type="button" 
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#addItemModal">
                    <i class="bi bi-plus-circle me-1"></i> Добавить товар
                </button>
            @endif
            
            <!-- Кнопка удаления для всех статусов -->
            <button type="button" 
                    class="btn btn-outline-danger delete-inventory-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteInventoryModal"
                    data-id="{{ $inventory->id }}"
                    data-name="{{ $inventory->name }}">
                <i class="bi bi-trash me-1"></i> Удалить инвентаризацию
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        // Вычисляем общие финансовые итоги
        $totalLoss = 0;
        $totalGain = 0;
        $hasNegativeItems = false;
        $hasPositiveItems = false;
    @endphp

    <!-- Сводка по финансам -->
    @if(!$inventory->items->isEmpty())
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Финансовый итог инвентаризации</h6>
                        @foreach($inventory->items as $item)
                            @php
                                $product = $item->product;
                                $difference = $item->actual_quantity - $item->system_quantity;
                                
                                // Используем поле cost (себестоимость)
                                $costPrice = $product->cost ?? 0;
                                
                                // Рассчитываем финансовую разницу (отрицательная = убыток, положительная = прибыль)
                                if ($difference < 0) {
                                    // Потеря товара (убыток)
                                    $itemLoss = abs($difference) * $costPrice;
                                    $totalLoss += $itemLoss;
                                    $hasNegativeItems = true;
                                } elseif ($difference > 0) {
                                    // Излишек товара (прибыль или ошибка в учете)
                                    $itemGain = $difference * $costPrice;
                                    $totalGain += $itemGain;
                                    $hasPositiveItems = true;
                                }
                            @endphp
                        @endforeach
                        
                        @if($hasNegativeItems || $hasPositiveItems)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Общий убыток:</span>
                                <span class="text-danger fw-bold">{{ number_format($totalLoss, 2) }} ₽</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Общий излишек:</span>
                                <span class="text-success fw-bold">{{ number_format($totalGain, 2) }} ₽</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Итоговый баланс:</span>
                                <span class="fw-bold fs-5 {{ ($totalGain - $totalLoss) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($totalGain - $totalLoss, 2) }} ₽
                                </span>
                            </div>
                        @else
                            <div class="text-center text-muted">
                                Нет расхождений в количестве товаров
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Список товаров -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">
                <i class="bi bi-box-seam me-2"></i>Товары в инвентаризации
            </h5>
        </div>
        
        <div class="card-body p-0">
            @if($inventory->items->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-box display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет товаров в инвентаризации</p>
                    @if($inventory->isCreated())
                        <button type="button" 
                                class="btn btn-primary mt-2"
                                data-bs-toggle="modal"
                                data-bs-target="#addItemModal">
                            <i class="bi bi-plus-circle me-1"></i> Добавить первый товар
                        </button>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Товар</th>
                                <th class="text-center">Системное кол-во</th>
                                <th class="text-center">Фактическое кол-во</th>
                                <th class="text-center">Разница</th>
                                <th class="text-center">Финансовая разница</th>
                                @if($inventory->isCreated())
                                    <th class="text-end">Действия</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventory->items as $item)
                            @php
                                $product = $item->product;
                                $unit = $product->unit ?? 'шт';
                                
                                // Используем поле cost (себестоимость)
                                $costPrice = $product->cost ?? 0;
                                
                                // Функция для форматирования чисел без лишних нулей
                                $formatQuantity = function($quantity) {
                                    $formatted = number_format($quantity, 0, '.', '');
                                    if (fmod($quantity, 1) !== 0.0) {
                                        $formatted = rtrim(number_format($quantity, 3, '.', ''), '0');
                                        if (substr($formatted, -1) === '.') {
                                            $formatted = substr($formatted, 0, -1);
                                        }
                                    }
                                    return $formatted;
                                };
                                
                                // Функция для форматирования конвертации
                                $formatConversion = function($quantity, $unit) use ($formatQuantity) {
                                    if ($unit === 'мл' && $quantity >= 1000) {
                                        $converted = $quantity / 1000;
                                        return $formatQuantity($converted) . ' л';
                                    } elseif ($unit === 'г' && $quantity >= 1000) {
                                        $converted = $quantity / 1000;
                                        return $formatQuantity($converted) . ' кг';
                                    } elseif ($unit === 'л' && $quantity < 1 && $quantity > 0) {
                                        $converted = $quantity * 1000;
                                        return $formatQuantity($converted) . ' мл';
                                    } elseif ($unit === 'кг' && $quantity < 1 && $quantity > 0) {
                                        $converted = $quantity * 1000;
                                        return $formatQuantity($converted) . ' г';
                                    }
                                    return null;
                                };
                                
                                $formattedSystem = $formatQuantity($item->system_quantity);
                                $formattedActual = $formatQuantity($item->actual_quantity);
                                $difference = $item->actual_quantity - $item->system_quantity;
                                $formattedDiff = $formatQuantity(abs($difference));
                                
                                $systemConverted = $formatConversion($item->system_quantity, $unit);
                                $actualConverted = $formatConversion($item->actual_quantity, $unit);
                                $diffConverted = $difference != 0 ? $formatConversion(abs($difference), $unit) : null;
                                
                                // Рассчитываем финансовую разницу
                                $financialDifference = 0;
                                if ($difference != 0) {
                                    $financialDifference = $difference * $costPrice;
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $item->product->name }}</strong>
                                        <div class="text-muted small">
                                            {{ $unit }}
                                            @if($product->packaging > 1 && $unit !== 'шт')
                                                ({{ $formatQuantity($product->packaging) }} {{ $unit }}/уп.)
                                            @endif
                                            <div class="mt-1">
                                                <small>Себестоимость: {{ number_format($costPrice, 2) }} ₽/{{ $unit }}</small>
                                                @if($product->price)
                                                    <br><small>Цена продажи: {{ number_format($product->price, 2) }} ₽/{{ $unit }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div>
                                        <span class="badge bg-secondary">{{ $formattedSystem }} {{ $unit }}</span>
                                        @if($systemConverted)
                                            <div class="text-muted small">
                                                ≈ {{ $systemConverted }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($inventory->isCreated())
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary edit-quantity-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editQuantityModal"
                                                data-item-id="{{ $item->id }}"
                                                data-product-name="{{ $item->product->name }}"
                                                data-actual-quantity="{{ $item->actual_quantity }}">
                                            {{ $formattedActual }} {{ $unit }}
                                            @if($actualConverted)
                                                <div class="text-muted small">
                                                    ≈ {{ $actualConverted }}
                                                </div>
                                            @endif
                                        </button>
                                    @else
                                        <div>
                                            <span class="badge bg-primary">{{ $formattedActual }} {{ $unit }}</span>
                                            @if($actualConverted)
                                                <div class="text-muted small">
                                                    ≈ {{ $actualConverted }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($difference != 0)
                                        <div>
                                            <span class="badge bg-{{ $difference > 0 ? 'info' : 'danger' }}">
                                                {{ $difference > 0 ? '+' : '-' }}{{ $formattedDiff }} {{ $unit }}
                                            </span>
                                            @if($diffConverted)
                                                <div class="text-muted small">
                                                    ≈ {{ $difference > 0 ? '+' : '-' }}{{ $diffConverted }}
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">0 {{ $unit }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($difference != 0)
                                        <div class="{{ $financialDifference > 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>{{ $financialDifference > 0 ? '+' : '' }}{{ number_format($financialDifference, 2) }} ₽</strong>
                                            <div class="text-muted small">
                                                @ {{ number_format($costPrice, 2) }} ₽/{{ $unit }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">0 ₽</span>
                                    @endif
                                </td>
                                @if($inventory->isCreated())
                                    <td class="text-end">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger remove-item-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#removeItemModal"
                                                data-item-id="{{ $item->id }}"
                                                data-product-name="{{ $item->product->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                        @if($hasNegativeItems || $hasPositiveItems)
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Итого по инвентаризации:</th>
                                <th class="text-center">
                                    <div class="{{ ($totalGain - $totalLoss) >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ ($totalGain - $totalLoss) >= 0 ? '+' : '' }}{{ number_format($totalGain - $totalLoss, 2) }} ₽</strong>
                                        <div class="text-muted small">
                                            (убыток: {{ number_format($totalLoss, 2) }} ₽,
                                            излишек: {{ number_format($totalGain, 2) }} ₽)
                                        </div>
                                    </div>
                                </th>
                                @if($inventory->isCreated())
                                    <th></th>
                                @endif
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка модалки редактирования количества
    const editQuantityModal = document.getElementById('editQuantityModal');
    if (editQuantityModal) {
        editQuantityModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-quantity-btn')) {
                document.getElementById('editProductName').textContent = button.dataset.productName;
                document.getElementById('edit_actual_quantity').value = button.dataset.actualQuantity;
                document.getElementById('editQuantityForm').action = `/inventories/{{ $inventory->id }}/items/${button.dataset.itemId}`;
            }
        });
    }

    // Обработка модалки удаления товара
    const removeItemModal = document.getElementById('removeItemModal');
    if (removeItemModal) {
        removeItemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('remove-item-btn')) {
                document.getElementById('removeProductName').textContent = button.dataset.productName;
                document.getElementById('removeItemForm').action = `/inventories/{{ $inventory->id }}/items/${button.dataset.itemId}`;
            }
        });
    }
});
</script>

@include('inventories.modals.add-item')
@include('inventories.modals.edit-quantity')
@include('inventories.modals.remove-item')
@include('inventories.modals.close-inventory')

@endsection