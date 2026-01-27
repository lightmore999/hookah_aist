@extends('layouts.app')

@section('title', 'Статистика расходов')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cash-stack me-2"></i>Статистика расходов
            </h1>
            <p class="text-muted mb-0 small">Анализ расходов по категориям</p>
        </div>
        <div>
            <a href="{{ route('statistics.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> К общей статистике
            </a>
        </div>
    </div>
    
    <!-- Фильтры -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="expensesFilterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Период</label>
                        <select class="form-select form-select-sm" id="periodSelect" name="period">
                            <option value="month">За последний месяц</option>
                            <option value="week">За последнюю неделю</option>
                            <option value="custom">Выбрать даты</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Дата от</label>
                        <input type="date" class="form-control form-control-sm" id="startDate" name="start_date" 
                               value="{{ now()->subMonth()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Дата до</label>
                        <input type="date" class="form-control form-control-sm" id="endDate" name="end_date" 
                               value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-filter me-1"></i>Применить
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Индикатор загрузки -->
    <div id="loadingIndicator" class="d-none text-center py-5">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Загрузка...</span>
        </div>
        <p class="text-muted mt-3">Загрузка статистики...</p>
    </div>
    
    <!-- Контейнер для данных -->
    <div id="dataContainer"></div>
</div>

<!-- Подключаем Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const API_URL = '{{ route("statistics.expenses.data") }}';
let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    loadExpensesData();
    setupFilters();
});

function showLoading() {
    document.getElementById('loadingIndicator').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loadingIndicator').classList.add('d-none');
}

function setupFilters() {
    const periodSelect = document.getElementById('periodSelect');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    periodSelect.addEventListener('change', function() {
        if (this.value === 'month') {
            const now = new Date();
            const monthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
            startDate.value = monthAgo.toISOString().split('T')[0];
            endDate.value = now.toISOString().split('T')[0];
        } else if (this.value === 'week') {
            const now = new Date();
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            startDate.value = weekAgo.toISOString().split('T')[0];
            endDate.value = now.toISOString().split('T')[0];
        }
    });
    
    document.getElementById('expensesFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadExpensesData();
    });
}

async function loadExpensesData() {
    showLoading();
    
    const container = document.getElementById('dataContainer');
    container.innerHTML = '';
    
    // Уничтожаем старые графики
    Object.values(charts).forEach(chart => {
        if (chart && chart.destroy) chart.destroy();
    });
    charts = {};
    
    const formData = new FormData(document.getElementById('expensesFilterForm'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData) {
        params.append(key, value);
    }
    
    try {
        const response = await fetch(`${API_URL}?${params.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            renderAllCharts(data);
        } else {
            container.innerHTML = `
                <div class="alert alert-danger">
                    Ошибка загрузки данных: ${data.error || 'Неизвестная ошибка'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Ошибка:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                Ошибка загрузки: ${error.message}
            </div>
        `;
    } finally {
        hideLoading();
    }
}

function renderAllCharts(data) {
    const container = document.getElementById('dataContainer');
    
    // Таблица с детальной статистикой по категориям (сначала)
    renderExpensesTable(data.expenses_stats);
    
    // Столбчатая диаграмма распределения расходов (потом)
    renderBarChart(data.pie_chart_data, data.summary.total_expenses);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function renderBarChart(pieChartData, totalExpenses) {
    if (pieChartData.length === 0) {
        const html = `
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="mb-0">Распределение расходов по категориям</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center py-5">
                                <p class="text-muted">Нет данных по расходам за выбранный период</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
        return;
    }
    
    const chartId = 'bar-chart-' + Date.now();
    
    let html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Распределение расходов по категориям</h5>
                        <div class="badge bg-danger">
                            <i class="bi bi-cash-stack me-1"></i>
                            Общие расходы: ${formatCurrency(totalExpenses)}
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
    
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    const labels = pieChartData.map(item => item.name);
    const dataValues = pieChartData.map(item => item.value);
    const backgroundColors = pieChartData.map(item => item.color.hex);
    
    // Добавляем тени для каждого цвета
    const borderColors = backgroundColors.map(color => 
        color.replace(')', ', 0.8)').replace('rgb', 'rgba')
    );
    
    charts[chartId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Сумма расходов',
                data: dataValues,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const item = pieChartData[context.dataIndex];
                            const value = context.parsed.y;
                            const percentage = item.percentage || 0;
                            return [
                                `Сумма: ${formatCurrency(value)}`,
                                `Доля: ${percentage}%`,
                                `Количество расходов: ${item.count} раз(а)`
                            ];
                        },
                        title: function(tooltipItems) {
                            return tooltipItems[0].label;
                        }
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: {
                            size: 11
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Сумма расходов (руб.)'
                    },
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            }
        }
    });
}

function renderExpensesTable(expensesStats) {
    // Фильтруем категории с расходами
    const expensesWithCost = expensesStats.filter(item => item.total_cost > 0);
    
    const rows = expensesStats.map(item => {
        const costClass = item.total_cost > 0 ? 'fw-bold' : 'text-muted';
        const rowClass = item.total_cost === 0 ? 'bg-light' : '';
        
        return `
            <tr class="${rowClass}">
                <td class="ps-3">${item.category_name}</td>
                <td class="text-center">
                    <span class="badge bg-primary">${item.count}</span>
                </td>
                <td class="text-end ${costClass}">${formatCurrency(item.total_cost)}</td>
                <td class="text-end">
                    ${item.percentage > 0 ? 
                        `<span class="badge bg-success">${item.percentage}%</span>` : 
                        `<span class="badge bg-light text-dark">0%</span>`}
                </td>
            </tr>
        `;
    }).join('');
    
    const totalCount = expensesStats.reduce((sum, item) => sum + item.count, 0);
    const totalCost = expensesStats.reduce((sum, item) => sum + item.total_cost, 0);
    
    let html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Расходы по категориям</h5>
                        <div>
                            <span class="badge bg-primary me-2">${expensesStats.length} категорий</span>
                            <span class="badge bg-success">${expensesWithCost.length} с расходами</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Категория расхода</th>
                                        <th class="text-center">Количество расходов</th>
                                        <th class="text-end">Сумма расходов</th>
                                        <th class="text-end">Процент от всех расходов</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows}
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td class="ps-3 fw-bold" colspan="1">Итого:</td>
                                        <td class="text-center fw-bold">${totalCount}</td>
                                        <td class="text-end fw-bold">${formatCurrency(totalCost)}</td>
                                        <td class="text-end fw-bold">100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dataContainer').innerHTML = html;
}
</script>

<style>
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,.05);
}
.table th {
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
}
.table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}
.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}
.table tfoot td {
    font-weight: 600;
    background-color: #f8f9fa;
}
.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endsection