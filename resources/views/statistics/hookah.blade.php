@extends('layouts.app')

@section('title', 'Статистика по кальянам')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-fire me-2"></i>Статистика по кальянам
            </h1>
            <p class="text-muted mb-0 small">Анализ продаж и прибыльности кальянов</p>
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
            <form id="hookahFilterForm">
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
const API_URL = '{{ route("statistics.hookah.data") }}';
let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    loadHookahData();
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
    
    document.getElementById('hookahFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadHookahData();
    });
}

async function loadHookahData() {
    showLoading();
    
    const container = document.getElementById('dataContainer');
    container.innerHTML = '';
    
    // Уничтожаем старые графики
    Object.values(charts).forEach(chart => {
        if (chart && chart.destroy) chart.destroy();
    });
    charts = {};
    
    const formData = new FormData(document.getElementById('hookahFilterForm'));
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
    
    // Блок с общей статистикой - БЕЗ СРЕДНЕЙ МАРЖИ
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Общая статистика за период</h5>
                        <p class="text-muted mb-0 small">
                            ${new Date(data.date_range.start).toLocaleDateString('ru-RU')} - 
                            ${new Date(data.date_range.end).toLocaleDateString('ru-RU')}
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-primary">
                                    <div class="display-6 fw-bold mb-1">${data.summary.total_sales}</div>
                                    <div class="text-muted small">Продано кальянов</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-success">
                                    <div class="display-6 fw-bold mb-1">${formatCurrency(data.summary.total_revenue)}</div>
                                    <div class="text-muted small">Выручка</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-warning">
                                    <div class="display-6 fw-bold mb-1">${formatCurrency(data.summary.total_profit)}</div>
                                    <div class="text-muted small">Прибыль</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-info">
                                    <div class="display-6 fw-bold mb-1">${formatCurrency(data.summary.total_cost)}</div>
                                    <div class="text-muted small">Себестоимость</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Таблица с детальной статистикой
    renderTable(data.table_data);
    
    // Графики - ТОЛЬКО КРУГОВАЯ И ДИНАМИКА
    renderPieChart(data.pie_chart_data);
    renderDynamicsChart(data.dynamics_data);
    // Убрали renderWeekdayChart
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function renderTable(tableData) {
    const container = document.getElementById('dataContainer');
    
    let tableHtml = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Детальная статистика по кальянам</h5>
                        <span class="badge bg-primary">${tableData.length} кальянов</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Кальян</th>
                                        <th>Цена продажи</th>
                                        <th>Себестоимость</th>
                                        <th>Продано шт.</th>
                                        <th>Выручка</th>
                                        <th>Прибыль</th>
                                        <th>Маржа</th>
                                    </tr>
                                </thead>
                                <tbody>
    `;
    
    tableData.forEach(item => {
        const profitClass = item.total_profit > 0 ? 'text-success' : 'text-danger';
        const marginClass = item.profit_margin >= 30 ? 'text-success' : 
                          item.profit_margin >= 20 ? 'text-warning' : 'text-danger';
        
        tableHtml += `
            <tr>
                <td class="ps-3 fw-bold">${item.name}</td>
                <td>${formatCurrency(item.price)}</td>
                <td>${formatCurrency(item.cost)}</td>
                <td><span class="badge bg-primary">${item.sales_count}</span></td>
                <td>${formatCurrency(item.total_revenue)}</td>
                <td class="${profitClass} fw-bold">${formatCurrency(item.total_profit)}</td>
                <td class="${marginClass} fw-bold">${item.profit_margin.toFixed(1)}%</td>
            </tr>
        `;
    });
    
    tableHtml += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', tableHtml);
}

function renderPieChart(pieData) {
    const chartId = 'pie-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Распределение продаж</h5>
                        <p class="text-muted mb-0 small">Доля каждого кальяна в общих продажах</p>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
    `;
    
    document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
    
    const canvas = document.getElementById(chartId);
    if (!canvas || pieData.length === 0) return;
    
    const ctx = canvas.getContext('2d');
    
    // Преобразуем данные для Chart.js
    const labels = pieData.map(item => `${item.name} (${item.percentage}%)`);
    const dataValues = pieData.map(item => item.sales_count);
    const backgroundColors = pieData.map(item => {
        const [r, g, b] = item.color;
        return `rgba(${r}, ${g}, ${b}, 0.7)`;
    });
    
    charts[chartId] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: dataValues,
                backgroundColor: backgroundColors,
                borderWidth: 1,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label.split(' (')[0]}: ${value} шт. (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function renderDynamicsChart(dynamicsData) {
    const chartId = 'dynamics-chart-' + Date.now();
    const scrollbarId = 'scrollbar-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Динамика продаж по кальянам</h5>
                        <p class="text-muted mb-0 small">Количество проданных кальянов по дням (каждый кальян отдельной линией)</p>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper" style="position: relative; overflow: hidden; height: 400px; width: 100%;">
                            <div class="chart-inner" style="position: absolute; left: 0; top: 0; height: 100%;">
                                <canvas id="${chartId}"></canvas>
                            </div>
                            <div class="fade-left" style="position: absolute; top: 0; left: 0; width: 20px; height: 100%; background: linear-gradient(to right, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                            <div class="fade-right" style="position: absolute; top: 0; right: 0; width: 20px; height: 100%; background: linear-gradient(to left, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%); pointer-events: none; z-index: 10;"></div>
                        </div>
                        
                        <!-- Кастомный скроллбар -->
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small id="visible-start-${chartId}" class="text-muted"></small>
                                <small id="visible-end-${chartId}" class="text-muted"></small>
                            </div>
                            <div class="custom-scrollbar" style="position: relative; height: 8px; background: #e9ecef; border-radius: 4px; cursor: pointer;">
                                <div id="${scrollbarId}" class="scrollbar-thumb" style="position: absolute; left: 66.67%; width: 33.33%; height: 100%; background: #0d6efd; border-radius: 4px; cursor: grab;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
    
    if (dynamicsData.labels.length === 0) return;
    
    // Рендерим полный график
    const chartInfo = renderFullDynamicsChart(chartId, dynamicsData);
    
    // Инициализируем скролл
    initDynamicsScroll(chartId, scrollbarId, dynamicsData, chartInfo);
}

function renderFullDynamicsChart(chartId, data) {
    const canvas = document.getElementById(chartId);
    const chartInner = canvas.closest('.chart-inner');
    const chartWrapper = canvas.closest('.chart-wrapper');
    
    if (!canvas || !chartInner || !chartWrapper) return;
    
    canvas.width = chartWrapper.offsetWidth * 3;
    canvas.height = chartWrapper.offsetHeight;
    
    const ctx = canvas.getContext('2d');
    
    // Создаем копию данных для Chart.js
    const chartData = {
        labels: data.labels,
        datasets: data.datasets
    };
    
    charts[chartId] = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y} шт.`;
                        }
                    }
                },
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'line',
                        padding: 20
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 20
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Количество, шт.'
                    }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false
            }
        }
    });
    
    return {
        totalWidth: canvas.width,
        visibleWidth: chartWrapper.offsetWidth,
        totalPoints: data.labels.length
    };
}

function initDynamicsScroll(chartId, scrollbarId, data, chartInfo) {
    const scrollbar = document.getElementById(scrollbarId);
    const container = scrollbar.closest('.custom-scrollbar');
    const chartWrapper = document.querySelector('.chart-wrapper');
    const chartInner = document.querySelector('.chart-inner');
    
    if (!scrollbar || !container || !chartWrapper || !chartInner) return;
    
    const visiblePercentage = 0.3333;
    const scrollbarWidth = visiblePercentage * 100;
    const startLeft = 100 - scrollbarWidth;
    
    scrollbar.style.left = startLeft + '%';
    scrollbar.style.width = scrollbarWidth + '%';
    
    const totalWidth = chartInfo?.totalWidth || chartWrapper.offsetWidth * 3;
    const visibleWidth = chartInfo?.visibleWidth || chartWrapper.offsetWidth;
    const maxShift = totalWidth - visibleWidth;
    const initialShift = maxShift;
    
    chartInner.style.transform = `translateX(-${initialShift}px)`;
    
    let isDragging = false;
    let dragStartX = 0;
    let dragStartLeft = startLeft;
    
    scrollbar.addEventListener('mousedown', startDrag);
    container.addEventListener('mousedown', jumpToPosition);
    
    function startDrag(e) {
        e.preventDefault();
        e.stopPropagation();
        isDragging = true;
        dragStartX = e.clientX;
        dragStartLeft = parseFloat(scrollbar.style.left) || startLeft;
        scrollbar.style.cursor = 'grabbing';
        document.addEventListener('mousemove', handleDrag);
        document.addEventListener('mouseup', stopDrag);
    }
    
    function jumpToPosition(e) {
        if (e.target === scrollbar || isDragging) return;
        const rect = container.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickPercent = (clickX / rect.width) * 100;
        const newLeft = Math.max(0, Math.min(clickPercent - (scrollbarWidth / 2), 100 - scrollbarWidth));
        updateScrollAndChart(newLeft);
        updateVisibleRange(newLeft);
    }
    
    function handleDrag(e) {
        if (!isDragging) return;
        const rect = container.getBoundingClientRect();
        const deltaX = e.clientX - dragStartX;
        const deltaPercent = (deltaX / rect.width) * 100;
        const newLeft = Math.max(0, Math.min(dragStartLeft + deltaPercent, 100 - scrollbarWidth));
        updateScrollAndChart(newLeft);
        updateVisibleRange(newLeft);
    }
    
    function stopDrag() {
        isDragging = false;
        scrollbar.style.cursor = 'grab';
        document.removeEventListener('mousemove', handleDrag);
        document.removeEventListener('mouseup', stopDrag);
    }
    
    function updateScrollAndChart(leftPercent) {
        scrollbar.style.left = leftPercent + '%';
        const shift = (leftPercent / (100 - scrollbarWidth)) * maxShift;
        chartInner.style.transform = `translateX(-${shift}px)`;
    }
    
    function updateVisibleRange(leftPercent) {
        if (!data || !data.labels || data.labels.length === 0) return;
        const totalPoints = data.labels.length;
        const startRatio = leftPercent / 100;
        const endRatio = startRatio + visiblePercentage;
        const startIndex = Math.floor(startRatio * totalPoints);
        const endIndex = Math.min(Math.ceil(endRatio * totalPoints), totalPoints - 1);
        document.getElementById(`visible-start-${chartId}`).textContent = data.labels[startIndex] || data.labels[0];
        document.getElementById(`visible-end-${chartId}`).textContent = data.labels[endIndex] || data.labels[data.labels.length - 1];
    }
    
    updateVisibleRange(startLeft);
}

function renderWeekdayChart(weekdayData) {
    const chartId = 'weekday-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Продажи по дням недели</h5>
                        <p class="text-muted mb-0 small">Распределение продаж по дням недели</p>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
    `;
    
    document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
    
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const labels = Object.keys(weekdayData);
    const dataValues = Object.values(weekdayData);
    
    charts[chartId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Количество продаж',
                data: dataValues,
                backgroundColor: '#198754',
                borderColor: '#198754',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

// Стили для графиков
const style = document.createElement('style');
style.textContent = `
    .chart-wrapper {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: white;
        position: relative;
        overflow: hidden !important;
    }
    .chart-inner {
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
    }
    .scrollbar-thumb:hover {
        background: #0b5ed7;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
`;
document.head.appendChild(style);
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
.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endsection