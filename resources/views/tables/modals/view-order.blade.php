<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">
                    <i class="bi bi-receipt me-2"></i>Заказ #<span id="viewOrderSaleId"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <!-- Информация о клиенте и бонусах -->
                <div class="row mb-4" id="viewClientBonusInfo" style="display: none;">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-8">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle me-2"></i>
                                        <div>
                                            <strong id="viewClientName"></strong>
                                            <div class="small mt-1">
                                                Бонусов на момент заказа: 
                                                <span id="viewClientBonusPoints" class="badge bg-primary"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="small text-muted">Использовано бонусов:</div>
                                    <div>
                                        <strong id="viewUsedBonuses" class="text-success">0</strong>
                                        <span class="small text-muted"> бонусов</span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Лимит: <span id="viewMaxSpendPercent">50</span>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Информация о столе -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-table me-2"></i>
                                    <strong id="viewTableNumber"></strong> - 
                                    <span id="viewGuestName"></span>
                                </div>
                                <div>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-door-closed me-1"></i>Закрыт
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Товары -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-cart me-2"></i>Товары
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Товар</th>
                                        <th width="100">Кол-во</th>
                                        <th width="100">Цена</th>
                                        <th width="100">Сумма</th>
                                    </tr>
                                </thead>
                                <tbody id="viewOrderProductsBody">
                                    <!-- Товары будут загружены через JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Кальяны -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-cup-straw me-2"></i>Кальяны
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Кальян</th>
                                        <th width="100">Цена</th>
                                    </tr>
                                </thead>
                                <tbody id="viewOrderHookahsBody">
                                    <!-- Кальяны будут загружены через JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Комментарий -->
                <div class="row mb-4" id="viewCommentContainer" style="display: none;">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-chat-left-text me-2"></i>Комментарий
                        </h6>
                        <div class="card">
                            <div class="card-body">
                                <p class="card-text mb-0" id="viewOrderComment"></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Итоги -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-2">
                                            <small class="text-muted">Сумма товаров:</small>
                                            <div class="fw-bold" id="viewProductsTotal">0 ₽</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Сумма кальянов:</small>
                                            <div class="fw-bold" id="viewHookahsTotal">0 ₽</div>
                                        </div>
                                        <div class="mb-2" id="viewBonusDiscountContainer" style="display: none;">
                                            <small class="text-muted">Скидка бонусами:</small>
                                            <div class="fw-bold text-info" id="viewBonusDiscount">0 ₽</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-2">
                                            <small class="text-muted">Скидка:</small>
                                            <div class="fw-bold text-danger" id="viewDiscount">0 ₽</div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Способ оплаты:</small>
                                            <div class="fw-bold" id="viewPaymentMethod"></div>
                                        </div>
                                        <div class="mb-2" id="viewBonusEarnedContainer" style="display: none;">
                                            <small class="text-muted">Начислено бонусов:</small>
                                            <div class="fw-bold text-success" id="viewBonusEarned">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 border-top pt-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Итого к оплате:</h5>
                                            <h4 class="mb-0 text-success" id="viewFinalTotal">0 ₽</h4>
                                        </div>
                                        <div class="small text-muted text-end" id="viewTotalBreakdown">
                                            (Товары + Кальяны) - Скидка
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i> Закрыть
                </button>
            </div>
        </div>
    </div>
</div>