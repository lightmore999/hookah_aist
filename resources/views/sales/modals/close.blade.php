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
                        <div class="input-group">
                            <input type="number" 
                                min="0" 
                                step="0.01"
                                class="form-control" 
                                id="closeDiscount" 
                                name="discount" 
                                value="0">
                            <span class="input-group-text">₽</span>
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