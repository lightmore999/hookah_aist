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
    
    <!-- Фильтры и статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-cash-stack fs-4 text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Общая сумма</h6>
                            <h4 class="mb-0 text-danger">{{ number_format($expenditures->sum('cost'), 2) }} ₽</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-wallet fs-4 text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Наличные</h6>
                            <h4 class="mb-0">{{ number_format($expenditures->where('payment_method', 'cash')->sum('cost'), 2) }} ₽</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 bg-info bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-credit-card fs-4 text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Картой</h6>
                            <h4 class="mb-0">{{ number_format($expenditures->where('payment_method', 'card')->sum('cost'), 2) }} ₽</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="bi bi-calendar-month fs-4 text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0">Ежемесячные</h6>
                            <h4 class="mb-0">{{ number_format($expenditures->where('is_monthly_expense', true)->sum('cost'), 2) }} ₽</h4>
                        </div>
                    </div>
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
                                    @if($expenditure->payment_method == 'cash')
                                        <span class="badge bg-success">Наличные</span>
                                    @else
                                        <span class="badge bg-primary">Карта</span>
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
                                            data-name="{{ $expenditure->name }}"
                                            data-cost="{{ $expenditure->cost }}"
                                            data-payment-method="{{ $expenditure->payment_method }}"
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
                                            data-cost="{{ $expenditure->cost }}">
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
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('edit_cost').value = button.dataset.cost;
                document.getElementById('edit_payment_method').value = button.dataset.paymentMethod;
                document.getElementById('edit_comment').value = button.dataset.comment;
                document.getElementById('edit_expenditure_date').value = button.dataset.expenditureDate;
                
                // Чекбоксы - исправленная логика
                const isHiddenAdminCheckbox = document.getElementById('edit_is_hidden_admin');
                const isMonthlyExpenseCheckbox = document.getElementById('edit_is_monthly_expense');
                
                // Преобразуем строку в boolean и устанавливаем checked
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
                document.getElementById('deleteExpenditureName').textContent = button.dataset.name;
                document.getElementById('deleteExpenditureCost').textContent = button.dataset.cost + ' ₽';
                document.getElementById('deleteExpenditureForm').action = `/expenditures/${button.dataset.id}`;
            }
        });
    }
});
</script>

@include('expenditures.modals.create')
@include('expenditures.modals.edit')
@include('expenditures.modals.delete')

@endsection