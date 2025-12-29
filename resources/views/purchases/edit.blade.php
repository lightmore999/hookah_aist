@extends('layouts.app')

@section('title', 'Редактировать закупку')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('warehouses.index') }}">Склады</a>
            </li>
            <li class="breadcrumb-item active">Редактировать закупку</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                Редактировать закупку
            </h1>
            <p class="text-muted mb-0">
                Товар: {{ $purchase->product->name }}
                ({{ $purchase->quantity }} {{ $purchase->product->unit }})
            </p>
        </div>
        
        <div>
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning border-2">
                <div class="card-header bg-warning bg-opacity-10">
                    <strong>Редактирование закупки</strong>
                </div>
                
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Пожалуйста, исправьте ошибки:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('purchases.update', $purchase) }}" method="POST" id="purchaseForm">
                        @csrf
                        @method('PUT')
                        
                        
                        <div class="mb-4">
                            <label for="product_id" class="form-label">
                                <strong>Товар</strong> *
                            </label>
                            <select name="product_id" 
                                    class="form-select form-select-lg @error('product_id') is-invalid @enderror" 
                                    id="product_id" 
                                    required
                                    onchange="updateUnitInfo()">
                                <option value="">-- Выберите товар --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" 
                                            data-unit="{{ $product->unit }}"
                                            {{ old('product_id', $purchase->product_id) == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->unit }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="unitInfo" class="form-text mt-1"></div>
                        </div>

                        
                        <div class="mb-4">
                            <label for="warehouse_id" class="form-label">
                                <strong>Склад</strong> *
                            </label>
                            <select name="warehouse_id" 
                                    class="form-select form-select-lg @error('warehouse_id') is-invalid @enderror" 
                                    id="warehouse_id" 
                                    required>
                                <option value="">-- Выберите склад --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" 
                                        {{ old('warehouse_id', $purchase->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="quantity" class="form-label">
                                <strong>Количество</strong> *
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       step="0.001"
                                       min="0.001" 
                                       class="form-control @error('quantity') is-invalid @enderror" 
                                       id="quantity" 
                                       name="quantity" 
                                       value="{{ old('quantity', $purchase->quantity) }}" 
                                       required>
                                <span class="input-group-text" id="unitLabel">{{ $purchase->product->unit }}</span>
                            </div>
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Количество в указанных единицах товара</div>
                        </div>

                        
                        <div class="mb-4">
                            <label for="unit_price" class="form-label">
                                <strong>Цена за единицу (₽)</strong> *
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       class="form-control @error('unit_price') is-invalid @enderror" 
                                       id="unit_price" 
                                       name="unit_price" 
                                       value="{{ old('unit_price', $purchase->unit_price) }}" 
                                       required>
                                <span class="input-group-text" id="priceLabel">₽/{{ $purchase->product->unit }}</span>
                            </div>
                            @error('unit_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small id="priceInfo" class="form-text text-muted">Цена за указанную единицу товара</small>
                        </div>

                        
                        <div class="mb-4">
                            <label for="purchase_date" class="form-label">
                                <strong>Дата закупки</strong> *
                            </label>
                            <input type="datetime-local" 
                                   class="form-control @error('purchase_date') is-invalid @enderror" 
                                   id="purchase_date" 
                                   name="purchase_date" 
                                   value="{{ old('purchase_date', $purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d\TH:i') : '') }}" 
                                   required>
                            @error('purchase_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="update_cost_price" 
                                       name="update_cost_price" 
                                       value="1" 
                                       {{ old('update_cost_price') ? 'checked' : '' }}>
                                <label class="form-check-label" for="update_cost_price">
                                    Обновить себестоимость товара
                                </label>
                            </div>
                            <small class="text-muted">
                                Себестоимость будет обновлена на основе цены за единицу
                            </small>
                        </div>

                        
                        <div class="d-flex justify-content-between pt-3">
                            <div>
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Обновить закупку
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            
            <div class="card border-danger mt-4">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Удаление закупки невозможно отменить. Все данные будут безвозвратно удалены.
                    </p>
                    <form action="{{ route('purchases.destroy', $purchase) }}" 
                          method="POST" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить эту закупку? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Удалить эту закупку
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentUnit = '{{ $purchase->product->unit ?? "шт" }}';

function updateUnitInfo() {
    const productSelect = document.getElementById('product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (selectedOption.value) {
        currentUnit = selectedOption.getAttribute('data-unit') || 'шт';
        
        document.getElementById('unitLabel').textContent = currentUnit;
        document.getElementById('priceLabel').textContent = `₽/${currentUnit}`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateUnitInfo();
});
</script>

@endsection