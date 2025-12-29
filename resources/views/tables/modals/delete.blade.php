<!-- Модальное окно удаления стола -->
<div class="modal fade" id="deleteTableModal" tabindex="-1" aria-labelledby="deleteTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteTableModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление стола
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteTableForm" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="redirect_date" value="{{ $currentDate }}">
                
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-trash display-4 text-danger mb-3"></i>
                        <p class="fs-5">Вы уверены, что хотите удалить этот стол?</p>
                        <p class="text-muted">Действие нельзя будет отменить.</p>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
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