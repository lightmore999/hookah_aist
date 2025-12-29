<div class="modal fade" id="createFineModal" tabindex="-1" aria-labelledby="createFineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createFineModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить штраф
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('fines.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <!-- Выбор сотрудника -->
                    <div class="mb-4">
                        <label for="user_id" class="form-label fw-bold">Сотрудник *</label>
                        <select class="form-select @error('user_id') is-invalid @enderror" 
                                id="user_id" 
                                name="user_id" 
                                required>
                            <option value="">Выберите сотрудника</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Сумма штрафа -->
                    <div class="mb-4">
                        <label for="amount" class="form-label fw-bold">Сумма штрафа (₽) *</label>
                        <div class="input-group">
                            <input type="number" 
                                min="0" 
                                step="0.01"
                                class="form-control @error('amount') is-invalid @enderror" 
                                id="amount" 
                                name="amount" 
                                value="{{ old('amount') }}" 
                                placeholder="0.00" 
                                required>
                            <span class="input-group-text">₽</span>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Комментарий -->
                    <div class="mb-4">
                        <label for="comment" class="form-label fw-bold">Причина / Комментарий *</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror" 
                                id="comment" 
                                name="comment" 
                                rows="4" 
                                placeholder="Опишите причину штрафа..." 
                                required>{{ old('comment') }}</textarea>
                        @error('comment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Максимум 1000 символов</div>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Добавить штраф
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>