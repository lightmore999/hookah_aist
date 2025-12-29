<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteProductModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление товара
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteProductForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-trash text-danger display-4"></i>
                    </div>
                    <p class="text-center">
                        Вы уверены, что хотите удалить товар 
                        <strong id="deleteProductName"></strong>?
                    </p>
                    <p class="text-muted small text-center mb-0">
                        Это действие нельзя отменить.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Удалить товар</button>
                </div>
            </form>
        </div>
    </div>
</div>