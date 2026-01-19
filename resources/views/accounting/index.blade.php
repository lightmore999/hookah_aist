@extends('layouts.app')

@section('title', 'Бухгалтерия')

@section('content')
<div class="container-fluid py-4">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-calculator me-2"></i>Бухгалтерия
        </h1>
    </div>

    <!-- Общая статистика по способам оплаты -->
    @php
        $colors = ['primary', 'success', 'info', 'warning', 'danger', 'dark'];
    @endphp

    <div class="row mb-4">
        @foreach($paymentMethods as $index => $method)
            @php
                $methodId = $method->IDPaymentMethod;
                $methodTotal = 0;
                if (isset($totalStats['payment_stats'][$methodId])) {
                    $methodTotal = $totalStats['payment_stats'][$methodId]['total'] ?? 0;
                }
            @endphp
            <div class="col-md-3 mb-3">
                <div class="card border-{{ $colors[$index % count($colors)] }}">
                    <div class="card-body text-center">
                        <h6 class="text-muted mb-2">{{ $method->Name }}</h6>
                        <h3 class="text-{{ $colors[$index % count($colors)] }}">
                            {{ number_format($methodTotal, 0) }} ₽
                        </h3>
                    </div>
                </div>
            </div>
    @endforeach
        
        <!-- Общий доход -->
        <div class="col-md-3 mb-3">
            <div class="card border-dark">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Общий доход</h6>
                    <h3 class="text-dark">
                        {{ number_format($totalStats['total_income'], 0) }} ₽
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('accounting.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Показывать по</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="day" {{ $type == 'day' ? 'selected' : '' }}>Дням</option>
                        <option value="week" {{ $type == 'week' ? 'selected' : '' }}>Неделям</option>
                        <option value="month" {{ $type == 'month' ? 'selected' : '' }}>Месяцам</option>
                    </select>
                </div>

                @if($type == 'day')
                <div class="col-md-3">
                    <label class="form-label">Показать дней</label>
                    <select name="days_count" class="form-select" onchange="this.form.submit()">
                        <option value="7" {{ $daysCount == 7 ? 'selected' : '' }}>7 дней</option>
                        <option value="14" {{ $daysCount == 14 ? 'selected' : '' }}>14 дней</option>
                        <option value="30" {{ $daysCount == 30 ? 'selected' : '' }}>30 дней</option>
                        <option value="90" {{ $daysCount == 90 ? 'selected' : '' }}>90 дней</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Фильтр по датам (опционально)</label>
                    <div class="input-group">
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="Начало">
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="Конец">
                    </div>
                    <small class="text-muted">Оставьте пустыми для авто-периода</small>
                </div>
                @elseif($type == 'week')
                <div class="col-md-3">
                    <label class="form-label">Показать недель</label>
                    <select name="weeks_count" class="form-select" onchange="this.form.submit()">
                        <option value="4" {{ $weeksCount == 4 ? 'selected' : '' }}>4 недели</option>
                        <option value="8" {{ $weeksCount == 8 ? 'selected' : '' }}>8 недель</option>
                        <option value="12" {{ $weeksCount == 12 ? 'selected' : '' }}>12 недель</option>
                        <option value="16" {{ $weeksCount == 16 ? 'selected' : '' }}>16 недель</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Фильтр по датам (опционально)</label>
                    <div class="input-group">
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" placeholder="Начало">
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" placeholder="Конец">
                    </div>
                    <small class="text-muted">Оставьте пустыми для авто-периода</small>
                </div>
                @elseif($type == 'month')
                <div class="col-md-3">
                    <label class="form-label">Показать месяцев</label>
                    <select name="months_count" class="form-select" onchange="this.form.submit()">
                        <option value="3" {{ $monthsCount == 3 ? 'selected' : '' }}>3 месяца</option>
                        <option value="6" {{ $monthsCount == 6 ? 'selected' : '' }}>6 месяцев</option>
                        <option value="12" {{ $monthsCount == 12 ? 'selected' : '' }}>12 месяцев</option>
                        <option value="24" {{ $monthsCount == 24 ? 'selected' : '' }}>24 месяца</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Начать с месяца</label>
                    <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
                </div>
                @endif

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Обновить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Основная таблица -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="bi bi-calendar-week me-2"></i>
                Отчет по 
                @if($type == 'day') дням 
                @elseif($type == 'week') неделям 
                @else месяцам 
                @endif
                <span class="badge bg-primary ms-2">Сегодня/Текущий период выделен</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th style="position: sticky; left: 0; background: #f8f9fa; min-width: 150px;">Период</th>
                            
                            <!-- КАССА - Динамические столбцы -->
                            @if(count($paymentMethods) > 0)
                                <th colspan="{{ count($paymentMethods) }}" class="text-center bg-light">КАССА</th>
                            @endif
                            
                            <!-- ВЫРУЧКА -->
                            <th class="text-center bg-light">ВЫРУЧКА</th>
                            
                            <!-- СТОЛЫ -->
                            <th colspan="5" class="text-center bg-light">СТОЛЫ</th>
                            
                            <!-- ОСТАЛЬНЫЕ ПРОДАЖИ -->
                            <th class="text-center bg-light">ОСТАЛЬНЫЕ ПРОДАЖИ</th>
                            
                            <!-- РАСХОДЫ И ПРИБЫЛЬ -->
                            <th colspan="4" class="text-center bg-light">РАСХОДЫ И ПРИБЫЛЬ</th>
                        </tr>
                        <tr>
                            <th style="position: sticky; left: 0; background: #f8f9fa;">&nbsp;</th>
                            
                            <!-- СПОСОБЫ ОПЛАТЫ -->
                           @foreach($paymentMethods as $method)
                                <th class="text-end" style="min-width: 100px;">{{ $method->Name }}</th>
                            @endforeach
                            
                            <!-- ВЫРУЧКА -->ы
                            <th class="text-end">Всего</th>
                            
                            <!-- СТОЛЫ -->
                            <th class="text-end">Гости</th>
                            <th class="text-end">Кальяны</th>
                            <th class="text-end">С кальянов</th>
                            <th class="text-end">С продаж</th>
                            <th class="text-end">Средний чек</th>
                            
                            <!-- ОСТАЛЬНЫЕ ПРОДАЖИ -->
                            <th class="text-end">Выручка</th>
                            
                            <!-- РАСХОДЫ И ПРИБЫЛЬ -->
                            <th class="text-end">Расходы</th>
                            <th class="text-end">Зарплата</th>
                            <th class="text-end">Штрафы</th>
                            <th class="text-end">Прибыль</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableData as $row)
                        <tr class="{{ isset($row['is_today']) && $row['is_today'] ? 'table-primary' : (isset($row['is_current']) && $row['is_current'] ? 'table-primary' : '') }}">
                            <td style="position: sticky; left: 0; background: {{ isset($row['is_today']) && $row['is_today'] ? '#cfe2ff' : (isset($row['is_current']) && $row['is_current'] ? '#cfe2ff' : 'white') }};">
                                <div class="d-flex align-items-center">
                                    <strong>{{ $row['period'] }}</strong>
                                    @if(isset($row['is_today']) && $row['is_today'])
                                        <span class="badge bg-primary ms-2">Сегодня</span>
                                    @elseif(isset($row['is_current']) && $row['is_current'])
                                        <span class="badge bg-primary ms-2">Текущий</span>
                                    @endif
                                </div>
                                @if(isset($row['date']))
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('l') }}</small>
                                @endif
                            </td>
                            
                            <!-- СПОСОБЫ ОПЛАТЫ -->
                           @foreach($paymentMethods as $method)
                                @php
                                    $methodId = $method->IDPaymentMethod;
                                    $paymentAmount = $row["payment_{$methodId}"] ?? 0;
                                    $colors = ['primary', 'success', 'info', 'warning', 'danger', 'dark'];
                                    $colorClass = $colors[$loop->index % count($colors)];
                                @endphp
                                <td class="text-end">
                                    <span class="text-{{ $colorClass }}">
                                        {{ number_format($paymentAmount, 0) }} ₽
                                    </span>
                                </td>
                            @endforeach
                            
                            <!-- ВЫРУЧКА -->
                            <td class="text-end fw-bold">{{ number_format($row['revenue'], 0) }} ₽</td>
                            
                            <!-- СТОЛЫ -->
                            <td class="text-end">{{ number_format($row['guests_count'], 0) }}</td>
                            <td class="text-end">{{ number_format($row['hookah_count'], 0) }}</td>
                            <td class="text-end">{{ number_format($row['hookah_income'], 0) }} ₽</td>
                            <td class="text-end">{{ number_format($row['table_product_income'], 0) }} ₽</td>
                            <td class="text-end">{{ number_format($row['average_check'], 0) }} ₽</td>
                            
                            <!-- ОСТАЛЬНЫЕ ПРОДАЖИ -->
                            <td class="text-end">{{ number_format($row['non_table_income'], 0) }} ₽</td>
                            
                            <!-- РАСХОДЫ И ПРИБЫЛЬ -->
                            <td class="text-end text-danger">{{ number_format($row['expenses'], 0) }} ₽</td>
                            <td class="text-end text-danger">{{ number_format($row['salary'], 0) }} ₽</td>
                            <td class="text-end text-danger">{{ number_format($row['fines'], 0) }} ₽</td>
                            <td class="text-end fw-bold {{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($row['profit'], 0) }} ₽
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 1 + count($paymentMethods) + 10 }}" class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x me-2"></i>Нет данных за выбранный период
                            </td>
                        </tr>
                        @endforelse
                        
                        <!-- Итого -->
                        @if(count($tableData) > 0)
                        <tr class="table-active">
                            <td style="position: sticky; left: 0; background: #f8f9fa;"><strong>ИТОГО</strong></td>
                            
                            <!-- СПОСОБЫ ОПЛАТЫ -->
                            @foreach($paymentMethods as $method)
                                <td class="text-end fw-bold">
                                    @php
                                        $paymentTotal = 0;
                                        foreach ($tableData as $row) {
                                            $paymentTotal += $row["payment_{$method->id}"] ?? 0;
                                        }
                                        $colors = ['primary', 'success', 'info', 'warning', 'danger', 'dark'];
                                        $colorClass = $colors[$loop->index % count($colors)];
                                    @endphp
                                    <span class="text-{{ $colorClass }}">
                                        {{ number_format($paymentTotal, 0) }} ₽
                                    </span>
                                </td>
                            @endforeach
                            
                            <!-- ВЫРУЧКА -->
                            <td class="text-end fw-bold">{{ number_format(collect($tableData)->sum('revenue'), 0) }} ₽</td>
                            
                            <!-- СТОЛЫ -->
                            <td class="text-end fw-bold">{{ number_format(collect($tableData)->sum('guests_count'), 0) }}</td>
                            <td class="text-end fw-bold">{{ number_format(collect($tableData)->sum('hookah_count'), 0) }}</td>
                            <td class="text-end fw-bold">{{ number_format(collect($tableData)->sum('hookah_income'), 0) }} ₽</td>
                            <td class="text-end fw-bold">{{ number_format(collect($tableData)->sum('table_product_income'), 0) }} ₽</td>
                            <td class="text-end fw-bold">
                                @php
                                    $totalTableSales = collect($tableData)->sum('table_sales');
                                    $totalTableIncome = collect($tableData)->sum('hookah_income') + collect($tableData)->sum('table_product_income');
                                    $avgCheck = $totalTableSales > 0 ? $totalTableIncome / $totalTableSales : 0;
                                @endphp
                                {{ number_format($avgCheck, 0) }} ₽
                            </td>
                            
                            <!-- ОСТАЛЬНЫЕ ПРОДАЖИ -->
                            <td class="text-end fw-bold">{{ number_format(collect($tableData)->sum('non_table_income'), 0) }} ₽</td>
                            
                            <!-- РАСХОДЫ И ПРИБЫЛЬ -->
                            <td class="text-end fw-bold text-danger">{{ number_format(collect($tableData)->sum('expenses'), 0) }} ₽</td>
                            <td class="text-end fw-bold text-danger">{{ number_format(collect($tableData)->sum('salary'), 0) }} ₽</td>
                            <td class="text-end fw-bold text-danger">{{ number_format(collect($tableData)->sum('fines'), 0) }} ₽</td>
                            <td class="text-end fw-bold {{ collect($tableData)->sum('profit') >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format(collect($tableData)->sum('profit'), 0) }} ₽
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted">
            <small>
                <i class="bi bi-info-circle me-1"></i>
                Показано: {{ count($tableData) }} периодов | 
                Способов оплаты: {{ count($paymentMethods) }} |
                <span class="text-primary">■ Текущий период выделен синим</span> |
                Прибыль = Выручка - Себестоимость - Расходы - Зарплата - Штрафы
            </small>
        </div>
    </div>

    <!-- Кнопки экспорта -->
    <div class="mt-3 text-end">
        <a href="{{ route('accounting.export', array_merge(request()->all(), ['format' => 'csv'])) }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-excel me-1"></i> Экспорт в CSV
        </a>
        <a href="{{ route('accounting.export', array_merge(request()->all(), ['format' => 'pdf'])) }}" 
           class="btn btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> Экспорт в PDF
        </a>
    </div>
</div>

<style>
.table th, .table td {
    white-space: nowrap;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    font-size: 0.9rem;
}
.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    vertical-align: middle;
}
.table tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.table-primary {
    background-color: rgba(13, 110, 253, 0.1) !important;
}
.table-active {
    background-color: rgba(0, 0, 0, 0.05) !important;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Сохраняем значения фильтров в localStorage
    const typeSelect = document.querySelector('select[name="type"]');
    const daysCountSelect = document.querySelector('select[name="days_count"]');
    const weeksCountSelect = document.querySelector('select[name="weeks_count"]');
    const monthsCountSelect = document.querySelector('select[name="months_count"]');
    
    // Восстанавливаем значения при загрузке
    if (daysCountSelect) {
        const savedDays = localStorage.getItem('accounting_days_count');
        if (savedDays) daysCountSelect.value = savedDays;
    }
    if (weeksCountSelect) {
        const savedWeeks = localStorage.getItem('accounting_weeks_count');
        if (savedWeeks) weeksCountSelect.value = savedWeeks;
    }
    if (monthsCountSelect) {
        const savedMonths = localStorage.getItem('accounting_months_count');
        if (savedMonths) monthsCountSelect.value = savedMonths;
    }
    
    // Сохраняем значения при изменении
    if (daysCountSelect) {
        daysCountSelect.addEventListener('change', function() {
            localStorage.setItem('accounting_days_count', this.value);
        });
    }
    if (weeksCountSelect) {
        weeksCountSelect.addEventListener('change', function() {
            localStorage.setItem('accounting_weeks_count', this.value);
        });
    }
    if (monthsCountSelect) {
        monthsCountSelect.addEventListener('change', function() {
            localStorage.setItem('accounting_months_count', this.value);
        });
    }
});
</script>
@endsection