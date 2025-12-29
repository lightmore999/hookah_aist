<div class="modal fade" id="removeHookahModal" tabindex="-1" aria-labelledby="removeHookahModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="removeHookahModalLabel">
                    <i class="bi bi-trash me-2"></i>Удалить кальян из заказа
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" id="removeHookahForm">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить кальян <strong id="removeHookahName"></strong> из заказа?</p>
                    <p class="text-danger"><small>Это действие нельзя отменить.</small></p>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>