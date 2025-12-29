<div class="modal fade" id="deleteExpenditureTypeModal" tabindex="-1" aria-labelledby="deleteExpenditureTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteExpenditureTypeModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление типа расхода
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteExpenditureTypeForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle text-danger display-4"></i>
                        <p class="mt-3">Вы уверены, что хотите удалить тип расхода?</p>
                        <div class="alert alert-warning">
                            <strong>Тип:</strong> <span id="deleteExpenditureTypeName" class="fw-bold"></span><br>
                            <strong>Количество расходов:</strong> <span id="deleteExpenditureTypeCount" class="badge bg-info"></span>
                        </div>
                        <p class="text-danger fw-bold" id="deleteWarningMessage"></p>
                        <p class="text-muted small">Это действие невозможно отменить.</p>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger" id="deleteSubmitBtn">
                        <i class="bi bi-trash me-1"></i>Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteExpenditureTypeModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-expenditure-type-btn')) {
                const count = parseInt(button.dataset.count);
                const warningMessage = document.getElementById('deleteWarningMessage');
                const submitBtn = document.getElementById('deleteSubmitBtn');
                
                if (count > 0) {
                    warningMessage.textContent = 'ВНИМАНИЕ: У этого типа есть связанные расходы! Удаление невозможно.';
                    warningMessage.classList.remove('text-success');
                    warningMessage.classList.add('text-danger');
                    submitBtn.disabled = true;
                    submitBtn.classList.add('disabled');
                } else {
                    warningMessage.textContent = 'Можно удалить, так как нет связанных расходов.';
                    warningMessage.classList.remove('text-danger');
                    warningMessage.classList.add('text-success');
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('disabled');
                }
            }
        });
    }
});
</script>