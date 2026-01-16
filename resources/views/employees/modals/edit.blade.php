<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="editEmployeeModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактировать сотрудника
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editEmployeeForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <!-- Основная информация -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label fw-bold">Имя *</label>
                            <input type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="edit_name" 
                                name="name" 
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_position" class="form-label fw-bold">Должность *</label>
                            <select class="form-select @error('position') is-invalid @enderror" 
                                id="edit_position" 
                                name="position" 
                                required>
                                <option value="Кальянщик">Кальянщик</option>
                                <option value="Администратор">Администратор</option>
                                <option value="Бармен">Бармен</option>
                                <option value="Официант">Официант</option>
                                <option value="Уборщик">Уборщик</option>
                            </select>
                            @error('position')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Контакты -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label fw-bold">Email *</label>
                            <input type="email" 
                                class="form-control @error('email') is-invalid @enderror" 
                                id="edit_email" 
                                name="email" 
                                required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_password" class="form-label">Новый пароль</label>
                            <div class="input-group">
                                <input type="password" 
                                    class="form-control @error('password') is-invalid @enderror" 
                                    id="edit_password" 
                                    name="password" 
                                    placeholder="Оставьте пустым, если не нужно менять">
                                <button class="btn btn-outline-secondary" type="button" id="toggleEditPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Заполните только если нужно изменить пароль</div>
                        </div>
                    </div>

                    <!-- Телефон и соцсеть -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="edit_phone" class="form-label">Телефон</label>
                            <input type="tel" 
                                class="form-control @error('phone') is-invalid @enderror" 
                                id="edit_phone" 
                                name="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_social_network" class="form-label">Социальная сеть</label>
                            <input type="url" 
                                class="form-control @error('social_network') is-invalid @enderror" 
                                id="edit_social_network" 
                                name="social_network">
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
                                <label for="edit_shift_salary" class="form-label">Ставка за смену (₽)</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        step="0.01"
                                        class="form-control @error('shift_salary') is-invalid @enderror" 
                                        id="edit_shift_salary" 
                                        name="shift_salary"
                                        value="0">
                                    <span class="input-group-text">₽</span>
                                    @error('shift_salary')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-text">Фиксированная оплата за смену</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="edit_revenue_percentage" class="form-label">Процент с выручки (%)</label>
                                <div class="input-group">
                                    <input type="number" 
                                        min="0" 
                                        max="100" 
                                        step="0.1"
                                        class="form-control @error('revenue_percentage') is-invalid @enderror" 
                                        id="edit_revenue_percentage" 
                                        name="revenue_percentage"
                                        value="0">
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
                            <label for="edit_inn" class="form-label">ИНН</label>
                            <input type="text" 
                                class="form-control @error('inn') is-invalid @enderror" 
                                id="edit_inn" 
                                name="inn">
                            @error('inn')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_tips_link" class="form-label">Ссылка для чаевых</label>
                            <input type="url" 
                                class="form-control @error('tips_link') is-invalid @enderror" 
                                id="edit_tips_link" 
                                name="tips_link">
                            @error('tips_link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Заметки -->
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Заметки</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                            id="edit_notes" 
                            name="notes" 
                            rows="3" 
                            placeholder="Дополнительная информация о сотруднике"></textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>Отмена
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Показать/скрыть пароль в модалке редактирования
document.addEventListener('DOMContentLoaded', function() {
    const toggleEditPassword = document.getElementById('toggleEditPassword');
    if (toggleEditPassword) {
        toggleEditPassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('edit_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
});
</script>