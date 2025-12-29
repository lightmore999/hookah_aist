<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addItemModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить товар в инвентаризацию
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="addItemForm" method="POST" action="{{ route('inventories.items.store', $inventory) }}">
                @csrf
                
                <div class="modal-body">
                    <!-- Выбор товара -->
                    <div class="mb-4">
                        <label for="product_id" class="form-label fw-bold">Товар *</label>
                        <select name="product_id" id="product_id" 
                                class="form-select" 
                                required>
                            <option value="">Выберите товар</option>
                            @if(isset($availableProducts) && count($availableProducts) > 0)
                                @foreach($availableProducts as $product)
                                    <option value="{{ $product['id'] }}" 
                                            data-system-quantity="{{ $product['system_quantity'] }}"
                                            data-unit="{{ $product['unit'] }}">
                                        {{ $product['name'] }} ({{ $product['current_stock'] }} {{ $product['unit'] }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @if(!isset($availableProducts) || count($availableProducts) == 0)
                            <div class="alert alert-warning mt-2 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Нет доступных товаров на складе или все товары уже добавлены
                            </div>
                        @endif
                    </div>

                    <!-- Системное количество (только для информации) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Системное количество (в базе)</label>
                        <div class="form-control-plaintext bg-light p-2 rounded">
                            <span id="systemQuantityDisplay">0</span> <span id="unitDisplay"></span>
                        </div>
                        <div class="form-text text-muted small">
                            Это количество товара, которое сейчас числится в системе
                        </div>
                    </div>

                    <!-- Фактическое количество -->
                    <div class="mb-4">
                        <label for="actual_quantity" class="form-label fw-bold">Фактическое количество *</label>
                        <input type="number" 
                            class="form-control" 
                            id="actual_quantity" 
                            name="actual_quantity" 
                            value="0" 
                            min="0" 
                            required>
                        <div class="form-text">
                            Введите фактическое количество товара на складе
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle me-1"></i>Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const systemQuantityDisplay = document.getElementById('systemQuantityDisplay');
    const unitDisplay = document.getElementById('unitDisplay');
    const actualQuantityInput = document.getElementById('actual_quantity');
    const addItemForm = document.getElementById('addItemForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Обновление системного количества при выборе товара
    if (productSelect) {
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const systemQty = selectedOption.getAttribute('data-system-quantity');
                const unit = selectedOption.getAttribute('data-unit') || 'ед.';
                
                systemQuantityDisplay.textContent = systemQty;
                unitDisplay.textContent = unit;
                
                // Устанавливаем фактическое количество равным системному
                actualQuantityInput.value = systemQty;
                submitBtn.disabled = false;
            } else {
                systemQuantityDisplay.textContent = '0';
                unitDisplay.textContent = '';
                actualQuantityInput.value = '0';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Проверяем, есть ли товары для выбора
    if (productSelect && productSelect.options.length <= 1) {
        submitBtn.disabled = true;
        submitBtn.title = "Нет доступных товаров для добавления";
    }
    
    // Обработка формы - обычная отправка, не AJAX
    // Просто позволим форме отправиться стандартным способом
    if (addItemForm) {
        // Можно оставить пустым или добавить простую валидацию
        addItemForm.addEventListener('submit', function(e) {
            // Простая проверка перед отправкой
            const productId = document.getElementById('product_id').value;
            const actualQuantity = document.getElementById('actual_quantity').value;
            
            if (!productId) {
                e.preventDefault();
                alert('Пожалуйста, выберите товар');
                return;
            }
            
            if (!actualQuantity || actualQuantity < 0) {
                e.preventDefault();
                alert('Пожалуйста, введите корректное количество');
                return;
            }
        });
    }
    
    // Сброс формы при закрытии модалки
    const addItemModal = document.getElementById('addItemModal');
    if (addItemModal) {
        addItemModal.addEventListener('hidden.bs.modal', function () {
            if (productSelect) {
                productSelect.selectedIndex = 0;
                systemQuantityDisplay.textContent = '0';
                unitDisplay.textContent = '';
                actualQuantityInput.value = '0';
                
                // Включаем кнопку если есть товары
                if (productSelect.options.length > 1) {
                    submitBtn.disabled = false;
                }
            }
        });
    }
});
</script>