<div class="modal fade" id="editTimeModal" tabindex="-1" aria-labelledby="editTimeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editTimeModalLabel">
                    <i class="bi bi-clock me-2"></i>Редактировать время работы
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editTimeForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <p class="mb-3">
                        Сотрудник: <strong id="editEmployeeName"></strong>
                    </p>
                    
                    <div class="mb-3">
                        <label for="edit_start_time" class="form-label">Время начала</label>
                        <input type="datetime-local" 
                               class="form-control" 
                               id="edit_start_time" 
                               name="start_time">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_end_time" class="form-label">Время окончания</label>
                        <input type="datetime-local" 
                               class="form-control" 
                               id="edit_end_time" 
                               name="end_time">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Примечание</label>
                        <textarea class="form-control" 
                                  id="edit_notes" 
                                  name="notes" 
                                  rows="2" 
                                  placeholder="Комментарий к корректировке"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>