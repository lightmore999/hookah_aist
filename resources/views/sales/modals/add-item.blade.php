<!-- sales/modals/add-item.blade.php -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- Увеличил до modal-lg -->
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addItemModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить товар в продажу
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('sales.items.store', $sale->id) }}" method="POST" id="addItemForm">
                @csrf
                
                <div class="modal-body">
                    <!-- Блок выбора товара - теперь занимает всю ширину -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="categoryFilter" class="form-label fw-bold">Фильтр по категории</label>
                            <select class="form-select" id="categoryFilter" onchange="filterProducts()">
                                <option value="all">Все категории</option>
                                @php
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
                                
                                @if($products->where('product_category_id', null)->count() > 0)
                                    <option value="null">Без категории</option>
                                @endif
                            </select>
                        </div>
                        
                        <div class="col-md-8">
                            <!-- Блок поиска и выбора товара -->
                            <div class="product-select-container">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="product_id" class="form-label fw-bold mb-0 h6">Поиск и выбор товара</label>
                                    <small class="text-muted">Начните вводить или выберите из списка</small>
                                </div>
                                
                                <!-- Поле поиска -->
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-search text-primary"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control form-control-lg py-2" 
                                           id="searchProduct" 
                                           placeholder="Введите название товара для быстрого поиска..."
                                           oninput="filterProducts()"
                                           style="border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()" style="border-bottom-right-radius: 0;">
                                        <i class="bi bi-x"></i> Очистить
                                    </button>
                                </div>
                                
                                <!-- Select с товарами -->
                                <div class="product-list-container">
                                    <select class="form-select" 
                                            id="product_id" 
                                            name="product_id" 
                                            size="6"
                                            style="height: 250px; border-top-left-radius: 0; border-top-right-radius: 0; font-size: 0.95rem;"
                                            required 
                                            onchange="updateProductInfo()">
                                        <option value="" class="text-muted">Выберите товар из списка ниже...</option>
                                        @foreach($products as $product)
                                        @php
                                            $available = \App\Models\Stock::where('product_id', $product->id)->sum('quantity');
                                            
                                            if ($product->is_composite) {
                                                $minAvailable = PHP_INT_MAX;
                                                foreach ($product->recipeComponents as $component) {
                                                    $totalComponentQuantity = \App\Models\Stock::where('product_id', $component->component_product_id)
                                                        ->sum('quantity');
                                                    
                                                    if ($totalComponentQuantity <= 0) {
                                                        $available = 0;
                                                        break;
                                                    }
                                                    
                                                    $availableForComponent = floor($totalComponentQuantity / $component->quantity);
                                                    $minAvailable = min($minAvailable, $availableForComponent);
                                                }
                                                $available = ($minAvailable !== PHP_INT_MAX) ? $minAvailable : 0;
                                            }
                                            
                                            $categoryName = $product->category ? $product->category->name : 'Без категории';
                                            $categoryId = $product->product_category_id ? (string)$product->product_category_id : 'null';
                                        @endphp
                                        <option value="{{ $product->id }}" 
                                                data-price="{{ $product->price }}"
                                                data-unit="{{ $product->unit ?? 'шт' }}"
                                                data-is-composite="{{ $product->is_composite ? '1' : '0' }}"
                                                data-cost="{{ $product->cost ?? 0 }}"
                                                data-available="{{ $available }}"
                                                data-category="{{ $categoryId }}"
                                                data-category-name="{{ $categoryName }}"
                                                class="product-option"
                                                {{ $available <= 0 ? 'disabled' : '' }}>
                                            <div class="d-flex justify-content-between">
                                                <span>{{ $product->name }}</span>
                                                <span class="text-end ms-2">
                                                    @if($available > 0)
                                                        <span class="badge bg-success">{{ $available }} шт</span>
                                                        <span class="text-primary ms-2">{{ number_format($product->price, 2) }} ₽</span>
                                                    @else
                                                        <span class="badge bg-secondary">Нет в наличии</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                @if(isset($products) && $products->isEmpty())
                                <div class="alert alert-warning mt-3">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Нет доступных товаров для добавления
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Информация о выбранном товаре -->
                    <div class="card mb-4 border-primary" id="productInfoCard" style="display: none;">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Информация о выбранном товаре</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Товар</small>
                                            <div class="h5 text-primary mb-0" id="productName">—</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted d-block mb-1">Категория</small>
                                            <span class="badge bg-info fs-6 px-3 py-2" id="productCategory">—</span>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block mb-1">Единица измерения</small>
                                            <strong class="fs-5" id="productUnitInfo">—</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Цена за единицу</small>
                                            <div class="h4 text-success mb-0" id="productUnitPrice">0.00 ₽</div>
                                        </div>
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Доступно в системе</small>
                                            <div class="d-flex align-items-center">
                                                <span class="h5 text-success mb-0 me-2" id="productAvailable">0</span>
                                                <span class="fs-5" id="productAvailableUnit"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block mb-1">Себестоимость</small>
                                            <strong class="fs-5 text-muted" id="productCost">0.00 ₽</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded">
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Остаток после добавления</small>
                                            <div class="h4 mb-0">
                                                <span class="text-warning" id="remainingStock">0</span>
                                                <span class="fs-5 ms-1" id="remainingUnit"></span>
                                            </div>
                                        </div>
                                        <div id="compositeWarning" style="display: none;">
                                            <div class="alert alert-warning p-2 mb-0">
                                                <i class="bi bi-info-circle me-1"></i> Составной товар - списание по рецепту
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ввод данных для добавления -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Количество</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label fw-bold h6">Количество *</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" 
                                                   step="0.001"
                                                   min="0.001" 
                                                   class="form-control form-control-lg" 
                                                   id="quantity" 
                                                   name="quantity" 
                                                   value="1" 
                                                   required
                                                   oninput="checkQuantity()">
                                            <span class="input-group-text fs-5" id="quantityUnitLabel">шт</span>
                                        </div>
                                        <small class="text-muted mt-2 d-block" id="quantityInfo">Введите количество товара</small>
                                        <div id="quantityWarning" class="alert alert-warning mt-3 p-3" style="display: none;">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            <span id="warningMessage" class="fw-medium"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Цена</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="unit_price" class="form-label fw-bold h6">Цена за единицу *</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   class="form-control form-control-lg" 
                                                   id="unit_price" 
                                                   name="unit_price" 
                                                   required>
                                            <span class="input-group-text fs-5" id="priceUnitLabel">₽/ед.</span>
                                            <button class="btn btn-outline-primary" type="button" onclick="setDefaultPrice()">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Сброс
                                            </button>
                                        </div>
                                        <small class="text-muted mt-2 d-block" id="priceInfo">Установите цену продажи</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Итоговая сумма -->
                    <div class="card border-success">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="h5 mb-2">Сумма к добавлению в продажу:</div>
                                    <div class="d-flex align-items-center">
                                        <div class="fs-6 text-muted me-3">Количество:</div>
                                        <div class="fs-5 fw-bold" id="displayQuantity">1</div>
                                        <div class="fs-6 text-muted mx-2">×</div>
                                        <div class="fs-5 fw-bold" id="displayPrice">0.00 ₽</div>
                                        <div class="fs-6 text-muted mx-2">=</div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="h2 text-success mb-0" id="preliminaryTotal">0.00 ₽</div>
                                    <div class="text-muted">Итоговая сумма</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-lg btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary" id="submitBtn">
                        <i class="bi bi-plus-circle me-2"></i>Добавить товар
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Основные стили */
.product-select-container {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 16px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.product-list-container {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-top: 8px;
}

/* Стили для select */
.product-select-container select {
    border: none !important;
    box-shadow: none !important;
    background: transparent;
}

.product-select-container select:focus {
    outline: none;
}

/* Плавный переход при фокусе */
.product-select-container:focus-within {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

/* Стили для опций товаров */
.product-option {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.95rem;
    transition: all 0.2s;
    cursor: pointer;
}

.product-option:last-child {
    border-bottom: none;
}

.product-option:hover {
    background-color: #f8f9fa;
}

.product-option:checked {
    background-color: #e7f1ff;
    color: #0d6efd;
    font-weight: 500;
}

.product-option:disabled {
    color: #adb5bd;
    background-color: #f8f9fa;
    cursor: not-allowed;
}

/* Бейджи в опциях */
.product-option .badge {
    font-size: 0.75rem;
    padding: 4px 8px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .product-select-container {
        padding: 12px;
    }
    
    .product-select-container select {
        font-size: 0.9rem;
    }
    
    .product-option {
        padding: 8px 12px;
    }
}

/* Стили для карточек */
.card {
    border: 1px solid #e0e0e0;
    border-radius: 0.75rem;
    overflow: hidden;
}

.card-header {
    border-bottom: 1px solid #e0e0e0;
    padding: 1rem 1.25rem;
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

#productInfoCard {
    animation: fadeIn 0.3s ease-out;
}

/* Поля ввода */
.form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1rem;
}

.input-group-lg > .form-control {
    padding: 0.75rem 1rem;
}

/* Заголовки */
.h6 {
    font-weight: 600;
}

/* Иконки */
.bi {
    font-size: 1.1em;
}

/* Убираем стрелку у select в Firefox */
select {
    -moz-appearance: none;
    -webkit-appearance: none;
    appearance: none;
}

/* Кастомная полоса прокрутки */
.product-list-container::-webkit-scrollbar {
    width: 6px;
}

.product-list-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.product-list-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.product-list-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
let currentProduct = {
    id: null,
    name: '',
    unit: 'шт',
    price: 0,
    cost: 0,
    isComposite: false,
    available: 0,
    category: null,
    categoryName: ''
};

let allProducts = [];

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Собираем все товары в массив с полной информацией
    document.querySelectorAll('#product_id option.product-option').forEach(option => {
        if (option.value) {
            allProducts.push({
                element: option,
                id: option.value,
                text: option.textContent.toLowerCase(),
                category: option.dataset.category || 'null',
                categoryName: option.dataset.categoryName || 'Без категории',
                price: parseFloat(option.dataset.price) || 0,
                unit: option.dataset.unit || 'шт',
                isComposite: option.dataset.isComposite === '1',
                cost: parseFloat(option.dataset.cost) || 0,
                available: parseFloat(option.dataset.available) || 0
            });
        }
    });
    
    // Отладочная информация
    console.log('=== ИНИЦИАЛИЗАЦИЯ ===');
    console.log('Всего загружено товаров:', allProducts.length);
    console.log('Уникальные категории в товарах:', [...new Set(allProducts.map(p => p.category + ' - ' + p.categoryName))]);
    
    // Выводим подробную информацию о каждом товаре
    allProducts.forEach(product => {
        console.log(`Товар ID=${product.id}: category="${product.category}", categoryName="${product.categoryName}"`);
    });
    console.log('=== КОНЕЦ ИНИЦИАЛИЗАЦИИ ===');
    
    // Инициализируем обработчики событий
    document.getElementById('quantity').addEventListener('input', function() {
        checkQuantity();
        calculateTotal();
    });
    
    document.getElementById('unit_price').addEventListener('input', calculateTotal);
    
    // При открытии модалки фильтруем товары
    const addItemModal = document.getElementById('addItemModal');
    if (addItemModal) {
        addItemModal.addEventListener('shown.bs.modal', function() {
            setTimeout(filterProducts, 100);
        });
    }
});

// Обновление информации о выбранном товаре
function updateProductInfo() {
    const productSelect = document.getElementById('product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const productInfoCard = document.getElementById('productInfoCard');
    const compositeWarning = document.getElementById('compositeWarning');
    
    if (!productInfoCard || !selectedOption || !selectedOption.value) {
        currentProduct = { 
            id: null, 
            name: '', 
            unit: 'шт', 
            price: 0, 
            cost: 0,
            isComposite: false, 
            available: 0,
            category: null,
            categoryName: ''
        };
        productInfoCard.style.display = 'none';
        hideWarning();
        return;
    }
    
    // Показываем информацию о товаре
    productInfoCard.style.display = 'block';
    
    // Обновляем информацию о товаре
    const optionText = selectedOption.textContent;
    const nameMatch = optionText.match(/^([^(]+)/);
    
    currentProduct = {
        id: selectedOption.value,
        name: nameMatch ? nameMatch[1].trim() : selectedOption.textContent,
        unit: selectedOption.dataset.unit || 'шт',
        price: parseFloat(selectedOption.dataset.price) || 0,
        cost: parseFloat(selectedOption.dataset.cost) || 0,
        isComposite: selectedOption.dataset.isComposite === '1',
        available: parseFloat(selectedOption.dataset.available) || 0,
        category: selectedOption.dataset.category || 'null',
        categoryName: selectedOption.dataset.categoryName || 'Без категории'
    };
    
    // Заполняем информацию
    const productNameEl = document.getElementById('productName');
    const productUnitPriceEl = document.getElementById('productUnitPrice');
    const productAvailableEl = document.getElementById('productAvailable');
    const productAvailableUnitEl = document.getElementById('productAvailableUnit');
    const productUnitInfoEl = document.getElementById('productUnitInfo');
    const remainingStockEl = document.getElementById('remainingStock');
    const productCategoryEl = document.getElementById('productCategory');
    
    if (productNameEl) productNameEl.textContent = currentProduct.name;
    if (productUnitPriceEl) productUnitPriceEl.textContent = currentProduct.price.toFixed(2) + ' ₽';
    if (productAvailableEl) productAvailableEl.textContent = currentProduct.available;
    if (productAvailableUnitEl) productAvailableUnitEl.textContent = currentProduct.unit;
    if (productUnitInfoEl) productUnitInfoEl.textContent = currentProduct.unit;
    if (remainingStockEl) remainingStockEl.textContent = currentProduct.available;
    if (productCategoryEl) productCategoryEl.textContent = currentProduct.categoryName;
    
    // Показываем/скрываем предупреждение о составном товаре
    if (compositeWarning) {
        compositeWarning.style.display = currentProduct.isComposite ? 'block' : 'none';
    }
    
    // Обновляем поля
    updateQuantityFields();
    updatePriceFields();
    checkQuantity();
    calculateTotal();
}

// Обновление полей количества
function updateQuantityFields() {
    const quantityInput = document.getElementById('quantity');
    const quantityUnitLabel = document.getElementById('quantityUnitLabel');
    const quantityInfo = document.getElementById('quantityInfo');
    
    if (!quantityInput || !quantityUnitLabel) return;
    
    // Устанавливаем правильный step в зависимости от единицы измерения
    if (currentProduct.unit === 'шт') {
        quantityInput.step = '1';
        quantityInput.min = '1';
        quantityInput.value = '1';
        if (quantityInfo) {
            quantityInfo.textContent = 'Для штучных товаров количество должно быть целым числом';
        }
    } else {
        quantityInput.step = '0.001';
        quantityInput.min = '0.001';
        quantityInput.value = '1';
        if (quantityInfo) {
            quantityInfo.textContent = `Единица измерения: ${currentProduct.unit}`;
        }
    }
    
    quantityUnitLabel.textContent = currentProduct.unit;
    
    calculateTotal();
}

// Обновление полей цены
function updatePriceFields() {
    const priceInput = document.getElementById('unit_price');
    const priceUnitLabel = document.getElementById('priceUnitLabel');
    
    if (!priceInput || !priceUnitLabel) return;
    
    // Устанавливаем цену по умолчанию из товара
    priceInput.value = currentProduct.price.toFixed(2);
    priceUnitLabel.textContent = `₽/${currentProduct.unit}`;
    
    calculateTotal();
}

// Установить стандартную цену
function setDefaultPrice() {
    const priceInput = document.getElementById('unit_price');
    if (!priceInput) return;
    
    priceInput.value = currentProduct.price.toFixed(2);
    calculateTotal();
}

// Проверка количества
function checkQuantity() {
    const quantityInput = document.getElementById('quantity');
    const quantityWarning = document.getElementById('quantityWarning');
    const warningMessage = document.getElementById('warningMessage');
    const submitBtn = document.getElementById('submitBtn');
    const remainingStock = document.getElementById('remainingStock');
    
    if (!quantityInput || !submitBtn || !currentProduct.id) return;
    
    const requested = parseFloat(quantityInput.value) || 0;
    const available = currentProduct.available || 0;
    
    // Обновляем отображение остатка
    if (remainingStock) {
        const remaining = Math.max(0, available - requested);
        remainingStock.textContent = remaining;
        
        if (remaining < available) {
            remainingStock.className = 'text-warning';
        } else {
            remainingStock.className = '';
        }
    }
    
    if (requested <= 0) {
        showWarning('Введите корректное количество', 'warning');
        submitBtn.disabled = true;
        return;
    }
    
    if (currentProduct.unit === 'шт' && !Number.isInteger(requested)) {
        showWarning('Для штучных товаров количество должно быть целым числом', 'warning');
        submitBtn.disabled = true;
        return;
    }
    
    if (available <= 0) {
        showWarning('Товар отсутствует на складе', 'danger');
        submitBtn.disabled = true;
        return;
    }
    
    if (requested > available) {
        showWarning(`Запрошено: ${requested}, доступно: ${available}`, 'danger');
        submitBtn.disabled = true;
        return;
    }
    
    // Если все хорошо
    hideWarning();
    submitBtn.disabled = false;
}

// Показать предупреждение
function showWarning(message, type = 'warning') {
    const quantityWarning = document.getElementById('quantityWarning');
    const warningMessage = document.getElementById('warningMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!quantityWarning || !warningMessage || !submitBtn) return;
    
    quantityWarning.style.display = 'block';
    quantityWarning.className = `alert alert-${type} mt-2 p-2`;
    warningMessage.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i> ${message}`;
    submitBtn.disabled = true;
}

// Скрыть предупреждение
function hideWarning() {
    const quantityWarning = document.getElementById('quantityWarning');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!quantityWarning || !submitBtn) return;
    
    quantityWarning.style.display = 'none';
    submitBtn.disabled = false;
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
    
    console.log('=== ФИЛЬТРАЦИЯ ===');
    console.log('Выбранная категория:', selectedCategory);
    console.log('Поисковый запрос:', searchTerm);
    
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
                // Сравниваем как строки
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
    
    console.log('Видимых товаров:', visibleCount);
    
    // Автоматически выбираем первый видимый товар если есть фильтры
    if ((selectedCategory !== 'all' || searchTerm) && firstVisibleOption) {
        productSelect.value = firstVisibleOption.value;
        updateProductInfo();
    }
    
    // Сообщение если ничего не найдено
    const existingWarning = productSelect.parentNode.querySelector('.alert-warning:not(#quantityWarning)');
    
    if (visibleCount === 0 && productSelect.options.length > 1) {
        if (!existingWarning) {
            const warningDiv = document.createElement('div');
            warningDiv.className = 'alert alert-warning mt-2';
            warningDiv.textContent = 'Товары не найдены';
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

// AJAX отправка формы
const addItemForm = document.getElementById('addItemForm');
if (addItemForm) {
    addItemForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const productId = document.getElementById('product_id').value;
        const quantity = document.getElementById('quantity').value;
        const unitPrice = document.getElementById('unit_price').value;
        
        if (!productId || !quantity || !unitPrice) {
            alert('Заполните все обязательные поля');
            return;
        }
        
        // Проверяем доступность через AJAX перед отправкой
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            alert('Ошибка безопасности. Обновите страницу.');
            return;
        }
        
        fetch('{{ route("sales.check-stock", $sale->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                unit_price: unitPrice
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Если товар доступен, отправляем форму
                this.submit();
            } else {
                // Показываем ошибку
                showWarning(data.message || 'Не удалось добавить товар', 'danger');
                
                // Предлагаем добавить доступное количество
                if (data.available > 0 && confirm(`Доступно только ${data.available} ${data.unit}. Добавить это количество?`)) {
                    document.getElementById('quantity').value = data.available;
                    checkQuantity();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка проверки товара. Попробуйте еще раз.');
        });
    });
}
</script>