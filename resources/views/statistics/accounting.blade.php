@extends('layouts.app')

@section('title', 'Финансовая статистика')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-cash-coin me-2"></i>Финансовая статистика
            </h1>
            <p class="text-muted mb-0 small">Анализ выручки, прибыли, расходов и среднего чека</p>
        </div>
        <div>
            <a href="{{ route('statistics.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> К статистике посещений
            </a>
        </div>
    </div>
    
    <!-- Индикатор загрузки -->
    <div id="loadingIndicator" class="d-none text-center py-5">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Загрузка...</span>
        </div>
        <p class="text-muted mt-3">Загрузка финансовой статистики...</p>
    </div>
    
    <!-- Контейнер для данных -->
    <div id="dataContainer"></div>
</div>

<!-- Подключаем Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Конфигурация URL для финансовой статистики
const API_URLS = {
    revenueProfit: '{{ url("/statistics/revenue-profit") }}',
    averageCheck: '{{ url("/statistics/average-check") }}',
    expenses: '{{ url("/statistics/expenses") }}'
};

let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    loadFinancialData();
});

function showLoading() {
    const loading = document.getElementById('loadingIndicator');
    if (loading) loading.classList.remove('d-none');
}

function hideLoading() {
    const loading = document.getElementById('loadingIndicator');
    if (loading) loading.classList.add('d-none');
}

async function loadFinancialData() {
    showLoading();
    const container = document.getElementById('dataContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    // Уничтожаем старые графики
    Object.values(charts).forEach(chart => {
        if (chart && chart.destroy) chart.destroy();
    });
    charts = {};
    
    try {
        // Загружаем и рендерим все графики
        await renderRevenueProfitChart();
        await renderAverageCheckChart();
        await renderExpensesChart();
        
    } catch (error) {
        console.error('Ошибка загрузки:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                Ошибка загрузки финансовых данных: ${error.message}
            </div>
        `;
    } finally {
        hideLoading();
    }
}

async function fetchData(url, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = `${url}?${queryString}`;
    console.log('Fetching:', fullUrl);
    
    const response = await fetch(fullUrl);
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('Response data:', data); // Добавьте эту строку
    
    if (!data.success) {
        console.error('API error:', data.error);
    }
    
    return data;
}

async function renderRevenueProfitChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'revenue-profit-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up me-2"></i>Выручка и прибыль
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary revenue-period-btn active" data-period="day">
                                    По дням
                                </button>
                                <button type="button" class="btn btn-outline-primary revenue-period-btn" data-period="week">
                                    По неделям
                                </button>
                                <button type="button" class="btn btn-outline-primary revenue-period-btn" data-period="month">
                                    По месяцам
                                </button>
                            </div>
                            <span class="ms-3 small text-muted" id="revenue-period-text">Последние 30 дней</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                        <div class="row text-center mt-4">
                            <div class="col-md-4">
                                <div class="text-primary">
                                    <div class="h4 fw-bold mb-1" id="total-revenue">0 ₽</div>
                                    <div class="text-muted small">Общая выручка</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-success">
                                    <div class="h4 fw-bold mb-1" id="total-profit">0 ₽</div>
                                    <div class="text-muted small">Общая прибыль</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-danger">
                                    <div class="h4 fw-bold mb-1" id="total-expenses">0 ₽</div>
                                    <div class="text-muted small">Общие расходы</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию (последние 30 дней)
    const data = await fetchData(API_URLS.revenueProfit, { period: 'day' });
    renderRevenueProfitChartData(chartId, data);
    updateRevenueStats(data);
    document.getElementById('revenue-period-text').textContent = 'Последние 30 дней';
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.revenue-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.revenue-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.revenueProfit, { period: period });
            renderRevenueProfitChartData(chartId, newData);
            updateRevenueStats(newData);
            
            // Обновляем текст периода
            const periodText = document.getElementById('revenue-period-text');
            if (period === 'day') {
                periodText.textContent = 'Последние 30 дней';
            } else if (period === 'week') {
                periodText.textContent = 'Последние 12 недель';
            } else {
                periodText.textContent = 'Последние 12 месяцев';
            }
        });
    });
}

function renderRevenueProfitChartData(chartId, data) {
    if (!data.success) return;
    
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    charts[chartId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Выручка',
                    data: data.revenue_data,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Прибыль',
                    data: data.profit_data,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Расходы',
                    data: data.expenses_data,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toLocaleString('ru-RU', {
                                style: 'currency',
                                currency: 'RUB',
                                minimumFractionDigits: 0
                            });
                            return label;
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    display: true,
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU', {
                                style: 'currency',
                                currency: 'RUB',
                                minimumFractionDigits: 0
                            });
                        }
                    },
                    title: {
                        display: true,
                        text: 'Сумма (руб.)'
                    }
                }
            }
        }
    });
}

function updateRevenueStats(data) {
    if (!data.success) return;
    
    const formatCurrency = (amount) => {
        return amount.toLocaleString('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        });
    };
    
    document.getElementById('total-revenue').textContent = formatCurrency(data.total_revenue);
    document.getElementById('total-profit').textContent = formatCurrency(data.total_profit);
    document.getElementById('total-expenses').textContent = formatCurrency(data.total_expenses);
}

async function renderAverageCheckChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'average-check-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt me-2"></i>Средний чек и количество продаж
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary average-period-btn active" data-period="day">
                                    По дням
                                </button>
                                <button type="button" class="btn btn-outline-primary average-period-btn" data-period="week">
                                    По неделям
                                </button>
                                <button type="button" class="btn btn-outline-primary average-period-btn" data-period="month">
                                    По месяцам
                                </button>
                            </div>
                            <span class="ms-3 small text-muted" id="average-period-text">Последние 30 дней</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                        <div class="row text-center mt-4">
                            <div class="col-md-6">
                                <div class="text-info">
                                    <div class="h4 fw-bold mb-1" id="avg-check-total">0 ₽</div>
                                    <div class="text-muted small">Средний чек за период</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-warning">
                                    <div class="h4 fw-bold mb-1" id="total-sales-count">0</div>
                                    <div class="text-muted small">Всего продаж за период</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию
    const data = await fetchData(API_URLS.averageCheck, { period: 'day' });
    renderAverageCheckChartData(chartId, data);
    updateAverageCheckStats(data);
    document.getElementById('average-period-text').textContent = 'Последние 30 дней';
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.average-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.average-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.averageCheck, { period: period });
            renderAverageCheckChartData(chartId, newData);
            updateAverageCheckStats(newData);
            
            // Обновляем текст периода
            const periodText = document.getElementById('average-period-text');
            if (period === 'day') {
                periodText.textContent = 'Последние 30 дней';
            } else if (period === 'week') {
                periodText.textContent = 'Последние 12 недель';
            } else {
                periodText.textContent = 'Последние 12 месяцев';
            }
        });
    });
}

function renderAverageCheckChartData(chartId, data) {
    if (!data.success) return;
    
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    charts[chartId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Средний чек (руб.)',
                    data: data.average_check_data,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Количество продаж',
                    data: data.sales_count_data,
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                // Средний чек
                                label += context.parsed.y.toLocaleString('ru-RU', {
                                    style: 'currency',
                                    currency: 'RUB',
                                    minimumFractionDigits: 0
                                });
                            } else {
                                // Количество продаж
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Средний чек (руб.)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU', {
                                style: 'currency',
                                currency: 'RUB',
                                minimumFractionDigits: 0
                            });
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Количество продаж'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

function updateAverageCheckStats(data) {
    if (!data.success || !data) return;
    
    const formatCurrency = (amount) => {
        // Добавьте проверку
        if (amount === undefined || amount === null) {
            console.error('Amount is undefined:', data);
            return '0 ₽';
        }
        return amount.toLocaleString('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        });
    };
    
    // Используйте правильное поле
    const avgCheckValue = data.total_average_check !== undefined ? data.total_average_check : 0;
    const salesCount = data.total_sales !== undefined ? data.total_sales : 0;
    
    document.getElementById('avg-check-total').textContent = formatCurrency(avgCheckValue);
    document.getElementById('total-sales-count').textContent = salesCount.toLocaleString('ru-RU');
}

async function renderExpensesChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'expenses-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-cash-stack me-2"></i>Динамика расходов
                        </h5>
                        <div class="d-flex align-items-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary expenses-period-btn active" data-period="day">
                                    По дням
                                </button>
                                <button type="button" class="btn btn-outline-primary expenses-period-btn" data-period="week">
                                    По неделям
                                </button>
                                <button type="button" class="btn btn-outline-primary expenses-period-btn" data-period="month">
                                    По месяцам
                                </button>
                            </div>
                            <span class="ms-3 small text-muted" id="expenses-period-text">Последние 30 дней</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                        <div class="text-center mt-4">
                            <div class="text-danger">
                                <div class="h3 fw-bold mb-1" id="total-expenses-detail">0 ₽</div>
                                <div class="text-muted small">Общая сумма расходов за период</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию
    const data = await fetchData(API_URLS.expenses, { period: 'day' });
    renderExpensesChartData(chartId, data);
    updateExpensesStats(data);
    document.getElementById('expenses-period-text').textContent = 'Последние 30 дней';
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.expenses-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.expenses-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.expenses, { period: period });
            renderExpensesChartData(chartId, newData);
            updateExpensesStats(newData);
            
            // Обновляем текст периода
            const periodText = document.getElementById('expenses-period-text');
            if (period === 'day') {
                periodText.textContent = 'Последние 30 дней';
            } else if (period === 'week') {
                periodText.textContent = 'Последние 12 недель';
            } else {
                periodText.textContent = 'Последние 12 месяцев';
            }
        });
    });
}

function renderExpensesChartData(chartId, data) {
    if (!data.success) return;
    
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    charts[chartId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Расходы',
                    data: data.expenses_data,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toLocaleString('ru-RU', {
                                style: 'currency',
                                currency: 'RUB',
                                minimumFractionDigits: 0
                            });
                            return label;
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('ru-RU', {
                                style: 'currency',
                                currency: 'RUB',
                                minimumFractionDigits: 0
                            });
                        }
                    },
                    title: {
                        display: true,
                        text: 'Сумма расходов (руб.)'
                    }
                }
            }
        }
    });
}

function updateExpensesStats(data) {
    if (!data.success) return;
    
    const formatCurrency = (amount) => {
        return amount.toLocaleString('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        });
    };
    
    document.getElementById('total-expenses-detail').textContent = formatCurrency(data.total_expenses);
}
</script>

<style>
/* Стили для графиков */
.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,.05);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}

/* Адаптивные стили */
@media (max-width: 768px) {
    .card-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .btn-group {
        margin-top: 0.5rem;
        width: 100%;
        justify-content: center;
    }
    
    .small.text-muted {
        margin-top: 0.5rem;
        text-align: center;
        width: 100%;
    }
}
</style>
@endsection