<div class="modal fade" id="editCardModal" tabindex="-1" aria-labelledby="editCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editCardModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактирование бонусной карты
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <!-- Форма без action, он будет установлен через JS -->
            <form id="editCardForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_Name" class="form-label fw-bold">Название карты *</label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_Name" 
                               name="Name" 
                               required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_RequiredSpendAmount" class="form-label fw-bold">Необходимые траты (руб) *</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="edit_RequiredSpendAmount" 
                                   name="RequiredSpendAmount" 
                                   min="0" 
                                   step="100"
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_EarntRantTable" class="form-label fw-bold">Начисление за стол (%) *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="edit_EarntRantTable" 
                                       name="EarntRantTable" 
                                       min="0" 
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_EarntRantTakeaway" class="form-label fw-bold">Начисление с собой (%) *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="edit_EarntRantTakeaway" 
                                       name="EarntRantTakeaway" 
                                       min="0" 
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_MaxSpendPercent" class="form-label fw-bold">Макс. оплата бонусами (%) *</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="edit_MaxSpendPercent" 
                                       name="MaxSpendPercent" 
                                       min="0" 
                                       max="100"
                                       required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_TableCloseDiscountPercent" class="form-label fw-bold">Скидка при закрытии (%) *</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control" 
                                   id="edit_TableCloseDiscountPercent" 
                                   name="TableCloseDiscountPercent" 
                                   min="0" 
                                   max="100"
                                   required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-warning">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>