<div class="modal fade" id="addEmployeesModal" tabindex="-1" aria-labelledby="addEmployeesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addEmployeesModalLabel">
                    <i class="bi bi-people me-2"></i>Управление сотрудниками
                    <span id="modal_shift_date" class="badge bg-light text-primary ms-2"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="addEmployeesForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <!-- Контейнер для сотрудников - будет заполнен JavaScript -->
                    <div id="employeesContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-2">Загрузка сотрудников...</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Сохранить изменения
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addEmployeesModal = document.getElementById('addEmployeesModal');
    
    if (addEmployeesModal) {
        addEmployeesModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.dataset.shiftId) {
                const shiftId = button.dataset.shiftId;
                const date = button.dataset.date || '';
                
                console.log('Opening modal for shift:', shiftId);
                
                // Обновляем заголовок
                document.getElementById('modal_shift_date').textContent = date;
                
                // Обновляем action формы
                const form = document.getElementById('addEmployeesForm');
                form.action = `/shifts/${shiftId}/update-employees`;
                
                // Загружаем сотрудников смены через AJAX (простой запрос)
                loadShiftEmployees(shiftId);
            }
        });
    }
    
    async function loadShiftEmployees(shiftId) {
        try {
            // Загружаем информацию о смене и её сотрудниках
            const response = await fetch(`/shifts/${shiftId}/json-data`);
            const data = await response.json();
            
            // Показываем сотрудников
            renderEmployees(data.employees, data.shiftEmployees);
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('employeesContainer').innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <p class="mt-2 text-danger">Ошибка загрузки</p>
                </div>
            `;
        }
    }
    
    function renderEmployees(allEmployees, shiftEmployees) {
        const container = document.getElementById('employeesContainer');
        
        if (allEmployees.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="bi bi-people display-4 text-muted"></i>
                    <p class="mt-3 text-muted">Нет сотрудников в системе</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="mb-3">
                <input type="text" class="form-control" placeholder="Поиск сотрудников..." id="employeeSearch">
            </div>
            <div class="row">
        `;
        
        allEmployees.forEach(employee => {
            const isInShift = shiftEmployees.some(e => e.id === employee.id);
            
            html += `
                <div class="col-md-4 mb-3">
                    <div class="card ${isInShift ? 'border-primary' : ''}" style="cursor: pointer;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${employee.name}</h6>
                                    ${employee.position ? `<small class="text-muted d-block">${employee.position}</small>` : ''}
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input employee-checkbox" 
                                           type="checkbox" 
                                           name="employee_ids[]" 
                                           value="${employee.id}"
                                           ${isInShift ? 'checked' : ''}>
                                </div>
                            </div>
                            ${isInShift ? `
                                <div class="mt-2">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-check-circle me-1"></i>В смене
                                    </span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Добавляем обработчики
        addEventListeners();
    }
    
    function addEventListeners() {
        // Поиск
        const searchInput = document.getElementById('employeeSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                document.querySelectorAll('.col-md-4.mb-3').forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(term) ? 'block' : 'none';
                });
            });
        }
        
        // Клик по карточке
        document.addEventListener('click', function(e) {
            const card = e.target.closest('.card');
            if (card && !e.target.closest('.form-check')) {
                const checkbox = card.querySelector('.employee-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    card.classList.toggle('border-primary', checkbox.checked);
                }
            }
        });
        
        // Изменение чекбокса
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('employee-checkbox')) {
                const card = e.target.closest('.card');
                card.classList.toggle('border-primary', e.target.checked);
            }
        });
    }
});
</script>

<style>
.card.border-primary {
    border-width: 2px;
}
</style>