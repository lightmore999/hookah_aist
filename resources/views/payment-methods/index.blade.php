@extends('layouts.app')

@section('title', 'Статистика')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-bar-chart-line me-2"></i>Статистика
            </h1>
            <p class="text-muted mb-0 small">Анализ посещаемости и эффективности работы заведения</p>
        </div>
        
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary btn-sm" data-period="day">
                <i class="bi bi-calendar-day me-1"></i> День
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" data-period="week">
                <i class="bi bi-calendar-week me-1"></i> Неделя
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-period="month">
                <i class="bi bi-calendar-month me-1"></i> Месяц
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" data-period="year">
                <i class="bi bi-calendar me-1"></i> Год
            </button>
        </div>
    </div>
    
    <!-- Фильтры -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Дата начала</label>
                    <input type="date" id="startDate" class="form-control form-control-sm">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label small text-muted">Дата окончания</label>
                    <input type="date" id="endDate" class="form-control form-control-sm">
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary btn-sm me-2" id="applyFilter">
                        <i class="bi bi-funnel me-1"></i> Применить
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" id="resetFilter">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Сбросить
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Сводка статистики -->
    <div class="row mb-4" id="summaryRow">
        <!-- Динамически загружается -->
    </div>
    
    <!-- Основные графики -->
    <div class="row mb-4">
        <!-- 1. Динамика посещений -->
        <div class="col-xl-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Динамика посещений
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="visitDynamicsChart"></canvas>
                    </div>
                    <div class="row mt-3 text-center">
                        <div class="col-md-4">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                Столы: <span id="totalTables">0</span>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                Гости: <span id="totalGuests">0</span>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                Среднее: <span id="avgGuests">0</span> гостей/стол
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 2. Статусы столов -->
        <div class="col-xl-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>Статусы столов
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container" style="height: 250px; width: 250px;">
                        <canvas id="tableStatusesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Второй ряд графиков -->
    <div class="row mb-4">
        <!-- 3. Популярное время -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-clock me-2"></i>Популярное время посещения
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="popularHoursChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                            Пиковый час: <span id="peakHour">-</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 4. Популярные столы -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Популярные столы
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="popularTablesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Третий ряд графиков -->
    <div class="row">
        <!-- 5. Дни недели -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Популярные дни недели
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="popularWeekdaysChart"></canvas>
                    </div>
                    <div class="mt-3 text-center">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                            Самый популярный день: <span id="popularDay">-</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 6. Средняя продолжительность -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-hourglass-split me-2"></i>Средняя продолжительность
                    </h5>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <div class="display-5 text-primary mb-2 fw-bold" id="avgDuration">0 мин</div>
                    <p class="text-muted small mb-3">среднее время бронирования</p>
                    <div class="row w-100 text-center">
                        <div class="col-6">
                            <div class="text-success">
                                <i class="bi bi-arrow-up me-1"></i>
                                <span id="maxDuration">0</span> мин
                            </div>
                            <small class="text-muted">максимум</small>
                        </div>
                        <div class="col-6">
                            <div class="text-danger">
                                <i class="bi bi-arrow-down me-1"></i>
                                <span id="minDuration">0</span> мин
                            </div>
                            <small class="text-muted">минимум</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Глобальные переменные
let charts = {};
let currentPeriod = 'month';
let isInitialized = false;

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initEventListeners();
    initCharts();
    updateAllCharts();
});

// Инициализация слушателей событий
function initEventListeners() {
    // Кнопки периода
    document.querySelectorAll('[data-period]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-period]').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
            currentPeriod = this.dataset.period;
            updateAllCharts();
        });
    });
    
    // Кнопка применения фильтра
    document.getElementById('applyFilter').addEventListener('click', updateAllCharts);
    
    // Кнопка сброса фильтра
    document.getElementById('resetFilter').addEventListener('click', function() {
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        updateAllCharts();
    });
    
    // Enter в полях даты
    ['startDate', 'endDate'].forEach(id => {
        document.getElementById(id).addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                updateAllCharts();
            }
        });
    });
}

// Получение параметров фильтра
function getFilterParams() {
    return {
        period: currentPeriod,
        start_date: document.getElementById('startDate').value || null,
        end_date: document.getElementById('endDate').value || null
    };
}

// Инициализация всех графиков
function initCharts() {
    if (isInitialized) return;
    
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: {
                        size: 11
                    },
                    padding: 15
                }
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                titleFont: {
                    size: 12
                },
                bodyFont: {
                    size: 11
                },
                padding: 10
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        size: 10
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                ticks: {
                    font: {
                        size: 10
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            }
        }
    };
    
    // 1. Динамика посещений
    const dynamicsCtx = document.getElementById('visitDynamicsChart').getContext('2d');
    charts.dynamics = new Chart(dynamicsCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Количество столов',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.05)',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5
                },
                {
                    label: 'Количество гостей',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.05)',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: chartOptions
    });
    
    // 2. Популярные столы
    const tablesCtx = document.getElementById('popularTablesChart').getContext('2d');
    charts.tables = new Chart(tablesCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Количество бронирований',
                data: [],
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                borderRadius: 3
            }]
        },
        options: {
            ...chartOptions,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // 3. Популярное время
    const hoursCtx = document.getElementById('popularHoursChart').getContext('2d');
    charts.hours = new Chart(hoursCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Количество столов',
                data: [],
                backgroundColor: 'rgba(153, 102, 255, 0.6)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1,
                borderRadius: 3
            }]
        },
        options: chartOptions
    });
    
    // 4. Статусы столов
    const statusCtx = document.getElementById('tableStatusesChart').getContext('2d');
    charts.status = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
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
                        font: {
                            size: 11
                        },
                        padding: 10,
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
    
    // 5. Дни недели
    const weekdaysCtx = document.getElementById('popularWeekdaysChart').getContext('2d');
    charts.weekdays = new Chart(weekdaysCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Количество столов',
                data: [],
                backgroundColor: 'rgba(255, 159, 64, 0.6)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1,
                borderRadius: 3
            }]
        },
        options: chartOptions
    });
    
    isInitialized = true;
}

// Обновление всех графиков
async function updateAllCharts() {
    const params = getFilterParams();
    showLoading();
    
    try {
        // Запросы данных
        const [dynamics, tables, hours, weekdays, status, duration, summary] = await Promise.all([
            fetchData('{{ route("statistics.visit-dynamics") }}', params),
            fetchData('{{ route("statistics.popular-tables") }}', params),
            fetchData('{{ route("statistics.popular-hours") }}', params),
            fetchData('{{ route("statistics.popular-weekdays") }}', params),
            fetchData('{{ route("statistics.table-statuses") }}', params),
            fetchData('{{ route("statistics.average-duration") }}', params),
            fetchData('{{ route("statistics.summary") }}', params)
        ]);
        
        // Обновление графиков
        updateDynamicsChart(dynamics);
        updateTablesChart(tables);
        updateHoursChart(hours);
        updateWeekdaysChart(weekdays);
        updateStatusChart(status);
        updateDurationInfo(duration);
        updateSummary(summary);
        
    } catch (error) {
        console.error('Ошибка загрузки данных:', error);
        showError('Ошибка загрузки данных статистики');
    } finally {
        hideLoading();
    }
}

// Обновление графика динамики
function updateDynamicsChart(data) {
    charts.dynamics.data.labels = data.labels;
    charts.dynamics.data.datasets[0].data = data.tables_data;
    charts.dynamics.data.datasets[1].data = data.guests_data;
    charts.dynamics.update('none');
    
    document.getElementById('totalTables').textContent = data.total_tables;
    document.getElementById('totalGuests').textContent = data.total_guests;
    document.getElementById('avgGuests').textContent = data.avg_guests_per_table;
}

// Обновление графика популярных столов
function updateTablesChart(data) {
    charts.tables.data.labels = data.labels;
    charts.tables.data.datasets[0].data = data.visits_data;
    charts.tables.update('none');
}

// Обновление графика популярного времени
function updateHoursChart(data) {
    charts.hours.data.labels = data.labels;
    charts.hours.data.datasets[0].data = data.tables_data;
    charts.hours.update('none');
    
    if (data.peak_hour !== null) {
        document.getElementById('peakHour').textContent = 
            data.peak_hour.toString().padStart(2, '0') + ':00';
    }
}

// Обновление графика дней недели
function updateWeekdaysChart(data) {
    charts.weekdays.data.labels = data.labels;
    charts.weekdays.data.datasets[0].data = data.tables_data;
    charts.weekdays.update('none');
    
    if (data.most_popular_day) {
        document.getElementById('popularDay').textContent = data.most_popular_day;
    }
}

// Обновление графика статусов
function updateStatusChart(data) {
    charts.status.data.labels = data.labels;
    charts.status.data.datasets[0].data = data.data;
    charts.status.update('none');
}

// Обновление информации о продолжительности
function updateDurationInfo(data) {
    document.getElementById('avgDuration').textContent = data.avg_duration_formatted;
    document.getElementById('minDuration').textContent = data.min_duration;
    document.getElementById('maxDuration').textContent = data.max_duration;
}

// Обновление сводки
function updateSummary(data) {
    const summaryHtml = `
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                            <i class="bi bi-table fs-4"></i>
                        </div>
                        <div>
                            <div class="h5 mb-0">${data.total_bookings}</div>
                            <small class="text-muted">Всего бронирований</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 text-success rounded p-2 me-3">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div>
                            <div class="h5 mb-0">${data.total_guests}</div>
                            <small class="text-muted">Всего гостей</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info rounded p-2 me-3">
                            <i class="bi bi-person-check fs-4"></i>
                        </div>
                        <div>
                            <div class="h5 mb-0">${data.avg_guests_per_booking}</div>
                            <small class="text-muted">Среднее гостей/бронирование</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                            <i class="bi bi-calendar-day fs-4"></i>
                        </div>
                        <div>
                            <div class="h5 mb-0">${data.avg_bookings_per_day}</div>
                            <small class="text-muted">Среднее в день</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('summaryRow').innerHTML = summaryHtml;
}

// Функция загрузки данных
async function fetchData(url, params) {
    const queryString = new URLSearchParams(params).toString();
    const response = await fetch(`${url}?${queryString}`);
    if (!response.ok) throw new Error('Network response was not ok');
    return await response.json();
}

// Показать индикатор загрузки
function showLoading() {
    const btn = document.getElementById('applyFilter');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Загрузка...';
    btn.disabled = true;
    btn.dataset.original = originalHtml;
}

// Скрыть индикатор загрузки
function hideLoading() {
    const btn = document.getElementById('applyFilter');
    if (btn.dataset.original) {
        btn.innerHTML = btn.dataset.original;
        btn.disabled = false;
    }
}

// Показать ошибку
function showError(message) {
    // Создаем временное уведомление
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    // Удаляем через 5 секунд
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 5000);
}
</script>
@endsection