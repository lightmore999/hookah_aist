<div class="modal fade" id="deleteExpenditureModal" tabindex="-1" aria-labelledby="deleteExpenditureModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteExpenditureModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление расхода
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteExpenditureForm" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle text-danger display-4"></i>
                        <p class="mt-3 fw-bold">Вы уверены, что хотите удалить этот расход?</p>
                        
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-receipt fs-4 me-3"></i>
                                <div>
                                    <strong id="deleteExpenditureName" class="d-block"></strong>
                                    <span id="deleteExpenditureCost" class="text-danger fw-bold"></span>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Это действие невозможно отменить. Все данные о расходе будут безвозвратно удалены.
                        </p>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Удалить расход
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>