@extends('layouts.app')

@section('title', 'Статистика по товарам')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-box-seam me-2"></i>Статистика по товарам
            </h1>
            <p class="text-muted mb-0 small">Анализ продаж и прибыльности товаров</p>
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
            <form id="productsFilterForm">
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
                    <div class="col-md-3">
                        <label class="form-label small">Категория</label>
                        <select class="form-select form-select-sm" id="categorySelect" name="category_id">
                            <option value="">Все категории</option>
                            <!-- Категории будут заполнены через JavaScript -->
                        </select>
                    </div>
                    <div class="col-md-12 d-flex align-items-end">
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
const API_URL = '{{ route("statistics.products.data") }}';
let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    loadProductsData();
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
    
    document.getElementById('productsFilterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadProductsData();
    });
}

async function loadProductsData() {
    showLoading();
    
    const container = document.getElementById('dataContainer');
    container.innerHTML = '';
    
    // Уничтожаем старые графики
    Object.values(charts).forEach(chart => {
        if (chart && chart.destroy) chart.destroy();
    });
    charts = {};
    
    const formData = new FormData(document.getElementById('productsFilterForm'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData) {
        params.append(key, value);
    }
    
    try {
        const response = await fetch(`${API_URL}?${params.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            // Заполняем категории в фильтре
            populateCategories(data.categories);
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

function populateCategories(categories) {
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect) return;
    
    // Сохраняем текущее значение
    const currentValue = categorySelect.value;
    
    // Очищаем и заполняем заново
    categorySelect.innerHTML = '<option value="">Все категории</option>';
    
    if (categories && categories.length > 0) {
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });
        
        // Восстанавливаем выбранное значение
        if (currentValue) {
            categorySelect.value = currentValue;
        }
    }
}

function renderAllCharts(data) {
    const container = document.getElementById('dataContainer');
    
    // Блок с общей статистикой
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
                                    <div class="display-6 fw-bold mb-1">${formatCurrency(data.summary.total_revenue)}</div>
                                    <div class="text-muted small">Общая выручка</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-success">
                                    <div class="display-6 fw-bold mb-1">${formatCurrency(data.summary.table_revenue)}</div>
                                    <div class="text-muted small">Выручка со столов</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-info">
                                    <div class="display-6 fw-bold mb-1">${formatCurrency(data.summary.total_profit)}</div>
                                    <div class="text-muted small">Общая прибыль</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="text-warning">
                                    <div class="display-6 fw-bold mb-1">${data.summary.total_products_sold}</div>
                                    <div class="text-muted small">Товаров продано</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Таблица с детальной статистикой (ПЕРВОЙ)
    renderProductsTable(data.products_stats);
    
    // Топ товаров по прибыли
    renderTopProducts(data.top_products);
    
    // График по категориям
    renderCategoryChart(data.category_stats);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function renderProductsTable(productsData) {
    // Фильтруем товары с продажами
    const productsWithSales = productsData.filter(item => item.total_quantity > 0);
    
    const rows = productsData.map(item => {
        const profitClass = item.total_profit > 0 ? 'text-success' : 'text-danger';
        const marginClass = item.profit_margin >= 30 ? 'text-success' : 
                          item.profit_margin >= 20 ? 'text-warning' : 'text-danger';
        const rowClass = item.total_quantity === 0 ? 'bg-light' : '';
        
        return `
            <tr class="${rowClass}">
                <td class="ps-3 fw-bold">${item.name}</td>
                <td><span class="badge bg-secondary">${item.category}</span></td>
                <td>${formatCurrency(item.price)}</td>
                <td>${formatCurrency(item.cost)}</td>
                <td>
                    ${item.total_quantity > 0 ? 
                        `<span class="badge bg-primary">${item.total_quantity} шт.</span>` : 
                        `<span class="badge bg-light text-dark">0</span>`}
                </td>
                <td>${formatCurrency(item.total_revenue)}</td>
                <td class="${profitClass} fw-bold">${formatCurrency(item.total_profit)}</td>
                <td class="${marginClass} fw-bold">${item.profit_margin.toFixed(1)}%</td>
                <td>${item.sales_count}</td>
            </tr>
        `;
    }).join('');
    
    const totalQuantity = productsData.reduce((sum, item) => sum + item.total_quantity, 0);
    const totalRevenue = productsData.reduce((sum, item) => sum + item.total_revenue, 0);
    const totalProfit = productsData.reduce((sum, item) => sum + item.total_profit, 0);
    const totalSalesCount = productsData.reduce((sum, item) => sum + item.sales_count, 0);
    const totalMargin = totalRevenue > 0 ? (totalProfit / totalRevenue * 100).toFixed(1) : 0;
    
    let html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Детальная статистика по товарам </h5>
                        <div>
                            <span class="badge bg-primary me-2">${productsData.length} товаров</span>
                            <span class="badge bg-success">${productsWithSales.length} с продажами</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-3">Товар</th>
                                        <th>Категория</th>
                                        <th>Цена</th>
                                        <th>Себест.</th>
                                        <th>Продано</th>
                                        <th>Выручка</th>
                                        <th>Прибыль</th>
                                        <th>Маржа</th>
                                        <th>Продаж</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rows}
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td class="ps-3 fw-bold" colspan="4">Итого:</td>
                                        <td class="fw-bold">${totalQuantity} шт.</td>
                                        <td class="fw-bold">${formatCurrency(totalRevenue)}</td>
                                        <td class="fw-bold">${formatCurrency(totalProfit)}</td>
                                        <td class="fw-bold">${totalMargin}%</td>
                                        <td class="fw-bold">${totalSalesCount}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
}

function renderTopProducts(topProducts) {
    if (!topProducts || topProducts.length === 0) return;
    
    let html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Топ-10 товаров по прибыли</h5>
                        <p class="text-muted mb-0 small">Самые прибыльные товары за период</p>
                    </div>
                    <div class="card-body">
                        <div class="row">
    `;
    
    topProducts.forEach((product, index) => {
        const medalClass = index === 0 ? 'text-warning' : 
                          index === 1 ? 'text-secondary' : 
                          index === 2 ? 'text-danger' : 'text-muted';
        const medalIcon = index === 0 ? '🥇' : 
                         index === 1 ? '🥈' : 
                         index === 2 ? '🥉' : `${index + 1}.`;
        
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card border h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="${medalClass} fw-bold me-2">${medalIcon}</span>
                                <span class="fw-bold">${product.name}</span>
                            </div>
                            <span class="badge bg-secondary">${product.category}</span>
                        </div>
                        <div class="row small">
                            <div class="col-6">
                                <div class="text-muted">Продано:</div>
                                <div class="fw-bold">${product.total_quantity} шт.</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Выручка:</div>
                                <div class="fw-bold">${formatCurrency(product.total_revenue)}</div>
                            </div>
                            <div class="col-6 mt-2">
                                <div class="text-muted">Прибыль:</div>
                                <div class="fw-bold text-success">${formatCurrency(product.total_profit)}</div>
                            </div>
                            <div class="col-6 mt-2">
                                <div class="text-muted">Маржа:</div>
                                <div class="fw-bold ${product.profit_margin >= 30 ? 'text-success' : 'text-warning'}">
                                    ${product.profit_margin.toFixed(1)}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('dataContainer').insertAdjacentHTML('beforeend', html);
}

function renderCategoryChart(categoryStats) {
    const chartId = 'category-chart-' + Date.now();
    
    const html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Выручка по категориям</h5>
                        <p class="text-muted mb-0 small">Общая выручка по категориям товаров</p>
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
    
    // Фильтруем категории с выручкой > 0
    const filteredStats = categoryStats.filter(item => item.total_revenue > 0);
    
    if (filteredStats.length === 0) {
        document.querySelector(`#${chartId}`).closest('.card-body').innerHTML = `
            <div class="text-center py-4">
                <p class="text-muted">Нет данных по категориям за выбранный период</p>
            </div>
        `;
        return;
    }
    
    const labels = filteredStats.map(item => item.category);
    const dataValues = filteredStats.map(item => item.total_revenue);
    
    // Генерируем цвета для категорий
    const colors = labels.map((label, index) => {
        const hue = (index * 137) % 360;
        return `hsl(${hue}, 70%, 60%)`;
    });
    
    charts[chartId] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Выручка',
                data: dataValues,
                backgroundColor: colors,
                borderColor: colors.map(color => color.replace('60%)', '50%)')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const item = filteredStats[context.dataIndex];
                            return [
                                `Выручка: ${formatCurrency(item.total_revenue)}`,
                                `Кол-во: ${item.total_quantity} шт.`,
                                `Прибыль: ${formatCurrency(item.total_profit)}`,
                                `Маржа: ${item.profit_margin}%`
                            ];
                        }
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    });
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