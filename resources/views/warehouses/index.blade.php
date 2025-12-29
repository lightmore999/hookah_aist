@extends('layouts.app')

@section('title', 'Склады')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Склады
            </h1>
            <p class="text-muted mb-0 small">Управление складами</p>
        </div>

        <div class="d-flex gap-3">
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createWarehouseModal">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить склад
            </button>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Склады</h5>
        </div>
        <div class="card-body p-0">
            @if($warehouses->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет складов. Добавьте первый!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createWarehouseModal">
                        Добавить склад
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouses as $warehouse)
                            <tr>
                                <td>
                                    <strong>{{ $warehouse->name }}</strong>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('warehouses.show', $warehouse) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-warning edit-warehouse-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editWarehouseModal"
                                                data-id="{{ $warehouse->id }}"
                                                data-name="{{ $warehouse->name }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger delete-warehouse-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteWarehouseModal"
                                                data-id="{{ $warehouse->id }}"
                                                data-name="{{ $warehouse->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-end align-items-center mb-4">
        <a href="{{ route('purchases.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i>
            Добавить закупку
        </a>
    </div>
    
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Закупки</h5>
        </div>
        <div class="card-body p-0">
            @if($purchases->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет закупок. Добавьте первую!</p>
                    <a href="{{ route('purchases.create') }}" class="btn btn-success mt-2">
                        Добавить закупку
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Товар</th>
                                <th>Склад</th>
                                <th>Количество</th>
                                <th>Цена за ед. (₽)</th>
                                <th>Общая сумма (₽)</th>
                                <th>Дата закупки</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchases as $purchase)
                            <tr>
                                <td>
                                    <strong>{{ $purchase->product->name }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $purchase->product->unit }}
                                        @if($purchase->product->packaging > 1)
                                            ({{ $purchase->product->packaging }} {{ $purchase->product->unit }}/уп.)
                                        @endif
                                    </small>
                                </td>
                                <td>{{ $purchase->warehouse->name }}</td>
                                <td>
                                    {{ $purchase->formatted_quantity }}
                                    @if($purchase->product->packaging > 1 && $purchase->product->unit !== 'шт')
                                        <br>
                                        <small class="text-muted">
                                            {{ number_format($purchase->quantity_in_packages, 2) }} уп.
                                        </small>
                                    @endif
                                </td>
                                <td>{{ number_format($purchase->unit_price, 2) }}</td>                              
                                <td>{{ number_format($purchase->total_price, 2) }}</td>
                                <td>{{ $purchase->purchase_date->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('purchases.edit', $purchase) }}" 
                                           class="btn btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('purchases.destroy', $purchase) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Удалить закупку?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@include('warehouses.modals.warehouses_create')
@include('warehouses.modals.warehouses_edit')
@include('warehouses.modals.warehouses_delete')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editWarehouseModal = document.getElementById('editWarehouseModal');
    if (editWarehouseModal) {
        editWarehouseModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-warehouse-btn')) {
                document.getElementById('edit_warehouse_name').value = button.dataset.name;
                document.getElementById('editWarehouseForm').action = `/warehouses/${button.dataset.id}`;
            }
        });
    }

    const deleteWarehouseModal = document.getElementById('deleteWarehouseModal');
    if (deleteWarehouseModal) {
        deleteWarehouseModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-warehouse-btn')) {
                document.getElementById('deleteWarehouseName').textContent = button.dataset.name;
                document.getElementById('deleteWarehouseForm').action = `/warehouses/${button.dataset.id}`;
            }
        });
    }
});
</script>

@endsection