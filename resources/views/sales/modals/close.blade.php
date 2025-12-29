<div class="modal fade" id="closeSaleModal" tabindex="-1" aria-labelledby="closeSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="closeSaleModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Завершить продажу #<span id="closeSaleId"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" id="closeSaleForm">
                @csrf
                
                <div class="modal-body">
                    <!-- Информация о клиенте и бонусах -->
                    <div class="alert alert-info mb-3" id="clientBonusInfo" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-person-circle me-2"></i>
                                <strong id="clientName"></strong>
                                <div class="small mt-1">Доступно бонусов: <span id="clientBonusPoints" class="badge bg-primary"></span></div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Можно использовать:</div>
                                <div><strong id="maxUsableBonuses" class="text-success"></strong> бонусов</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Детализация сумм -->
                    <div class="card mb-3 border-0 bg-light">
                        <div class="card-body p-3">
                            <div class="row mb-2">
                                <div class="col-6">Товары:</div>
                                <div class="col-6 text-end" id="closeItemsTotal">0.00 ₽</div>
                            </div>
                            <div class="row mb-2" id="hookahRow" style="display: none;">
                                <div class="col-6">Кальяны:</div>
                                <div class="col-6 text-end" id="closeHookahsTotal">0.00 ₽</div>
                            </div>
                            <div class="row mb-2" id="bonusDiscountRow" style="display: none;">
                                <div class="col-6 text-danger">Скидка бонусами:</div>
                                <div class="col-6 text-end text-danger" id="closeBonusDiscount">-0.00 ₽</div>
                            </div>
                            <div class="row" id="subtotalRow" style="display: none;">
                                <div class="col-6"><strong>Промежуточный итог:</strong></div>
                                <div class="col-6 text-end" id="closeSubtotal">0.00 ₽</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Скидка -->
                    <div class="mb-3">
                        <label for="closeDiscount" class="form-label fw-bold">Скидка</label>
                        
                        <div class="row mb-2">
                            <div class="col-4">
                                <select class="form-select form-select-sm" id="discountTypeSelect">
                                    <option value="fixed">₽ Сумма</option>
                                    <option value="percent">% Процент</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control" 
                                        id="closeDiscount" 
                                        name="discount" 
                                        value="0"
                                        placeholder="0">
                                    <span class="input-group-text" id="discountSuffix">₽</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-2 small" id="discountConversion" style="display: none;">
                            <span class="text-muted">Скидка составит: </span>
                            <span id="discountAmount" class="fw-bold">0.00 ₽</span>
                        </div>
                        
                        <div class="form-text">Необязательно</div>
                    </div>
                    
                    <!-- Использование бонусов -->
                    <div class="mb-3 border p-3 rounded" id="bonusSection" style="display: none;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="useBonuses" 
                                   name="use_bonuses" 
                                   value="1">
                            <label class="form-check-label fw-bold" for="useBonuses">
                                Использовать бонусы
                            </label>
                        </div>
                        
                        <div class="row align-items-center" id="bonusInputRow" style="display: none;">
                            <div class="col-8">
                                <label for="bonusPointsToUse" class="form-label small mb-1">Сколько бонусов использовать:</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" 
                                           min="0" 
                                           step="1"
                                           class="form-control" 
                                           id="bonusPointsToUse" 
                                           name="bonus_points_to_use" 
                                           value="0"
                                           disabled>
                                    <span class="input-group-text">бонусов</span>
                                </div>
                                <div class="form-text small">
                                    1 бонус = 1 рубль
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm" 
                                        id="useMaxBonusesBtn">
                                    <i class="bi bi-star-fill"></i> Максимум
                                </button>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-2 small" id="bonusWarning" style="display: none;">
                            <i class="bi bi-info-circle me-1"></i>
                            <span id="bonusWarningText"></span>
                        </div>
                    </div>
                    
                    <!-- Способ оплаты -->
                    <div class="mb-3">
                        <label for="closePaymentMethod" class="form-label fw-bold">Способ оплаты *</label>
                        <select class="form-select" 
                                id="closePaymentMethod" 
                                name="payment_method" 
                                required>
                            <option value="">Выберите способ оплаты</option>
                            <option value="cash">Наличные</option>
                            <option value="card">Карта</option>
                            <option value="online">Онлайн</option>
                            <option value="terminal">Терминал</option>
                        </select>
                    </div>
                    
                    <!-- Комментарий -->
                    <div class="mb-3">
                        <label for="closeComment" class="form-label">Комментарий</label>
                        <textarea class="form-control" 
                                id="closeComment" 
                                name="comment" 
                                rows="2"
                                placeholder="Примечания к продаже..."></textarea>
                    </div>
                    
                    <!-- Итоговая сумма -->
                    <div class="card mt-3 border-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">Итого к оплате:</span>
                                <span class="h4 mb-0 text-success" id="closeFinalTotal">0.00 ₽</span>
                            </div>
                            <small class="text-muted" id="finalTotalBreakdown">(Товары + Кальяны) - Скидка</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <small>При завершении продажи товары будут списаны со склада!</small>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Завершить продажу
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const discountTypeFixed = document.getElementById('discountTypeFixed');
    const discountTypePercent = document.getElementById('discountTypePercent');
    const closeDiscountInput = document.getElementById('closeDiscount');
    const discountSuffix = document.getElementById('discountSuffix');
    const discountConversion = document.getElementById('discountConversion');
    const discountAmount = document.getElementById('discountAmount');
    
    let currentDiscountType = 'fixed'; // 'fixed' или 'percent'
    let currentItemsTotal = 0;
    let currentHookahsTotal = 0;
    let currentSubtotal = 0;

    // Функция для обновления отображения скидки
    function updateDiscountDisplay() {
        const discountValue = parseFloat(closeDiscountInput.value) || 0;
        
        if (currentDiscountType === 'percent') {
            // Показываем конвертацию
            discountConversion.style.display = 'block';
            
            // Рассчитываем сумму скидки
            const subtotal = currentItemsTotal + currentHookahsTotal;
            const discountInRubles = (subtotal * discountValue / 100);
            
            discountAmount.textContent = discountInRubles.toFixed(2) + ' ₽';
            discountSuffix.textContent = '%';
            
            // Устанавливаем максимум 100% для процентов
            closeDiscountInput.max = 100;
            closeDiscountInput.step = "0.1";
            closeDiscountInput.placeholder = "0-100";
        } else {
            // Скрываем конвертацию для фиксированной суммы
            discountConversion.style.display = 'none';
            discountSuffix.textContent = '₽';
            
            // Снимаем ограничение максимума
            closeDiscountInput.removeAttribute('max');
            closeDiscountInput.step = "0.01";
            closeDiscountInput.placeholder = "0";
        }
        
        // Пересчитываем итоговую сумму
        calculateFinalTotal();
    }

    // Обработчики для переключателей
    if (discountTypeFixed) {
        discountTypeFixed.addEventListener('change', function() {
            if (this.checked) {
                currentDiscountType = 'fixed';
                updateDiscountDisplay();
            }
        });
    }

    if (discountTypePercent) {
        discountTypePercent.addEventListener('change', function() {
            if (this.checked) {
                currentDiscountType = 'percent';
                updateDiscountDisplay();
            }
        });
    }

    // Обработчик изменения значения скидки
    if (closeDiscountInput) {
        closeDiscountInput.addEventListener('input', function() {
            updateDiscountDisplay();
        });
    }

    // Функция для получения скидки в рублях (для отправки на сервер)
    function getDiscountInRubles() {
        const discountValue = parseFloat(closeDiscountInput.value) || 0;
        
        if (currentDiscountType === 'percent') {
            const subtotal = currentItemsTotal + currentHookahsTotal;
            return (subtotal * discountValue / 100);
        }
        
        return discountValue;
    }

    // Модифицируем существующую функцию calculateFinalTotal
    // (предполагая, что она уже есть в вашем коде)
    window.calculateFinalTotal = function() {
        const discountInRubles = getDiscountInRubles();
        const subtotal = currentItemsTotal + currentHookahsTotal;
        const finalTotal = Math.max(0, subtotal - discountInRubles - currentBonusDiscount);
        
        // Обновляем отображение (ваш существующий код)
        // ... ваш существующий код обновления интерфейса ...
        
        return finalTotal;
    };

    // Модифицируем обработчик открытия модалки
    const closeSaleModal = document.getElementById('closeSaleModal');
    if (closeSaleModal) {
        closeSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('close-sale-btn')) {
                // Ваш существующий код установки данных
                
                // Сбрасываем тип скидки на "фиксированная сумма"
                if (discountTypeFixed) discountTypeFixed.checked = true;
                if (discountTypePercent) discountTypePercent.checked = false;
                
                // Сбрасываем значение скидки
                if (closeDiscountInput) {
                    closeDiscountInput.value = button.dataset.discount || 0;
                }
                
                // Обновляем отображение
                currentDiscountType = 'fixed';
                updateDiscountDisplay();
            }
        });
    }

    // Модифицируем отправку формы - конвертируем проценты в рубли перед отправкой
    const closeSaleForm = document.getElementById('closeSaleForm');
    if (closeSaleForm) {
        closeSaleForm.addEventListener('submit', function(e) {
            // Если выбраны проценты, конвертируем в рубли
            if (currentDiscountType === 'percent') {
                const discountInRubles = getDiscountInRubles();
                
                // Создаем скрытое поле для отправки скидки в рублях
                const hiddenDiscountField = document.createElement('input');
                hiddenDiscountField.type = 'hidden';
                hiddenDiscountField.name = 'discount';
                hiddenDiscountField.value = discountInRubles.toFixed(2);
                
                // Добавляем в форму
                this.appendChild(hiddenDiscountField);
                
                // Отключаем оригинальное поле (чтобы не отправлялось два значения)
                closeDiscountInput.disabled = true;
            }
        });
    }
});
</script>