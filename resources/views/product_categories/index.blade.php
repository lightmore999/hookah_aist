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
            <a href="{{ route('product_categories.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить категорию
            </a>
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
                    <a href="{{ route('product_categories.create') }}" class="btn btn-primary mt-2">
                        Добавить Категорию 
                    </a>
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
                                        <a href="{{ route('product_categories.edit', $category) }}" 
                                           class="btn btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                        {{ $product_categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection