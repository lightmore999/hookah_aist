<div class="modal fade" id="closeSaleModal" tabindex="-1" aria-labelledby="closeSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                    <div class="row">
                        <!-- Левая колонка - Информация и суммы -->
                        <div class="col-md-6">
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
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0"><i class="bi bi-cash-stack me-1"></i>Расчет</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row mb-2">
                                        <div class="col-7 small">Товары:</div>
                                        <div class="col-5 text-end small" id="closeItemsTotal">0.00 ₽</div>
                                    </div>
                                    <div class="row mb-2" id="hookahRow" style="display: none;">
                                        <div class="col-7 small">Кальяны:</div>
                                        <div class="col-5 text-end small" id="closeHookahsTotal">0.00 ₽</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-7"><small>Промежуточный итог:</small></div>
                                        <div class="col-5 text-end" id="closeSubtotal"><small>0.00 ₽</small></div>
                                    </div>
                                    <div class="row mb-2" id="regularDiscountRow" style="display: none;">
                                        <div class="col-7 small text-success">Скидка:</div>
                                        <div class="col-5 text-end small text-success" id="closeRegularDiscount">-0.00 ₽</div>
                                    </div>
                                    <div class="row mb-2" id="bonusDiscountRow" style="display: none;">
                                        <div class="col-7 small text-danger">Скидка бонусами:</div>
                                        <div class="col-5 text-end small text-danger" id="closeBonusDiscount">-0.00 ₽</div>
                                    </div>
                                    <hr class="my-2">
                                    <div class="row mt-2">
                                        <div class="col-7"><strong>Итого к оплате:</strong></div>
                                        <div class="col-5 text-end">
                                            <strong id="closeFinalTotal" class="h5 text-primary mb-0">0.00 ₽</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Калькулятор сдачи -->
                            <div class="card mb-3 border-info shadow-sm" id="cashCalculator" style="display: none;">
                                <div class="card-header bg-info bg-opacity-10 border-info py-2">
                                    <h6 class="mb-0"><i class="bi bi-calculator me-1"></i>Калькулятор сдачи</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small">К оплате:</span>
                                            <strong id="calcTotalAmount" class="text-info">0.00 ₽</strong>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cashReceived" class="form-label small mb-1">
                                                <i class="bi bi-cash-stack me-1"></i>Получено от клиента:
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" 
                                                       min="0" 
                                                       step="0.01"
                                                       class="form-control" 
                                                       id="cashReceived" 
                                                       placeholder="0.00">
                                                <span class="input-group-text">₽</span>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-success py-2 mb-2" id="calcResult" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span><i class="bi bi-cash-coin me-1"></i>Сдача:</span>
                                                <strong id="changeAmount" class="h5 mb-0">0.00 ₽</strong>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-danger py-2 small" id="insufficientCash" style="display: none;">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            <span>Нужно еще: <strong id="missingAmount">0.00 ₽</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Правая колонка - Настройки -->
                        <div class="col-md-6">
                            <!-- Скидка -->
                            <div class="mb-4">
                                <label for="closeDiscount" class="form-label fw-bold">Скидка</label>
                                
                                <div class="row mb-2">
                                    <div class="col-5">
                                        <select class="form-select form-select-sm" id="discountTypeSelect">
                                            <option value="fixed">₽ Сумма</option>
                                            <option value="percent">% Процент</option>
                                        </select>
                                    </div>
                                    <div class="col-7">
                                        <div class="input-group input-group-sm">
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
                                
                                <div class="form-text small">Необязательно</div>
                            </div>
                            
                            <!-- Использование бонусов -->
                            <div class="mb-4 border p-3 rounded" id="bonusSection" style="display: none;">
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
                                        <label for="bonusPointsToUse" class="form-label small mb-1">Количество бонусов:</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" 
                                                   min="0" 
                                                   step="1"
                                                   class="form-control" 
                                                   id="bonusPointsToUse" 
                                                   name="bonus_points_to_use" 
                                                   value="0"
                                                   disabled>
                                            <span class="input-group-text">баллов</span>
                                        </div>
                                        <div class="form-text small">
                                            1 бонус = 1 рубль
                                        </div>
                                    </div>
                                    <div class="col-4 text-end pt-3">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-sm" 
                                                id="useMaxBonusesBtn">
                                            <i class="bi bi-star-fill"></i> Макс
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning mt-2 small p-2" id="bonusWarning" style="display: none;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <span id="bonusWarningText" class="small"></span>
                                </div>
                            </div>
                            
                            <!-- Способ оплаты -->
                            <div class="mb-4">
                                <label for="closePaymentMethodId" class="form-label fw-bold">Способ оплаты *</label>
                                <select class="form-select" 
                                        id="closePaymentMethodId" 
                                        name="payment_method_id" 
                                        required>
                                    <option value="">Выберите способ оплаты</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->IDPaymentMethod }}">{{ $method->Name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Комментарий -->
                            <div class="mb-4">
                                <label for="closeComment" class="form-label fw-bold">Комментарий</label>
                                <textarea class="form-control" 
                                        id="closeComment" 
                                        name="comment" 
                                        rows="2"
                                        placeholder="Примечания к продаже..."></textarea>
                            </div>
                            
                            <div class="alert alert-warning p-2 small">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <small>При завершении продажи товары будут списаны со склада!</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle me-1"></i>Завершить продажу
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Элементы DOM из модального окна
    const closeSaleModal = document.getElementById('closeSaleModal');
    const closeDiscountInput = document.getElementById('closeDiscount');
    const discountTypeSelect = document.getElementById('discountTypeSelect');
    const discountSuffix = document.getElementById('discountSuffix');
    const discountConversion = document.getElementById('discountConversion');
    const discountAmount = document.getElementById('discountAmount');
    
    const itemsTotalElem = document.getElementById('closeItemsTotal');
    const hookahsTotalElem = document.getElementById('closeHookahsTotal');
    const hookahRow = document.getElementById('hookahRow');
    const subtotalElem = document.getElementById('closeSubtotal');
    const regularDiscountRow = document.getElementById('regularDiscountRow');
    const regularDiscountElem = document.getElementById('closeRegularDiscount');
    const bonusDiscountRow = document.getElementById('bonusDiscountRow');
    const bonusDiscountElem = document.getElementById('closeBonusDiscount');
    const finalTotalElem = document.getElementById('closeFinalTotal');
    
    // Элементы бонусов
    const clientBonusInfo = document.getElementById('clientBonusInfo');
    const clientNameElem = document.getElementById('clientName');
    const clientBonusPointsElem = document.getElementById('clientBonusPoints');
    const maxUsableBonusesElem = document.getElementById('maxUsableBonuses');
    const bonusSection = document.getElementById('bonusSection');
    const useBonusesCheckbox = document.getElementById('useBonuses');
    const bonusPointsToUseInput = document.getElementById('bonusPointsToUse');
    const bonusInputRow = document.getElementById('bonusInputRow');
    const useMaxBonusesBtn = document.getElementById('useMaxBonusesBtn');
    const bonusWarning = document.getElementById('bonusWarning');
    const bonusWarningText = document.getElementById('bonusWarningText');
    
    // Элементы калькулятора сдачи
    const cashCalculator = document.getElementById('cashCalculator');
    const calcTotalAmount = document.getElementById('calcTotalAmount');
    const cashReceivedInput = document.getElementById('cashReceived');
    const calcResult = document.getElementById('calcResult');
    const changeAmount = document.getElementById('changeAmount');
    const insufficientCash = document.getElementById('insufficientCash');
    const missingAmount = document.getElementById('missingAmount');
    const paymentMethodSelect = document.getElementById('closePaymentMethodId');
    
    // Переменные состояния
    let currentItemsTotal = 0;
    let currentHookahsTotal = 0;
    let currentClientId = null;
    let currentClientName = '';
    let currentClientBonusPoints = 0;
    let maxUsableBonuses = 0;
    let currentBonusDiscount = 0;
    let currentDiscountType = 'fixed';
    let maxSpendPercent = 50;
    let currentFinalTotal = 0;
    
    // Функция для получения скидки в рублях
    function getDiscountInRubles() {
        if (!closeDiscountInput) return 0;
        
        const discountValue = parseFloat(closeDiscountInput.value) || 0;
        
        if (currentDiscountType === 'percent') {
            const subtotal = currentItemsTotal + currentHookahsTotal;
            return (subtotal * discountValue / 100);
        }
        
        return discountValue;
    }
    
    // Функция расчета итоговой суммы
    function calculateFinalTotal() {
        const discountInRubles = getDiscountInRubles();
        const subtotal = currentItemsTotal + currentHookahsTotal;
        currentFinalTotal = Math.max(0, subtotal - discountInRubles - currentBonusDiscount);
        
        // Обновляем отображение
        if (itemsTotalElem) itemsTotalElem.textContent = currentItemsTotal.toFixed(2) + ' ₽';
        
        // Показываем/скрываем блок с кальянами
        if (hookahRow && hookahsTotalElem) {
            if (currentHookahsTotal > 0) {
                hookahRow.style.display = 'flex';
                hookahsTotalElem.textContent = currentHookahsTotal.toFixed(2) + ' ₽';
            } else {
                hookahRow.style.display = 'none';
            }
        }
        
        // Промежуточный итог
        if (subtotalElem) subtotalElem.textContent = subtotal.toFixed(2) + ' ₽';
        
        // Показываем/скрываем обычную скидку
        if (regularDiscountRow && regularDiscountElem) {
            if (discountInRubles > 0) {
                regularDiscountRow.style.display = 'flex';
                regularDiscountElem.textContent = '-' + discountInRubles.toFixed(2) + ' ₽';
            } else {
                regularDiscountRow.style.display = 'none';
            }
        }
        
        // Показываем/скрываем скидку бонусами
        if (bonusDiscountRow && bonusDiscountElem) {
            if (currentBonusDiscount > 0) {
                bonusDiscountRow.style.display = 'flex';
                bonusDiscountElem.textContent = '-' + currentBonusDiscount.toFixed(2) + ' ₽';
            } else {
                bonusDiscountRow.style.display = 'none';
            }
        }
        
        // Итоговая сумма
        if (finalTotalElem) finalTotalElem.textContent = currentFinalTotal.toFixed(2) + ' ₽';
        
        // Обновляем сумму в калькуляторе сдачи
        if (calcTotalAmount) {
            calcTotalAmount.textContent = currentFinalTotal.toFixed(2) + ' ₽';
        }
        
        // Если выбран способ оплаты "Наличные", показываем калькулятор
        if (paymentMethodSelect && paymentMethodSelect.value) {
            const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
            const paymentMethodName = selectedOption.text.toLowerCase();
            
            if (paymentMethodName.includes('налич')) {
                cashCalculator.style.display = 'block';
                // Устанавливаем значение в поле полученной суммы
                if (cashReceivedInput) {
                    cashReceivedInput.value = currentFinalTotal.toFixed(2);
                }
                calculateChange();
            } else {
                cashCalculator.style.display = 'none';
                calcResult.style.display = 'none';
                insufficientCash.style.display = 'none';
            }
        }
        
        return currentFinalTotal;
    }
    
    // Функция для обновления отображения скидки
    function updateDiscountDisplay() {
        if (!closeDiscountInput) return;
        
        const discountValue = parseFloat(closeDiscountInput.value) || 0;
        
        if (currentDiscountType === 'percent') {
            // Показываем конвертацию
            if (discountConversion) discountConversion.style.display = 'block';
            
            // Рассчитываем сумму скидки
            const subtotal = currentItemsTotal + currentHookahsTotal;
            const discountInRubles = (subtotal * discountValue / 100);
            
            if (discountAmount) discountAmount.textContent = discountInRubles.toFixed(2) + ' ₽';
            if (discountSuffix) discountSuffix.textContent = '%';
            
            // Устанавливаем максимум 100% для процентов
            closeDiscountInput.max = 100;
            closeDiscountInput.step = "0.1";
            closeDiscountInput.placeholder = "0-100";
        } else {
            // Скрываем конвертацию для фиксированной суммы
            if (discountConversion) discountConversion.style.display = 'none';
            if (discountSuffix) discountSuffix.textContent = '₽';
            
            // Снимаем ограничение максимума
            closeDiscountInput.removeAttribute('max');
            closeDiscountInput.step = "0.01";
            closeDiscountInput.placeholder = "0";
        }
        
        // Пересчитываем итоговую сумму и обновляем бонусы
        calculateFinalTotal();
        updateBonusInfo();
    }
    
    // Функция обновления информации о бонусах
    function updateBonusInfo() {
        if (!currentClientId) {
            // Нет клиента - скрываем секцию бонусов
            if (clientBonusInfo) clientBonusInfo.style.display = 'none';
            if (bonusSection) bonusSection.style.display = 'none';
            currentBonusDiscount = 0;
            return;
        }
        
        // Показываем информацию о клиенте
        if (clientBonusInfo) clientBonusInfo.style.display = 'block';
        if (clientNameElem) clientNameElem.textContent = currentClientName;
        if (clientBonusPointsElem) clientBonusPointsElem.textContent = currentClientBonusPoints.toLocaleString();
        
        // Рассчитываем максимальное количество бонусов
        const discountInRubles = getDiscountInRubles();
        const totalAmount = currentItemsTotal + currentHookahsTotal;
        
        // Максимум бонусов = X% от (сумма товаров - скидка)
        const amountAfterDiscount = Math.max(0, totalAmount - discountInRubles);
        const percentage = maxSpendPercent / 100;
        maxUsableBonuses = Math.floor(amountAfterDiscount * percentage);
        
        // Нельзя использовать больше, чем есть у клиента
        maxUsableBonuses = Math.min(currentClientBonusPoints, maxUsableBonuses);
        maxUsableBonuses = Math.max(0, maxUsableBonuses);
        
        if (maxUsableBonusesElem) {
            maxUsableBonusesElem.textContent = maxUsableBonuses.toLocaleString() + ' бонусов';
        }
        
        // Обновляем текст в блоке предупреждения
        if (bonusWarning) bonusWarning.style.display = 'block';
        
        if (maxUsableBonuses > 0 && bonusWarningText) {
            bonusWarningText.innerHTML = `
                Можно использовать до <strong>${maxUsableBonuses.toLocaleString()}</strong> бонусов<br>
                <small>Лимит: <strong>${maxSpendPercent}%</strong> от суммы заказа</small>
            `;
        } else if (currentClientBonusPoints > 0 && bonusWarningText) {
            bonusWarningText.innerHTML = `
                У клиента недостаточно бонусов для использования<br>
                <small>Лимит: <strong>${maxSpendPercent}%</strong> от суммы заказа</small>
            `;
        } else if (bonusWarningText) {
            bonusWarningText.textContent = 'У клиента нет бонусов';
        }
        
        // Показываем секцию бонусов
        if (bonusSection) bonusSection.style.display = 'block';
        
        // Сбрасываем состояние бонусов, если превышен лимит
        if (currentBonusDiscount > maxUsableBonuses) {
            currentBonusDiscount = 0;
            if (bonusPointsToUseInput) {
                bonusPointsToUseInput.value = 0;
            }
            if (useBonusesCheckbox) {
                useBonusesCheckbox.checked = false;
            }
        }
        
        // Пересчитываем сумму с учетом обновленных бонусов
        calculateFinalTotal();
    }
    
    // Функция расчета сдачи
    function calculateChange() {
        if (!cashReceivedInput || currentFinalTotal <= 0) return;
        
        const cashReceived = parseFloat(cashReceivedInput.value) || 0;
        
        if (cashReceived === 0) {
            calcResult.style.display = 'none';
            insufficientCash.style.display = 'none';
            return;
        }
        
        if (cashReceived >= currentFinalTotal) {
            const change = cashReceived - currentFinalTotal;
            changeAmount.textContent = change.toFixed(2) + ' ₽';
            calcResult.style.display = 'block';
            insufficientCash.style.display = 'none';
        } else {
            const missing = currentFinalTotal - cashReceived;
            missingAmount.textContent = missing.toFixed(2) + ' ₽';
            calcResult.style.display = 'none';
            insufficientCash.style.display = 'block';
        }
    }
    
    // Обработчик изменения способа оплаты
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const paymentMethodName = selectedOption.text.toLowerCase();
            
            if (paymentMethodName.includes('налич')) {
                cashCalculator.style.display = 'block';
                // Устанавливаем значение в поле полученной суммы
                if (cashReceivedInput) {
                    cashReceivedInput.value = currentFinalTotal.toFixed(2);
                }
                calculateChange();
            } else {
                cashCalculator.style.display = 'none';
                calcResult.style.display = 'none';
                insufficientCash.style.display = 'none';
            }
        });
    }
    
    // Обработчик изменения суммы полученной наличности
    if (cashReceivedInput) {
        cashReceivedInput.addEventListener('input', calculateChange);
    }
    
    // Обработчик открытия модалки
    if (closeSaleModal) {
        closeSaleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('close-sale-btn')) {
                // Устанавливаем ID продажи
                const saleId = button.dataset.id;
                const saleIdElem = document.getElementById('closeSaleId');
                if (saleIdElem) saleIdElem.textContent = saleId;
                
                const closeSaleForm = document.getElementById('closeSaleForm');
                if (closeSaleForm) {
                    closeSaleForm.action = `/sales/${saleId}/complete`;
                }
                
                // Получаем суммы товаров и кальянов
                currentItemsTotal = parseFloat(button.dataset.itemsTotal) || 0;
                currentHookahsTotal = parseFloat(button.dataset.hookahsTotal) || 0;
                
                // Получаем данные клиента
                currentClientId = button.dataset.clientId || null;
                currentClientName = button.dataset.clientName || '';
                currentClientBonusPoints = parseInt(button.dataset.clientBonusPoints) || 0;
                maxSpendPercent = parseInt(button.dataset.clientMaxSpendPercent) || 50;
                
                // Заполняем существующие значения
                if (closeDiscountInput) {
                    closeDiscountInput.value = button.dataset.discount || 0;
                }
                
                if (paymentMethodSelect) {
                    paymentMethodSelect.value = button.dataset.paymentMethodId || '';
                    // Определяем, показывать ли калькулятор сдачи
                    if (paymentMethodSelect.value) {
                        const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
                        if (selectedOption && selectedOption.text.toLowerCase().includes('налич')) {
                            cashCalculator.style.display = 'block';
                        }
                    }
                }
                
                const closeComment = document.getElementById('closeComment');
                if (closeComment) {
                    closeComment.value = button.dataset.comment || '';
                }
                
                // Сбрасываем тип скидки на фиксированную сумму
                currentDiscountType = 'fixed';
                if (discountTypeSelect) discountTypeSelect.value = 'fixed';
                
                // Сбрасываем бонусы
                currentBonusDiscount = 0;
                
                // Обновляем отображение скидки
                updateDiscountDisplay();
                
                // Обновляем информацию о бонусах
                updateBonusInfo();
                
                // Рассчитываем итог
                calculateFinalTotal();
            }
        });
    }
    
    // Обработчики для переключения типа скидки
    if (discountTypeSelect) {
        discountTypeSelect.addEventListener('change', function() {
            currentDiscountType = this.value;
            updateDiscountDisplay();
        });
    }
    
    // Обработчик изменения значения скидки
    if (closeDiscountInput) {
        closeDiscountInput.addEventListener('input', function() {
            updateDiscountDisplay();
        });
    }
    
    // Обработчики для бонусов
    if (useBonusesCheckbox) {
        useBonusesCheckbox.addEventListener('change', function() {
            if (this.checked && maxUsableBonuses > 0) {
                if (bonusPointsToUseInput) {
                    bonusPointsToUseInput.disabled = false;
                    bonusPointsToUseInput.max = maxUsableBonuses;
                    bonusPointsToUseInput.placeholder = `До ${maxUsableBonuses}`;
                    
                    // Автоматически ставим значение
                    const initialValue = Math.min(100, maxUsableBonuses);
                    if (bonusPointsToUseInput.value == 0 && initialValue > 0) {
                        bonusPointsToUseInput.value = initialValue;
                        currentBonusDiscount = initialValue;
                    }
                }
                if (bonusInputRow) bonusInputRow.style.display = 'flex';
                calculateFinalTotal();
            } else {
                if (bonusPointsToUseInput) {
                    bonusPointsToUseInput.disabled = true;
                    bonusPointsToUseInput.value = 0;
                }
                if (bonusInputRow) bonusInputRow.style.display = 'none';
                currentBonusDiscount = 0;
                calculateFinalTotal();
            }
        });
    }
    
    if (bonusPointsToUseInput) {
        bonusPointsToUseInput.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            
            if (value > maxUsableBonuses) {
                this.value = maxUsableBonuses;
                currentBonusDiscount = maxUsableBonuses;
            } else if (value < 0) {
                this.value = 0;
                currentBonusDiscount = 0;
            } else {
                currentBonusDiscount = value;
            }
            
            calculateFinalTotal();
        });
    }
    
    if (useMaxBonusesBtn) {
        useMaxBonusesBtn.addEventListener('click', function() {
            if (maxUsableBonuses > 0) {
                if (bonusPointsToUseInput) {
                    bonusPointsToUseInput.value = maxUsableBonuses;
                }
                currentBonusDiscount = maxUsableBonuses;
                calculateFinalTotal();
            }
        });
    }
    
    // Обработчик отправки формы
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
                
                // Отключаем оригинальное поле
                if (closeDiscountInput) {
                    closeDiscountInput.disabled = true;
                }
            }
            // Убедимся, что поле с бонусами заполнено правильно
            if (useBonusesCheckbox && !useBonusesCheckbox.checked) {
                const bonusField = document.getElementById('bonusPointsToUse');
                if (bonusField) {
                    bonusField.value = 0;
                }
            }
        });
    }
});
</script>