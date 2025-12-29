<div class="modal fade" id="editQuantityModal" tabindex="-1" aria-labelledby="editQuantityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editQuantityModalLabel">
                    <i class="bi bi-pencil me-2"></i>Изменить количество
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editQuantityForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <p class="mb-3">
                        Товар: <strong id="editProductName"></strong>
                    </p>
                    
                    <div class="mb-4">
                        <label for="edit_actual_quantity" class="form-label fw-bold">Фактическое количество *</label>
                        <input type="number" 
                            class="form-control @error('actual_quantity') is-invalid @enderror" 
                            id="edit_actual_quantity" 
                            name="actual_quantity" 
                            min="0" 
                            required>
                        @error('actual_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editQuantityForm = document.getElementById('editQuantityForm');
    
    if (editQuantityForm) {
        editQuantityForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Закрываем модалку
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editQuantityModal'));
                    modal.hide();
                    
                    // Обновляем таблицу товаров
                    location.reload();
                } else {
                    alert(data.message || 'Ошибка при обновлении количества');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке формы');
            });
        });
    }
});
</script>