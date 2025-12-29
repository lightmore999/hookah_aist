<div class="modal fade" id="editExpenditureModal" tabindex="-1" aria-labelledby="editExpenditureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editExpenditureModalLabel">
                    <i class="bi bi-pencil me-2"></i>Редактировать расход
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editExpenditureForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Тип расхода -->
                        <div class="col-md-6">
                            <label for="edit_expenditure_type_id" class="form-label fw-bold">Тип расхода *</label>
                            <select class="form-select" 
                                    id="edit_expenditure_type_id" 
                                    name="expenditure_type_id" 
                                    required>
                                <option value="">Выберите тип</option>
                                @foreach($expenditureTypes as $type)
                                    <option value="{{ $type->id }}">
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Дата расхода -->
                        <div class="col-md-6">
                            <label for="edit_expenditure_date" class="form-label fw-bold">Дата расхода *</label>
                            <input type="datetime-local" 
                                class="form-control" 
                                id="edit_expenditure_date" 
                                name="expenditure_date" 
                                required>
                        </div>

                        <!-- Название расхода -->
                        <div class="col-12">
                            <label for="edit_name" class="form-label fw-bold">Название расхода *</label>
                            <input type="text" 
                                class="form-control" 
                                id="edit_name" 
                                name="name" 
                                placeholder="Название расхода" 
                                required>
                        </div>

                        <!-- Сумма и способ оплаты -->
                        <div class="col-md-6">
                            <label for="edit_cost" class="form-label fw-bold">Сумма (₽) *</label>
                            <div class="input-group">
                                <input type="number" 
                                    min="0" 
                                    step="0.01"
                                    class="form-control" 
                                    id="edit_cost" 
                                    name="cost" 
                                    placeholder="0.00" 
                                    required>
                                <span class="input-group-text">₽</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_payment_method" class="form-label fw-bold">Способ оплаты *</label>
                            <select class="form-select" 
                                    id="edit_payment_method" 
                                    name="payment_method" 
                                    required>
                                <option value="">Выберите способ</option>
                                <option value="cash">Наличные</option>
                                <option value="card">Карта</option>
                            </select>
                        </div>

                        <!-- Комментарий -->
                        <div class="col-12">
                            <label for="edit_comment" class="form-label fw-bold">Комментарий</label>
                            <textarea class="form-control" 
                                    id="edit_comment" 
                                    name="comment" 
                                    rows="3" 
                                    placeholder="Дополнительная информация..."></textarea>
                        </div>

                        <!-- Чекбоксы -->
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="edit_is_hidden_admin" 
                                       name="is_hidden_admin"
                                       value="1">  <!-- Добавлено value -->
                                <label class="form-check-label fw-bold" for="edit_is_hidden_admin">
                                    Скрыть от администратора
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="edit_is_monthly_expense" 
                                       name="is_monthly_expense"
                                       value="1">  <!-- Добавлено value -->
                                <label class="form-check-label fw-bold" for="edit_is_monthly_expense">
                                    Ежемесячный расход
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Обновить расход
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>