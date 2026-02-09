@extends('layouts.app')

@section('title', 'Редактирование профиля')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Редактирование профиля
            </h1>
            <p class="text-muted mb-0 small">Обновление личной информации</p>
        </div>
        
        <div>
            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Назад к профилю
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Ошибка!</strong> Пожалуйста, исправьте следующие ошибки:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Личная информация
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" id="profileForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Имя <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}" 
                                       required
                                       autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="text" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone', $user->phone) }}"
                                       placeholder="+7 (XXX) XXX-XX-XX">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="social_network" class="form-label">Социальная сеть</label>
                                <input type="text" 
                                       class="form-control @error('social_network') is-invalid @enderror" 
                                       id="social_network" 
                                       name="social_network" 
                                       value="{{ old('social_network', $user->social_network) }}"
                                       placeholder="https://vk.com/username">
                                @error('social_network')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="inn" class="form-label">ИНН</label>
                                <input type="text" 
                                       class="form-control @error('inn') is-invalid @enderror" 
                                       id="inn" 
                                       name="inn" 
                                       value="{{ old('inn', $user->inn) }}"
                                       maxlength="12"
                                       placeholder="12 цифр">
                                @error('inn')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Смена пароля -->
                        <div class="border-top pt-4 mt-4">
                            <h5 class="mb-3">
                                <i class="bi bi-key me-2"></i>Смена пароля
                                <small class="text-muted fw-normal">(заполняйте только если хотите сменить пароль)</small>
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="current_password" class="form-label">Текущий пароль</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control @error('current_password') is-invalid @enderror" 
                                               id="current_password" 
                                               name="current_password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    @error('current_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Новый пароль</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control @error('password') is-invalid @enderror" 
                                               id="password" 
                                               name="password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                                    <div class="input-group">
                                        <input type="password" 
                                               class="form-control" 
                                               id="password_confirmation" 
                                               name="password_confirmation">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation', this)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <small>Пароль должен содержать не менее 8 символов. Рекомендуется использовать буквы, цифры и специальные символы.</small>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Сохранить изменения
                            </button>
                            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary ms-2">
                                Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Информационная панель -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Важная информация
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="bi bi-exclamation-triangle me-1"></i>Внимание!
                        </h6>
                        <p class="mb-2 small">Все изменения вступают в силу сразу после сохранения.</p>
                        <p class="mb-0 small">При смене email может потребоваться повторная авторизация.</p>
                    </div>
                    
                    <div class="mt-3">
                        <h6 class="text-muted mb-2">
                            <i class="bi bi-calendar-check me-1"></i>Дата последнего обновления:
                        </h6>
                        <p class="mb-0">
                            <span class="badge bg-light text-dark">
                                {{ $user->updated_at->format('d.m.Y H:i') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-badge me-2"></i>Текущие данные
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Роль</div>
                        <div>
                            <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : 'primary' }}">
                                {{ $user->role === 'admin' ? 'Администратор' : 'Сотрудник' }}
                            </span>
                        </div>
                    </div>
                    
                    @if($user->position)
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Должность</div>
                        <div class="fw-medium">{{ $user->position }}</div>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Дата регистрации</div>
                        <div>{{ $user->created_at->format('d.m.Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Функция для показа/скрытия пароля
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Валидация формы
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Проверяем обязательные поля
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                hasErrors = true;
            }
        });
        
        // Валидация пароля
        if (newPassword.value || confirmPassword.value) {
            if (!currentPassword.value) {
                currentPassword.classList.add('is-invalid');
                document.querySelector('[for="current_password"]').innerHTML += 
                    '<span class="text-danger"> *</span>';
                hasErrors = true;
            }
            
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                hasErrors = true;
            }
            
            if (newPassword.value.length < 8) {
                newPassword.classList.add('is-invalid');
                hasErrors = true;
            }
        }
        
        if (hasErrors) {
            e.preventDefault();
            
            // Прокрутка к первой ошибке
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            
            return false;
        }
    });
    
    // Сброс ошибок при вводе
    const inputs = form.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});
</script>
@endsection