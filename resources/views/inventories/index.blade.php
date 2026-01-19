@extends('layouts.app')

@section('title', 'Инвентаризация')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Инвентаризация
            </h1>
            <p class="text-muted mb-0 small">Управление инвентаризацией товаров на складах</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary mt-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createInventoryModal">
                <i class="bi bi-plus-circle me-1"></i> Новая инвентаризация
            </button>
        </div>
    </div>

    <!-- Упрощенные фильтры -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('inventories.index') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-bold mb-1">Поиск по названию</label>
                    <input type="text" name="name" id="name" 
                        class="form-control" 
                        value="{{ request('name') }}"
                        placeholder="Введите название инвентаризации...">
                </div>
                
                <div class="col-md-4">
                    <label for="warehouse_id" class="form-label small fw-bold mb-1">Фильтр по складу</label>
                    <select name="warehouse_id" id="warehouse_id" 
                        class="form-select">
                        <option value="">Все склады</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" 
                                {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Найти
                    </button>
                    @if(request()->has('name') || request()->has('warehouse_id'))
                        <a href="{{ route('inventories.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle me-1"></i> Сбросить
                        </a>
                    @endif
                </div>
            </form>
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
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($inventories->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-data display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет инвентаризаций. Создайте первую!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createInventoryModal">
                        <i class="bi bi-plus-circle me-1"></i> Создать инвентаризацию
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th>Склады</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th>Товаров</th>
                                <th>Финансовый результат</th>
                                <th>Создал</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventories as $inventory)
                            @php
                                // Вычисляем финансовый результат
                                $financialResult = 0;
                                $totalLoss = 0;
                                $totalGain = 0;
                                
                                foreach ($inventory->items as $item) {
                                    $product = $item->product;
                                    $difference = $item->actual_quantity - $item->system_quantity;
                                    $costPrice = $product->cost ?? 0;
                                    
                                    if ($difference != 0) {
                                        $itemFinancial = $difference * $costPrice;
                                        $financialResult += $itemFinancial;
                                        
                                        if ($itemFinancial < 0) {
                                            $totalLoss += abs($itemFinancial);
                                        } else {
                                            $totalGain += $itemFinancial;
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $inventory->name }}</strong>
                                </td>
                                <td>
                                    @if($inventory->warehouses->count() > 0)
                                        <div>
                                            <span class="badge bg-primary">{{ $inventory->warehouses->count() }}</span>
                                            @if($inventory->warehouses->count() <= 3)
                                                {{ $inventory->warehouses->pluck('name')->join(', ') }}
                                            @else
                                                {{ $inventory->warehouses->first()->name }} 
                                                <span class="text-muted">+{{ $inventory->warehouses->count() - 1 }}</span>
                                            @endif
                                        </div>
                                    @elseif($inventory->warehouse)
                                        <span class="badge bg-secondary">{{ $inventory->warehouse->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $inventory->inventory_date->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($inventory->isCreated())
                                        <span class="badge bg-warning text-dark">Создана</span>
                                    @elseif($inventory->isClosed())
                                        <span class="badge bg-success">Закрыта</span>
                                    @endif
                                </td>
                                <td>
                                    @if($inventory->items_count > 0)
                                        <span class="badge bg-primary">{{ $inventory->items_count }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($financialResult != 0)
                                        <div class="{{ $financialResult > 0 ? 'text-success' : 'text-danger' }}">
                                            <strong>{{ $financialResult > 0 ? '+' : '' }}{{ number_format($financialResult, 2) }} ₽</strong>
                                            @if($totalLoss > 0 || $totalGain > 0)
                                                <div class="text-muted small">
                                                    @if($totalLoss > 0)
                                                        <span class="text-danger">↓{{ number_format($totalLoss, 2) }} ₽</span>
                                                    @endif
                                                    @if($totalGain > 0)
                                                        @if($totalLoss > 0)
                                                            /
                                                        @endif
                                                        <span class="text-success">↑{{ number_format($totalGain, 2) }} ₽</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">0 ₽</span>
                                    @endif
                                </td>
                                <td>{{ $inventory->creator->name }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventories.show', $inventory) }}" 
                                    class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($inventory->isCreated())
                                        <button type="button" 
                                                class="btn btn-sm btn-warning edit-inventory-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editInventoryModal"
                                                data-id="{{ $inventory->id }}"
                                                data-name="{{ $inventory->name }}"
                                                data-inventory-date="{{ $inventory->inventory_date->format('Y-m-d\TH:i') }}"
                                                data-warehouse-ids="{{ $inventory->warehouses->pluck('id')->join(',') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endif
                                    
                                    <!-- Кнопка удаления всегда видна -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger delete-inventory-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteInventoryModal"
                                            data-id="{{ $inventory->id }}"
                                            data-name="{{ $inventory->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        @if($inventories->count() > 0)
                        <tfoot class="table-light">
                            <tr>
                                @php
                                    // Итоговые суммы по всем инвентаризациям
                                    $overallFinancialResult = 0;
                                    $overallTotalLoss = 0;
                                    $overallTotalGain = 0;
                                    
                                    foreach ($inventories as $inv) {
                                        foreach ($inv->items as $item) {
                                            $product = $item->product;
                                            $difference = $item->actual_quantity - $item->system_quantity;
                                            $costPrice = $product->cost ?? 0;
                                            
                                            if ($difference != 0) {
                                                $itemFinancial = $difference * $costPrice;
                                                $overallFinancialResult += $itemFinancial;
                                                
                                                if ($itemFinancial < 0) {
                                                    $overallTotalLoss += abs($itemFinancial);
                                                } else {
                                                    $overallTotalGain += $itemFinancial;
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <th colspan="4" class="text-end">Итого по всем инвентаризациям:</th>
                                <th>{{ $inventories->sum('items_count') }}</th>
                                <th>
                                    <div class="{{ $overallFinancialResult >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ $overallFinancialResult >= 0 ? '+' : '' }}{{ number_format($overallFinancialResult, 2) }} ₽</strong>
                                        @if($overallTotalLoss > 0 || $overallTotalGain > 0)
                                            <div class="text-muted small">
                                                @if($overallTotalLoss > 0)
                                                    <span class="text-danger">↓{{ number_format($overallTotalLoss, 2) }} ₽</span>
                                                @endif
                                                @if($overallTotalGain > 0)
                                                    @if($overallTotalLoss > 0)
                                                        /
                                                    @endif
                                                    <span class="text-success">↑{{ number_format($overallTotalGain, 2) }} ₽</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                
                @if($inventories->hasPages())
                    <div class="card-footer border-top-0">
                        {{ $inventories->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка модалки редактирования
    const editInventoryModal = document.getElementById('editInventoryModal');
    if (editInventoryModal) {
        editInventoryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-inventory-btn')) {
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('edit_inventory_date').value = button.dataset.inventoryDate;
                document.getElementById('editInventoryForm').action = `/inventories/${button.dataset.id}`;
                
                // Устанавливаем выбранные склады
                const warehouseIds = button.dataset.warehouseIds.split(',');
                if (document.getElementById('edit_warehouse_ids')) {
                    // Снимаем все предыдущие выборы
                    $('#edit_warehouse_ids option').prop('selected', false);
                    
                    // Устанавливаем выбранные склады
                    warehouseIds.forEach(function(warehouseId) {
                        if (warehouseId) {
                            $('#edit_warehouse_ids option[value="' + warehouseId + '"]').prop('selected', true);
                        }
                    });
                    $('#edit_warehouse_ids').trigger('change');
                }
            }
        });
    }

    // Обработка модалки удаления
    const deleteInventoryModal = document.getElementById('deleteInventoryModal');
    if (deleteInventoryModal) {
        deleteInventoryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-inventory-btn')) {
                document.getElementById('deleteInventoryName').textContent = button.dataset.name;
                document.getElementById('deleteInventoryForm').action = `/inventories/${button.dataset.id}`;
            }
        });
    }
});
</script>

@include('inventories.modals.create')
@include('inventories.modals.edit')
@include('inventories.modals.delete')

@endsection 