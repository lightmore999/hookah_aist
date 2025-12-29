<div class="modal fade" id="deleteInventoryModal" tabindex="-1" aria-labelledby="deleteInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteInventoryModalLabel">
                    <i class="bi bi-trash me-2"></i>Удаление
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-danger display-4 mb-3"></i>
                <h5 class="mb-3">Вы уверены?</h5>
                <p class="text-muted mb-0">
                    Инвентаризация "<span id="deleteInventoryName" class="fw-bold"></span>" будет удалена.
                </p>
                <p class="text-muted small mt-2">
                    Это действие нельзя отменить.
                </p>
            </div>
            
            <form id="deleteInventoryForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-footer border-top-0 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>