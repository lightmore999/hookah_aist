<div class="modal fade" id="deleteSaleModal" tabindex="-1" aria-labelledby="deleteSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSaleModalLabel">
                    <i class="bi bi-trash me-2"></i>Удалить продажу
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form method="POST" id="deleteSaleForm">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить продажу <strong id="deleteSaleNumber"></strong>?</p>
                    <p class="text-danger"><small>Это действие нельзя отменить. Все позиции продажи также будут удалены.</small></p>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>