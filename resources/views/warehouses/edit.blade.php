@extends('layouts.app')

@section('title', 'Редактировать склад')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('warehouses.index') }}">Склады</a>
            </li>
            <li class="breadcrumb-item active">Редактировать: {{ $warehouse->name }}</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                Редактировать склад
            </h1>
        </div>
        
        <div>
            <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning border-2">
                <div class="card-header bg-warning bg-opacity-10">
                    <strong>Редактирование склада "{{ $warehouse->name }}"</strong>
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

                    <form action="{{ route('warehouses.update', $warehouse) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <strong>Название склада</strong> *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $warehouse->name) }}" 
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-between pt-3">
                            <div>
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Обновить склад
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card border-danger mt-4">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Удаление склада невозможно отменить. Все данные будут безвозвратно удалены.
                    </p>
                    <form action="{{ route('warehouses.destroy', $warehouse) }}" 
                          method="POST" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить склад \"{{ $warehouse->name }}\"? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Удалить этот склад
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection


