@extends('layouts.app')

@section('title', 'Категории товаров')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Категории
            </h1>
            <p class="text-muted mb-0 small">Управление категориями</p>
        </div>

        <div class="d-flex gap-3">
            <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-tags me-1"></i>
                Товары
            </a>
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createCategoryModal">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить категорию
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
            @if($product_categories->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет категорий. Добавьте первую!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createCategoryModal">
                        Добавить категорию
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($product_categories as $category)
                            <tr>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-warning edit-category-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editCategoryModal"
                                                data-id="{{ $category->id }}"
                                                data-name="{{ $category->name }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger delete-category-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCategoryModal"
                                                data-id="{{ $category->id }}"
                                                data-name="{{ $category->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
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


@include('product_categories.modals.create')
@include('product_categories.modals.edit') 
@include('product_categories.modals.delete')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editCategoryModal = document.getElementById('editCategoryModal');
    if (editCategoryModal) {
        editCategoryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-category-btn')) {
                document.getElementById('edit_category_name').value = button.dataset.name;
                document.getElementById('editCategoryForm').action = `/product_categories/${button.dataset.id}`;
            }
        });
    }

    const deleteCategoryModal = document.getElementById('deleteCategoryModal');
    if (deleteCategoryModal) {
        deleteCategoryModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-category-btn')) {
                document.getElementById('deleteCategoryName').textContent = button.dataset.name;
                document.getElementById('deleteCategoryForm').action = `/product_categories/${button.dataset.id}`;
            }
        });
    }
});
</script>

@endsection
