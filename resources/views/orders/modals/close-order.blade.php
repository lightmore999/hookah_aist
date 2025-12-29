<div class="modal fade" id="closeOrderModal" tabindex="-1" aria-labelledby="closeOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="closeOrderModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Закрыть заказ #<span id="closeOrderId"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" id="closeOrderForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="Status" value="completed">
                
                <div class="modal-body">
                    <!-- Информация о сумме -->
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Сумма позиций:</span>
                                <strong id="itemsTotal">0.00 ₽</strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Чаевые -->
                    <div class="mb-3">
                        <label for="closeTips" class="form-label fw-bold">Чаевые</label>
                        <div class="input-group">
                            <input type="number" 
                                   min="0" 
                                   step="0.01"
                                   class="form-control" 
                                   id="closeTips" 
                                   name="Tips" 
                                   value="0">
                            <span class="input-group-text">₽</span>
                        </div>
                        <div class="form-text">Необязательно</div>
                    </div>
                    
                    <!-- Скидка -->
                    <div class="mb-3">
                        <label for="closeDiscount" class="form-label fw-bold">Скидка</label>
                        <div class="input-group">
                            <input type="number" 
                                   min="0" 
                                   step="0.01"
                                   class="form-control" 
                                   id="closeDiscount" 
                                   name="Discount" 
                                   value="0">
                            <span class="input-group-text">₽</span>
                        </div>
                        <div class="form-text">Необязательно</div>
                    </div>
                    
                    <!-- Способ оплаты -->
                    <div class="mb-3">
                        <label for="closePaymentMethod" class="form-label fw-bold">Способ оплаты *</label>
                        <select class="form-select" 
                                id="closePaymentMethod" 
                                name="PaymentMethod" 
                                required>
                            <option value="">Выберите способ оплаты</option>
                            <option value="cash">Наличные</option>
                            <option value="card">Карта</option>
                        </select>
                    </div>
                    
                    <!-- Комментарий -->
                    <div class="mb-3">
                        <label for="closeComment" class="form-label">Комментарий</label>
                        <textarea class="form-control" 
                                  id="closeComment" 
                                  name="Comment" 
                                  rows="2"
                                  placeholder="Примечания к заказу..."></textarea>
                    </div>
                    
                    <!-- Итоговая сумма -->
                    <div class="card mt-3 border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">Итого к оплате:</span>
                                <span class="h4 mb-0 text-success" id="finalTotal">0.00 ₽</span>
                            </div>
                            <small class="text-muted">Сумма позиций - Скидка + Чаевые</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Закрыть заказ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const closeOrderModal = document.getElementById('closeOrderModal');
    const itemsTotalElem = document.getElementById('itemsTotal');
    const finalTotalElem = document.getElementById('finalTotal');
    const closeTipsInput = document.getElementById('closeTips');
    const closeDiscountInput = document.getElementById('closeDiscount');
    
    let currentItemsTotal = 0;
    
    function calculateFinalTotal() {
        const tips = parseFloat(closeTipsInput.value) || 0;
        const discount = parseFloat(closeDiscountInput.value) || 0;
        const finalTotal = currentItemsTotal - discount + tips;
        
        itemsTotalElem.textContent = currentItemsTotal.toFixed(2) + ' ₽';
        finalTotalElem.textContent = finalTotal.toFixed(2) + ' ₽';
    }
    
    if (closeOrderModal) {
        closeOrderModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('close-order-btn')) {
                // Устанавливаем ID заказа
                const orderId = button.dataset.id;
                document.getElementById('closeOrderId').textContent = orderId;
                document.getElementById('closeOrderForm').action = `/orders/${orderId}`;
                
                // Получаем сумму позиций
                currentItemsTotal = parseFloat(button.dataset.itemsTotal) || 0;
                
                // Заполняем существующие значения
                document.getElementById('closeTips').value = button.dataset.tips || 0;
                document.getElementById('closeDiscount').value = button.dataset.discount || 0;
                document.getElementById('closePaymentMethod').value = button.dataset.paymentmethod || '';
                document.getElementById('closeComment').value = button.dataset.comment || '';
                
                // Рассчитываем итог
                calculateFinalTotal();
            }
        });
    }
    
    // Пересчет при изменении чаевых или скидки
    if (closeTipsInput) {
        closeTipsInput.addEventListener('input', calculateFinalTotal);
    }
    
    if (closeDiscountInput) {
        closeDiscountInput.addEventListener('input', calculateFinalTotal);
    }
});
</script>