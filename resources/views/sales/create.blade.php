@extends('layouts.app')

@section('title', 'Добавить продажу')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('sales.index') }}">Продажи</a>
            </li>
            <li class="breadcrumb-item active">Добавить продажу</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle text-primary me-2"></i>
                Добавить продажу
            </h1>
            <p class="text-muted mb-0">Заполните информацию о новой продаже</p>
        </div>
        
        <div>
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

                    <form action="{{ route('sales.store') }}" method="POST">
                        @csrf
                        
                        
                        <div class="mb-4">
                            <label for="product_id" class="form-label">
                                <strong>Товар</strong> *
                            </label>
                            <select name="product_id" 
                                    class="form-select form-select-lg @error('product_id') is-invalid @enderror" 
                                    id="product_id" 
                                    required>
                                <option value="">-- Выберите товар --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="warehouse_id" class="form-label">
                                <strong>Склад</strong> *
                            </label>
                            <select name="warehouse_id" 
                                    class="form-select form-select-lg @error('warehouse_id') is-invalid @enderror" 
                                    id="warehouse_id" 
                                    required>
                                <option value="">-- Выберите склад --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="client_id" class="form-label">
                                <strong>Клиент</strong>
                            </label>
                            <select name="client_id" 
                                    class="form-select form-select-lg @error('client_id') is-invalid @enderror" 
                                    id="client_id">
                                <option value="">-- Выберите клиента (необязательно) --</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="quantity" class="form-label">
                                    <strong>Количество</strong> *
                                </label>
                                <input type="number" 
                                       min="1" 
                                       class="form-control @error('quantity') is-invalid @enderror" 
                                       id="quantity" 
                                       name="quantity" 
                                       value="{{ old('quantity') }}" 
                                       placeholder="0" 
                                       required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="payment_method" class="form-label">
                                    <strong>Способ оплаты</strong> *
                                </label>
                                <select name="payment_method" 
                                        class="form-select @error('payment_method') is-invalid @enderror" 
                                        id="payment_method" 
                                        required>
                                    <option value="">-- Выберите способ оплаты --</option>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Наличные</option>
                                    <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Карта</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="total" class="form-label">
                                    <strong>Общая сумма (₽)</strong> *
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control @error('total') is-invalid @enderror" 
                                           id="total" 
                                           name="total" 
                                           value="{{ old('total') }}" 
                                           placeholder="0.00" 
                                           required>
                                    <span class="input-group-text">₽</span>
                                    @error('total')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="sold_at" class="form-label">
                                    <strong>Дата продажи</strong> *
                                </label>
                                <input type="datetime-local" 
                                       class="form-control @error('sold_at') is-invalid @enderror" 
                                       id="sold_at" 
                                       name="sold_at" 
                                       value="{{ old('sold_at', date('Y-m-d\TH:i')) }}" 
                                       required>
                                @error('sold_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-1"></i> Сохранить продажу
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

