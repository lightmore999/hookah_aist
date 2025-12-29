@extends('layouts.app')

@section('title', 'Типы расходов')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Типы расходов
            </h1>
            <p class="text-muted mb-0 small">Управление категориями расходов</p>
        </div>
        
        <div>
            <button type="button" 
                    class="btn btn-primary mt-2"
                    data-bs-toggle="modal"
                    data-bs-target="#createExpenditureTypeModal">
                <i class="bi bi-plus-circle me-1"></i> Добавить тип
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
            @if($expenditureTypes->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет типов расходов. Добавьте первый!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createExpenditureTypeModal">
                        <i class="bi bi-plus-circle me-1"></i> Добавить тип
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Название типа</th>
                                <th>Кол-во расходов</th>
                                <th>Дата создания</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenditureTypes as $type)
                            <tr>
                                <td>{{ $type->id }}</td>
                                <td>
                                    <strong>{{ $type->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $type->expenditures()->count() }}</span>
                                </td>
                                <td>{{ $type->created_at->format('d.m.Y') }}</td>
                                <td class="text-end">
                                    <button type="button" 
                                            class="btn btn-warning btn-sm edit-expenditure-type-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editExpenditureTypeModal"
                                            data-id="{{ $type->id }}"
                                            data-name="{{ $type->name }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm delete-expenditure-type-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteExpenditureTypeModal"
                                            data-id="{{ $type->id }}"
                                            data-name="{{ $type->name }}"
                                            data-count="{{ $type->expenditures()->count() }}">
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
    const editModal = document.getElementById('editExpenditureTypeModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-expenditure-type-btn')) {
                document.getElementById('edit_name').value = button.dataset.name;
                document.getElementById('editExpenditureTypeForm').action = `/expenditure-types/${button.dataset.id}`;
            }
        });
    }

    const deleteModal = document.getElementById('deleteExpenditureTypeModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-expenditure-type-btn')) {
                document.getElementById('deleteExpenditureTypeName').textContent = button.dataset.name;
                document.getElementById('deleteExpenditureTypeCount').textContent = button.dataset.count;
                document.getElementById('deleteExpenditureTypeForm').action = `/expenditure-types/${button.dataset.id}`;
            }
        });
    }
});
</script>

@include('expenditure-types.modals.create')
@include('expenditure-types.modals.edit')
@include('expenditure-types.modals.delete')

@endsection