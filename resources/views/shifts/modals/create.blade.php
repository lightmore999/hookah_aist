<div class="modal fade" id="createShiftModal" tabindex="-1" aria-labelledby="createShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createShiftModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Создать смену
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('shifts.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date" class="form-label">Дата смены *</label>
                        <input type="date" 
                               class="form-control" 
                               id="date" 
                               name="date" 
                               required>
                        <div class="form-text">Выберите дату для смены</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать смену</button>
                </div>
            </form>
        </div>
    </div>
</div>