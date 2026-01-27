<div class="modal fade" id="deleteExpenditureModal" tabindex="-1" aria-labelledby="deleteExpenditureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteExpenditureModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Удаление расхода
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="deleteExpenditureForm" method="POST" action="{{ route('expenditures.destroy', ['expenditure' => '__ID__']) }}">
                @csrf
                @method('DELETE')
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-warning mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-receipt fs-4 me-3"></i>
                                    <div>
                                        <strong id="deleteExpenditureName" class="d-block fs-5"></strong>
                                        <div class="mt-2">
                                            <span id="deleteExpenditureCost" class="text-danger fw-bold fs-5"></span>
                                            <span class="text-muted ms-2" id="deleteExpenditureType"></span>
                                        </div>
                                        <div class="mt-1">
                                            <small class="text-muted" id="deleteExpenditureDate"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="delete_comment" class="form-label fw-bold">
                            <i class="bi bi-chat-left-text me-1"></i>Причина удаления *
                            <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            name="delete_comment" 
                            id="delete_comment" 
                            class="form-control @error('delete_comment') is-invalid @enderror" 
                            rows="3" 
                            placeholder="Опишите причину удаления этого расхода. Например: 'Ошибка при добавлении', 'Дублирующая запись', 'Отмена операции' и т.д." 
                            required
                            minlength="5"
                            maxlength="500"
                        >{{ old('delete_comment') }}</textarea>
                        
                        @error('delete_comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        
                        <div class="form-text">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                Этот комментарий будет сохранен в истории операций для аудита.
                            </small>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <div class="d-flex">
                            <i class="bi bi-clock-history me-2 mt-1"></i>
                            <div>
                                <small>
                                    <strong>Внимание:</strong> Это действие невозможно отменить. 
                                    Все данные о расходе будут удалены, но информация об удалении 
                                    сохранится в истории операций с указанием причины.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-danger" id="submitDeleteBtn">
                        <i class="bi bi-trash me-1"></i>Удалить расход
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>