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
                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-bold">Товар *</label>
                        <select class="form-select" id="product_id" name="product_id" required onchange="updateProductInfo()">
                            <option value="">Выберите товар</option>
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
                            @endphp
                            <option value="{{ $product->id }}" 
                                    data-price="{{ $product->price }}"
                                    data-unit="{{ $product->unit ?? 'шт' }}"
                                    data-is-composite="{{ $product->is_composite ? '1' : '0' }}"
                                    data-cost="{{ $product->cost ?? 0 }}"
                                    data-available="{{ $available }}">
                                {{ $product->name }} ({{ $product->unit ?? 'шт' }})
                                @if($available > 0)
                                    - доступно: {{ $available }}
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
                    
                    <!-- Информация о товаре -->
                    <div class="card mb-3 border-0 bg-light" id="productInfoCard" style="display: none;">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Цена за единицу:</small>
                                    <strong id="productUnitPrice">0.00 ₽</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Доступно на складе:</small>
                                    <strong id="productAvailable" class="text-success">0</strong>
                                    <span id="productAvailableUnit"></span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted d-block">Единица измерения:</small>
                                    <small id="productUnitInfo">—</small>
                                    <div id="compositeWarning" style="display: none;" class="mt-2 alert alert-warning p-2">
                                        <i class="bi bi-info-circle"></i> Составной товар
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ввод количества -->
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
                        <small class="text-muted" id="quantityInfo"></small>
                        <div id="quantityWarning" class="alert alert-warning mt-2 p-2" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <span id="warningMessage"></span>
                        </div>
                    </div>
                    
                    <!-- Ввод цены -->
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
                        </div>
                        <small class="text-muted" id="priceInfo"></small>
                    </div>
                    
                    <!-- Расчет суммы -->
                    <div class="card mt-3 border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 mb-0">Предварительная сумма:</span>
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
                        Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentProduct = {
    unit: 'шт',
    price: 0,
    isComposite: false,
    available: 0
};

function updateProductInfo() {
    const productSelect = document.getElementById('product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const productInfoCard = document.getElementById('productInfoCard');
    const compositeWarning = document.getElementById('compositeWarning');
    
    if (productSelect.value) {
        currentProduct = {
            unit: selectedOption.dataset.unit || 'шт',
            price: parseFloat(selectedOption.dataset.price) || 0,
            cost: parseFloat(selectedOption.dataset.cost) || 0,
            isComposite: selectedOption.dataset.isComposite === '1',
            available: parseFloat(selectedOption.dataset.available) || 0
        };
        
        // Показываем информацию о товаре
        productInfoCard.style.display = 'block';
        
        // Заполняем информацию
        document.getElementById('productUnitPrice').textContent = 
            currentProduct.price.toFixed(2) + ' ₽';
        document.getElementById('productAvailable').textContent = 
            currentProduct.available;
        document.getElementById('productAvailableUnit').textContent = 
            currentProduct.unit;
        document.getElementById('productUnitInfo').textContent = 
            `Единица измерения: ${currentProduct.unit}`;
        
        // Показываем/скрываем предупреждение о составном товаре
        if (currentProduct.isComposite) {
            compositeWarning.style.display = 'block';
        } else {
            compositeWarning.style.display = 'none';
        }
        
        // Обновляем поля
        updateQuantityFields();
        updatePriceFields();
        checkQuantity();
        calculateTotal();
    } else {
        productInfoCard.style.display = 'none';
        currentProduct = { unit: 'шт', price: 0, isComposite: false, available: 0 };
        hideWarning();
    }
}

function updateQuantityFields() {
    const quantityInput = document.getElementById('quantity');
    const quantityUnitLabel = document.getElementById('quantityUnitLabel');
    const quantityInfo = document.getElementById('quantityInfo');
    
    // Устанавливаем правильный step в зависимости от единицы измерения
    if (currentProduct.unit === 'шт') {
        quantityInput.step = '1';
        quantityInput.min = '1';
        quantityInput.value = '1';
        quantityInfo.textContent = 'Для штучных товаров количество должно быть целым числом';
    } else {
        quantityInput.step = '0.001';
        quantityInput.min = '0.001';
        quantityInput.value = '1';
        quantityInfo.textContent = `Единица измерения: ${currentProduct.unit}`;
    }
    
    quantityUnitLabel.textContent = currentProduct.unit;
    
    calculateTotal();
}

function updatePriceFields() {
    const priceInput = document.getElementById('unit_price');
    const priceUnitLabel = document.getElementById('priceUnitLabel');
    
    // Устанавливаем цену по умолчанию из товара
    priceInput.value = currentProduct.price.toFixed(2);
    priceUnitLabel.textContent = `₽/${currentProduct.unit}`;
    
    calculateTotal();
}

function checkQuantity() {
    const quantityInput = document.getElementById('quantity');
    const quantityWarning = document.getElementById('quantityWarning');
    const warningMessage = document.getElementById('warningMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    const requested = parseFloat(quantityInput.value) || 0;
    const available = currentProduct.available || 0;
    
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
        showWarning(`Запрошено: ${requested}, доступно: ${available}. Можно добавить максимум ${available} ${currentProduct.unit}`, 'danger');
        submitBtn.disabled = true;
        return;
    }
    
    // Если все хорошо
    hideWarning();
    submitBtn.disabled = false;
}

function showWarning(message, type = 'warning') {
    const quantityWarning = document.getElementById('quantityWarning');
    const warningMessage = document.getElementById('warningMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    quantityWarning.style.display = 'block';
    quantityWarning.className = `alert alert-${type} mt-2 p-2`;
    warningMessage.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i> ${message}`;
    submitBtn.disabled = true;
}

function hideWarning() {
    const quantityWarning = document.getElementById('quantityWarning');
    const submitBtn = document.getElementById('submitBtn');
    
    quantityWarning.style.display = 'none';
    submitBtn.disabled = false;
}

function calculateTotal() {
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('unit_price');
    const preliminaryTotal = document.getElementById('preliminaryTotal');
    
    const quantity = parseFloat(quantityInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    
    const total = quantity * price;
    preliminaryTotal.textContent = total.toFixed(2) + ' ₽';
    
    // Также обновляем скрытые поля для сервера
    updateHiddenFields(quantity, price);
}

function updateHiddenFields(quantity, pricePerUnit) {
    // Создаем или обновляем скрытые поля
    let hiddenQuantity = document.getElementById('final_quantity');
    let hiddenPrice = document.getElementById('final_unit_price');
    
    if (!hiddenQuantity) {
        hiddenQuantity = document.createElement('input');
        hiddenQuantity.type = 'hidden';
        hiddenQuantity.id = 'final_quantity';
        hiddenQuantity.name = 'final_quantity';
        document.getElementById('addItemForm').appendChild(hiddenQuantity);
    }
    
    if (!hiddenPrice) {
        hiddenPrice = document.createElement('input');
        hiddenPrice.type = 'hidden';
        hiddenPrice.id = 'final_unit_price';
        hiddenPrice.name = 'final_unit_price';
        document.getElementById('addItemForm').appendChild(hiddenPrice);
    }
    
    hiddenQuantity.value = quantity.toFixed(3);
    hiddenPrice.value = pricePerUnit.toFixed(2);
}

// Добавляем AJAX проверку при отправке формы для двойной проверки
document.getElementById('addItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const productId = document.getElementById('product_id').value;
    const quantity = document.getElementById('quantity').value;
    const unitPrice = document.getElementById('unit_price').value;
    
    if (!productId || !quantity || !unitPrice) {
        alert('Заполните все обязательные поля');
        return;
    }
    
    // Проверяем доступность через AJAX перед отправкой
    fetch('{{ route("sales.check-stock", $sale->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity,
            unit_price: unitPrice
        })
    })
    .then(response => response.json())
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
                // Повторяем проверку
                checkQuantity();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка проверки товара. Попробуйте еще раз.');
    });
});

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    // Слушаем изменения в полях
    document.getElementById('quantity').addEventListener('input', function() {
        checkQuantity();
        calculateTotal();
    });
    document.getElementById('unit_price').addEventListener('input', calculateTotal);
    
    // Если уже выбран товар - обновляем информацию
    const productSelect = document.getElementById('product_id');
    if (productSelect.value) {
        updateProductInfo();
    }
});
</script>