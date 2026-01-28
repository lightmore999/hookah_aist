<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Добавить стол
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.table-names.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="tableName" class="form-label">Название стола *</label>
                        <input type="text" 
                               class="form-control" 
                               id="tableName" 
                               name="name"
                               placeholder="Например: 8, VIP-стол, Балкон и т.д."
                               required>
                        <div class="form-text">
                            Название должно быть уникальным
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        Добавить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>