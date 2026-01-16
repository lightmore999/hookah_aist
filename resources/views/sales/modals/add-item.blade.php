<!-- sales/modals/add-item.blade.php -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
                    <!-- Фильтры в две колонки -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="categoryFilter" class="form-label fw-bold">Категория</label>
                            <select class="form-select form-select-sm" id="categoryFilter" onchange="filterProducts()">
                                <option value="all">Все категории</option>
                                @php
                                    // Получаем категории из продуктов - ИСПРАВЛЕНО: используем product_category_id
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
                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-bold">Выберите товар *</label>
                        <select class="form-select" 
                                id="product_id" 
                                name="product_id" 
                                size="6"
                                style="height: auto; min-height: 150px;"
                                required 
                                onchange="updateProductInfo()">
                            <option value="">-- Выберите товар --</option>
                            @foreach($products as $product)
                            @php
                                // Получаем доступное количество с учетом склада
                                $stock = \App\Models\Stock::where('warehouse_id', $sale->warehouse_id)
                                    ->where('product_id', $product->id)
                                    ->first();
                                $available = $stock ? $stock->quantity : 0;
                                
                                // Для составных товаров рассчитываем доступное количество
                                if ($product->is_composite) {
                                    $minAvailable = PHP_INT_MAX;
                                    foreach ($product->recipeComponents as $component) {
                                        $componentStock = \App\Models\Stock::where('warehouse_id', $sale->warehouse_id)
                                            ->where('product_id', $component->component_product_id)
                                            ->first();
                                        
                                        if (!$componentStock || $componentStock->quantity <= 0) {
                                            $available = 0;
                                            break;
                                        }
                                        
                                        $availableForComponent = floor($componentStock->quantity / $component->quantity);
                                        $minAvailable = min($minAvailable, $availableForComponent);
                                    }
                                    $available = ($minAvailable !== PHP_INT_MAX) ? $minAvailable : 0;
                                }
                                
                                // Категория товара - ИСПРАВЛЕНО: используем product_category_id
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
                                {{ $product->name }} 
                                @if($available > 0)
                                    ({{ $available }} шт • {{ number_format($product->price, 2) }} ₽)
                                @else
                                    - нет в наличии
                                @endif
                            </option>
                            @endforeach
                        </select>
                        @if(isset($products) && $products->isEmpty())
                        <div class="alert alert-warning mt-2">
                            Нет доступных товаров для добавления
                        </div>
                        @endif
                    </div>
                    
                    <!-- Информация о товаре в две колонки -->
                    <div class="card mb-3 border-0 bg-light" id="productInfoCard" style="display: none;">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Товар:</small>
                                        <strong id="productName" class="text-primary">—</strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Категория:</small>
                                        <span class="badge bg-info" id="productCategory">—</span>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Единица измерения:</small>
                                        <strong id="productUnitInfo">—</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Цена за единицу:</small>
                                        <strong id="productUnitPrice" class="text-success">0.00 ₽</strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Доступно на складе:</small>
                                        <strong id="productAvailable" class="text-success">0</strong>
                                        <span id="productAvailableUnit"></span>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Остаток после добавления:</small>
                                        <strong id="remainingStock" class="text-warning">0</strong>
                                    </div>
                                </div>
                            </div>
                            <div id="compositeWarning" style="display: none;" class="mt-3 alert alert-warning p-2">
                                <i class="bi bi-info-circle me-1"></i> Составной товар - будет списан по рецепту
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ввод количества и цены в две колонки -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label fw-bold">Количество *</label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.001"
                                           min="0.001" 
                                           class="form-control" 
                                           id="quantity" 
                                           name="quantity" 
                                           value="1" 
                                           required
                                           oninput="checkQuantity()">
                                    <span class="input-group-text" id="quantityUnitLabel">шт</span>
                                </div>
                                <small class="text-muted" id="quantityInfo">Введите количество</small>
                                <div id="quantityWarning" class="alert alert-warning mt-2 p-2" style="display: none;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <span id="warningMessage"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_price" class="form-label fw-bold">Цена за единицу *</label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.01" 
                                           min="0.01" 
                                           class="form-control" 
                                           id="unit_price" 
                                           name="unit_price" 
                                           required>
                                    <span class="input-group-text" id="priceUnitLabel">₽/ед.</span>
                                    <button class="btn btn-outline-secondary" type="button" onclick="setDefaultPrice()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                                <small class="text-muted" id="priceInfo">Цена за единицу товара</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Расчет суммы -->
                    <div class="card mt-3 border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 mb-0">Сумма к добавлению:</span>
                                <span class="h5 mb-0 text-primary" id="preliminaryTotal">0.00 ₽</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-plus-circle me-1"></i>Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.product-option {
    padding: 6px 10px;
    border-bottom: 1px solid #eee;
    font-size: 0.9rem;
}
.product-option:last-child {
    border-bottom: none;
}
.product-option:disabled {
    color: #999;
    background-color: #f8f9fa;
}
select[multiple] option:checked {
    background-color: #e7f1ff;
    color: #0d6efd;
}
.input-group-text {
    font-size: 0.875rem;
}
.form-label {
    font-size: 0.875rem;
    font-weight: 600;
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