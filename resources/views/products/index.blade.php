@extends('layouts.app')

@section('title', 'Товары')

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Товары
            </h1>
            <p class="text-muted mb-0 small">Управление товарами</p>
        </div>
        <div class="d-flex gap-3">
            <a href="{{ route('product_categories.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-tags me-1"></i>
                Категории
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить товар
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
            
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('products.index') }}" id="filter-form">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="category_id" class="form-label">Категория</label>
                                    <select name="category_id" id="category_id" class="form-select"
                                            onchange="this.form.submit()"> 
                                        <option value="">Все категории</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @if($products->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет товаров. Добавьте первый!</p>
                    <a href="{{ route('products.create') }}" class="btn btn-primary mt-2">
                        Добавить Товар
                    </a>
                </div>
            @else
                
                
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th>Цена (₽)</th>
                                <th>Себестоимость (₽)</th>
                                <th>Категория</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                </td>
                                <td>{{ number_format($product->price, 2) }}</td>
                                <td>{{ number_format($product->cost, 2) }}</td>
                                <td>{{ $product->category->name ?? 'Без категории' }}</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('products.edit', $product) }}" 
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
            @endif
        </div>
    </div>
</div>
@endsection {{-- Закрываем content --}}

