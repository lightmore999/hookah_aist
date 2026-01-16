@extends('layouts.app')

@section('title', 'Продажа #' . $sale->id)

@section('content')
<div class="container-fluid py-4">
    
    <!-- Шапка -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Продажи</a></li>
                    <li class="breadcrumb-item active">Продажа #{{ $sale->id }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="bi bi-receipt me-2"></i>Продажа #{{ $sale->id }}
                @php
                    $statusColors = [
                        'new' => 'primary',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $statusTexts = [
                        'new' => 'Новый',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершен',
                        'cancelled' => 'Отменен'
                    ];
                @endphp
                <span class="badge bg-{{ $statusColors[$sale->status] ?? 'secondary' }} ms-2">
                    {{ $statusTexts[$sale->status] ?? $sale->status }}
                </span>
            </h1>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
            
            @if($sale->status != 'completed' && $sale->status != 'cancelled')
                <button type="button" 
                        class="btn btn-outline-warning edit-sale-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editSaleModal"
                        data-id="{{ $sale->id }}"
                        data-client="{{ $sale->client_id }}"
                        data-warehouse="{{ $sale->warehouse_id }}"
                        data-comment="{{ $sale->comment }}">
                    <i class="bi bi-pencil me-1"></i> Изменить
                </button>
                
                <!-- Кнопка закрытия продажи -->
                <button type="button" 
                        class="btn btn-success close-sale-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#closeSaleModal"
                        data-id="{{ $sale->id }}"
                        data-items-total="{{ $sale->items->sum(function($item) { return $item->quantity * $item->unit_price; }) }}"
                        data-hookahs-total="{{ $sale->hookahs->sum('price') }}"
                        data-discount="{{ $sale->discount }}"
                        data-payment-method="{{ $sale->payment_method }}"
                        data-comment="{{ $sale->comment }}"
                        data-client-id="{{ $sale->client_id }}"
                        data-client-name="{{ $sale->client->name ?? '' }}"
                        data-client-bonus-points="{{ $sale->client->bonus_points ?? 0 }}"
                        data-client-max-spend-percent="{{ $sale->client && $sale->client->bonusCard ? $sale->client->bonusCard->MaxSpendPercent : 50 }}">
                    <i class="bi bi-check-circle me-1"></i> Завершить продажу 
                </button>
            @else
                <!-- Если продажа уже закрыта, показываем статус -->
                <span class="btn btn-outline-success disabled">
                    <i class="bi bi-check-circle me-1"></i> Продажа завершена
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="row">
        <!-- Основная информация -->
        <div class="col-lg-8">
            <!-- Товары в продаже -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-cart me-2"></i>Товары в продаже</h5>
                </div>
                <div class="card-body">
                    @if($sale->items->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">Нет товаров в продаже</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Товар</th>
                                        <th class="text-center">Кол-во</th>
                                        <th class="text-end">Цена за ед.</th>
                                        <th class="text-end">Сумма</th>
                                        @if($sale->status != 'completed')
                                            <th class="text-end">Действия</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->items as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->product->name ?? 'Товар' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ $item->product->unit ?? 'шт' }}
                                                @if($item->product->packaging > 1 && $item->product->unit !== 'шт')
                                                    (уп. {{ $item->product->packaging }} {{ $item->product->unit }})
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            {{ $item->quantity }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($item->unit_price, 2) }} ₽
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($item->quantity * $item->unit_price, 2) }} ₽</strong>
                                        </td>
                                        @if($sale->status != 'completed')
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-outline-warning btn-sm edit-item-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editItemModal"
                                                        data-item-id="{{ $item->id }}"
                                                        data-product-name="{{ $item->product->name ?? 'Товар' }}"
                                                        data-quantity="{{ $item->quantity }}"
                                                        data-unit-price="{{ $item->unit_price }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm remove-item-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#removeItemModal"
                                                        data-item-id="{{ $item->id }}"
                                                        data-item-name="{{ $item->product->name ?? 'Товар' }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Итого по товарам:</strong></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($sale->items->sum(function($item) { return $item->quantity * $item->unit_price; }), 2) }} ₽</strong>
                                        </td>
                                        @if($sale->status != 'completed')
                                            <td></td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                    
                    <!-- Кнопка добавления товара -->
                    @if($sale->status != 'completed')
                        <div class="mt-3">
                            <button type="button" 
                                    class="btn btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addItemModal">
                                <i class="bi bi-plus-circle me-1"></i> Добавить товар
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            @if($sale->table_id)
            <div class="card border-info border-0 shadow-sm mb-4">
                <div class="card-header bg-info bg-opacity-10">
                    <h5 class="mb-0 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cup-straw me-2"></i>Кальяны</span>
                        @if($sale->status != 'completed')
                        <button type="button" 
                                class="btn btn-sm btn-info"
                                data-bs-toggle="modal"
                                data-bs-target="#addHookahModal">
                            <i class="bi bi-plus-circle"></i> Добавить кальян
                        </button>
                        @endif
                    </h5>
                </div>
                
                <div class="card-body">
                    @if($sale->hookahs->isEmpty())
                        <p class="text-muted mb-0">Кальяны не добавлены</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th class="text-end">Цена</th>
                                        <th class="text-center">Добавлен</th>
                                        @if($sale->status != 'completed')
                                        <th class="text-end">Действия</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->hookahs as $hookah)
                                    <tr>
                                        <td>{{ $hookah->name }}</td>
                                        <td class="text-end">{{ number_format($hookah->price, 2) }} ₽</td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ $hookah->pivot->created_at->format('H:i') }}</small>
                                        </td>
                                        @if($sale->status != 'completed')
                                        <td class="text-end">
                                            <form action="{{ route('sales.hookahs.destroy', ['sale' => $sale->id, 'hookah' => $hookah->id]) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить кальян?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td class="text-end"><strong>Итого по кальянам:</strong></td>
                                        <td class="text-end">
                                            <strong class="text-info">{{ number_format($sale->hookahs->sum('price'), 2) }} ₽</strong>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
        
        <!-- Боковая панель -->
        <div class="col-lg-4">
            <!-- Информация о продаже -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Информация о продаже</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Клиент</small>
                        <strong>{{ $sale->client->name ?? 'Гость' }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Склад</small>
                        <strong>{{ $sale->warehouse->name ?? 'Не указан' }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Способ оплаты</small>
                        @if($sale->payment_method)
                            @php
                                $paymentMethods = [
                                    'cash' => 'Наличные',
                                    'card' => 'Карта',
                                    'online' => 'Онлайн',
                                    'terminal' => 'Терминал'
                                ];
                            @endphp
                            <strong>{{ $paymentMethods[$sale->payment_method] ?? $sale->payment_method }}</strong>
                        @else
                            <span class="text-muted">Не указан</span>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Дата продажи</small>
                        <strong>{{ $sale->sale_date->format('d.m.Y H:i') }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Дата создания</small>
                        <strong>{{ $sale->created_at->format('d.m.Y H:i') }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Последнее обновление</small>
                        <strong>{{ $sale->updated_at->format('d.m.Y H:i') }}</strong>
                    </div>
                    
                    @if($sale->comment)
                    <div class="mb-3">
                        <small class="text-muted d-block">Комментарий</small>
                        <p class="mb-0">{{ $sale->comment }}</p>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Финансовая информация -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Финансы</h5>
                </div>
                <div class="card-body">
                     <div class="d-flex justify-content-between mb-2">
                        <span>Сумма товаров:</span>
                        <strong>{{ number_format($sale->items->sum(function($item) { return $item->quantity * $item->unit_price; }), 2) }} ₽</strong>
                    </div>
                    
                    <!-- Добавьте эту строку для кальянов -->
                    @if($sale->hookahs->count() > 0)
                    <div class="d-flex justify-content-between mb-2 text-info">
                        <span>Кальяны:</span>
                        <strong>+{{ number_format($sale->hookahs->sum('price'), 2) }} ₽</strong>
                    </div>
                    @endif
                    
                    @if($sale->discount > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Скидка:</span>
                        <strong>-{{ number_format($sale->discount, 2) }} ₽</strong>
                    </div>
                    @endif

                    @if($sale->used_bonus_points > 0)
                    <div class="d-flex justify-content-between mb-2 text-info">
                        <span>Скидка бонусами:</span>
                        <strong>-{{ number_format($sale->used_bonus_points, 2) }} ₽</strong>
                    </div>
                    @endif
                    
                    <!-- Получаем информацию о начисленных бонусах из истории -->
                    @php
                        $bonusAwardedForSale = \App\Models\BonusHistory::where('sale_id', $sale->id)
                            ->where('operation_type', 'credit')
                            ->sum('amount');
                    @endphp

                    @if($bonusAwardedForSale > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span>Начислено бонусов:</span>
                        <strong class="text-warning">+{{ number_format($bonusAwardedForSale, 0) }} баллов</strong>
                    </div>
                    @endif
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="h5">Итого к оплате:</span>
                        <span class="h5 text-primary">{{ number_format($sale->total, 2) }} ₽</span>
                    </div>
                    
                    <!-- Дополнительная проверка: расчет на лету -->
                    <div class="mt-3 small text-muted">
                        <small>Проверка: 
                            Товары: {{ number_format($sale->items->sum(function($item) { return $item->quantity * $item->unit_price; }), 2) }} ₽
                            @if($sale->hookahs->count() > 0)
                                + Кальяны: {{ number_format($sale->hookahs->sum('price'), 2) }} ₽
                            @endif
                            @if($sale->discount > 0)
                                - Скидка: {{ number_format($sale->discount, 2) }} ₽
                            @endif
                            = {{ number_format($sale->total, 2) }} ₽
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальные окна -->ы
@include('sales.modals.edit')
@include('sales.modals.close')
@include('sales.modals.add-item')
@include('sales.modals.edit-item')
@include('sales.modals.remove-item')
@include('sales.modals.add-hookah')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Заполнение модалки редактирования продажи
    const editSaleModal = document.getElementById('editSaleModal');
    if (editSaleModal) {
        editSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-sale-btn')) {
                document.getElementById('edit_sale_id').value = button.dataset.id;
                document.getElementById('edit_client_id').value = button.dataset.client;
                document.getElementById('edit_warehouse_id').value = button.dataset.warehouse;
                document.getElementById('edit_comment').value = button.dataset.comment;
                document.getElementById('editSaleForm').action = `/sales/${button.dataset.id}`;
            }
        });
    }

    // Редактирование товара
    const editItemModal = document.getElementById('editItemModal');
    if (editItemModal) {
        editItemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-item-btn')) {
                const saleId = {{ $sale->id }};
                const itemId = button.dataset.itemId;
                
                document.getElementById('editItemProductName').textContent = button.dataset.productName;
                document.getElementById('editQuantity').value = button.dataset.quantity;
                document.getElementById('editUnitPrice').value = button.dataset.unitPrice;
                
                // Устанавливаем unit если есть в data-атрибутах
                if (button.dataset.unit) {
                    const unitLabel = document.getElementById('editQuantityUnit');
                    if (unitLabel) {
                        unitLabel.textContent = button.dataset.unit;
                    }
                }
                
                // Устанавливаем action с правильными параметрами
                document.getElementById('editItemForm').action = `/sales/${saleId}/items/${itemId}`;
            }
        });
    }
    
    // Удаление товара
    const removeItemModal = document.getElementById('removeItemModal');
    if (removeItemModal) {
        removeItemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('remove-item-btn')) {
                const saleId = {{ $sale->id }};
                const itemId = button.dataset.itemId;
                
                document.getElementById('removeItemName').textContent = button.dataset.itemName;
                document.getElementById('removeItemForm').action = `/sales/${saleId}/items/${itemId}`;
            }
        });
    }
    
    // Добавляем обработчики для валидации количества в editItemModal
    const editQuantityInput = document.getElementById('editQuantity');
    if (editQuantityInput) {
        editQuantityInput.addEventListener('input', function() {
            const unit = document.getElementById('editQuantityUnit').textContent;
            if (unit === 'шт' && this.value.includes('.')) {
                // Для штучных товаров нельзя вводить дробные
                this.value = Math.floor(this.value);
            }
        });
    }
});
</script>

@endsection