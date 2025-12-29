<div class="modal fade" id="editWarehouseModal" tabindex="-1" aria-labelledby="editWarehouseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editWarehouseModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактировать склад
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editWarehouseForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_warehouse_name" class="form-label fw-bold">Название склада *</label>
                        <input type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="edit_warehouse_name" 
                            name="name" 
                            placeholder="Название склада" 
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-warning">Обновить склад</button>
                </div>
            </form>
        </div>
    </div>
</div>