<div class="modal fade" id="writeOffModal" tabindex="-1" aria-labelledby="writeOffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="writeOffModalLabel">
                    <i class="bi bi-dash-circle text-danger me-2"></i>
                    Списание товара
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('write-offs.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="warehouse_id" id="modalWarehouseId">
                    <input type="hidden" name="product_id" id="modalProductId">
                    
                    <div class="mb-3">
                        <label class="form-label">Товар</label>
                        <p class="form-control-plaintext" id="modalProductName"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Доступно на складе</label>
                        <p class="form-control-plaintext">
                            <span id="modalAvailableQuantity"></span> 
                            <span id="modalUnit"></span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalQuantity" class="form-label">
                            Количество для списания <span id="quantityUnit"></span>
                        </label>
                        <input type="number" 
                               step="0.001" 
                               min="0.001" 
                               class="form-control" 
                               id="modalQuantity" 
                               name="quantity" 
                               required>
                        <small class="text-muted">
                            Максимум: <span id="maxQuantity"></span> <span id="maxQuantityUnit"></span>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modalOperationType" class="form-label">Причина списания</label>
                        <select class="form-select" id="modalOperationType" name="operation_type" required>
                            <option value="">-- Выберите причину --</option>
                            <option value="spoilage">Порча</option>
                            <option value="damage">Бой/Повреждение</option>
                            <option value="expired">Просрочка</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Списать</button>
                </div>
            </form>
        </div>
    </div>
</div>