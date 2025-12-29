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

    <!-- Фильтры -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inventories.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="name" class="form-label small fw-bold">Поиск по названию</label>
                    <input type="text" name="name" id="name" 
                        class="form-control form-control-sm" 
                        value="{{ request('name') }}"
                        placeholder="Введите название...">
                </div>
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label small fw-bold">С даты</label>
                    <input type="date" name="date_from" id="date_from" 
                        class="form-control form-control-sm" 
                        value="{{ request('date_from') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label small fw-bold">По дату</label>
                    <input type="date" name="date_to" id="date_to" 
                        class="form-control form-control-sm" 
                        value="{{ request('date_to') }}">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary me-2">
                        <i class="bi bi-search"></i> Найти
                    </button>
                    <a href="{{ route('inventories.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Сбросить
                    </a>
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
                                <th>Склад</th>
                                <th>Дата</th>
                                <th>Статус</th>
                                <th>Товаров</th>
                                <th>Разница</th>
                                <th>Создал</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventories as $inventory)
                            <tr>
                                <td>
                                    <strong>{{ $inventory->name }}</strong>
                                </td>
                                <td>{{ $inventory->warehouse->name }}</td>
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
                                    @php
                                        $difference = $inventory->items->sum(function($item) {
                                            return $item->actual_quantity - $item->system_quantity;
                                        });
                                    @endphp
                                    @if($difference != 0)
                                        <span class="badge bg-{{ $difference > 0 ? 'info' : 'danger' }}">
                                            {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
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
                                                data-inventory-date="{{ $inventory->inventory_date->format('Y-m-d\TH:i') }}">
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