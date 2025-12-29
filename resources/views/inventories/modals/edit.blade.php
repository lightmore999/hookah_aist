<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-labelledby="editInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editInventoryModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать инвентаризацию
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editInventoryForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <!-- Название -->
                    <div class="mb-4">
                        <label for="edit_name" class="form-label fw-bold">Название *</label>
                        <input type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="edit_name" 
                            name="name" 
                            required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Дата инвентаризации -->
                    <div class="mb-4">
                        <label for="edit_inventory_date" class="form-label fw-bold">Дата инвентаризации *</label>
                        <input type="datetime-local" 
                            class="form-control @error('inventory_date') is-invalid @enderror" 
                            id="edit_inventory_date" 
                            name="inventory_date" 
                            required>
                        @error('inventory_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Заметки -->
                    <div class="mb-4">
                        <label for="edit_notes" class="form-label fw-bold">Заметки</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                  id="edit_notes" 
                                  name="notes" 
                                  rows="3"
                                  placeholder="Дополнительная информация об инвентаризации">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>