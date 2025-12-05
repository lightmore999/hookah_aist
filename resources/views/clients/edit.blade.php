@extends('layouts.app')

@section('title', 'Редактировать клиента')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('clients.index') }}">Клиенты</a>
            </li>
            <li class="breadcrumb-item active">Редактировать: {{ $client->name }}</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                Редактировать клиента
            </h1>
        </div>
        
        <div>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning border-2">
                <div class="card-header bg-warning bg-opacity-10">
                    <strong>Редактирование клиента "{{ $client->name }}"</strong>
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

                    <form action="{{ route('clients.update', $client) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <strong>Имя клиента</strong> *
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $client->name) }}" 
                                   required
                                   autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="phone" class="form-label">
                                <strong>Телефон</strong> *
                            </label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $client->phone) }}" 
                                   required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="birth_date" class="form-label">
                                <strong>Дата рождения</strong>
                            </label>
                            <input type="date" 
                                   class="form-control @error('birth_date') is-invalid @enderror" 
                                   id="birth_date" 
                                   name="birth_date" 
                                   value="{{ old('birth_date', $client->birth_date ? $client->birth_date->format('Y-m-d') : '') }}">
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="comment" class="form-label">
                                <strong>Комментарий</strong>
                            </label>
                            <textarea class="form-control @error('comment') is-invalid @enderror" 
                                      id="comment" 
                                      name="comment" 
                                      rows="4">{{ old('comment', $client->comment) }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between pt-3">
                            <div>
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Обновить клиента
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            
            <div class="card border-danger mt-4">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Удаление клиента невозможно отменить. Все данные будут безвозвратно удалены.
                    </p>
                    <form action="{{ route('clients.destroy', $client) }}" 
                          method="POST" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить клиента \"{{ $client->name }}\"? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Удалить этого клиента
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection


