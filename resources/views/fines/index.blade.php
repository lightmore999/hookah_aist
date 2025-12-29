@extends('layouts.app')

@section('title', 'Штрафы')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Штрафы
            </h1>
            <p class="text-muted mb-0 small">Управление штрафами сотрудников</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary mt-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createFineModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить штраф
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
    
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($fines->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет штрафов. Добавьте первый!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createFineModal">
                        <i class="bi bi-plus-circle me-1"></i> Добавить штраф
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Сотрудник</th>
                                <th>Комментарий</th>
                                <th>Сумма (₽)</th>
                                <th>Дата</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fines as $fine)
                            <tr>
                                <td>
                                    <strong>{{ $fine->user->name }}</strong><br>
                                    <small class="text-muted">{{ $fine->user->email }}</small>
                                </td>
                                <td>{{ Str::limit($fine->comment, 100) }}</td>
                                <td class="text-danger fw-bold">{{ number_format($fine->amount, 2) }} ₽</td>
                                <td>{{ $fine->created_at->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-fine-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editFineModal"
                                            data-id="{{ $fine->id }}"
                                            data-user-id="{{ $fine->user_id }}"
                                            data-comment="{{ $fine->comment }}"
                                            data-amount="{{ $fine->amount }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-fine-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteFineModal"
                                            data-id="{{ $fine->id }}"
                                            data-user-name="{{ $fine->user->name }}"
                                            data-amount="{{ $fine->amount }}">
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
    const editFineModal = document.getElementById('editFineModal');
    if (editFineModal) {
        editFineModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-fine-btn')) {
                document.getElementById('edit_user_id').value = button.dataset.userId;
                document.getElementById('edit_comment').value = button.dataset.comment;
                document.getElementById('edit_amount').value = button.dataset.amount;
                document.getElementById('editFineForm').action = `/fines/${button.dataset.id}`;
            }
        });
    }

    const deleteFineModal = document.getElementById('deleteFineModal');
    if (deleteFineModal) {
        deleteFineModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-fine-btn')) {
                document.getElementById('deleteFineUserName').textContent = button.dataset.userName;
                document.getElementById('deleteFineAmount').textContent = button.dataset.amount + ' ₽';
                document.getElementById('deleteFineForm').action = `/fines/${button.dataset.id}`;
            }
        });
    }
});
</script>

@include('fines.modals.create')
@include('fines.modals.edit')
@include('fines.modals.delete')

@endsection