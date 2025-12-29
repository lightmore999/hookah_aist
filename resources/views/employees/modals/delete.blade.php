<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteEmployeeModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Подтверждение удаления
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteEmployeeForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-x display-1 text-danger"></i>
                    </div>
                    
                    <h5 class="text-center mb-3">Вы уверены, что хотите удалить сотрудника?</h5>
                    
                    <div class="alert alert-danger">
                        <div class="d-flex">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <p class="mb-1 fw-bold" id="deleteEmployeeName"></p>
                                <p class="mb-0">Это действие нельзя будет отменить.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Да, удалить
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>