@extends('layouts.app')

@section('title', 'Редактировать стол')

@section('content')
<div class="container py-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('tables.index', ['date' => $table->booking_date->format('Y-m-d')]) }}">Столы</a>
            </li>
            <li class="breadcrumb-item active">Редактировать стол</li>
        </ol>
    </nav>

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                Редактировать стол
            </h1>
        </div>
        
        <div>
            <a href="{{ route('tables.index', ['date' => $table->booking_date->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад
            </a>
        </div>
    </div>

    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning border-2">
                <div class="card-header bg-warning bg-opacity-10">
                    <strong>Редактирование бронирования стола</strong>
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

                    <form action="{{ route('tables.update', $table) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        
                        <div class="mb-4">
                            <label for="table_number" class="form-label">
                                <strong>Номер стола</strong> *
                            </label>
                            <select name="table_number" 
                                    class="form-select form-select-lg @error('table_number') is-invalid @enderror" 
                                    id="table_number" 
                                    required>
                                <option value="">-- Выберите стол --</option>
                                <option value="1" {{ old('table_number', $table->table_number) == '1' ? 'selected' : '' }}>1</option>
                                <option value="2" {{ old('table_number', $table->table_number) == '2' ? 'selected' : '' }}>2</option>
                                <option value="3" {{ old('table_number', $table->table_number) == '3' ? 'selected' : '' }}>3</option>
                                <option value="4" {{ old('table_number', $table->table_number) == '4' ? 'selected' : '' }}>4</option>
                                <option value="Барная стойка" {{ old('table_number', $table->table_number) == 'Барная стойка' ? 'selected' : '' }}>Барная стойка</option>
                                <option value="6" {{ old('table_number', $table->table_number) == '6' ? 'selected' : '' }}>6</option>
                                <option value="7" {{ old('table_number', $table->table_number) == '7' ? 'selected' : '' }}>7</option>
                            </select>
                            @error('table_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="booking_date" class="form-label">
                                    <strong>Дата</strong> *
                                </label>
                                <input type="date" 
                                       class="form-control @error('booking_date') is-invalid @enderror" 
                                       id="booking_date" 
                                       name="booking_date" 
                                       value="{{ old('booking_date', $table->booking_date->format('Y-m-d')) }}" 
                                       required>
                                @error('booking_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="booking_time" class="form-label">
                                    <strong>Время</strong> *
                                </label>
                                <input type="time" 
                                       class="form-control @error('booking_time') is-invalid @enderror" 
                                       id="booking_time" 
                                       name="booking_time" 
                                       value="{{ old('booking_time', is_string($table->booking_time) ? $table->booking_time : $table->booking_time->format('H:i')) }}" 
                                       required>
                                @error('booking_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="duration" class="form-label">
                                    <strong>Длительность (минуты)</strong> *
                                </label>
                                <input type="number" 
                                       min="30" 
                                       step="30" 
                                       class="form-control @error('duration') is-invalid @enderror" 
                                       id="duration" 
                                       name="duration" 
                                       value="{{ old('duration', $table->duration) }}" 
                                       required>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="guests_count" class="form-label">
                                    <strong>Количество гостей</strong> *
                                </label>
                                <input type="number" 
                                       min="1" 
                                       class="form-control @error('guests_count') is-invalid @enderror" 
                                       id="guests_count" 
                                       name="guests_count" 
                                       value="{{ old('guests_count', $table->guests_count) }}" 
                                       required>
                                @error('guests_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>


                        
                        <div class="mb-4">
                            <label for="client_id" class="form-label">
                                <strong>Клиент</strong>
                            </label>
                            <select name="client_id" 
                                    class="form-select @error('client_id') is-invalid @enderror" 
                                    id="client_id">
                                <option value="">-- Выберите клиента (необязательно) --</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $table->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }} ({{ $client->phone }})
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="guest_name" class="form-label">
                                <strong>Имя гостя</strong>
                            </label>
                            <input type="text" 
                                   class="form-control @error('guest_name') is-invalid @enderror" 
                                   id="guest_name" 
                                   name="guest_name" 
                                   value="{{ old('guest_name', $table->guest_name) }}">
                            @error('guest_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="mb-4">
                            <label for="phone" class="form-label">
                                <strong>Телефон</strong>
                            </label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $table->phone) }}">
                            @error('phone')
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
                                      rows="4">{{ old('comment', $table->comment) }}</textarea>
                            @error('comment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between pt-3">
                            <div>
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-check-circle me-1"></i> Обновить стол
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            
            <div class="card border-danger mt-4">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Удаление бронирования невозможно отменить. Все данные будут безвозвратно удалены.
                    </p>
                    <form action="{{ route('tables.destroy', $table) }}" 
                          method="POST" 
                          onsubmit="return confirm('Вы уверены, что хотите удалить это бронирование? Это действие нельзя отменить.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Удалить это бронирование
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

