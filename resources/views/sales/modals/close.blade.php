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
                            <div class="row mb-2">
                                <div class="col-6">Промежуточный итог:</div>
                                <div class="col-6 text-end" id="closeSubtotal">0.00 ₽</div>
                            </div>
                            <div class="row mb-2" id="regularDiscountRow" style="display: none;">
                                <div class="col-6 text-success">Скидка (руб./%):</div>
                                <div class="col-6 text-end text-success" id="closeRegularDiscount">-0.00 ₽</div>
                            </div>
                            <div class="row mb-2" id="bonusDiscountRow" style="display: none;">
                                <div class="col-6 text-danger">Скидка бонусами:</div>
                                <div class="col-6 text-end text-danger" id="closeBonusDiscount">-0.00 ₽</div>
                            </div>
                            <hr class="my-2">
                            <div class="row mt-2">
                                <div class="col-6"><strong>Итого к оплате:</strong></div>
                                <div class="col-6 text-end" id="closeFinalTotal">0.00 ₽</div>
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
        const finalTotal = Math.max(0, subtotal - discountInRubles - currentBonusDiscount);
        
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
        if (finalTotalElem) finalTotalElem.textContent = finalTotal.toFixed(2) + ' ₽';
        
        return finalTotal;
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
                <div>Клиент может использовать до <strong>${maxUsableBonuses.toLocaleString()}</strong> бонусов</div>
                <div class="small mt-1">Лимит из бонусной карты: <strong>${maxSpendPercent}%</strong> от суммы заказа</div>
            `;
        } else if (currentClientBonusPoints > 0 && bonusWarningText) {
            bonusWarningText.innerHTML = `
                <div>У клиента недостаточно бонусов для использования</div>
                <div class="small mt-1">Лимит из бонусной карты: <strong>${maxSpendPercent}%</strong> от суммы заказа</div>
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
                
                const closePaymentMethod = document.getElementById('closePaymentMethod');
                if (closePaymentMethod) {
                    closePaymentMethod.value = button.dataset.paymentMethod || '';
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