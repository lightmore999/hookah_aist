@extends('layouts.app')

@section('title', 'Сотрудники')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-people me-2"></i>Сотрудники
            </h1>
            <p class="text-muted mb-0 small">Управление персоналом кальянной</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary mt-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createEmployeeModal">
                <i class="bi bi-person-plus me-1"></i> Добавить сотрудника
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($employees->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-people display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет сотрудников. Добавьте первого!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createEmployeeModal">
                        <i class="bi bi-person-plus me-1"></i> Добавить сотрудника
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Имя</th>
                                <th>Должность</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Ставки</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            {{ substr($employee->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <strong>{{ $employee->name }}</strong>
                                            @if($employee->notes)
                                                <br><small class="text-muted">{{ Str::limit($employee->notes, 50) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $employee->position }}</span>
                                </td>
                                <td>
                                    <a href="mailto:{{ $employee->email }}" class="text-decoration-none">
                                        {{ $employee->email }}
                                    </a>
                                </td>
                                <td>
                                    @if($employee->phone)
                                        <a href="tel:{{ $employee->phone }}" class="text-decoration-none">
                                            {{ $employee->phone }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        @if($employee->hourly_rate > 0)
                                            <span class="badge bg-info" title="Почасовая ставка">{{ $employee->hourly_rate }} ₽/ч</span>
                                        @endif
                                        @if($employee->shift_rate > 0)
                                            <span class="badge bg-warning" title="Ставка за смену">{{ $employee->shift_rate }} ₽</span>
                                        @endif
                                        @if($employee->hookah_percentage > 0)
                                            <span class="badge bg-success" title="Процент от кальяна">{{ $employee->hookah_percentage }}%</span>
                                        @endif
                                        @if($employee->hookah_rate > 0)
                                            <span class="badge bg-primary" title="Ставка за кальян">{{ $employee->hookah_rate }} ₽</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-employee-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editEmployeeModal"
                                            data-id="{{ $employee->id }}"
                                            data-name="{{ $employee->name }}"
                                            data-email="{{ $employee->email }}"
                                            data-position="{{ $employee->position }}"
                                            data-phone="{{ $employee->phone }}"
                                            data-social="{{ $employee->social_network }}"
                                            data-notes="{{ $employee->notes }}"
                                            data-percentage="{{ $employee->hookah_percentage }}"
                                            data-hookah-rate="{{ $employee->hookah_rate }}"
                                            data-shift-rate="{{ $employee->shift_rate }}"
                                            data-hourly-rate="{{ $employee->hourly_rate }}"
                                            data-inn="{{ $employee->inn }}"
                                            data-tips="{{ $employee->tips_link }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-employee-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteEmployeeModal"
                                            data-id="{{ $employee->id }}"
                                            data-name="{{ $employee->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Подключаем модальные окна -->
@include('employees.modals.create')
@include('employees.modals.edit')
@include('employees.modals.delete')

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Страница сотрудников загружена');
    
    // Модалка редактирования
    const editEmployeeModal = document.getElementById('editEmployeeModal');
    if (editEmployeeModal) {
        editEmployeeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            if (button && button.classList.contains('edit-employee-btn')) {
                // Заполняем поля формы
                document.getElementById('edit_name').value = button.dataset.name || '';
                document.getElementById('edit_email').value = button.dataset.email || '';
                document.getElementById('edit_position').value = button.dataset.position || '';
                document.getElementById('edit_phone').value = button.dataset.phone || '';
                document.getElementById('edit_social_network').value = button.dataset.social || '';
                document.getElementById('edit_notes').value = button.dataset.notes || '';
                document.getElementById('edit_hookah_percentage').value = button.dataset.percentage || 0;
                document.getElementById('edit_hookah_rate').value = button.dataset.hookahRate || 0;
                document.getElementById('edit_shift_rate').value = button.dataset.shiftRate || 0;
                document.getElementById('edit_hourly_rate').value = button.dataset.hourlyRate || 0;
                document.getElementById('edit_inn').value = button.dataset.inn || '';
                document.getElementById('edit_tips_link').value = button.dataset.tips || '';
                
                // Устанавливаем action формы
                const form = document.getElementById('editEmployeeForm');
                form.action = '/employees/' + button.dataset.id;
                console.log('Form action установлен:', form.action);
            }
        });
    }

    // Модалка удаления
    const deleteEmployeeModal = document.getElementById('deleteEmployeeModal');
    if (deleteEmployeeModal) {
        deleteEmployeeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            
            if (button && button.classList.contains('delete-employee-btn')) {
                // Устанавливаем имя сотрудника
                document.getElementById('deleteEmployeeName').textContent = button.dataset.name || '';
                
                // Устанавливаем action формы
                const form = document.getElementById('deleteEmployeeForm');
                form.action = '/employees/' + button.dataset.id;
                console.log('DELETE form action:', form.action);
            }
        });
    }
    
    // Показать/скрыть пароль в модалке редактирования
    const toggleEditPassword = document.getElementById('toggleEditPassword');
    if (toggleEditPassword) {
        toggleEditPassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('edit_password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
    
    // Автоматическое скрытие алертов
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
    font-weight: bold;
}
.badge {
    font-size: 0.75em;
    margin-right: 3px;
}
</style>
@endsection