@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Зарплата: {{ $user->name }}</h2>
            <p class="text-muted">{{ $user->position }}</p>
        </div>
        <div class="col-auto">
            <form method="GET" class="row g-2">
                <div class="col-auto">
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }} год</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Итого за {{ $year }} год</h5>
                    <h2 class="text-primary">{{ number_format($yearlyTotal, 2) }} ₽</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Всего смен</h5>
                    <h2>{{ $totalShifts }}</h2>
                    <small class="text-muted">Закрыто: {{ $closedShifts }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Месяцев с зарплатой</h5>
                    <h2>{{ count($monthlyData) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- По месяцам -->
    <div class="row">
        @foreach($monthlyData as $month)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">{{ $month['month_name'] }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-4">{{ number_format($month['total_amount'], 2) }} ₽</span>
                            <span class="badge bg-primary">{{ $month['shifts_count'] }} смен</span>
                        </div>
                        
                        @if(count($month['salaries']) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless mb-0">
                                    <tbody>
                                        @foreach($month['salaries'] as $salary)
                                            <tr>
                                                <td class="text-muted">{{ $salary['shift_date'] }}</td>
                                                <td class="text-end">{{ number_format($salary['amount'], 2) }} ₽</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">Нет данных по сменам</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        
        @if(empty($monthlyData))
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Нет данных о зарплате за {{ $year }} год
                </div>
            </div>
        @endif
    </div>
</div>
@endsection