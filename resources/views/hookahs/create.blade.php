@extends('layouts.app')

@section('title', 'Добавить кальян')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('hookahs.index') }}">Кальяны</a>
            </li>
            <li class="breadcrumb-item active">Добавить кальян</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle text-primary me-2"></i>
                Добавить кальян
            </h1>
            <p class="text-muted mb-0">Заполните информацию о новом кальяне</p>
        </div>
        
        <div>
            <a href="{{ route('hookahs.index') }}" class="btn btn-outline-secondary">
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

                    <form action="{{ route('hookahs.store') }}" method="POST">
                        @csrf
                        
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <strong>Название кальяна</strong> *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="Например: Яблочный кальян" 
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
                                    По этой цене кальян будет продаваться
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

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="hookah_maker_rate" class="form-label">
                                    <strong>Ставка кальянщика (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           min="0" 
                                           class="form-control @error('hookah_maker_rate') is-invalid @enderror" 
                                           id="hookah_maker_rate" 
                                           name="hookah_maker_rate" 
                                           value="{{ old('hookah_maker_rate') }}" 
                                           placeholder="0.00" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('hookah_maker_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Оплата за приготовление одного кальяна
                                </small>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="administrator_rate" class="form-label">
                                    <strong>Ставка администратора (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           min="0" 
                                           class="form-control @error('administrator_rate') is-invalid @enderror" 
                                           id="administrator_rate" 
                                           name="administrator_rate" 
                                           value="{{ old('administrator_rate') }}" 
                                           placeholder="0.00" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('administrator_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">
                                    Оплата за обслуживание одного кальяна
                                </small>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i> Сохранить кальян
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection