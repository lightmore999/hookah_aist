@extends('layouts.app')

@section('title', 'Анализ движения товаров')

@section('content')
<div class="container py-4">
    
    <div class="mb-4">
        <h1 class="h3 mb-2">
            <i class="bi bi-graph-up me-2"></i>Анализ движения товаров
        </h1>
        <p class="text-muted mb-0">Сравнение двух инвентаризаций и анализ движения товаров между ними</p>
    </div>

    <!-- Форма выбора -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inventory-analysis.index') }}" class="row g-3">
                @csrf
                
                <div class="col-md-5">
                    <label for="start_inventory_id" class="form-label fw-bold">Начальная инвентаризация *</label>
                    <select name="start_inventory_id" id="start_inventory_id" 
                            class="form-select" 
                            required>
                        <option value="">Выберите первую инвентаризацию</option>
                        @foreach($inventories as $inventory)
                            <option value="{{ $inventory->id }}" 
                                    {{ old('start_inventory_id', request('start_inventory_id')) == $inventory->id ? 'selected' : '' }}>
                                {{ $inventory->name }} ({{ $inventory->inventory_date->format('d.m.Y H:i') }}, {{ $inventory->warehouse->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label for="end_inventory_id" class="form-label fw-bold">Конечная инвентаризация *</label>
                    <select name="end_inventory_id" id="end_inventory_id" 
                            class="form-select" 
                            required>
                        <option value="">Выберите вторую инвентаризацию</option>
                        @foreach($inventories as $inventory)
                            <option value="{{ $inventory->id }}" 
                                    {{ old('end_inventory_id', request('end_inventory_id')) == $inventory->id ? 'selected' : '' }}>
                                {{ $inventory->name }} ({{ $inventory->inventory_date->format('d.m.Y H:i') }}, {{ $inventory->warehouse->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calculator me-1"></i>Анализировать
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($reportData) && isset($startInventory) && isset($endInventory))
        <!-- Статистика -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3">Период анализа</h6>
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-primary bg-opacity-10 rounded p-2 me-3">
                                <i class="bi bi-calendar text-primary"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Начало</div>
                                <div class="fw-bold">{{ $startInventory->name }}</div>
                                <div class="small text-muted">{{ $startInventory->inventory_date->format('d.m.Y H:i') }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 rounded p-2 me-3">
                                <i class="bi bi-calendar-check text-success"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Конец</div>
                                <div class="fw-bold">{{ $endInventory->name }}</div>
                                <div class="small text-muted">{{ $endInventory->inventory_date->format('d.m.Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3">Сводка</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">Товаров</div>
                                    <div class="h5 mb-0">{{ count($reportData['products']) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">С расхождениями</div>
                                    <div class="h5 mb-0 {{ $summary['products_with_difference'] > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $summary['products_with_difference'] }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">Продано</div>
                                    <div class="h5 mb-0 text-success">{{ number_format($summary['total_sales'], 3) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">Потери</div>
                                    <div class="h5 mb-0 {{ $summary['total_difference'] > 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($summary['total_difference'], 3) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Таблица товаров -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-table me-2"></i>Движение товаров
                        <span class="badge bg-light text-dark ms-2">{{ count($reportData['products']) }}</span>
                    </h5>
                    
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" placeholder="Поиск..." id="searchProduct">
                        </div>
                        
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="showAll">
                                Все
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" id="showDifferences">
                                С расхождениями
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                @if(empty($reportData['products']))
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <p class="mt-3 text-muted">Нет данных для отображения</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Товар</th>
                                    <th class="text-end">Начало</th>
                                    <th class="text-end">Поступления</th>
                                    <th class="text-end">Продажи</th>
                                    <th class="text-end">Списания</th>
                                    <th class="text-end">Теорет.</th>
                                    <th class="text-end">Факт.</th>
                                    <th class="text-end">Разница</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['products'] as $productId => $data)
                                <tr class="{{ $data['has_difference'] ? 'table-warning' : '' }}" 
                                    data-product-name="{{ strtolower($data['product']->name) }}"
                                    data-has-difference="{{ $data['has_difference'] ? '1' : '0' }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2">
                                                @if($data['has_difference'])
                                                    <i class="bi bi-exclamation-triangle text-warning"></i>
                                                @else
                                                    <i class="bi bi-check-circle text-success"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $data['product']->name }}</div>
                                                <div class="text-muted small">
                                                    {{ $data['product']->unit }}
                                                    @if($data['product']->category)
                                                        • {{ $data['product']->category->name }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ number_format($data['start_quantity'], 3) }}</td>
                                    <td class="text-end text-success">
                                        +{{ number_format($data['purchases_quantity'], 3) }}
                                    </td>
                                    <td class="text-end text-danger">
                                        -{{ number_format($data['sales_quantity'], 3) }}
                                    </td>
                                    <td class="text-end text-danger">
                                        -{{ number_format($data['writeoffs_quantity'], 3) }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($data['theoretical_quantity'], 3) }}
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($data['end_quantity'], 3) }}
                                    </td>
                                    <td class="text-end">
                                        @if($data['has_difference'])
                                            <span class="badge bg-{{ $data['difference'] > 0 ? 'success' : 'danger' }}">
                                                {{ $data['difference'] > 0 ? '+' : '' }}{{ number_format($data['difference'], 3) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            
            @if(!empty($reportData['products']) && count($reportData['products']) > 10)
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <span id="visibleCount">{{ count($reportData['products']) }}</span> из {{ count($reportData['products']) }} товаров
                        </div>
                        <div>
                            <button class="btn btn-sm btn-link" id="scrollTopBtn">
                                <i class="bi bi-arrow-up"></i> Наверх
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- Инструкция при первом входе -->
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-data display-1 text-muted mb-4"></i>
                <h4 class="mb-3">Выберите инвентаризации для анализа</h4>
                <p class="text-muted mb-0">
                    Выберите начальную и конечную инвентаризации чтобы увидеть движение товаров между ними
                </p>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchProduct');
    const showAllBtn = document.getElementById('showAll');
    const showDifferencesBtn = document.getElementById('showDifferences');
    const productsTable = document.getElementById('productsTable');
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    const visibleCount = document.getElementById('visibleCount');
    
    let currentFilter = 'all'; // 'all' или 'differences'
    
    // Функция фильтрации
    function filterTable(searchTerm = '', filterType = 'all') {
        if (!productsTable) return;
        
        const rows = productsTable.querySelectorAll('tbody tr');
        let visibleRows = 0;
        
        rows.forEach(row => {
            const productName = row.getAttribute('data-product-name') || '';
            const hasDifference = row.getAttribute('data-has-difference') === '1';
            
            const matchesSearch = !searchTerm || productName.includes(searchTerm.toLowerCase());
            const matchesFilter = filterType === 'all' || (filterType === 'differences' && hasDifference);
            
            if (matchesSearch && matchesFilter) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Обновляем счетчик
        if (visibleCount) {
            visibleCount.textContent = visibleRows;
        }
        
        // Обновляем состояние кнопок
        updateButtonStates(filterType);
    }
    
    // Обновление состояния кнопок
    function updateButtonStates(activeFilter) {
        if (showAllBtn && showDifferencesBtn) {
            if (activeFilter === 'all') {
                showAllBtn.classList.remove('btn-outline-secondary');
                showAllBtn.classList.add('btn-secondary');
                showDifferencesBtn.classList.remove('btn-warning');
                showDifferencesBtn.classList.add('btn-outline-warning');
            } else {
                showAllBtn.classList.remove('btn-secondary');
                showAllBtn.classList.add('btn-outline-secondary');
                showDifferencesBtn.classList.remove('btn-outline-warning');
                showDifferencesBtn.classList.add('btn-warning');
            }
        }
    }
    
    // События поиска
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterTable(this.value.trim(), currentFilter);
        });
    }
    
    // Кнопка "Все"
    if (showAllBtn) {
        showAllBtn.addEventListener('click', function() {
            currentFilter = 'all';
            filterTable(searchInput ? searchInput.value.trim() : '', currentFilter);
        });
    }
    
    // Кнопка "С расхождениями"
    if (showDifferencesBtn) {
        showDifferencesBtn.addEventListener('click', function() {
            currentFilter = 'differences';
            filterTable(searchInput ? searchInput.value.trim() : '', currentFilter);
        });
    }
    
    // Кнопка "Наверх"
    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Инициализация
    if (productsTable) {
        filterTable('', currentFilter);
    }
});
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.card {
    border: 1px solid rgba(0, 0, 0, 0.08);
}

.form-select, .form-control {
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.form-select:focus, .form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0a58ca 0%, #084298 100%);
}
</style>
@endsection