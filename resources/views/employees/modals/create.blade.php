<div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-labelledby="createEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createEmployeeModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Добавить нового сотрудника
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('employees.store') }}" method="POST">
                @csrf
                
                <div class="modal-body">
                    <!-- Основная информация -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-bold">Имя *</label>
                            <input type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name') }}" 
                                placeholder="Иван Иванов" 
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="position" class="form-label fw-bold">Должность *</label>
                            <select class="form-select @error('position') is-invalid @enderror" 
                                id="position" 
                                name="position" 
                                required>
                                <option value="" disabled selected>Выберите должность</option>
                                <option value="Кальянщик" {{ old('position') == 'Кальянщик' ? 'selected' : '' }}>Кальянщик</option>
                                <option value="Администратор" {{ old('position') == 'Администратор' ? 'selected' : '' }}>Администратор</option>
                                <option value="Бармен" {{ old('position') == 'Бармен' ? 'selected' : '' }}>Бармен</option>
                                <option value="Официант" {{ old('position') == 'Официант' ? 'selected' : '' }}>Официант</option>
                                <option value="Уборщик" {{ old('position') == 'Уборщик' ? 'selected' : '' }}>Уборщик</option>
                            </select>
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Контакты -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-bold">Email *</label>
                            <input type="email" 
                                class="form-control @error('email') is-invalid @enderror" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                placeholder="employee@example.com" 
                                required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password" class="form-label fw-bold">Пароль *</label>
                            <div class="input-group">
                                <input type="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    id="password" 
                                    name="password" 
                                    placeholder="Минимум 8 символов" 
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Для входа в систему</div>
                        </div>
                    </div>

                    <!-- Телефон и соцсеть -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="phone" class="form-label fw-bold">Телефон</label>
                            <input type="tel" 
                                class="form-control @error('phone') is-invalid @enderror" 
                                id="phone" 
                                name="phone" 
                                value="{{ old('phone') }}" 
                                placeholder="+7 999 123-45-67">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="social_network" class="form-label fw-bold">Социальная сеть</label>
                            <input type="url" 
                                class="form-control @error('social_network') is-invalid @enderror" 
                                id="social_network" 
                                name="social_network" 
                                value="{{ old('social_network') }}" 
                                placeholder="https://t.me/username">
                            @error('social_network')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Система оплаты -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="fw-bold mb-3">💵 Система оплаты</h6>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="shift_salary" class="form-label">Ставка за смену</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control @error('shift_salary') is-invalid @enderror" 
                                        id="shift_salary" 
                                        name="shift_salary" 
                                        value="{{ old('shift_salary', 0) }}">
                                    <span class="input-group-text">₽</span>
                                    @error('shift_salary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Фиксированная оплата за смену</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="revenue_percentage" class="form-label">Процент с выручки</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        max="100" 
                                        step="0.1"
                                        class="form-control @error('revenue_percentage') is-invalid @enderror" 
                                        id="revenue_percentage" 
                                        name="revenue_percentage" 
                                        value="{{ old('revenue_percentage', 0) }}">
                                    <span class="input-group-text">%</span>
                                    @error('revenue_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Процент от общей выручки заведения</div>
                            </div>
                        </div>
                    </div>

                    <!-- Дополнительная информация -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="inn" class="form-label fw-bold">ИНН</label>
                            <input type="text" 
                                class="form-control @error('inn') is-invalid @enderror" 
                                id="inn" 
                                name="inn" 
                                value="{{ old('inn') }}" 
                                placeholder="123456789012">
                            @error('inn')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="tips_link" class="form-label fw-bold">Ссылка для чаевых</label>
                            <input type="url" 
                                class="form-control @error('tips_link') is-invalid @enderror" 
                                id="tips_link" 
                                name="tips_link" 
                                value="{{ old('tips_link') }}" 
                                placeholder="https://tips.hookah-bar.ru/employee">
                            @error('tips_link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Заметки -->
                    <div class="mb-3">
                        <label for="notes" class="form-label fw-bold">Заметки</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                            id="notes" 
                            name="notes" 
                            rows="3" 
                            placeholder="Дополнительная информация о сотруднике">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Сохранить сотрудника
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Показать/скрыть пароль
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
});
</script>