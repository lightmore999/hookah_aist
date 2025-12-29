<div class="modal fade" id="editExpenditureTypeModal" tabindex="-1" aria-labelledby="editExpenditureTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editExpenditureTypeModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать тип расхода
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editExpenditureTypeForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="edit_name" class="form-label fw-bold">Название типа *</label>
                        <input type="text" 
                            class="form-control" 
                            id="edit_name" 
                            name="name" 
                            placeholder="Название типа расхода" 
                            required>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Обновить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>