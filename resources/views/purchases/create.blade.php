@extends('layouts.app')

@section('title', 'Добавить закупку')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('warehouses.index') }}">Склады</a>
            </li>
            <li class="breadcrumb-item active">Добавить закупку</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle text-primary me-2"></i>
                Добавить закупку
            </h1>
            <p class="text-muted mb-0">Заполните информацию о новой закупке</p>
        </div>
        
        <div>
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
     @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
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

                    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
                        @csrf
                        
                        <!-- Фильтры в две колонки -->
                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label for="categoryFilter" class="form-label fw-bold">Категория</label>
                                <select class="form-select form-select-sm" id="categoryFilter" onchange="filterProducts()">
                                    <option value="all">Все категории</option>
                                    @php
                                        // Получаем категории из продуктов
                                        $categories = $products->where('product_category_id', '!=', null)
                                            ->pluck('category')
                                            ->unique('id')
                                            ->filter()
                                            ->sortBy('name');
                                    @endphp
                                    
                                    @foreach($categories as $category)
                                        @if($category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endif
                                    @endforeach
                                    
                                    {{-- Опция для товаров без категории --}}
                                    @if($products->where('product_category_id', null)->count() > 0)
                                        <option value="null">Без категории</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="searchProduct" class="form-label fw-bold">Поиск</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="searchProduct" 
                                           placeholder="Название товара..."
                                           oninput="filterProducts()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Выбор товара -->
                        <div class="mb-4">
                            <label for="product_id" class="form-label">
                                <strong>Товар</strong> *
                            </label>
                            <select name="product_id" 
                                    class="form-select @error('product_id') is-invalid @enderror" 
                                    id="product_id" 
                                    size="6"
                                    style="height: auto; min-height: 150px;"
                                    required
                                    onchange="updateUnitInfo()">
                                <option value="">-- Выберите товар --</option>
                                @foreach($products as $product)
                                    @php
                                        // Категория товара
                                        $categoryName = $product->category ? $product->category->name : 'Без категории';
                                        $categoryId = $product->product_category_id ? (string)$product->product_category_id : 'null';
                                    @endphp
                                    <option value="{{ $product->id }}" 
                                            data-unit="{{ $product->unit }}"
                                            data-category="{{ $categoryId }}"
                                            data-category-name="{{ $categoryName }}"
                                            {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->unit }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="productInfo" class="mt-2"></div>
                        </div>

                        
                        <div class="mb-4">
                            <label for="warehouse_id" class="form-label">
                                <strong>Склад</strong> *
                            </label>
                            <select name="warehouse_id" 
                                    class="form-select @error('warehouse_id') is-invalid @enderror" 
                                    id="warehouse_id" 
                                    required>
                                <option value="">-- Выберите склад --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
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
                                       value="{{ old('quantity') }}" 
                                       placeholder="0"
                                       required>
                                <span class="input-group-text" id="unitLabel">ед.</span>
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
                                       value="{{ old('unit_price') }}" 
                                       placeholder="0.00" 
                                       required>
                                <span class="input-group-text" id="priceLabel">₽/ед.</span>
                            </div>
                            @error('unit_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small id="priceInfo" class="form-text text-muted">Цена за указанную единицу товара</small>
                        </div>

                        <!-- Расчет суммы -->
                        <div class="card mb-4 border-primary">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h6 mb-0">Итоговая сумма:</span>
                                    <span class="h5 mb-0 text-primary" id="preliminaryTotal">0.00 ₽</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="purchase_date" class="form-label">
                                <strong>Дата закупки</strong> *
                            </label>
                            <input type="datetime-local" 
                                   class="form-control @error('purchase_date') is-invalid @enderror" 
                                   id="purchase_date" 
                                   name="purchase_date" 
                                   value="{{ old('purchase_date', date('Y-m-d\TH:i')) }}" 
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
                                       {{ old('update_cost_price') ? 'checked' : 'checked' }}>
                                <label class="form-check-label" for="update_cost_price">
                                    Обновить себестоимость товара
                                </label>
                            </div>
                            <small class="text-muted">
                                Себестоимость будет обновлена на основе цены за единицу
                            </small>
                        </div>

                        
                        <div class="d-flex justify-content-between pt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i> Сохранить закупку
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#product_id option {
    padding: 6px 10px;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}
#product_id option:last-child {
    border-bottom: none;
}
.form-label {
    font-size: 0.875rem;
    font-weight: 600;
}
.input-group-sm {
    font-size: 0.875rem;
}
</style>

<script>
let allProducts = [];
let currentUnit = 'шт';

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Собираем все товары в массив с полной информацией
    document.querySelectorAll('#product_id option').forEach(option => {
        if (option.value) {
            allProducts.push({
                element: option,
                id: option.value,
                text: option.textContent.toLowerCase(),
                category: option.dataset.category || 'null',
                categoryName: option.dataset.categoryName || 'Без категории',
                unit: option.dataset.unit || 'шт'
            });
        }
    });
    
    // Инициализация информации о товаре
    updateUnitInfo();
    
    // Обработчики событий для расчета суммы
    document.getElementById('quantity').addEventListener('input', calculateTotal);
    document.getElementById('unit_price').addEventListener('input', calculateTotal);
    
    // Фильтрация при загрузке
    setTimeout(filterProducts, 100);
    
    // Пересчет суммы при изменении значений
    calculateTotal();
});

// Фильтрация товаров
function filterProducts() {
    const categoryFilter = document.getElementById('categoryFilter');
    const searchInput = document.getElementById('searchProduct');
    const productSelect = document.getElementById('product_id');
    
    if (!categoryFilter || !searchInput || !productSelect) {
        console.error('Не найдены элементы фильтрации');
        return;
    }
    
    const selectedCategory = categoryFilter.value;
    const searchTerm = searchInput.value.toLowerCase().trim();
    
    // Показываем все опции
    for (let i = 1; i < productSelect.options.length; i++) {
        productSelect.options[i].style.display = '';
    }
    
    let visibleCount = 0;
    let firstVisibleOption = null;
    
    // Фильтруем товары
    for (let i = 1; i < productSelect.options.length; i++) {
        const option = productSelect.options[i];
        if (!option.value) continue;
        
        const category = option.dataset.category || 'null';
        const text = option.textContent.toLowerCase();
        
        let shouldShow = true;
        
        // Фильтр по категории
        if (selectedCategory !== 'all') {
            if (selectedCategory === 'null') {
                shouldShow = category === 'null';
            } else {
                shouldShow = category === selectedCategory;
            }
        }
        
        // Фильтр по поиску
        if (shouldShow && searchTerm) {
            shouldShow = text.includes(searchTerm);
        }
        
        // Показываем/скрываем элемент
        if (shouldShow) {
            option.style.display = '';
            visibleCount++;
            
            if (!firstVisibleOption) {
                firstVisibleOption = option;
            }
        } else {
            option.style.display = 'none';
        }
    }
    
    // Автоматически выбираем первый видимый товар если есть фильтры
    if ((selectedCategory !== 'all' || searchTerm) && firstVisibleOption) {
        productSelect.value = firstVisibleOption.value;
        updateUnitInfo();
    }
    
    // Сообщение если ничего не найдено
    const existingWarning = productSelect.parentNode.querySelector('.alert-warning:not(#quantityWarning)');
    
    if (visibleCount === 0 && productSelect.options.length > 1) {
        if (!existingWarning) {
            const warningDiv = document.createElement('div');
            warningDiv.className = 'alert alert-warning mt-2';
            warningDiv.textContent = 'Товары не найдены. Измените параметры фильтрации.';
            productSelect.parentNode.insertBefore(warningDiv, productSelect.nextSibling);
        }
    } else if (existingWarning) {
        existingWarning.remove();
    }
}

// Очистка поиска
function clearSearch() {
    const searchInput = document.getElementById('searchProduct');
    if (searchInput) {
        searchInput.value = '';
        filterProducts();
    }
}

// Обновление информации о единицах измерения
function updateUnitInfo() {
    const productSelect = document.getElementById('product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const productInfo = document.getElementById('productInfo');
    
    if (selectedOption && selectedOption.value) {
        currentUnit = selectedOption.getAttribute('data-unit') || 'шт';
        const categoryName = selectedOption.getAttribute('data-category-name') || 'Без категории';
        
        // Обновляем метки
        document.getElementById('unitLabel').textContent = currentUnit;
        document.getElementById('priceLabel').textContent = `₽/${currentUnit}`;
        
        // Обновляем информацию о товаре
        if (productInfo) {
            productInfo.innerHTML = `
                <div class="d-flex gap-3">
                    <div>
                        <small class="text-muted d-block">Категория:</small>
                        <span class="badge bg-info">${categoryName}</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Единица измерения:</small>
                        <strong>${currentUnit}</strong>
                    </div>
                </div>
            `;
        }
    } else {
        if (productInfo) {
            productInfo.innerHTML = '';
        }
        document.getElementById('unitLabel').textContent = 'ед.';
        document.getElementById('priceLabel').textContent = '₽/ед.';
        currentUnit = 'шт';
    }
    
    // Обновляем расчет суммы
    calculateTotal();
}

// Расчет итоговой суммы
function calculateTotal() {
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('unit_price');
    const preliminaryTotal = document.getElementById('preliminaryTotal');
    
    if (!quantityInput || !priceInput || !preliminaryTotal) return;
    
    const quantity = parseFloat(quantityInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    
    const total = quantity * price;
    preliminaryTotal.textContent = total.toFixed(2) + ' ₽';
}
</script>

@endsection