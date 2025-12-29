@extends('layouts.app')

@section('title', 'Заказ #' . $order->IDOrder)

@section('content')
<div class="container-fluid py-4">
    
    <!-- Шапка -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Заказы</a></li>
                    <li class="breadcrumb-item active">Заказ #{{ $order->IDOrder }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="bi bi-receipt me-2"></i>Заказ #{{ $order->IDOrder }}
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
                <span class="badge bg-{{ $statusColors[$order->Status] ?? 'secondary' }} ms-2">
                    {{ $statusTexts[$order->Status] ?? $order->Status }}
                </span>
            </h1>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
            
            @if($order->Status != 'completed' && $order->Status != 'cancelled')
                <button type="button" 
                        class="btn btn-outline-warning edit-order-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editOrderModal"
                        data-id="{{ $order->IDOrder }}"
                        data-status="{{ $order->Status }}"
                        data-client="{{ $order->IDClient }}"
                        data-table="{{ $order->IDTable }}"
                        data-warehouse="{{ $order->IDWarehouses }}"
                        data-total="{{ $order->Total }}"
                        data-discount="{{ $order->Discount }}"
                        data-tips="{{ $order->Tips }}"
                        data-onloan="{{ $order->On_loan }}"
                        data-user="{{ $order->UserId }}"
                        data-comment="{{ $order->Comment }}">
                    <i class="bi bi-pencil me-1"></i> Изменить
                </button>
                
                <!-- Кнопка закрытия заказа -->
                <button type="button" 
                        class="btn btn-success close-order-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#closeOrderModal"
                        data-id="{{ $order->IDOrder }}"
                        data-items-total="{{ $order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) + $order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }) }}"
                        data-tips="{{ $order->Tips }}"
                        data-discount="{{ $order->Discount }}"
                        data-items-total="{{ $order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) + $order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }) + $order->recipeItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) }}"
                        data-paymentmethod="{{ $order->PaymentMethod }}"
                        data-comment="{{ $order->Comment }}">
                    <i class="bi bi-check-circle me-1"></i> Завершить заказ
                </button>
            @else
                <!-- Если заказ уже закрыт, показываем статус -->
                <span class="btn btn-outline-success disabled">
                    <i class="bi bi-check-circle me-1"></i> Заказ закрыт
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
            <!-- Товары в заказе -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-cart me-2"></i>Товары в заказе</h5>
                </div>
                <div class="card-body">
                    @if($order->orderItems->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">Нет товаров в заказе</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Товар</th>
                                        <th class="text-center">Кол-во</th>
                                        <th class="text-end">Цена</th>
                                        <th class="text-end">Сумма</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->orderItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->product->name ?? 'Товар' }}</strong>
                                            <br>
                                            <small class="text-muted">Артикул: {{ $item->product->sku ?? '—' }}</small>
                                        </td>
                                        <td class="text-center">
                                            {{ $item->Quantity }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($item->UnitPrice, 2) }} ₽
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($item->Quantity * $item->UnitPrice, 2) }} ₽</strong>
                                        </td>
                                        @if($order->Status != 'completed')
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-outline-warning btn-sm edit-item-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editItemModal"
                                                        data-item-id="{{ $item->IDHookah }}"
                                                        data-product-name="{{ $item->product->name ?? 'Товар' }}"
                                                        data-quantity="{{ $item->Quantity }}"
                                                        data-unitprice="{{ $item->UnitPrice }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm remove-item-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#removeItemModal"
                                                        data-item-id="{{ $item->IDHookah }}"
                                                        data-item-name="{{ $item->product->name ?? 'Товар' }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        @else
                                            <td class="text-end">
                                                <span class="text-muted small">Завершен</span>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Итого по товарам:</strong></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }), 2) }} ₽</strong>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                    
                    <!-- Кнопка добавления товара -->
                    @if($order->Status != 'completed')
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
            
            <!-- Кальяны в заказе -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-thermometer-sun me-2"></i>Кальяны в заказе</h5>
                </div>
                <div class="card-body">
                    @if($order->hookahItems->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">Нет кальянов в заказе</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Кальян</th>
                                        <th class="text-end">Цена</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->hookahItems as $hookahItem)
                                    <tr>
                                        <td>
                                            <strong>{{ $hookahItem->hookah->name ?? 'Кальян' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                Себестоимость: {{ number_format($hookahItem->hookah->cost ?? 0, 2) }} ₽
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($hookahItem->hookah->price ?? 0, 2) }} ₽</strong>
                                        </td>
                                        @if($order->Status != 'completed')
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm remove-hookah-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#removeHookahModal"
                                                        data-item-id="{{ $hookahItem->IDHookahOrderItem }}"
                                                        data-item-name="{{ $hookahItem->hookah->name ?? 'Кальян' }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        @else
                                            <td class="text-end">
                                                <span class="text-muted small">Завершен</span>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td class="text-end"><strong>Итого по кальянам:</strong></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }), 2) }} ₽</strong>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                    
                    <!-- Кнопка добавления кальяна -->
                    @if($order->Status != 'completed')
                        <div class="mt-3">
                            <button type="button" 
                                    class="btn btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addHookahModal">
                                <i class="bi bi-plus-circle me-1"></i> Добавить кальян
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Рецепты в заказе -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-cup-straw me-2"></i>Рецепты в заказе</h5>
                </div>
                <div class="card-body">
                    @if($order->recipeItems->isEmpty())
                        <div class="text-center py-4">
                            <p class="text-muted">Нет рецептов в заказе</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Рецепт</th>
                                        <th class="text-center">Кол-во</th>
                                        <th class="text-end">Цена за шт</th>
                                        <th class="text-end">Сумма</th>
                                        <th class="text-end"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->recipeItems as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item->recipe->name ?? 'Рецепт' }}</strong>
                                            @if($item->recipe->description)
                                                <br>
                                                <small class="text-muted">{{ Str::limit($item->recipe->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $item->Quantity }}
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($item->UnitPrice, 2) }} ₽
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($item->Quantity * $item->UnitPrice, 2) }} ₽</strong>
                                        </td>
                                        @if($order->Status != 'completed')
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-outline-warning btn-sm edit-recipe-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editRecipeModal"
                                                        data-item-id="{{ $item->IDSales }}"
                                                        data-recipe-name="{{ $item->recipe->name ?? 'Рецепт' }}"
                                                        data-quantity="{{ $item->Quantity }}"
                                                        data-unitprice="{{ $item->UnitPrice }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-sm remove-recipe-btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#removeRecipeModal"
                                                        data-item-id="{{ $item->IDSales }}"
                                                        data-item-name="{{ $item->recipe->name ?? 'Рецепт' }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        @else
                                            <td class="text-end">
                                                <span class="text-muted small">Завершен</span>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Итого по рецептам:</strong></td>
                                        <td class="text-end">
                                            <strong>{{ number_format($order->recipeItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }), 2) }} ₽</strong>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                    
                    <!-- Кнопка добавления рецепта -->
                    @if($order->Status != 'completed')
                        <div class="mt-3">
                            <button type="button" 
                                    class="btn btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addRecipeModal">
                                <i class="bi bi-plus-circle me-1"></i> Добавить рецепт
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
        
        <!-- Боковая панель -->
        <div class="col-lg-4">
            <!-- Информация о заказе -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Информация о заказе</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Клиент</small>
                        <strong>{{ $order->client->name ?? 'Гость' }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Столик</small>
                        <strong>{{ $order->table->name ?? 'Не указан' }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Способ оплаты</small>
                        @if($order->PaymentMethod)
                            @php
                                $paymentMethods = [
                                    'cash' => 'Наличные',
                                    'card' => 'Карта'
                                ];
                            @endphp
                            <strong>{{ $paymentMethods[$order->PaymentMethod] ?? $order->PaymentMethod }}</strong>
                        @else
                            <span class="text-muted">Не указан</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Склад</small>
                        <strong>{{ $order->warehouse->name ?? 'Не указан' }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Ответственный</small>
                        <strong>{{ $order->user->name ?? 'Не указан' }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Дата создания</small>
                        <strong>{{ $order->created_at->format('d.m.Y H:i') }}</strong>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Последнее обновление</small>
                        <strong>{{ $order->updated_at->format('d.m.Y H:i') }}</strong>
                    </div>
                    
                    @if($order->Comment)
                    <div class="mb-3">
                        <small class="text-muted d-block">Комментарий</small>
                        <p class="mb-0">{{ $order->Comment }}</p>
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
                        <span>Товары:</span>
                        <strong>{{ number_format($order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }), 2) }} ₽</strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Кальяны:</span>
                        <strong>{{ number_format($order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }), 2) }} ₽</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Рецепты:</span>
                        <strong>{{ number_format($order->recipeItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }), 2) }} ₽</strong>
                    </div>
                                        
                    <div class="d-flex justify-content-between mb-2">
                        <span>Сумма позиций:</span>
                        <strong>{{ number_format($order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) + $order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }) + $order->recipeItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }), 2) }} ₽</strong>
                    </div>
                    
                    @if($order->Discount > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Скидка:</span>
                        <strong>-{{ number_format($order->Discount, 2) }} ₽</strong>
                    </div>
                    @endif
                    
                    @if($order->On_loan > 0)
                    <div class="d-flex justify-content-between mb-2 text-warning">
                        <span>В долг:</span>
                        <strong>{{ number_format($order->On_loan, 2) }} ₽</strong>
                    </div>
                    @endif
                    
                    @if($order->Tips > 0)
                    <div class="d-flex justify-content-between mb-2 text-info">
                        <span>Чаевые:</span>
                        <strong>+{{ number_format($order->Tips, 2) }} ₽</strong>
                    </div>
                    @endif
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <span class="h5">Итого к оплате:</span>
                        <span class="h5 text-primary">{{ number_format($order->Total, 2) }} ₽</span>
                    </div>
                    
                    <!-- Дополнительная проверка: расчет на лету -->
                    <div class="mt-3 small text-muted">
                        <small>Проверка: 
                            {{ number_format($order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) + $order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }) + $order->recipeItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }), 2) }}
                            - {{ number_format($order->Discount, 2) }}
                            + {{ number_format($order->Tips, 2) }}
                            = {{ number_format($order->orderItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) + $order->hookahItems->sum(function($item) { return $item->hookah->price ?? 0; }) + $order->recipeItems->sum(function($item) { return $item->Quantity * $item->UnitPrice; }) - $order->Discount + $order->Tips, 2) }} ₽
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальные окна -->
@include('orders.modals.edit')
@include('orders.modals.close-order')
@include('orders.modals.add-item')
@include('orders.modals.edit-item')
@include('orders.modals.remove-item')
@include('orders.modals.add-hookah')
@include('orders.modals.remove-hookah')
@include('orders.modals.add-recipe')
@include('orders.modals.edit-recipe')
@include('orders.modals.remove-recipe')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Заполнение модалки редактирования заказа
    const editOrderModal = document.getElementById('editOrderModal');
    if (editOrderModal) {
        editOrderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-order-btn')) {
                document.getElementById('edit_IDOrder').value = button.dataset.id;
                document.getElementById('edit_Status').value = button.dataset.status;
                document.getElementById('edit_IDClient').value = button.dataset.client;
                document.getElementById('edit_IDTable').value = button.dataset.table;
                document.getElementById('edit_IDWarehouses').value = button.dataset.warehouse;
                document.getElementById('edit_Total').value = button.dataset.total;
                document.getElementById('edit_Discount').value = button.dataset.discount;
                document.getElementById('edit_Tips').value = button.dataset.tips;
                document.getElementById('edit_On_loan').value = button.dataset.onloan;
                document.getElementById('edit_UserId').value = button.dataset.user;
                document.getElementById('edit_Comment').value = button.dataset.comment;
                document.getElementById('editOrderForm').action = `/orders/${button.dataset.id}`;
            }
        });
    }
    
    // Закрытие заказа
    const closeOrderModal = document.getElementById('closeOrderModal');
    const itemsTotalElem = document.getElementById('itemsTotal');
    const finalTotalElem = document.getElementById('finalTotal');
    const closeTipsInput = document.getElementById('closeTips');
    const closeDiscountInput = document.getElementById('closeDiscount');
    
    let currentItemsTotal = 0;
    
    function calculateFinalTotal() {
        const tips = parseFloat(closeTipsInput.value) || 0;
        const discount = parseFloat(closeDiscountInput.value) || 0;
        const finalTotal = currentItemsTotal - discount + tips;
        
        itemsTotalElem.textContent = currentItemsTotal.toFixed(2) + ' ₽';
        finalTotalElem.textContent = finalTotal.toFixed(2) + ' ₽';
    }
    
    if (closeOrderModal) {
        closeOrderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('close-order-btn')) {
                // Устанавливаем ID заказа
                const orderId = button.dataset.id;
                document.getElementById('closeOrderId').textContent = orderId;
                document.getElementById('closeOrderForm').action = `/orders/${orderId}`;
                
                // Получаем сумму позиций (товары + кальяны)
                currentItemsTotal = parseFloat(button.dataset.itemsTotal) || 0;
                
                // Заполняем существующие значения
                document.getElementById('closeTips').value = button.dataset.tips || 0;
                document.getElementById('closeDiscount').value = button.dataset.discount || 0;
                document.getElementById('closePaymentMethod').value = button.dataset.paymentmethod || '';
                document.getElementById('closeComment').value = button.dataset.comment || '';
                
                // Рассчитываем итог
                calculateFinalTotal();
            }
        });
    }
    
    // Пересчет при изменении чаевых или скидки
    if (closeTipsInput) {
        closeTipsInput.addEventListener('input', calculateFinalTotal);
    }
    
    if (closeDiscountInput) {
        closeDiscountInput.addEventListener('input', calculateFinalTotal);
    }

    // Редактирование товара
    const editItemModal = document.getElementById('editItemModal');
    if (editItemModal) {
        editItemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-item-btn')) {
                document.getElementById('editItemProductName').textContent = button.dataset.productName;
                document.getElementById('editQuantity').value = button.dataset.quantity;
                document.getElementById('editUnitPrice').value = button.dataset.unitprice;
                document.getElementById('editItemForm').action = `/orders/{{ $order->IDOrder }}/product-items/${button.dataset.itemId}`;
            }
        });
    }
    
    // Удаление товара
    const removeItemModal = document.getElementById('removeItemModal');
    if (removeItemModal) {
        removeItemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('remove-item-btn')) {
                document.getElementById('removeItemName').textContent = button.dataset.itemName;
                document.getElementById('removeItemForm').action = `/orders/{{ $order->IDOrder }}/product-items/${button.dataset.itemId}`;
            }
        });
    }
    
    // Удаление кальяна
    const removeHookahModal = document.getElementById('removeHookahModal');
    if (removeHookahModal) {
        removeHookahModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('remove-hookah-btn')) {
                document.getElementById('removeHookahName').textContent = button.dataset.itemName;
                document.getElementById('removeHookahForm').action = `/orders/{{ $order->IDOrder }}/hookah-items/${button.dataset.itemId}`;
            }
        });
    }

    const editRecipeModal = document.getElementById('editRecipeModal');
    if (editRecipeModal) {
        editRecipeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-recipe-btn')) {
                document.getElementById('editRecipeName').textContent = button.dataset.recipeName;
                document.getElementById('editRecipeQuantity').value = button.dataset.quantity;
                document.getElementById('editRecipeUnitPrice').value = button.dataset.unitprice;
                document.getElementById('editRecipeForm').action = `/orders/{{ $order->IDOrder }}/recipe-items/${button.dataset.itemId}`;
            }
        });
    }

    // Удаление рецепта
    const removeRecipeModal = document.getElementById('removeRecipeModal');
    if (removeRecipeModal) {
        removeRecipeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('remove-recipe-btn')) {
                document.getElementById('removeRecipeName').textContent = button.dataset.itemName;
                document.getElementById('removeRecipeForm').action = `/orders/{{ $order->IDOrder }}/recipe-items/${button.dataset.itemId}`;
            }
        });
    }

});
</script>

@endsection