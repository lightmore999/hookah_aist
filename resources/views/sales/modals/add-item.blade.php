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
                            <option value="{{ $product->id }}" 
                                    data-price="{{ $product->price }}"
                                    data-unit="{{ $product->unit ?? 'шт' }}"
                                    data-is-composite="{{ $product->is_composite ? '1' : '0' }}"
                                    data-cost="{{ $product->cost ?? 0 }}">
                                {{ $product->name }} ({{ $product->unit ?? 'шт' }})   
                            </option>
                            @endforeach
                        </select>
                        <!-- Добавляем эту проверку ПОСЛЕ select: -->
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
                                <div class="col-12">
                                    <small class="text-muted d-block">Цена за единицу:</small>
                                    <strong id="productUnitPrice">0.00 ₽</strong>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted d-block">Единица измерения:</small>
                                    <small id="productUnitInfo">—</small>
                                    @if($product->is_composite ?? false)
                                        <div class="mt-2 alert alert-warning p-2">
                                            <i class="bi bi-info-circle"></i> Составной товар
                                        </div>
                                    @endif
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
                                   required>
                            <span class="input-group-text" id="quantityUnitLabel">шт</span>
                        </div>
                        <small class="text-muted" id="quantityInfo"></small>
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
                    <button type="submit" class="btn btn-primary">
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
    isComposite: false
};

function updateProductInfo() {
    const productSelect = document.getElementById('product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const productInfoCard = document.getElementById('productInfoCard');
    
    if (productSelect.value) {
        currentProduct = {
            unit: selectedOption.dataset.unit || 'шт',
            price: parseFloat(selectedOption.dataset.price) || 0,
            cost: parseFloat(selectedOption.dataset.cost) || 0,
            isComposite: selectedOption.dataset.isComposite === '1'
        };
        
        // Показываем информацию о товаре
        productInfoCard.style.display = 'block';
        
        // Заполняем информацию
        document.getElementById('productUnitPrice').textContent = 
            currentProduct.price.toFixed(2) + ' ₽';
        document.getElementById('productUnitInfo').textContent = 
            `Единица измерения: ${currentProduct.unit}`;
        
        // Обновляем поля
        updateQuantityFields();
        updatePriceFields();
        calculateTotal();
    } else {
        productInfoCard.style.display = 'none';
        currentProduct = { unit: 'шт', price: 0, isComposite: false };
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

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    // Слушаем изменения в полях
    document.getElementById('quantity').addEventListener('input', calculateTotal);
    document.getElementById('unit_price').addEventListener('input', calculateTotal);
    
    // Если уже выбран товар - обновляем информацию
    const productSelect = document.getElementById('product_id');
    if (productSelect.value) {
        updateProductInfo();
    }
});

</script>