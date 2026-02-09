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
    expenses: '{{ url("/statistics/expenses-stats") }}'
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
    
    const response = await fetch(fullUrl);
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
        console.error('API error:', data.error);
    }
    
    return data;
}

// ============= ГРАФИК 1: ВЫРУЧКА И ПРИБЫЛЬ =============
async function renderRevenueProfitChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'revenue-profit-chart-' + Date.now();
    const scrollbarId = 'revenue-scrollbar-' + Date.now();
    
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
                            <span class="ms-3 small text-muted" id="revenue-period-text">Вся история (по дням)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- График с overflow скрытым -->
                        <div class="chart-wrapper" style="position: relative; overflow: hidden; height: 400px; width: 100%;">
                            <div class="chart-inner" style="position: absolute; left: 0; top: 0; height: 100%;">
                                <canvas id="${chartId}" style="display: block;"></canvas>
                            </div>
                            <!-- Затемнение для скрытых частей -->
                            <div class="fade-left" style="position: absolute; top: 0; left: 0; width: 20px; height: 100%; background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                            <div class="fade-right" style="position: absolute; top: 0; right: 0; width: 20px; height: 100%; background: linear-gradient(to left, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                        </div>
                        
                        <!-- Кастомный горизонтальный скроллбар -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small id="revenue-visible-start" class="text-muted"></small>
                                <small id="revenue-visible-end" class="text-muted"></small>
                            </div>
                            <div class="custom-scrollbar" style="position: relative; height: 8px; background: #e9ecef; border-radius: 4px; cursor: pointer;">
                                <div id="${scrollbarId}" class="scrollbar-thumb" style="position: absolute; left: 66.67%; width: 33.33%; height: 100%; background: #0d6efd; border-radius: 4px; cursor: grab;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Перетащите для просмотра</small>
                                <small class="text-muted" id="revenue-zoom-percentage">Показано: 33%</small>
                            </div>
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
    
    // Загружаем данные по умолчанию (по дням)
    const data = await fetchData(API_URLS.revenueProfit, { period: 'day' });
    
    if (data.success) {
        // Рендерим ВЕСЬ график
        const chartInfo = renderRevenueChart(chartId, data);
        
        // Инициализируем скролл - начинаем с КОНЦА графика
        initChartScroll(chartId, scrollbarId, data, chartInfo, 'revenue');
        
        // Обновляем текст и статистику
        document.getElementById('revenue-period-text').textContent = 'Вся история (по дням)';
        updateRevenueStats(data);
    }
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.revenue-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.revenue-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.revenueProfit, { period: period });
            
            if (newData.success) {
                // Перерисовываем весь график
                const chartInfo = renderRevenueChart(chartId, data);
                if (chartInfo) {
                    initChartScroll(chartId, scrollbarId, data, chartInfo, 'revenue');
                }
                
                // Обновляем текст периода и статистику
                const periodText = document.getElementById('revenue-period-text');
                let periodLabel = '';
                if (period === 'day') {
                    periodLabel = 'по дням';
                } else if (period === 'week') {
                    periodLabel = 'по неделям';
                } else {
                    periodLabel = 'по месяцам';
                }
                periodText.textContent = `Вся история (${periodLabel})`;
                updateRevenueStats(newData);
            }
        });
    });
}

function renderRevenueChart(chartId, data) {
    const canvas = document.getElementById(chartId);
    const chartWrapper = canvas?.closest('.chart-wrapper');
    
    if (!canvas || !chartWrapper) {
        console.error('Не найден canvas или chart-wrapper для:', chartId);
        return null;
    }
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    // Устанавливаем реальные размеры canvas
    canvas.width = chartWrapper.offsetWidth * 3; // 300% ширины
    canvas.height = chartWrapper.offsetHeight;
    
    // Создаем график
    charts[chartId] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Выручка',
                    data: data.revenue_data,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Прибыль',
                    data: data.profit_data,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true
                },
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
            responsive: false,
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
                        minRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 20
                    },
                    grid: {
                        display: true
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
                        text: 'Сумма (руб.)'
                    }
                }
            },
            elements: {
                point: {
                    radius: function(context) {
                        return context.raw === 0 ? 0 : 3;
                    },
                    hoverRadius: function(context) {
                        return context.raw === 0 ? 0 : 6;
                    }
                }
            }
        }
    });
    
    // Возвращаем данные для скролла
    return {
        totalWidth: canvas.width,
        visibleWidth: chartWrapper.offsetWidth,
        totalPoints: data.labels.length
    };
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

// ============= ГРАФИК 2: СРЕДНИЙ ЧЕК =============
async function renderAverageCheckChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'average-check-chart-' + Date.now();
    const scrollbarId = 'average-scrollbar-' + Date.now();
    
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
                            <span class="ms-3 small text-muted" id="average-period-text">Вся история (по дням)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- График с overflow скрытым -->
                        <div class="chart-wrapper" style="position: relative; overflow: hidden; height: 400px; width: 100%;">
                            <div class="chart-inner" style="position: absolute; left: 0; top: 0; height: 100%;">
                                <canvas id="${chartId}" style="display: block;"></canvas>
                            </div>
                            <!-- Затемнение для скрытых частей -->
                            <div class="fade-left" style="position: absolute; top: 0; left: 0; width: 20px; height: 100%; background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                            <div class="fade-right" style="position: absolute; top: 0; right: 0; width: 20px; height: 100%; background: linear-gradient(to left, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                        </div>
                        
                        <!-- Кастомный горизонтальный скроллбар -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small id="average-visible-start" class="text-muted"></small>
                                <small id="average-visible-end" class="text-muted"></small>
                            </div>
                            <div class="custom-scrollbar" style="position: relative; height: 8px; background: #e9ecef; border-radius: 4px; cursor: pointer;">
                                <div id="${scrollbarId}" class="scrollbar-thumb" style="position: absolute; left: 66.67%; width: 33.33%; height: 100%; background: #0d6efd; border-radius: 4px; cursor: grab;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Перетащите для просмотра</small>
                                <small class="text-muted" id="average-zoom-percentage">Показано: 33%</small>
                            </div>
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
    
    if (data.success) {
        // Рендерим ВЕСЬ график
        const chartInfo = renderAverageChart(chartId, data);
        if (chartInfo) {
            initChartScroll(chartId, scrollbarId, data, chartInfo, 'average');
        }
        
        // Обновляем текст и статистику
        document.getElementById('average-period-text').textContent = 'Вся история (по дням)';
        updateAverageCheckStats(data);
    }
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.average-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.average-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.averageCheck, { period: period });
            
            if (newData.success) {
                // Перерисовываем весь график
                const chartInfo = renderExpensesOnlyChart(chartId, data);
                if (chartInfo) {
                    initChartScroll(chartId, scrollbarId, data, chartInfo, 'expenses');
                }
                
                // Обновляем текст периода и статистику
                const periodText = document.getElementById('average-period-text');
                let periodLabel = '';
                if (period === 'day') {
                    periodLabel = 'по дням';
                } else if (period === 'week') {
                    periodLabel = 'по неделям';
                } else {
                    periodLabel = 'по месяцам';
                }
                periodText.textContent = `Вся история (${periodLabel})`;
                updateAverageCheckStats(newData);
            }
        });
    });
}

function renderAverageChart(chartId, data) {
    const canvas = document.getElementById(chartId);
    const chartInner = canvas.closest('.chart-inner');
    const chartWrapper = canvas.closest('.chart-wrapper');
    
    if (!canvas || !chartInner || !chartWrapper) return;
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    // Устанавливаем реальные размеры canvas
    canvas.width = chartWrapper.offsetWidth * 3; // 300% ширины
    canvas.height = chartWrapper.offsetHeight;
    
    // Создаем график
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
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Количество продаж',
                    data: data.sales_count_data,
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: false,
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
                        minRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 20
                    },
                    grid: {
                        display: true
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
            },
            elements: {
                point: {
                    radius: function(context) {
                        return context.raw === 0 ? 0 : 3;
                    },
                    hoverRadius: function(context) {
                        return context.raw === 0 ? 0 : 6;
                    }
                }
            }
        }
    });
    
    // Возвращаем данные для скролла
    return {
        totalWidth: canvas.width,
        visibleWidth: chartWrapper.offsetWidth,
        totalPoints: data.labels.length
    };
}

function updateAverageCheckStats(data) {
    if (!data.success || !data) return;
    
    const formatCurrency = (amount) => {
        if (amount === undefined || amount === null) {
            return '0 ₽';
        }
        return amount.toLocaleString('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        });
    };
    
    const avgCheckValue = data.total_average_check !== undefined ? data.total_average_check : 0;
    const salesCount = data.total_sales !== undefined ? data.total_sales : 0;
    
    document.getElementById('avg-check-total').textContent = formatCurrency(avgCheckValue);
    document.getElementById('total-sales-count').textContent = salesCount.toLocaleString('ru-RU');
}

// ============= ГРАФИК 3: РАСХОДЫ =============
async function renderExpensesChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'expenses-chart-' + Date.now();
    const scrollbarId = 'expenses-scrollbar-' + Date.now();
    
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
                            <span class="ms-3 small text-muted" id="expenses-period-text">Вся история (по дням)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- График с overflow скрытым -->
                        <div class="chart-wrapper" style="position: relative; overflow: hidden; height: 400px; width: 100%;">
                            <div class="chart-inner" style="position: absolute; left: 0; top: 0; height: 100%;">
                                <canvas id="${chartId}" style="display: block;"></canvas>
                            </div>
                            <!-- Затемнение для скрытых частей -->
                            <div class="fade-left" style="position: absolute; top: 0; left: 0; width: 20px; height: 100%; background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                            <div class="fade-right" style="position: absolute; top: 0; right: 0; width: 20px; height: 100%; background: linear-gradient(to left, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                        </div>
                        
                        <!-- Кастомный горизонтальный скроллбар -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small id="expenses-visible-start" class="text-muted"></small>
                                <small id="expenses-visible-end" class="text-muted"></small>
                            </div>
                            <div class="custom-scrollbar" style="position: relative; height: 8px; background: #e9ecef; border-radius: 4px; cursor: pointer;">
                                <div id="${scrollbarId}" class="scrollbar-thumb" style="position: absolute; left: 66.67%; width: 33.33%; height: 100%; background: #0d6efd; border-radius: 4px; cursor: grab;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Перетащите для просмотра</small>
                                <small class="text-muted" id="expenses-zoom-percentage">Показано: 33%</small>
                            </div>
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
    
    if (data.success) {
        // Рендерим ВЕСЬ график
        const chartInfo = renderExpensesOnlyChart(chartId, data);
        
        // Инициализируем скролл - начинаем с КОНЦА графика
        initChartScroll(chartId, scrollbarId, data, chartInfo, 'expenses');
        
        // Обновляем текст и статистику
        document.getElementById('expenses-period-text').textContent = 'Вся история (по дням)';
        updateExpensesStats(data);
    }
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.expenses-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.expenses-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.expenses, { period: period });
            
            if (newData.success) {
                // Перерисовываем весь график
                const chartInfo = renderExpensesOnlyChart(chartId, newData);
                
                // Инициализируем скролл снова
                initChartScroll(chartId, scrollbarId, newData, chartInfo, 'expenses');
                
                // Обновляем текст периода и статистику
                const periodText = document.getElementById('expenses-period-text');
                let periodLabel = '';
                if (period === 'day') {
                    periodLabel = 'по дням';
                } else if (period === 'week') {
                    periodLabel = 'по неделям';
                } else {
                    periodLabel = 'по месяцам';
                }
                periodText.textContent = `Вся история (${periodLabel})`;
                updateExpensesStats(newData);
            }
        });
    });
}

function renderExpensesOnlyChart(chartId, data) {
    const canvas = document.getElementById(chartId);
    const chartInner = canvas.closest('.chart-inner');
    const chartWrapper = canvas.closest('.chart-wrapper');
    
    if (!canvas || !chartInner || !chartWrapper) return;
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    // Устанавливаем реальные размеры canvas
    canvas.width = chartWrapper.offsetWidth * 3; // 300% ширины
    canvas.height = chartWrapper.offsetHeight;
    
    // Создаем график
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
            responsive: false,
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
                        minRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 20
                    },
                    grid: {
                        display: true
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
            },
            elements: {
                point: {
                    radius: function(context) {
                        return context.raw === 0 ? 0 : 3;
                    },
                    hoverRadius: function(context) {
                        return context.raw === 0 ? 0 : 6;
                    }
                }
            }
        }
    });
    
    // Возвращаем данные для скролла
    return {
        totalWidth: canvas.width,
        visibleWidth: chartWrapper.offsetWidth,
        totalPoints: data.labels.length
    };
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

// ============= ОБЩАЯ ЛОГИКА СКРОЛЛА (ИСПРАВЛЕННАЯ) =============
function initChartScroll(chartId, scrollbarId, data, chartInfo, prefix) {
    const scrollbar = document.getElementById(scrollbarId);
    const container = scrollbar?.closest('.custom-scrollbar');
    const chartWrapper = scrollbar?.closest('.card-body')?.querySelector('.chart-wrapper');
    
    if (!scrollbar || !container || !chartWrapper) {
        console.error('Не найден один из элементов для скролла:', { scrollbarId, chartId });
        return;
    }
    
    // Параметры
    const visiblePercentage = 0.3333; // Показываем 33% за раз
    const scrollbarWidth = visiblePercentage * 100;
    
    // Начинаем с конца графика
    const startLeft = 100 - scrollbarWidth; // 66.67%
    
    // Устанавливаем начальную позицию
    scrollbar.style.left = startLeft + '%';
    scrollbar.style.width = scrollbarWidth + '%';
    
    const chartInner = chartWrapper.querySelector('.chart-inner');
    if (!chartInner) {
        console.error('Не найден chart-inner для:', chartId);
        return;
    }
    
    // Рассчитываем начальное смещение для показа конца графика
    const totalWidth = chartInfo?.totalWidth || chartWrapper.offsetWidth * 3;
    const visibleWidth = chartInfo?.visibleWidth || chartWrapper.offsetWidth;
    const maxShift = Math.max(0, totalWidth - visibleWidth); // Максимальное смещение
    const initialShift = maxShift; // Начинаем с конца (максимальное смещение)
    
    chartInner.style.transform = `translateX(-${initialShift}px)`;
    
    // Состояние скролла
    let isDragging = false;
    let dragStartX = 0;
    let dragStartLeft = startLeft;
    let currentShift = initialShift;
    
    // Обработчики для ползунка
    scrollbar.addEventListener('mousedown', startDrag);
    container.addEventListener('mousedown', jumpToPosition);
    
    // Обработчик для touch-устройств
    scrollbar.addEventListener('touchstart', function(e) {
        e.preventDefault();
        startDrag(e.touches[0]);
    });
    
    function startDrag(e) {
        e.preventDefault();
        e.stopPropagation();
        
        isDragging = true;
        dragStartX = e.clientX;
        dragStartLeft = parseFloat(scrollbar.style.left) || startLeft;
        
        scrollbar.style.cursor = 'grabbing';
        scrollbar.style.userSelect = 'none';
        
        document.addEventListener('mousemove', handleDrag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchmove', handleTouchDrag, { passive: false });
        document.addEventListener('touchend', stopDrag);
    }
    
    function handleTouchDrag(e) {
        if (!isDragging || !e.touches[0]) return;
        e.preventDefault();
        handleDrag(e.touches[0]);
    }
    
    function jumpToPosition(e) {
        if (e.target === scrollbar || isDragging) return;
        
        const rect = container.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickPercent = (clickX / rect.width) * 100;
        
        // Центрируем ползунок на точке клика
        const newLeft = Math.max(0, Math.min(clickPercent - (scrollbarWidth / 2), 100 - scrollbarWidth));
        
        updateScrollAndChart(newLeft);
        updateVisibleRange(newLeft, data, prefix);
    }
    
    function handleDrag(e) {
        if (!isDragging) return;
        
        const rect = container.getBoundingClientRect();
        const deltaX = e.clientX - dragStartX;
        const deltaPercent = (deltaX / rect.width) * 100;
        
        const newLeft = Math.max(0, Math.min(dragStartLeft + deltaPercent, 100 - scrollbarWidth));
        
        updateScrollAndChart(newLeft);
        updateVisibleRange(newLeft, data, prefix);
    }
    
    function stopDrag() {
        isDragging = false;
        
        scrollbar.style.cursor = 'grab';
        scrollbar.style.userSelect = 'auto';
        
        document.removeEventListener('mousemove', handleDrag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchmove', handleTouchDrag);
        document.removeEventListener('touchend', stopDrag);
    }
    
    function updateScrollAndChart(leftPercent) {
        // Обновляем положение ползунка
        scrollbar.style.left = leftPercent + '%';
        
        // Рассчитываем смещение графика
        const shift = (leftPercent / (100 - scrollbarWidth)) * maxShift;
        currentShift = shift;
        
        if (chartInner) {
            chartInner.style.transform = `translateX(-${shift}px)`;
        }
        
        // Обновляем процент видимой области
        const percentageElement = document.getElementById(`${prefix}-zoom-percentage`);
        if (percentageElement) {
            percentageElement.textContent = `Показано: ${Math.round(scrollbarWidth)}%`;
        }
    }
    
    function updateVisibleRange(leftPercent, data, prefix) {
        if (!data || !data.labels || data.labels.length === 0) return;
        
        const totalPoints = data.labels.length;
        
        // Рассчитываем индексы видимых точек
        const startRatio = leftPercent / 100;
        const endRatio = startRatio + visiblePercentage;
        
        const startIndex = Math.floor(startRatio * totalPoints);
        const endIndex = Math.min(Math.ceil(endRatio * totalPoints), totalPoints - 1);
        
        const startLabel = data.labels[startIndex] || data.labels[0];
        const endLabel = data.labels[endIndex] || data.labels[data.labels.length - 1];
        
        const startElement = document.getElementById(`${prefix}-visible-start`);
        const endElement = document.getElementById(`${prefix}-visible-end`);
        
        if (startElement) startElement.textContent = startLabel;
        if (endElement) endElement.textContent = endLabel;
    }
    
    // Инициализируем начальное положение
    updateVisibleRange(startLeft, data, prefix);
    
    // Обработчик ресайза окна
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Пересчитываем позицию при изменении размера окна
            const currentLeft = parseFloat(scrollbar.style.left) || startLeft;
            updateScrollAndChart(currentLeft);
        }, 250);
    });
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

/* Стили для скроллируемых графиков */
.chart-wrapper {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: white;
    position: relative;
    overflow: hidden !important;
}

.chart-inner {
    position: absolute;
    top: 0;
    left: 0;
    transition: transform 0.3s ease;
}

.custom-scrollbar {
    transition: background-color 0.2s;
}

.custom-scrollbar:hover {
    background: #dee2e6;
}

.scrollbar-thumb {
    transition: background-color 0.2s;
    cursor: grab;
}

.scrollbar-thumb:hover {
    background: #0b5ed7;
}

.scrollbar-thumb:active {
    cursor: grabbing;
}

/* Важно: canvas должен быть inline-block */
canvas {
    display: block;
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