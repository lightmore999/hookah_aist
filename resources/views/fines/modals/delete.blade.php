<div class="modal fade" id="deleteFineModal" tabindex="-1" aria-labelledby="deleteFineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteFineModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление штрафа
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteFineForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle text-danger display-4"></i>
                        <p class="mt-3">Вы уверены, что хотите удалить штраф?</p>
                        <div class="alert alert-warning">
                            <strong>Сотрудник:</strong> <span id="deleteFineUserName"></span><br>
                            <strong>Сумма:</strong> <span id="deleteFineAmount" class="text-danger fw-bold"></span>
                        </div>
                        <p class="text-muted small">Это действие невозможно отменить.</p>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Удалить штраф
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>