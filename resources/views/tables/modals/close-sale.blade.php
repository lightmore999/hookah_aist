<div class="modal fade" id="closeSaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="closeSaleModalLabel">
                    <i class="bi bi-check-circle me-2"></i>Закрыть стол #<span id="closeTableNumber"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" id="closeSaleForm">
                @csrf
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Левая колонка - форма -->
                        <div class="col-md-6 border-end">
                            <!-- Информация о клиенте и бонусах -->
                            <div class="alert alert-info mb-3" id="clientBonusInfo" style="display: none;">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-circle me-2"></i>
                                            <div>
                                                <strong id="clientName"></strong>
                                                <div class="small mt-1">
                                                    Доступно бонусов: <span id="clientBonusPoints" class="badge bg-primary"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="small text-muted">Можно использовать:</div>
                                        <div><strong id="maxUsableBonuses" class="text-success"></strong> бонусов</div>
                                        <div class="small text-muted mt-1">
                                            Лимит: <span id="maxSpendPercentText"></span>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Информация о столе -->
                            <div class="mb-4">
                                <div class="alert alert-secondary" id="closeSaleInfo">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Стол #</strong> - <span id="closeGuestName"></span>
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
                                            <!-- Основное поле для рублей -->
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
                                            
                                            <!-- Скрытое поле для отправки процентов -->
                                            <input type="hidden" id="discountPercent" name="discount_percent" value="0">
                                        </div>
                                    </div>
                                    
                                    <!-- Конвертация процентов -->
                                    <div class="mt-2 small" id="discountConversion" style="display: none;">
                                        <span class="text-muted">Скидка составит: </span>
                                        <span id="discountAmount" class="fw-bold">0.00 ₽</span>
                                    </div>
                                    
                                    <div class="form-text">Выберите тип скидки и введите значение</div>
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
                                        rows="3"
                                        placeholder="Примечания к продаже..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Правая колонка - товары и кальяны -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Состав заказа:</h6>
                            
                            <!-- Товары -->
                            <div class="mb-4">
                                <h6><i class="bi bi-cart text-primary me-2"></i>Товары:</h6>
                                <div id="closeProductsList" class="mb-3">
                                    <!-- Товары будут загружены через JavaScript -->
                                    <div class="text-center text-muted py-3">
                                        <i class="bi bi-cart-x me-2"></i>
                                        Товары не добавлены
                                    </div>
                                </div>
                                <div class="text-end">
                                    <strong>Итого товары:</strong>
                                    <span id="closeItemsTotal" class="ms-2">0.00 ₽</span>
                                </div>
                            </div>
                            
                            <!-- Кальяны -->
                            <div class="mb-4">
                                <h6><i class="bi bi-cup-straw text-warning me-2"></i>Кальяны:</h6>
                                <div id="closeHookahsList" class="mb-3">
                                    <!-- Кальяны будут загружены через JavaScript -->
                                    <div class="text-center text-muted py-3">
                                        <i class="bi bi-cup-straw me-2"></i>
                                        Кальяны не добавлены
                                    </div>
                                </div>
                                <div class="text-end">
                                    <strong>Итого кальяны:</strong>
                                    <span id="closeHookahsTotal" class="ms-2">0.00 ₽</span>
                                </div>
                            </div>
                            
                            <!-- Итоговая сумма -->
                            <div class="card border-success">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h6 mb-0">Промежуточный итог:</span>
                                        <span class="h6 mb-0" id="closeSubtotal">0.00 ₽</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="h6 mb-0">Скидка:</span>
                                        <span class="h6 mb-0 text-danger" id="closeDiscountDisplay">0.00 ₽</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2" id="bonusDiscountRow" style="display: none;">
                                        <span class="h6 mb-0">Скидка бонусами:</span>
                                        <span class="h6 mb-0 text-danger" id="closeBonusDiscountDisplay">0.00 ₽</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5 mb-0 fw-bold">Итого к оплате:</span>
                                        <span class="h4 mb-0 text-success fw-bold" id="closeFinalTotal">0.00 ₽</span>
                                    </div>
                                    <small class="text-muted" id="finalTotalBreakdown">(Товары + Кальяны) - Скидка</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Закрыть стол и завершить продажу
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

