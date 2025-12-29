<div class="modal fade" id="deleteCardModal" tabindex="-1" aria-labelledby="deleteCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCardModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление бонусной карты
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <!-- Форма без action, он будет установлен через JS -->
            <form id="deleteCardForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Внимание!</strong> Это действие нельзя отменить.
                    </div>
                    
                    <p>Вы уверены, что хотите удалить бонусную карту <strong id="deleteCardName"></strong>?</p>
                    
                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            Если есть клиенты с этой картой, удаление будет невозможно.
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Удалить карту
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>