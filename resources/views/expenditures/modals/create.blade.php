<div class="modal fade" id="createExpenditureModal" tabindex="-1" aria-labelledby="createExpenditureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createExpenditureModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить расход
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('expenditures.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Тип расхода -->
                        <div class="col-md-6">
                            <label for="expenditure_type_id" class="form-label fw-bold">Тип расхода *</label>
                            <select class="form-select @error('expenditure_type_id') is-invalid @enderror" 
                                    id="expenditure_type_id" 
                                    name="expenditure_type_id" 
                                    required>
                                <option value="">Выберите тип</option>
                                @foreach($expenditureTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('expenditure_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('expenditure_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Дата расхода -->
                        <div class="col-md-6">
                            <label for="expenditure_date" class="form-label fw-bold">Дата расхода *</label>
                            <input type="datetime-local" 
                                class="form-control @error('expenditure_date') is-invalid @enderror" 
                                id="expenditure_date" 
                                name="expenditure_date" 
                                value="{{ old('expenditure_date', now()->format('Y-m-d\TH:i')) }}" 
                                required>
                            @error('expenditure_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Название расхода -->
                        <div class="col-12">
                            <label for="name" class="form-label fw-bold">Название расхода *</label>
                            <input type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}" 
                                placeholder="Например: Оплата аренды, Зарплата сотрудникам" 
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Сумма и способ оплаты -->
                        <div class="col-md-6">
                            <label for="cost" class="form-label fw-bold">Сумма (₽) *</label>
                            <div class="input-group">
                                <input type="number" 
                                    min="0" 
                                    step="0.01"
                                    class="form-control @error('cost') is-invalid @enderror" 
                                    id="cost" 
                                    name="cost" 
                                    value="{{ old('cost') }}" 
                                    placeholder="0.00" 
                                    required>
                                <span class="input-group-text">₽</span>
                                @error('cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_method" class="form-label fw-bold">Способ оплаты *</label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" 
                                    id="payment_method" 
                                    name="payment_method" 
                                    required>
                                <option value="">Выберите способ</option>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Наличные</option>
                                <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Карта</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Комментарий -->
                        <div class="col-12">
                            <label for="comment" class="form-label fw-bold">Комментарий</label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" 
                                    id="comment" 
                                    name="comment" 
                                    rows="3" 
                                    placeholder="Дополнительная информация...">{{ old('comment') }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Чекбоксы -->
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_hidden_admin" 
                                       name="is_hidden_admin"
                                       value="1"  <!-- Добавлено value="1" -->
                                       {{ old('is_hidden_admin') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_hidden_admin">
                                    Скрыть от администратора
                                </label>
                                <div class="form-text">Расход не будет виден в общих отчетах администратора</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_monthly_expense" 
                                       name="is_monthly_expense"
                                       value="1"  <!-- Добавлено value="1" -->
                                       {{ old('is_monthly_expense') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_monthly_expense">
                                    Ежемесячный расход
                                </label>
                                <div class="form-text">Регулярный расход (аренда, коммуналка и т.д.)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Добавить расход
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>