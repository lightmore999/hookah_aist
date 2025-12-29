<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editItemModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать товар
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" id="editItemForm">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Товар</label>
                        <p class="form-control-plaintext" id="editItemProductName"></p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editQuantity" class="form-label fw-bold">Количество *</label>
                            <input type="number" 
                                   min="1" 
                                   class="form-control @error('Quantity') is-invalid @enderror" 
                                   id="editQuantity" 
                                   name="Quantity" 
                                   required>
                            @error('Quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="editUnitPrice" class="form-label fw-bold">Цена за единицу *</label>
                            <div class="input-group">
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control @error('UnitPrice') is-invalid @enderror" 
                                       id="editUnitPrice" 
                                       name="UnitPrice" 
                                       required>
                                <span class="input-group-text">₽</span>
                                @error('UnitPrice')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>