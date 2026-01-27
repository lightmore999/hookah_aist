@extends('layouts.app')

@section('title', 'Статистика')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-bar-chart-line me-2"></i>Статистика
            </h1>
            <p class="text-muted mb-0 small">Анализ посещаемости и продаж заведения</p>
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
// Конфигурация URL
const API_URLS = {
    dynamics: '{{ url("/statistics/visit-dynamics") }}',
    tables: '{{ url("/statistics/popular-tables") }}',
    hours: '{{ url("/statistics/popular-hours") }}',
    weekdays: '{{ url("/statistics/popular-weekdays") }}',
    payment: '{{ url("/statistics/payment-methods") }}',
    summary: '{{ url("/statistics/summary") }}'
};

let charts = {};
let currentDynamicsOffset = 0; // Только для динамики
let currentTablesOffset = 0; // Для популярных столов
let currentHoursOffset = 0;  // Для популярного времени
let currentWeekdaysOffset = 0; // Для дней недели
let currentPaymentOffset = 0; // Для способов оплаты
let currentSummaryOffset = 0; // Для сводки

document.addEventListener('DOMContentLoaded', function() {
    loadData();
});

function showLoading() {
    const loading = document.getElementById('loadingIndicator');
    if (loading) loading.classList.remove('d-none');
}

function hideLoading() {
    const loading = document.getElementById('loadingIndicator');
    if (loading) loading.classList.add('d-none');
}

async function loadData() {
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
        // Загружаем сводку (по умолчанию текущая неделя)
        const summary = await fetchData(API_URLS.summary, { period: 'week', offset: 0 });
        
        if (summary.success) {
            // Рендерим все графики
            renderSummary(summary);
            await renderDynamicsChart();
            await renderTablesChart();
            await renderHoursChart();
            await renderWeekdaysChart();
            await renderPaymentChart();
        } else {
            container.innerHTML += `
                <div class="alert alert-warning">
                    Ошибка загрузки сводки: ${summary.error}
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Ошибка загрузки:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                Ошибка загрузки данных: ${error.message}
            </div>
        `;
    } finally {
        hideLoading();
    }
}

async function fetchData(url, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const response = await fetch(`${url}?${queryString}`);
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (!data.success) {
        console.error('API error:', data.error);
    }
    
    return data;
}

function renderSummary(data) {
    const container = document.getElementById('dataContainer');
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Сводка за период</h5>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2 summary-prev">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary summary-period-btn active" data-period="week">
                                    Неделя
                                </button>
                                <button type="button" class="btn btn-outline-primary summary-period-btn" data-period="month">
                                    Месяц
                                </button>
                                <button type="button" class="btn btn-outline-primary summary-period-btn" data-period="year">
                                    Год
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2 summary-next">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <span class="ms-3 small text-muted summary-period-text">${data.current_range?.label || 'Текущая неделя'}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-primary">
                                    <div class="display-6 fw-bold mb-1">${data.total_bookings}</div>
                                    <div class="text-muted small">Бронирований</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-success">
                                    <div class="display-6 fw-bold mb-1">${data.total_guests}</div>
                                    <div class="text-muted small">Гостей</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-info">
                                    <div class="display-6 fw-bold mb-1">${data.total_sales}</div>
                                    <div class="text-muted small">Продаж</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-warning">
                                    <div class="display-6 fw-bold mb-1">${data.total_revenue} ₽</div>
                                    <div class="text-muted small">Выручка</div>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center mt-3 pt-3 border-top">
                            <div class="col-md-4 col-6 mb-2">
                                <small class="text-muted">Среднее гостей/бронь:</small>
                                <div class="fw-bold">${data.avg_guests_per_booking}</div>
                            </div>
                            <div class="col-md-4 col-6 mb-2">
                                <small class="text-muted">Средний чек:</small>
                                <div class="fw-bold">${data.avg_sale} ₽</div>
                            </div>
                            <div class="col-md-4 col-6 mb-2">
                                <small class="text-muted">Бронирований/день:</small>
                                <div class="fw-bold">${data.avg_bookings_per_day}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Вешаем обработчики на кнопки периода сводки
    document.querySelectorAll('.summary-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.summary-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            currentSummaryOffset = 0; // Сбрасываем оффсет при смене периода
            const params = { period: period, offset: 0 };
            
            const newData = await fetchData(API_URLS.summary, params);
            updateSummary(newData);
        });
    });
    
    // Обработчики для стрелок сводки
    document.querySelector('.summary-prev')?.addEventListener('click', async function() {
        const activeBtn = document.querySelector('.summary-period-btn.active');
        const period = activeBtn.dataset.period;
        currentSummaryOffset--;
        
        const params = { period: period, offset: currentSummaryOffset };
        const newData = await fetchData(API_URLS.summary, params);
        updateSummary(newData);
    });
    
    document.querySelector('.summary-next')?.addEventListener('click', async function() {
        const activeBtn = document.querySelector('.summary-period-btn.active');
        const period = activeBtn.dataset.period;
        currentSummaryOffset++;
        
        const params = { period: period, offset: currentSummaryOffset };
        const newData = await fetchData(API_URLS.summary, params);
        updateSummary(newData);
    });
}

function updateSummary(data) {
    if (!data.success) return;
    
    // Обновляем заголовок периода
    const textElement = document.querySelector('.summary-period-text');
    if (textElement && data.current_range?.label) {
        textElement.textContent = data.current_range.label;
    }
    
    // Обновляем данные сводки (в реальном приложении нужно обновлять все значения)
    // Для простоты перезагружаем весь блок
    const summaryCard = document.querySelector('.card-header h5').closest('.card');
    if (summaryCard) {
        const html = `
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-primary">
                            <div class="display-6 fw-bold mb-1">${data.total_bookings}</div>
                            <div class="text-muted small">Бронирований</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-success">
                            <div class="display-6 fw-bold mb-1">${data.total_guests}</div>
                            <div class="text-muted small">Гостей</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-info">
                            <div class="display-6 fw-bold mb-1">${data.total_sales}</div>
                            <div class="text-muted small">Продаж</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-warning">
                            <div class="display-6 fw-bold mb-1">${data.total_revenue} ₽</div>
                            <div class="text-muted small">Выручка</div>
                        </div>
                    </div>
                </div>
                <div class="row text-center mt-3 pt-3 border-top">
                    <div class="col-md-4 col-6 mb-2">
                        <small class="text-muted">Среднее гостей/бронь:</small>
                        <div class="fw-bold">${data.avg_guests_per_booking}</div>
                    </div>
                    <div class="col-md-4 col-6 mb-2">
                        <small class="text-muted">Средний чек:</small>
                        <div class="fw-bold">${data.avg_sale} ₽</div>
                    </div>
                    <div class="col-md-4 col-6 mb-2">
                        <small class="text-muted">Бронирований/день:</small>
                        <div class="fw-bold">${data.avg_bookings_per_day}</div>
                    </div>
                </div>
            </div>
        `;
        
        const cardBody = summaryCard.querySelector('.card-body');
        if (cardBody) {
            cardBody.innerHTML = html;
        }
    }
}

async function renderDynamicsChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'dynamics-chart-' + Date.now();
    const scrollbarId = 'dynamics-scrollbar-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Динамика посещений</h5>
                        <div class="d-flex align-items-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary dynamics-period-btn active" data-period="day">
                                    По дням
                                </button>
                                <button type="button" class="btn btn-outline-primary dynamics-period-btn" data-period="week">
                                    По неделям
                                </button>
                                <button type="button" class="btn btn-outline-primary dynamics-period-btn" data-period="month">
                                    По месяцам
                                </button>
                            </div>
                            <span class="ms-3 small text-muted" id="dynamics-period-text">Вся история (по дням)</span>
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
                                <small id="visible-start" class="text-muted"></small>
                                <small id="visible-end" class="text-muted"></small>
                            </div>
                            <div class="custom-scrollbar" style="position: relative; height: 8px; background: #e9ecef; border-radius: 4px; cursor: pointer;">
                                <div id="${scrollbarId}" class="scrollbar-thumb" style="position: absolute; left: 66.67%; width: 33.33%; height: 100%; background: #0d6efd; border-radius: 4px; cursor: grab;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Перетащите для просмотра</small>
                                <small class="text-muted" id="zoom-percentage">Показано: 33%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию (по дням, вся история)
    const data = await fetchData(API_URLS.dynamics, { period: 'day' });
    
    if (data.success) {
        // Рендерим ВЕСЬ график и получаем данные о ширине
        const chartInfo = renderFullChart(chartId, data);
        
        // Инициализируем скролл - начинаем с КОНЦА графика
        initChartScroll(chartId, scrollbarId, data, chartInfo);
        
        // Обновляем текст
        document.getElementById('dynamics-period-text').textContent = 'Вся история (по дням)';
    }
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.dynamics-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.dynamics-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            const newData = await fetchData(API_URLS.dynamics, { period: period });
            
            if (newData.success) {
                // Перерисовываем весь график
                const chartInfo = renderFullChart(chartId, newData);
                
                // Инициализируем скролл снова
                initChartScroll(chartId, scrollbarId, newData, chartInfo);
                
                // Обновляем текст периода
                const periodText = document.getElementById('dynamics-period-text');
                let periodLabel = '';
                if (period === 'day') {
                    periodLabel = 'по дням';
                } else if (period === 'week') {
                    periodLabel = 'по неделям';
                } else {
                    periodLabel = 'по месяцам';
                }
                periodText.textContent = `Вся история (${periodLabel})`;
            }
        });
    });
}

function renderFullChart(chartId, data) {
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
                    label: 'Количество столов',
                    data: data.tables_data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    borderWidth: 2,
                    fill: true,
                    spanGaps: false
                },
                {
                    label: 'Количество гостей',
                    data: data.guests_data,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.3,
                    borderWidth: 2,
                    fill: true,
                    spanGaps: false
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
                            label += context.parsed.y;
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
                    title: {
                        display: true,
                        text: 'Количество'
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

function initChartScroll(chartId, scrollbarId, data, chartInfo) {
    const scrollbar = document.getElementById(scrollbarId);
    const container = scrollbar.closest('.custom-scrollbar');
    const chartWrapper = document.querySelector('.chart-wrapper');
    const chartInner = document.querySelector('.chart-inner');
    
    if (!scrollbar || !container || !chartWrapper || !chartInner) return;
    
    // Параметры
    const visiblePercentage = 0.3333; // Показываем 33% за раз
    const scrollbarWidth = visiblePercentage * 100;
    
    // Начинаем с конца графика
    const startLeft = 100 - scrollbarWidth; // 66.67%
    
    // Устанавливаем начальную позицию
    scrollbar.style.left = startLeft + '%';
    scrollbar.style.width = scrollbarWidth + '%';
    
    // Рассчитываем начальное смещение для показа конца графика
    const totalWidth = chartInfo?.totalWidth || chartWrapper.offsetWidth * 3;
    const visibleWidth = chartInfo?.visibleWidth || chartWrapper.offsetWidth;
    const maxShift = totalWidth - visibleWidth; // Максимальное смещение (200% ширины)
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
    
    function startDrag(e) {
        e.preventDefault();
        e.stopPropagation();
        
        isDragging = true;
        dragStartX = e.clientX;
        dragStartLeft = parseFloat(scrollbar.style.left) || startLeft;
        
        scrollbar.style.cursor = 'grabbing';
        document.body.style.cursor = 'grabbing';
        
        document.addEventListener('mousemove', handleDrag);
        document.addEventListener('mouseup', stopDrag);
    }
    
    function jumpToPosition(e) {
        if (e.target === scrollbar || isDragging) return;
        
        const rect = container.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickPercent = (clickX / rect.width) * 100;
        
        // Центрируем ползунок на точке клика
        const newLeft = Math.max(0, Math.min(clickPercent - (scrollbarWidth / 2), 100 - scrollbarWidth));
        
        updateScrollAndChart(newLeft);
        updateVisibleRange(newLeft, data);
    }
    
    function handleDrag(e) {
        if (!isDragging) return;
        
        const rect = container.getBoundingClientRect();
        const deltaX = e.clientX - dragStartX;
        const deltaPercent = (deltaX / rect.width) * 100;
        
        const newLeft = Math.max(0, Math.min(dragStartLeft + deltaPercent, 100 - scrollbarWidth));
        
        updateScrollAndChart(newLeft);
        updateVisibleRange(newLeft, data);
    }
    
    function stopDrag() {
        isDragging = false;
        
        scrollbar.style.cursor = 'grab';
        document.body.style.cursor = '';
        
        document.removeEventListener('mousemove', handleDrag);
        document.removeEventListener('mouseup', stopDrag);
    }
    
    function updateScrollAndChart(leftPercent) {
        // Обновляем положение ползунка
        scrollbar.style.left = leftPercent + '%';
        
        // Рассчитываем смещение графика
        // leftPercent идет от 0 (начало) до 66.67% (конец)
        // shift идет от 0 (начало) до maxShift (конец)
        const shift = (leftPercent / (100 - scrollbarWidth)) * maxShift;
        currentShift = shift;
        chartInner.style.transform = `translateX(-${shift}px)`;
        
        // Обновляем процент видимой области
        document.getElementById('zoom-percentage').textContent = `Показано: ${Math.round(scrollbarWidth)}%`;
    }
    
    function updateVisibleRange(leftPercent, data) {
        if (!data || !data.labels || data.labels.length === 0) return;
        
        const totalPoints = data.labels.length;
        
        // Рассчитываем индексы видимых точек
        // leftPercent от 0 до 66.67% соответствует началу от 0 до 66.67% данных
        const startRatio = leftPercent / 100;
        const endRatio = startRatio + visiblePercentage;
        
        const startIndex = Math.floor(startRatio * totalPoints);
        const endIndex = Math.min(Math.ceil(endRatio * totalPoints), totalPoints - 1);
        
        const startLabel = data.labels[startIndex] || data.labels[0];
        const endLabel = data.labels[endIndex] || data.labels[data.labels.length - 1];
        
        document.getElementById('visible-start').textContent = startLabel;
        document.getElementById('visible-end').textContent = endLabel;
    }
    
    // Инициализируем начальное положение
    updateVisibleRange(startLeft, data);
}


function updateVisibleRange(data, startRatio, visibleRatio) {
    if (!data || !data.labels || data.labels.length === 0) return;
    
    const totalItems = data.labels.length;
    const startIndex = Math.floor(startRatio * totalItems);
    const endIndex = Math.min(Math.ceil((startRatio + visibleRatio) * totalItems), totalItems - 1);
    
    const startLabel = data.labels[startIndex] || data.labels[0];
    const endLabel = data.labels[endIndex] || data.labels[data.labels.length - 1];
    
    document.getElementById('visible-start').textContent = startLabel;
    document.getElementById('visible-end').textContent = endLabel;
}


async function renderTablesChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'tables-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Популярные столы</h5>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2 tables-prev">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary tables-period-btn active" data-period="week">
                                    Неделя
                                </button>
                                <button type="button" class="btn btn-outline-primary tables-period-btn" data-period="month">
                                    Месяц
                                </button>
                                <button type="button" class="btn btn-outline-primary tables-period-btn" data-period="year">
                                    Год
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2 tables-next">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <span class="ms-3 small text-muted tables-period-text">Текущая неделя</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                    </div>
                </div>
            </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию (текущая неделя)
    const data = await fetchData(API_URLS.tables, { period: 'week', offset: 0 });
    renderTablesChartData(chartId, data);
    updateTablesPeriodText(data);
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.tables-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.tables-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            currentTablesOffset = 0; // Сбрасываем оффсет при смене периода
            const params = { period: period, offset: 0 };
            
            const newData = await fetchData(API_URLS.tables, params);
            renderTablesChartData(chartId, newData);
            updateTablesPeriodText(newData);
        });
    });
    
    // Обработчики для стрелок
    document.querySelector('.tables-prev').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.tables-period-btn.active');
        const period = activeBtn.dataset.period;
        currentTablesOffset--;
        
        const params = { period: period, offset: currentTablesOffset };
        const newData = await fetchData(API_URLS.tables, params);
        renderTablesChartData(chartId, newData);
        updateTablesPeriodText(newData);
    });
    
    document.querySelector('.tables-next').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.tables-period-btn.active');
        const period = activeBtn.dataset.period;
        currentTablesOffset++;
        
        const params = { period: period, offset: currentTablesOffset };
        const newData = await fetchData(API_URLS.tables, params);
        renderTablesChartData(chartId, newData);
        updateTablesPeriodText(newData);
    });
}

function updateTablesPeriodText(data) {
    const textElement = document.querySelector('.tables-period-text');
    if (textElement && data.current_range?.label) {
        textElement.textContent = data.current_range.label;
    }
}

function renderTablesChartData(chartId, data) {
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
            datasets: [{
                label: 'Количество бронирований',
                data: data.visits_data,
                backgroundColor: '#0d6efd',
                borderColor: '#0d6efd',
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
                        stepSize: 1
                    }
                }
            }
        }
    });
}

async function renderHoursChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'hours-chart-' + Date.now();
    
    const html = `
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Популярное время</h5>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2 hours-prev">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary hours-period-btn active" data-period="week">
                                    Неделя
                                </button>
                                <button type="button" class="btn btn-outline-primary hours-period-btn" data-period="month">
                                    Месяц
                                </button>
                                <button type="button" class="btn btn-outline-primary hours-period-btn" data-period="year">
                                    Год
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2 hours-next">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <span class="ms-3 small text-muted hours-period-text">Текущая неделя</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="badge bg-info" id="peak-hour-badge-${chartId}">Пиковый час: —</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию (текущая неделя)
    const data = await fetchData(API_URLS.hours, { period: 'week', offset: 0 });
    renderHoursChartData(chartId, data);
    updateHoursPeriodText(data);
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.hours-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.hours-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            currentHoursOffset = 0; // Сбрасываем оффсет при смене периода
            const params = { period: period, offset: 0 };
            
            const newData = await fetchData(API_URLS.hours, params);
            renderHoursChartData(chartId, newData);
            updateHoursPeriodText(newData);
        });
    });
    
    // Обработчики для стрелок
    document.querySelector('.hours-prev').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.hours-period-btn.active');
        const period = activeBtn.dataset.period;
        currentHoursOffset--;
        
        const params = { period: period, offset: currentHoursOffset };
        const newData = await fetchData(API_URLS.hours, params);
        renderHoursChartData(chartId, newData);
        updateHoursPeriodText(newData);
    });
    
    document.querySelector('.hours-next').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.hours-period-btn.active');
        const period = activeBtn.dataset.period;
        currentHoursOffset++;
        
        const params = { period: period, offset: currentHoursOffset };
        const newData = await fetchData(API_URLS.hours, params);
        renderHoursChartData(chartId, newData);
        updateHoursPeriodText(newData);
    });
}

function updateHoursPeriodText(data) {
    const textElement = document.querySelector('.hours-period-text');
    if (textElement && data.current_range?.label) {
        textElement.textContent = data.current_range.label;
    }
}

function renderHoursChartData(chartId, data) {
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
            datasets: [{
                label: 'Количество столов',
                data: data.tables_data,
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
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Обновляем пиковый час
    const badge = document.getElementById(`peak-hour-badge-${chartId}`);
    if (badge && data.peak_hour) {
        badge.textContent = `Пиковый час: ${data.peak_hour}`;
    }
}

async function renderWeekdaysChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'weekdays-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Дни недели</h5>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2 weekdays-prev">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary weekdays-period-btn active" data-period="week">
                                    Неделя
                                </button>
                                <button type="button" class="btn btn-outline-primary weekdays-period-btn" data-period="month">
                                    Месяц
                                </button>
                                <button type="button" class="btn btn-outline-primary weekdays-period-btn" data-period="year">
                                    Год
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2 weekdays-next">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <span class="ms-3 small text-muted weekdays-period-text">Текущая неделя</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="badge bg-info" id="popular-day-badge-${chartId}">Самый популярный день: —</span>
                        </div>
                    </div>
                </div>
            </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию (текущая неделя)
    const data = await fetchData(API_URLS.weekdays, { period: 'week', offset: 0 });
    renderWeekdaysChartData(chartId, data);
    updateWeekdaysPeriodText(data);
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.weekdays-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.weekdays-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            currentWeekdaysOffset = 0; // Сбрасываем оффсет при смене периода
            const params = { period: period, offset: 0 };
            
            const newData = await fetchData(API_URLS.weekdays, params);
            renderWeekdaysChartData(chartId, newData);
            updateWeekdaysPeriodText(newData);
        });
    });
    
    // Обработчики для стрелок
    document.querySelector('.weekdays-prev').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.weekdays-period-btn.active');
        const period = activeBtn.dataset.period;
        currentWeekdaysOffset--;
        
        const params = { period: period, offset: currentWeekdaysOffset };
        const newData = await fetchData(API_URLS.weekdays, params);
        renderWeekdaysChartData(chartId, newData);
        updateWeekdaysPeriodText(newData);
    });
    
    document.querySelector('.weekdays-next').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.weekdays-period-btn.active');
        const period = activeBtn.dataset.period;
        currentWeekdaysOffset++;
        
        const params = { period: period, offset: currentWeekdaysOffset };
        const newData = await fetchData(API_URLS.weekdays, params);
        renderWeekdaysChartData(chartId, newData);
        updateWeekdaysPeriodText(newData);
    });
}

function updateWeekdaysPeriodText(data) {
    const textElement = document.querySelector('.weekdays-period-text');
    if (textElement && data.current_range?.label) {
        textElement.textContent = data.current_range.label;
    }
}

function renderWeekdaysChartData(chartId, data) {
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
            datasets: [{
                label: 'Количество столов',
                data: data.tables_data,
                backgroundColor: '#6f42c1',
                borderColor: '#6f42c1',
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
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Обновляем самый популярный день
    const badge = document.getElementById(`popular-day-badge-${chartId}`);
    if (badge && data.most_popular_day) {
        badge.textContent = `Самый популярный день: ${data.most_popular_day}`;
    }
}

async function renderPaymentChart() {
    const container = document.getElementById('dataContainer');
    const chartId = 'payment-chart-' + Date.now();
    
    const html = `
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Способы оплаты</h5>
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2 payment-prev">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary payment-period-btn active" data-period="week">
                                    Неделя
                                </button>
                                <button type="button" class="btn btn-outline-primary payment-period-btn" data-period="month">
                                    Месяц
                                </button>
                                <button type="button" class="btn btn-outline-primary payment-period-btn" data-period="year">
                                    Год
                                </button>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2 payment-next">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                            <span class="ms-3 small text-muted payment-period-text">Текущая неделя</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="${chartId}"></canvas>
                        </div>
                        <div class="mt-3 text-center">
                            <span class="badge bg-info" id="popular-payment-badge-${chartId}">Самый популярный способ: —</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Загружаем данные по умолчанию (текущая неделя)
    const data = await fetchData(API_URLS.payment, { period: 'week', offset: 0 });
    renderPaymentChartData(chartId, data);
    updatePaymentPeriodText(data);
    
    // Вешаем обработчики на кнопки периода
    document.querySelectorAll('.payment-period-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('.payment-period-btn').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            const period = this.dataset.period;
            currentPaymentOffset = 0; // Сбрасываем оффсет при смене периода
            const params = { period: period, offset: 0 };
            
            const newData = await fetchData(API_URLS.payment, params);
            renderPaymentChartData(chartId, newData);
            updatePaymentPeriodText(newData);
        });
    });
    
    // Обработчики для стрелок
    document.querySelector('.payment-prev').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.payment-period-btn.active');
        const period = activeBtn.dataset.period;
        currentPaymentOffset--;
        
        const params = { period: period, offset: currentPaymentOffset };
        const newData = await fetchData(API_URLS.payment, params);
        renderPaymentChartData(chartId, newData);
        updatePaymentPeriodText(newData);
    });
    
    document.querySelector('.payment-next').addEventListener('click', async function() {
        const activeBtn = document.querySelector('.payment-period-btn.active');
        const period = activeBtn.dataset.period;
        currentPaymentOffset++;
        
        const params = { period: period, offset: currentPaymentOffset };
        const newData = await fetchData(API_URLS.payment, params);
        renderPaymentChartData(chartId, newData);
        updatePaymentPeriodText(newData);
    });
}

function updatePaymentPeriodText(data) {
    const textElement = document.querySelector('.payment-period-text');
    if (textElement && data.current_range?.label) {
        textElement.textContent = data.current_range.label;
    }
}

function renderPaymentChartData(chartId, data) {
    if (!data.success) return;
    
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    
    if (charts[chartId]) {
        charts[chartId].destroy();
    }
    
    const ctx = canvas.getContext('2d');
    
    charts[chartId] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                backgroundColor: [
                    '#0d6efd',
                    '#198754',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1',
                    '#fd7e14',
                    '#20c997',
                    '#e83e8c'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Обновляем самый популярный способ
    const badge = document.getElementById(`popular-payment-badge-${chartId}`);
    if (badge && data.most_popular) {
        badge.textContent = `Самый популярный способ: ${data.most_popular}`;
    }
}
</script>
<style>
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
</style>
@endsection