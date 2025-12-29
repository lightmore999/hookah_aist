<div class="modal fade" id="editFineModal" tabindex="-1" aria-labelledby="editFineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editFineModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать штраф
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editFineForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <!-- Выбор сотрудника -->
                    <div class="mb-4">
                        <label for="edit_user_id" class="form-label fw-bold">Сотрудник *</label>
                        <select class="form-select" 
                                id="edit_user_id" 
                                name="user_id" 
                                required>
                            <option value="">Выберите сотрудника</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Сумма штрафа -->
                    <div class="mb-4">
                        <label for="edit_amount" class="form-label fw-bold">Сумма штрафа (₽) *</label>
                        <div class="input-group">
                            <input type="number" 
                                min="0" 
                                step="0.01"
                                class="form-control" 
                                id="edit_amount" 
                                name="amount" 
                                placeholder="0.00" 
                                required>
                            <span class="input-group-text">₽</span>
                        </div>
                    </div>

                    <!-- Комментарий -->
                    <div class="mb-4">
                        <label for="edit_comment" class="form-label fw-bold">Причина / Комментарий *</label>
                        <textarea class="form-control" 
                                id="edit_comment" 
                                name="comment" 
                                rows="4" 
                                placeholder="Опишите причину штрафа..." 
                                required></textarea>
                        <div class="form-text">Максимум 1000 символов</div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Обновить штраф
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>