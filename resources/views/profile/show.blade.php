@extends('layouts.app')

@section('title', 'Мой профиль')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Мой профиль
            </h1>
            <p class="text-muted mb-0 small">Личная информация и настройки</p>
        </div>
        
        <div>
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Редактировать
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Основная информация -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-circle me-2"></i>Основная информация
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Имя:</div>
                        <div class="col-8 fw-medium">{{ $user->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Email:</div>
                        <div class="col-8">{{ $user->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Роль:</div>
                        <div class="col-8">
                            <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : 'primary' }}">
                                {{ $user->role === 'admin' ? 'Администратор' : 'Сотрудник' }}
                            </span>
                        </div>
                    </div>
                    @if($user->position)
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Должность:</div>
                        <div class="col-8">{{ $user->position }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Контактная информация -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-telephone me-2"></i>Контактная информация
                    </h5>
                </div>
                <div class="card-body">
                    @if($user->phone)
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Телефон:</div>
                        <div class="col-8">{{ $user->phone }}</div>
                    </div>
                    @endif
                    
                    @if($user->social_network)
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Соц. сеть:</div>
                        <div class="col-8">
                            <a href="{{ $user->social_network }}" target="_blank" class="text-decoration-none">
                                {{ $user->social_network }}
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    @if($user->inn)
                    <div class="row mb-3">
                        <div class="col-4 text-muted">ИНН:</div>
                        <div class="col-8">{{ $user->inn }}</div>
                    </div>
                    @endif
                    
                    @if($user->notes)
                    <div class="row">
                        <div class="col-4 text-muted">Заметки:</div>
                        <div class="col-8">{{ $user->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Рабочая информация (для сотрудников) -->
    @if($user->isEmployee())
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-briefcase me-2"></i>Рабочая информация
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($user->shift_salary)
                        <div class="col-md-4 mb-3">
                            <div class="text-muted small">Зарплата за смену</div>
                            <div class="fw-bold fs-5 text-success">{{ number_format($user->shift_salary, 2) }} ₽</div>
                        </div>
                        @endif
                        
                        @if($user->revenue_percentage)
                        <div class="col-md-4 mb-3">
                            <div class="text-muted small">Процент от выручки</div>
                            <div class="fw-bold fs-5 text-primary">{{ $user->revenue_percentage }}%</div>
                        </div>
                        @endif
                        
                        @if($user->tips_link)
                        <div class="col-md-4 mb-3">
                            <div class="text-muted small">Ссылка для чаевых</div>
                            <a href="{{ $user->tips_link }}" target="_blank" class="text-decoration-none">
                                {{ Str::limit($user->tips_link, 30) }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

   
</div>
@endsection