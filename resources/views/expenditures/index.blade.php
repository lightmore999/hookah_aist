@extends('layouts.app')

@section('title', 'Расходы')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Расходы
            </h1>
            <p class="text-muted mb-0 small">Управление финансовыми расходами</p>
        </div>
        
        <div class="d-flex gap-2">
            <!-- Кнопка для типов расходов -->
            <a href="{{ route('expenditure-types.index') }}" 
               class="btn btn-outline-secondary mt-2">
                <i class="bi bi-tags me-1"></i> Типы расходов
            </a>
            
            <!-- Кнопка добавления расхода -->
            <button type="button" 
                    class="btn btn-primary mt-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createExpenditureModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить расход
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
    
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Общая сумма</h6>
                    <h4 class="text-danger fw-bold">{{ number_format($totalAmount, 2) }} ₽</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Наличные</h6>
                    <h4 class="text-success fw-bold">{{ number_format($cashAmount, 2) }} ₽</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Карта</h6>
                    <h4 class="text-primary fw-bold">{{ number_format($cardAmount, 2) }} ₽</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Ежемесячные</h6>
                    <h4 class="text-info fw-bold">{{ number_format($monthlyAmount, 2) }} ₽</h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($expenditures->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет расходов. Добавьте первый!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createExpenditureModal">
                        <i class="bi bi-plus-circle me-1"></i> Добавить расход
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Дата</th>
                                <th>Название</th>
                                <th>Тип</th>
                                <th>Сумма</th>
                                <th>Оплата</th>
                                <th>Статус</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenditures as $expenditure)
                            <tr>
                                <td>{{ $expenditure->expenditure_date->format('d.m.Y H:i') }}</td>
                                <td>
                                    <strong>{{ $expenditure->name }}</strong>
                                    @if($expenditure->comment)
                                        <br><small class="text-muted">{{ Str::limit($expenditure->comment, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $expenditure->expenditureType->name }}</span>
                                </td>
                                <td class="text-danger fw-bold">{{ number_format($expenditure->cost, 2) }} ₽</td>
                                <td>
                                    @if($expenditure->paymentMethod)
                                        <span class="badge {{ $expenditure->paymentMethod->Name == 'Наличные' ? 'bg-success' : ($expenditure->paymentMethod->Name == 'Карта' ? 'bg-primary' : 'bg-secondary') }}">
                                            {{ $expenditure->paymentMethod->Name }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning">Не указано</span>
                                    @endif
                                </td>
                                <td>
                                    @if($expenditure->is_hidden_admin)
                                        <span class="badge bg-dark" title="Скрыто от администратора">
                                            <i class="bi bi-eye-slash"></i>
                                        </span>
                                    @endif
                                    @if($expenditure->is_monthly_expense)
                                        <span class="badge bg-info" title="Ежемесячный расход">
                                            <i class="bi bi-calendar-month"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-expenditure-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editExpenditureModal"
                                            data-id="{{ $expenditure->id }}"
                                            data-expenditure-type-id="{{ $expenditure->expenditure_type_id }}"
                                            data-payment-method-id="{{ $expenditure->payment_method_id }}"
                                            data-name="{{ $expenditure->name }}"
                                            data-cost="{{ $expenditure->cost }}"
                                            data-comment="{{ $expenditure->comment }}"
                                            data-expenditure-date="{{ $expenditure->expenditure_date->format('Y-m-d\TH:i') }}"
                                            data-is-hidden-admin="{{ $expenditure->is_hidden_admin ? 'true' : 'false' }}"
                                            data-is-monthly-expense="{{ $expenditure->is_monthly_expense ? 'true' : 'false' }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-expenditure-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteExpenditureModal"
                                            data-id="{{ $expenditure->id }}"
                                            data-name="{{ $expenditure->name }}"
                                            data-cost="{{ $expenditure->cost }}"
                                            data-type="{{ $expenditure->expenditureType->name ?? '' }}"
                                            data-date="{{ $expenditure->expenditure_date->format('d.m.Y H:i') }}">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editExpenditureModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-expenditure-btn')) {
                // Основные поля
                document.getElementById('edit_expenditure_type_id').value = button.dataset.expenditureTypeId;
                document.getElementById('edit_payment_method_id').value = button.dataset.paymentMethodId; // Изменено
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('edit_cost').value = button.dataset.cost;
                document.getElementById('edit_comment').value = button.dataset.comment;
                document.getElementById('edit_expenditure_date').value = button.dataset.expenditureDate;
                
                // Чекбоксы
                const isHiddenAdminCheckbox = document.getElementById('edit_is_hidden_admin');
                const isMonthlyExpenseCheckbox = document.getElementById('edit_is_monthly_expense');
                
                isHiddenAdminCheckbox.checked = button.dataset.isHiddenAdmin === 'true';
                isMonthlyExpenseCheckbox.checked = button.dataset.isMonthlyExpense === 'true';
                
                // Устанавливаем action формы
                document.getElementById('editExpenditureForm').action = `/expenditures/${button.dataset.id}`;
            }
        });
    }

    const deleteModal = document.getElementById('deleteExpenditureModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-expenditure-btn')) {
                // Заполняем данные о расходе
                document.getElementById('deleteExpenditureName').textContent = button.dataset.name;
                document.getElementById('deleteExpenditureCost').textContent = numberFormat(button.dataset.cost) + ' ₽';
                document.getElementById('deleteExpenditureType').textContent = button.dataset.type || '';
                document.getElementById('deleteExpenditureDate').textContent = button.dataset.date || '';
                
                // Устанавливаем action формы с ID
                const form = document.getElementById('deleteExpenditureForm');
                form.action = form.action.replace('__ID__', button.dataset.id);
                
                // Очищаем поле комментария при открытии новой модалки
                document.getElementById('delete_comment').value = '';
                
                // Сбрасываем валидацию
                form.classList.remove('was-validated');
            }
        });
        
        // Валидация формы перед отправкой
        const form = document.getElementById('deleteExpenditureForm');
        const submitBtn = document.getElementById('submitDeleteBtn');
        
        form.addEventListener('submit', function(event) {
            const commentField = document.getElementById('delete_comment');
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                
                // Прокручиваем к полю с ошибкой
                commentField.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                commentField.focus();
                
                // Мигание поля
                commentField.classList.add('is-invalid');
                setTimeout(() => {
                    commentField.classList.remove('is-invalid');
                }, 2000);
            } else {
                // Подтверждение перед отправкой
                if (!confirm('Вы уверены, что хотите удалить этот расход с указанной причиной?')) {
                    event.preventDefault();
                } else {
                    // Блокируем кнопку на время отправки
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Удаление...';
                }
            }
        }, false);
    }
    
    // Функция для форматирования чисел
    function numberFormat(number) {
        return parseFloat(number).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }
});
</script>

@include('expenditures.modals.create')
@include('expenditures.modals.edit')
@include('expenditures.modals.delete')

@endsection