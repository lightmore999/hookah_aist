<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editCategoryModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактировать категорию
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label fw-bold">Название категории *</label>
                        <input type="text" 
                            class="form-control" 
                            id="edit_category_name" 
                            name="name" 
                            placeholder="Введите название" 
                            required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-warning">Обновить категорию</button>
                </div>
            </form>
        </div>
    </div>
</div>