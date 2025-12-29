<div class="modal fade" id="deleteHookahModal" tabindex="-1" aria-labelledby="deleteHookahModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteHookahModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Подтверждение удаления
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                
                <form id="deleteHookahForm" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="bi bi-trash text-danger display-4"></i>
                        </div>
                        <p class="text-center">
                            Вы уверены, что хотите удалить кальян 
                            <strong id="deleteHookahName"></strong>?
                        </p>
                        <p class="text-muted small text-center mb-0">
                            Это действие нельзя отменить.
                        </p>
                    </div>

                    <div class="modal-footer border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Отмена
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Да, удалить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>