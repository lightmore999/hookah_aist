@extends('layouts.app')

@section('title', 'Заказы')

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-receipt me-2"></i>Заказы
            </h1>
            <p class="text-muted mb-0 small">Управление заказами кальянной</p>
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
            
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createOrderModal">
                <i class="bi bi-plus-circle me-1"></i> Новый заказ
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <!-- Вместо старой статистики - более простая -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Всего заказов</h6>
                    <h3 class="card-title mb-0">{{ $orders->total() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">В работе</h6>
                    <h3 class="card-title mb-0">{{ $orders->where('Status', 'in_progress')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Завершено</h6>
                    <h3 class="card-title mb-0">{{ $orders->where('Status', 'completed')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Таблица заказов -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($orders->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-cart display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет заказов. Создайте первый!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createOrderModal">
                        <i class="bi bi-plus-circle me-1"></i> Создать заказ
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Столик</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr data-status="{{ $order->Status }}">
                                <td>
                                    <strong>#{{ $order->IDOrder }}</strong>
                                </td>
                                <td>
                                    @if($order->client)
                                        {{ $order->client->name }}
                                    @else
                                        <span class="text-muted">Гость</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->table)
                                        <span class="badge bg-secondary">{{ $order->table->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($order->Total, 2) }} ₽</strong>
                                    @if($order->Discount > 0)
                                        <br>
                                        <small class="text-success">Скидка: {{ number_format($order->Discount, 2) }} ₽</small>
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
                                    <span class="badge bg-{{ $statusColors[$order->Status] ?? 'secondary' }}">
                                        {{ $statusTexts[$order->Status] ?? $order->Status }}
                                    </span>
                                </td>
                                <td>
                                    {{ $order->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('orders.show', $order->IDOrder) }}" 
                                       class="btn btn-outline-primary btn-sm"
                                       title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-warning btn-sm edit-order-btn"
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
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-order-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteOrderModal"
                                            data-id="{{ $order->IDOrder }}"
                                            data-number="#{{ $order->IDOrder }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация -->
                @if($orders->hasPages())
                <div class="card-footer border-top-0">
                    {{ $orders->links() }}
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
    
    // Редактирование заказа
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
    
    // Удаление заказа
    const deleteOrderModal = document.getElementById('deleteOrderModal');
    if (deleteOrderModal) {
        deleteOrderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-order-btn')) {
                document.getElementById('deleteOrderNumber').textContent = button.dataset.number;
                document.getElementById('deleteOrderForm').action = `/orders/${button.dataset.id}`;
            }
        });
    }
});
</script>

@include('orders.modals.create')
@include('orders.modals.edit')
@include('orders.modals.delete')

@endsection