<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addItemModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить товар в заказ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('orders.product-items.store', $order->IDOrder) }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="IDProduct" class="form-label fw-bold">Товар *</label>
                        <select class="form-select" id="IDProduct" name="IDProduct" required>
                            <option value="">Выберите товар</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}">
                                    {{ $product->name }} ({{ number_format($product->price, 2) }} ₽)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="Quantity" class="form-label fw-bold">Количество *</label>
                            <input type="number" 
                                   min="1" 
                                   class="form-control" 
                                   id="Quantity" 
                                   name="Quantity" 
                                   value="1" 
                                   required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="UnitPrice" class="form-label fw-bold">Цена за единицу *</label>
                            <div class="input-group">
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control" 
                                       id="UnitPrice" 
                                       name="UnitPrice" 
                                       required>
                                <span class="input-group-text">₽</span>
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
document.addEventListener('DOMContentLoaded', function() {
    // Автозаполнение цены при выборе товара
    const productSelect = document.getElementById('IDProduct');
    const unitPriceInput = document.getElementById('UnitPrice');
    
    if (productSelect && unitPriceInput) {
        productSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.price) {
                unitPriceInput.value = selectedOption.dataset.price;
            }
        });
    }
});
</script>