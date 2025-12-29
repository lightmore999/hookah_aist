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

            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted small">Товаров</h5>
                            <h3 class="mb-0">{{ $inventory->items->count() }}</h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted small">Разница</h5>
                            <h3 class="mb-0 {{ $inventory->total_difference > 0 ? 'text-success' : ($inventory->total_difference < 0 ? 'text-danger' : '') }}">
                                {{ $inventory->total_difference > 0 ? '+' : '' }}{{ $inventory->total_difference }}
                            </h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted small">Системное кол-во</h5>
                            <h3 class="mb-0">{{ $inventory->items->sum('system_quantity') }}</h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted small">Фактическое кол-во</h5>
                            <h3 class="mb-0">{{ $inventory->items->sum('actual_quantity') }}</h3>
                        </div>
                    </div>
                </div>
            </div>

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
                                        @if($inventory->isCreated())
                                            <th class="text-end">Действия</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inventory->items as $item)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $item->product->name }}</strong>
                                                <div class="text-muted small">
                                                    {{ $item->product->unit }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $item->system_quantity }}</span>
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
                                                    {{ $item->actual_quantity }}
                                                </button>
                                            @else
                                                <span class="badge bg-primary">{{ $item->actual_quantity }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $difference = $item->actual_quantity - $item->system_quantity;
                                            @endphp
                                            @if($difference != 0)
                                                <span class="badge bg-{{ $difference > 0 ? 'info' : 'danger' }}">
                                                    {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                                                </span>
                                            @else
                                                <span class="text-muted">0</span>
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