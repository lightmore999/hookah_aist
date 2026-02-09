@extends('layouts.app')

@section('title', 'Склад: ' . $warehouse->name)

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('warehouses.index') }}">Склады</a>
            </li>
            <li class="breadcrumb-item active">{{ $warehouse->name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-box-seam text-primary me-2"></i>
                Склад: {{ $warehouse->name }}
            </h1>
            <p class="text-muted mb-0">Товары на складе</p>
        </div>

        <div>
            <a href="{{ route('write-offs.index', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-outline-danger">
                <i class="bi bi-dash-circle me-1"></i> Списания
            </a>
            <button type="button" 
                    class="btn btn-outline-primary"
                    data-bs-toggle="modal" 
                    data-bs-target="#transferStockModal">
                <i class="bi bi-arrow-left-right me-1"></i> Перенос
            </button>
        </div>
        
        <div>
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Товары на складе</h5>
        </div>
        <div class="card-body p-0">
            @if($stocks->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">На этом складе нет товаров</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Товар</th>
                                <th>Количество</th>
                                <th>Единица</th>
                                <th>Дата обновления</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $stock)
                            @php
                                $product = $stock->product;
                                $unit = $product->unit ?? 'шт';
                                $quantity = $stock->quantity;
                                
                                // Форматирование числа без лишних нулей
                                $formattedQuantity = number_format($quantity, 0, '.', '');
                                if (fmod($quantity, 1) !== 0.0) {
                                    // Если число дробное, показываем до 3 знаков после запятой
                                    $formattedQuantity = rtrim(number_format($quantity, 3, '.', ''), '0');
                                    // Удаляем точку в конце, если осталась
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
                                
                                // Определяем цвет строки если мало остатков
                                $rowClass = '';
                                if ($quantity == 0) {
                                    $rowClass = 'table-danger';
                                } elseif ($quantity < 10 && $unit === 'шт') {
                                    $rowClass = 'table-warning';
                                } elseif ($quantity < 100 && ($unit === 'г' || $unit === 'мл')) {
                                    $rowClass = 'table-warning';
                                }
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->packaging > 1 && $unit !== 'шт')
                                        <br>
                                        <small class="text-muted">
                                            Упаковка: {{ $product->packaging }} {{ $unit }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $formattedQuantity }}</strong>
                                    @if($convertedValue !== null)
                                        <br>
                                        <small class="text-muted">
                                            ≈ {{ $formattedConvertedValue }} {{ $convertedUnit }}
                                        </small>
                                    @endif
                                    @if($product->packaging > 1 && $unit !== 'шт')
                                        @php
                                            $packages = $quantity / $product->packaging;
                                            $formattedPackages = number_format($packages, 0, '.', '');
                                            if (fmod($packages, 1) !== 0.0) {
                                                $formattedPackages = rtrim(number_format($packages, 2, '.', ''), '0');
                                                if (substr($formattedPackages, -1) === '.') {
                                                    $formattedPackages = substr($formattedPackages, 0, -1);
                                                }
                                            }
                                        @endphp
                                        <br>
                                        <small class="text-muted">
                                            {{ $formattedPackages }} уп.
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    {{ $unit }}
                                </td>
                                <td>
                                    {{ $stock->last_updated ? $stock->last_updated->format('d.m.Y H:i') : '-' }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-sm btn-danger write-off-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#writeOffModal"
                                                data-warehouse-id="{{ $warehouse->id }}"
                                                data-product-id="{{ $stock->product_id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-quantity="{{ $quantity }}"
                                                data-unit="{{ $unit }}">
                                            <i class="bi bi-dash-circle"></i> Списать
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger remove-stock-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#removeStockModal"
                                                data-warehouse-id="{{ $warehouse->id }}"
                                                data-product-id="{{ $stock->product_id }}"
                                                data-product-name="{{ $product->name }}"
                                                data-quantity="{{ $quantity }}"
                                                title="Удалить товар со склада">
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
</div>

@include('write-offs.modals.create')
@include('warehouses.modals.transfer')
@include('warehouses.modals.remove-stock')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка модального окна списания
    const writeOffModal = document.getElementById('writeOffModal');
    if (writeOffModal) {
        writeOffModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('write-off-btn')) {
                document.getElementById('modalWarehouseId').value = button.dataset.warehouseId;
                document.getElementById('modalProductId').value = button.dataset.productId;
                document.getElementById('modalProductName').textContent = button.dataset.productName;
                document.getElementById('modalAvailableQuantity').textContent = button.dataset.quantity;
                document.getElementById('maxQuantity').textContent = button.dataset.quantity;
                document.getElementById('modalQuantity').value = 1;
                document.getElementById('modalQuantity').max = button.dataset.quantity;
                document.getElementById('modalOperationType').value = '';
                
                const unit = button.dataset.unit || 'шт';
                document.getElementById('quantityUnit').textContent = unit;
                document.getElementById('maxQuantityUnit').textContent = unit;
                
                const quantityInput = document.getElementById('modalQuantity');
                if (unit === 'шт') {
                    quantityInput.min = 1;
                    quantityInput.step = 1;
                    quantityInput.value = 1;
                } else {
                    quantityInput.min = 0.001;
                    quantityInput.step = 0.001;
                    quantityInput.value = 0.001;
                }
            }
        });
    }
    const transferModal = document.getElementById('transferStockModal');
    const productSelect = document.getElementById('transferProductId');
    const quantityInput = document.getElementById('transferQuantity');
    const quantityUnit = document.getElementById('transferQuantityUnit');
    const availableQuantitySpan = document.getElementById('availableQuantity');
    const availableQuantityUnit = document.getElementById('availableQuantityUnit');
    
    if (transferModal && productSelect) {
        // Обновление доступного количества при выборе товара
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) return;
            
            const quantity = selectedOption.dataset.quantity || 0;
            const unit = selectedOption.dataset.unit || 'шт';
            
            // Форматируем количество для отображения
            const formattedQuantity = parseFloat(quantity).toFixed(3).replace(/\.?0+$/, '');
            availableQuantitySpan.textContent = formattedQuantity;
            availableQuantityUnit.textContent = unit;
            quantityUnit.textContent = unit;
            
            // Устанавливаем максимальное значение и шаг
            if (unit === 'шт') {
                quantityInput.max = quantity;
                quantityInput.step = 1;
                quantityInput.min = 1;
                quantityInput.value = 1;
            } else {
                quantityInput.max = quantity;
                quantityInput.step = 0.001;
                quantityInput.min = 0.001;
                quantityInput.value = 0.001;
            }
        });
        
        // Валидация количества
        quantityInput.addEventListener('input', function() {
            const max = parseFloat(this.max);
            const value = parseFloat(this.value);
            
            if (value > max) {
                this.value = max;
            }
            
            if (value < parseFloat(this.min)) {
                this.value = this.min;
            }
        });
        
        // Сброс формы при закрытии модального окна
        transferModal.addEventListener('hidden.bs.modal', function() {
            productSelect.selectedIndex = 0;
            quantityInput.value = '';
            availableQuantitySpan.textContent = '0';
            availableQuantityUnit.textContent = 'шт';
            quantityUnit.textContent = 'шт';
        });
    }

    const removeStockModal = document.getElementById('removeStockModal');
    
    if (removeStockModal) {
        removeStockModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('remove-stock-btn')) {
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;
                const quantity = parseFloat(button.dataset.quantity);
                
                // Устанавливаем product_id в скрытое поле
                document.getElementById('removeProductId').value = productId;
                
                // Устанавливаем название товара
                document.getElementById('removeProductName').textContent = productName;
                
                // Проверяем можно ли удалить (остаток должен быть 0)
                const deleteBtn = document.querySelector('#removeStockForm button[type="submit"]');
                if (quantity > 0) {
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<i class="bi bi-slash-circle me-1"></i> Нельзя удалить (есть остаток)';
                    deleteBtn.classList.remove('btn-danger');
                    deleteBtn.classList.add('btn-secondary');
                } else {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Удалить';
                    deleteBtn.classList.remove('btn-secondary');
                    deleteBtn.classList.add('btn-danger');
                }
            }
        });
        
        // Сброс состояния кнопки при закрытии модального окна
        removeStockModal.addEventListener('hidden.bs.modal', function() {
            const deleteBtn = document.querySelector('#removeStockForm button[type="submit"]');
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Удалить';
            deleteBtn.classList.remove('btn-secondary');
            deleteBtn.classList.add('btn-danger');
            
            // Очищаем поле
            document.getElementById('removeProductId').value = '';
        });
    }
});
</script>
@endsection