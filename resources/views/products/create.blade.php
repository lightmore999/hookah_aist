@extends('layouts.app')

@section('title', 'Добавить товар')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('products.index') }}">Товары</a>
            </li>
            <li class="breadcrumb-item active">Добавить товар</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle text-primary me-2"></i>
                Добавить товар
            </h1>
            <p class="text-muted mb-0">Заполните информацию о новом товаре</p>
        </div>
        
        <div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Пожалуйста, исправьте ошибки:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf
                        
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <strong>Название товара</strong> *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Например: Газировка" 
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Укажите название, которое будет отображаться в меню
                            </small>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="price" class="form-label">
                                    <strong>Цена продажи (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           min="0" 
                                           class="form-control @error('price') is-invalid @enderror" 
                                           id="price" 
                                           name="price" 
                                           value="{{ old('price') }}" 
                                           placeholder="0.00" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    По этой цене товар будет продаваться
                                </small>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="cost" class="form-label">
                                    <strong>Себестоимость (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           min="0" 
                                           class="form-control @error('cost') is-invalid @enderror" 
                                           id="cost" 
                                           name="cost" 
                                           value="{{ old('cost') }}" 
                                           placeholder="0.00" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Стоимость ингредиентов и материалов
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="product_category_id" class="form-label">Категория</label>
                            <select name="product_category_id" class="form-select">
                                <option value="">-- Выберите категорию --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i> Сохранить товар
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection