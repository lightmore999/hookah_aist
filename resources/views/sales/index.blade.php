@extends('layouts.app')

@section('title', 'Продажи')

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-receipt me-2"></i>Продажи
            </h1>
            <p class="text-muted mb-0 small">Управление продажами</p>
        </div>
        
        <div class="d-flex gap-2">
            <!-- Фильтры -->
            <select class="form-select form-select-sm w-auto" id="statusFilter">
                <option value="">Все статусы</option>
                <option value="new">Новый</option>
                <option value="in_progress">В работе</option>
                <option value="completed">Завершен</option>
                <option value="cancelled">Отменен</option>
            </select>
            
           <form action="{{ route('sales.store') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Новый заказ
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Всего продаж</h6>
                    <h3 class="card-title mb-0">{{ $sales->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">В работе</h6>
                    <h3 class="card-title mb-0">{{ $sales->where('status', 'in_progress')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Завершено</h6>
                    <h3 class="card-title mb-0">{{ $sales->where('status', 'completed')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Общая выручка</h6>
                    <h3 class="card-title mb-0">{{ number_format($sales->sum('total'), 2) }} ₽</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Таблица продаж -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($sales->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-cart display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет продаж. Создайте первую!</p>
                    <!-- Меняем эту кнопку тоже: -->
                    <form action="{{ route('sales.store') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle me-1"></i> Создать заказ
                        </button>
                    </form>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Склад</th>
                                <th>Стол</th> <!-- Новый столбец -->
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                            <tr data-status="{{ $sale->status }}">
                                <td>
                                    <strong>#{{ $sale->id }}</strong>
                                </td>
                                <td>
                                    @if($sale->client)
                                        {{ $sale->client->name }}
                                    @else
                                        <span class="text-muted">Гость</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sale->warehouse)
                                        <span class="badge bg-secondary">{{ $sale->warehouse->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sale->table_id && $sale->table)
                                        <span class="badge bg-info">
                                            <i class="bi bi-table"></i> {{ $sale->table->table_number }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($sale->total, 2) }} ₽</strong>
                                    @if($sale->discount > 0)
                                        <br>
                                        <small class="text-success">Скидка: {{ number_format($sale->discount, 2) }} ₽</small>
                                    @endif
                                </td>
                                <td>
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
                                    <span class="badge bg-{{ $statusColors[$sale->status] ?? 'secondary' }}">
                                        {{ $statusTexts[$sale->status] ?? $sale->status }}
                                    </span>
                                </td>
                                <td>
                                    {{ $sale->sale_date->format('d.m.Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('sales.show', $sale->id) }}" 
                                       class="btn btn-outline-primary btn-sm"
                                       title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-warning btn-sm edit-sale-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editSaleModal"
                                            data-id="{{ $sale->id }}"
                                            data-status="{{ $sale->status }}"
                                            data-client="{{ $sale->client_id }}"
                                            data-warehouse="{{ $sale->warehouse_id }}"
                                            data-total="{{ $sale->total }}"
                                            data-discount="{{ $sale->discount }}"
                                            data-payment-method="{{ $sale->payment_method }}"
                                            data-comment="{{ $sale->comment }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-sale-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteSaleModal"
                                            data-id="{{ $sale->id }}"
                                            data-number="#{{ $sale->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация -->
                @if($sales->hasPages())
                <div class="card-footer border-top-0">
                    {{ $sales->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Фильтр по статусу
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Редактирование продажи
    const editSaleModal = document.getElementById('editSaleModal');
    if (editSaleModal) {
        editSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-sale-btn')) {
                console.log('Opening edit modal for sale:', button.dataset.id);
                
                // 1. Устанавливаем action формы
                const form = document.getElementById('editSaleForm');
                const saleId = button.dataset.id;
                form.action = `/sales/${saleId}`;
                
                // 2. Заполняем hidden поле
                document.getElementById('edit_sale_id').value = saleId;
                
                // 3. Заполняем статус
                const statusSelect = document.getElementById('edit_status');
                if (statusSelect && button.dataset.status) {
                    statusSelect.value = button.dataset.status;
                }
                
                // 4. Заполняем клиента
                const clientSelect = document.getElementById('edit_client_id');
                if (clientSelect && button.dataset.client) {
                    const clientId = button.dataset.client;
                    clientSelect.value = clientId;
                    
                    // Проверяем, есть ли такой option
                    const optionExists = Array.from(clientSelect.options).some(opt => opt.value === clientId);
                    if (!optionExists && clientId) {
                        console.warn('Client ID', clientId, 'not found in select options');
                    }
                }
                
                // 5. Заполняем склад
                const warehouseSelect = document.getElementById('edit_warehouse_id');
                if (warehouseSelect && button.dataset.warehouse) {
                    const warehouseId = button.dataset.warehouse;
                    warehouseSelect.value = warehouseId;
                    
                    // Если не нашли option, берем первый
                    if (!warehouseSelect.value && warehouseSelect.options.length > 0) {
                        warehouseSelect.value = warehouseSelect.options[0].value;
                    }
                }
                
                // 6. Заполняем скидку
                const discountInput = document.getElementById('edit_discount');
                if (discountInput && button.dataset.discount) {
                    discountInput.value = parseFloat(button.dataset.discount) || 0;
                }
                
                // 7. Заполняем способ оплаты
                const paymentSelect = document.getElementById('edit_payment_method');
                if (paymentSelect && button.dataset.paymentMethod) {
                    paymentSelect.value = button.dataset.paymentMethod;
                }
                
                // 8. Заполняем комментарий
                const commentInput = document.getElementById('edit_comment');
                if (commentInput && button.dataset.comment) {
                    commentInput.value = button.dataset.comment;
                }
                
                console.log('Form configured:', {
                    action: form.action,
                    client: button.dataset.client,
                    warehouse: button.dataset.warehouse,
                    status: button.dataset.status
                });
            }
        });
    }
    
    // Удаление продажи
    const deleteSaleModal = document.getElementById('deleteSaleModal');
    if (deleteSaleModal) {
        deleteSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-sale-btn')) {
                document.getElementById('deleteSaleNumber').textContent = button.dataset.number;
                document.getElementById('deleteSaleForm').action = `/sales/${button.dataset.id}`;
            }
        });
    }
});
</script>

@include('sales.modals.edit', ['clients' => $clients, 'warehouses' => $warehouses])
@include('sales.modals.delete')

@endsection