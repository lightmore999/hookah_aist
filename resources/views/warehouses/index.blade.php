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
                            @foreach($warehouses as $warehouseItem)
                            <tr>
                                <td>
                                    <strong>{{ $warehouseItem->name }}</strong>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('warehouses.show', $warehouseItem) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-warning edit-warehouse-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editWarehouseModal"
                                                data-id="{{ $warehouseItem->id }}"
                                                data-name="{{ $warehouseItem->name }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger delete-warehouse-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteWarehouseModal"
                                                data-id="{{ $warehouseItem->id }}"
                                                data-name="{{ $warehouseItem->name }}">
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
                            @php
                                $product = $purchase->product;
                                $unit = $product->unit ?? 'шт';
                                $quantity = $purchase->quantity;
                                
                                // Форматирование числа без лишних нулей
                                $formattedQuantity = number_format($quantity, 0, '.', '');
                                if (fmod($quantity, 1) !== 0.0) {
                                    $formattedQuantity = rtrim(number_format($quantity, 3, '.', ''), '0');
                                    if (substr($formattedQuantity, -1) === '.') {
                                        $formattedQuantity = substr($formattedQuantity, 0, -1);
                                    }
                                }
                                
                                // Конвертация в большие единицы
                                $convertedValue = null;
                                $convertedUnit = null;
                                
                                if ($unit === 'мл' && $quantity >= 1000) {
                                    $convertedValue = $quantity / 1000;
                                    $convertedUnit = 'л';
                                } elseif ($unit === 'г' && $quantity >= 1000) {
                                    $convertedValue = $quantity / 1000;
                                    $convertedUnit = 'кг';
                                } elseif ($unit === 'л' && $quantity < 1) {
                                    $convertedValue = $quantity * 1000;
                                    $convertedUnit = 'мл';
                                } elseif ($unit === 'кг' && $quantity < 1) {
                                    $convertedValue = $quantity * 1000;
                                    $convertedUnit = 'г';
                                }
                                
                                // Форматирование конвертированного значения
                                $formattedConvertedValue = '';
                                if ($convertedValue !== null) {
                                    $formattedConvertedValue = number_format($convertedValue, 0, '.', '');
                                    if (fmod($convertedValue, 1) !== 0.0) {
                                        $formattedConvertedValue = rtrim(number_format($convertedValue, 3, '.', ''), '0');
                                        if (substr($formattedConvertedValue, -1) === '.') {
                                            $formattedConvertedValue = substr($formattedConvertedValue, 0, -1);
                                        }
                                    }
                                }
                                
                                // Расчет количества в упаковках
                                $packagesFormatted = '';
                                if ($product->packaging > 1 && $unit !== 'шт') {
                                    $packages = $quantity / $product->packaging;
                                    $packagesFormatted = number_format($packages, 0, '.', '');
                                    if (fmod($packages, 1) !== 0.0) {
                                        $packagesFormatted = rtrim(number_format($packages, 2, '.', ''), '0');
                                        if (substr($packagesFormatted, -1) === '.') {
                                            $packagesFormatted = substr($packagesFormatted, 0, -1);
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                </td>
                                <td>{{ $purchase->warehouse->name }}</td>
                                <td>
                                    <strong>{{ $formattedQuantity }} {{ $unit }}</strong>
                                    @if($convertedValue !== null)
                                        <br>
                                        <small class="text-muted">
                                            ≈ {{ $formattedConvertedValue }} {{ $convertedUnit }}
                                        </small>
                                    @endif
                                    @if($product->packaging > 1 && $unit !== 'шт')
                                        <br>
                                        <small class="text-muted">
                                            {{ $packagesFormatted }} уп.
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