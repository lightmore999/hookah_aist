<div class="modal fade" id="removeItemModal" tabindex="-1" aria-labelledby="removeItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="removeItemModalLabel">
                    <i class="bi bi-trash me-2"></i>Удаление товара
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-triangle text-danger display-4 mb-3"></i>
                <h5 class="mb-3">Удалить товар?</h5>
                <p class="text-muted mb-0">
                    Товар "<span id="removeProductName" class="fw-bold"></span>" будет удален из инвентаризации.
                </p>
            </div>
            
            <form id="removeItemForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-footer border-top-0 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const removeItemForm = document.getElementById('removeItemForm');
    
    if (removeItemForm) {
        removeItemForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'DELETE'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Закрываем модалку
                    const modal = bootstrap.Modal.getInstance(document.getElementById('removeItemModal'));
                    modal.hide();
                    
                    // Обновляем таблицу товаров
                    location.reload();
                } else {
                    alert(data.message || 'Ошибка при удалении товара');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при отправке запроса');
            });
        });
    }
});
</script>