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
                                    <strong>{{ number_format($quantity, 3) }}</strong>
                                    @if($product->packaging > 1 && $unit !== 'шт')
                                        <br>
                                        <small class="text-muted">
                                            {{ number_format($quantity / $product->packaging, 2) }} уп.
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
});
</script>
@endsection