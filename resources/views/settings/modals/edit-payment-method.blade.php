<div class="modal fade" id="editPaymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Редактировать способ оплаты
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Название *</label>
                        <input type="text" 
                               class="form-control @error('Name') is-invalid @enderror" 
                               id="editName" 
                               name="Name" 
                               required
                               maxlength="100"
                               placeholder="Введите новое название">
                        @error('Name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>