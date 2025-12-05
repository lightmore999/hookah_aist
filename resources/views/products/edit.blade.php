@extends('layouts.app')

@section('title', 'Редактировать товар')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('products.index') }}">Товары</a>
            </li>
            <li class="breadcrumb-item active">Редактировать: {{ $product->name }}</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                Редактировать товар
            </h1>
        </div>
        
        <div>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning border-2">
                <div class="card-header bg-warning bg-opacity-10">
                    <strong>Редактирование товара "{{ $product->name }}"</strong>
                </div>
                
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

                    <form action="{{ route('products.update', $product) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <strong>Название товара</strong> *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $product->name) }}" 
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="price" class="form-label">
                                    <strong>Цена продажи (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control @error('price') is-invalid @enderror" 
                                           id="price" 
                                           name="price" 
                                           value="{{ old('price', $product->price) }}" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="cost" class="form-label">
                                    <strong>Себестоимость (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control @error('cost') is-invalid @enderror" 
                                           id="cost" 
                                           name="cost" 
                                           value="{{ old('cost', $product->cost) }}" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="product_category_id" class="form-label">Категория</label>
                            <select name="product_category_id" class="form-select">
                                <option value="">-- Выберите категорию --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ $product->product_category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        
                        <div class="d-flex justify-content-between pt-3">
                            <div>
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Обновить товар
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            
            <div class="card border-danger mt-4">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Удаление товара невозможно отменить. Все данные будут безвозвратно удалены.
                    </p>
                    <form action="{{ route('products.destroy', $product) }}" 
                          method="POST" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить товар \"{{ $product->name }}\"? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Удалить этот товар
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection