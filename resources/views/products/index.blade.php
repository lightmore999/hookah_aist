@extends('layouts.app')

@section('title', 'Товары')

@php
// Хелпер для расчета стоимости компонента (упрощенный)
function calculateComponentCost($product, $quantity) {
    if (!$product) return 0;
    
    // Просто умножаем стоимость на количество
    $cost = $product->cost * $quantity;
    
    return round($cost, 2);
}
@endphp

@section('content')
<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                Товары
            </h1>
            <p class="text-muted mb-0 small">Управление товарами</p>
        </div>
        <div class="d-flex gap-3">
            <a href="{{ route('product_categories.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-tags me-1"></i>
                Категории
            </a>
            <button type="button" 
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createProductModal">
                <i class="bi bi-plus-circle me-1"></i>
                Добавить товар
            </button>
        </div>
    </div>
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('products.index') }}" id="filter-form">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="category_id" class="form-label">Категория</label>
                                    <select name="category_id" id="category_id" class="form-select"
                                            onchange="this.form.submit()"> 
                                        <option value="">Все категории</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @if($products->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Нет товаров. Добавьте первый!</p>
                    <button type="button" 
                            class="btn btn-primary mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#createProductModal">
                        Добавить Товар
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Название</th>
                                <th>Цена (₽)</th>
                                <th>Себестоимость (₽)</th>
                                <th>Единица</th>
                                <th>Категория</th>
                                <th>Состав</th>
                                <th>Артикул</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $product->barcode }}</small>
                                </td>
                                <td>{{ number_format($product->price, 2) }} ₽</td>
                                <td>{{ number_format($product->cost, 2) }} ₽</td>
                                <td>
                                    <span class="badge bg-{{ $product->unit == 'шт' ? 'primary' : 'info' }}">
                                        {{ $product->unit }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $product->category->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($product->is_composite)
                                        <span class="badge bg-warning">Составной</span>
                                    @else
                                        <span class="badge bg-success">Простой</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">
                                        @if($product->article_number)
                                            {{ $product->article_number }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-warning btn-sm edit-product-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editProductModal"
                                                data-id="{{ $product->id }}"
                                                data-name="{{ $product->name }}"
                                                data-price="{{ $product->price }}"
                                                data-cost="{{ $product->cost }}"
                                                data-unit="{{ $product->unit }}"
                                                data-category-id="{{ $product->product_category_id }}"
                                                data-article="{{ $product->article_number ?? '' }}"
                                                data-barcode="{{ $product->barcode ?? '' }}"
                                                data-components="{{ $product->recipeComponents->map(function($component) {
                                                    return [
                                                        'id' => $component->id,
                                                        'product_id' => $component->component_product_id,
                                                        'product_name' => $component->component->name ?? 'Неизвестно',
                                                        'quantity' => $component->quantity,
                                                        'unit' => $component->component->unit ?? '',
                                                        'cost' => $component->component->cost ?? 0,
                                                    ];
                                                })->toJson() }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm delete-product-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteProductModal"
                                                data-id="{{ $product->id }}"
                                                data-name="{{ $product->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

@include('products.modals.create')
@include('products.modals.edit')
@include('products.modals.delete')



<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== БАЗОВЫЙ КОД ДЛЯ МОДАЛОК ==========
    const editProductModal = document.getElementById('editProductModal');
    const deleteProductModal = document.getElementById('deleteProductModal');
    
    // Заполнение полей модалки редактирования
    if (editProductModal) {
        editProductModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('edit-product-btn')) {
                document.getElementById('edit_product_name').value = button.dataset.name;
                document.getElementById('edit_product_price').value = button.dataset.price;
                document.getElementById('edit_product_cost').value = button.dataset.cost;
                document.getElementById('edit_product_unit').value = button.dataset.unit;
                document.getElementById('edit_product_category_id').value = button.dataset.categoryId;
                document.getElementById('edit_product_article').value = button.dataset.article || '';
                document.getElementById('edit_product_barcode').value = button.dataset.barcode || '';
                document.getElementById('editProductForm').action = `/products/${button.dataset.id}`;
            }
        });
    }

    // Заполнение полей модалки удаления
    if (deleteProductModal) {
        deleteProductModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && button.classList.contains('delete-product-btn')) {
                document.getElementById('deleteProductName').textContent = button.dataset.name;
                document.getElementById('deleteProductForm').action = `/products/${button.dataset.id}`;
            }
        });
    }
    
    // ========== КОД ДЛЯ МОДАЛКИ РЕДАКТИРОВАНИЯ ==========
    if (editProductModal) {
        // Элементы модалки редактирования
        const editComponentsList = document.getElementById('edit_components_list');
        const editNoComponentsMessage = document.getElementById('edit_no_components_message');
        const editComponentsCostInfo = document.getElementById('edit_components_cost_info');
        const editComponentsTotalCost = document.getElementById('edit_components_total_cost');
        const editComponentProductSelect = document.getElementById('edit_component_product_id');
        const editComponentQuantityInput = document.getElementById('edit_component_quantity');
        const editAddComponentBtn = document.getElementById('edit_add_component_btn');
        const editCostInput = document.getElementById('edit_product_cost');
        const editCostSourceHint = document.getElementById('edit_cost_source_hint');
        const editProductForm = document.getElementById('editProductForm');
        
        let editComponents = [];
        
        // Упрощенная функция расчета стоимости компонента
        function calculateEditComponentCost(componentCost, quantity) {
            return parseFloat((componentCost * quantity).toFixed(4));
        }
        
        // Заполнение селекта продуктами
        function populateEditComponentSelect() {
            if (!editComponentProductSelect) return;
            
            editComponentProductSelect.innerHTML = '<option value="">Выберите продукт</option>';
            
            // Берем продукты из селекта создания (если он есть)
            const createSelect = document.getElementById('component_product_id');
            if (createSelect && createSelect.options.length > 1) {
                // Копируем все опции кроме пустой первой
                for (let i = 0; i < createSelect.options.length; i++) {
                    const option = createSelect.options[i];
                    if (option.value && option.value !== '') {
                        // Проверяем, не добавлен ли уже этот продукт
                        const isAlreadyAdded = editComponents.some(c => c.product_id == option.value);
                        if (!isAlreadyAdded) {
                            const newOption = new Option(option.text, option.value);
                            newOption.dataset.unit = option.dataset.unit;
                            newOption.dataset.cost = option.dataset.cost || '0';
                            editComponentProductSelect.add(newOption);
                        }
                    }
                }
            }
        }
        
        // Добавление компонента
        function addEditComponent() {
            const productId = editComponentProductSelect.value;
            const quantity = parseFloat(editComponentQuantityInput.value);
            
            if (!productId || !quantity || quantity <= 0) {
                return; // Никаких уведомлений
            }
            
            const selectedOption = editComponentProductSelect.options[editComponentProductSelect.selectedIndex];
            const productName = selectedOption.textContent.split(' (')[0];
            const productUnit = selectedOption.dataset.unit;
            const productCost = parseFloat(selectedOption.dataset.cost) || 0;
            
            // Проверка на дублирование
            if (editComponents.some(c => c.product_id == productId)) {
                return; // Никаких уведомлений
            }
            
            // Упрощенный расчет стоимости компонента
            const totalCost = calculateEditComponentCost(productCost, quantity);
            
            const component = {
                id: Date.now(),
                product_id: parseInt(productId),
                product_name: productName,
                quantity: quantity,
                unit: productUnit,
                cost: productCost,
                total_cost: totalCost
            };
            
            editComponents.push(component);
            renderEditComponents();
            updateEditCostCalculation();
            populateEditComponentSelect(); // Обновляем селект
            
            // Очистка формы
            editComponentQuantityInput.value = '';
            editComponentProductSelect.value = '';
        }
        
        // Удаление компонента
        function removeEditComponent(componentId) {
            editComponents = editComponents.filter(c => c.id !== componentId);
            renderEditComponents();
            updateEditCostCalculation();
            populateEditComponentSelect(); // Обновляем селект
        }
        
        // Отображение списка компонентов
        function renderEditComponents() {
            if (!editComponentsList || !editNoComponentsMessage || !editComponentsCostInfo) return;
            
            editComponentsList.innerHTML = '';
            
            if (editComponents.length === 0) {
                editNoComponentsMessage.style.display = 'block';
                editComponentsCostInfo.style.display = 'none';
                return;
            }
            
            editNoComponentsMessage.style.display = 'none';
            
            editComponents.forEach(component => {
                const componentElement = document.createElement('div');
                componentElement.className = 'card mb-2 border-warning';
                componentElement.innerHTML = `
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="small">${component.product_name}</strong>
                                <div class="text-muted small">
                                    ${component.quantity} ${component.unit}
                                    ${component.cost > 0 ? '× ' + component.cost.toFixed(2) + ' ₽ = ' + component.total_cost.toFixed(2) + ' ₽' : ''}
                                </div>
                            </div>
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm remove-edit-component" 
                                    data-id="${component.id}"
                                    style="padding: 0.15rem 0.5rem;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                editComponentsList.appendChild(componentElement);
            });
            
            editComponentsCostInfo.style.display = 'block';
        }
        
        // Обновление расчета себестоимости
        function updateEditCostCalculation() {
            if (!editCostInput || !editCostSourceHint || !editComponentsTotalCost) return;
            
            const totalComponentsCost = editComponents.reduce((sum, c) => {
                const cost = parseFloat(c.total_cost) || 0;
                return sum + cost;
            }, 0);
            
            if (totalComponentsCost > 0) {
                editComponentsTotalCost.textContent = totalComponentsCost.toFixed(2) + ' ₽';
                
                // Автоматически устанавливаем себестоимость из компонентов
                // НЕ перезаписываем если пользователь уже ввел значение вручную
                const currentCostValue = parseFloat(editCostInput.value) || 0;
                if (!currentCostValue || currentCostValue === 0) {
                    editCostInput.value = totalComponentsCost.toFixed(2);
                }
                editCostSourceHint.textContent = 'Рассчитана из компонентов: ' + totalComponentsCost.toFixed(2) + ' ₽';
                editCostSourceHint.className = 'text-success d-block';
            } else {
                editCostSourceHint.textContent = 'Укажите или добавьте компоненты';
                editCostSourceHint.className = 'text-muted d-block';
            }
        }
        
        // Подготовка данных компонентов для отправки
        function prepareEditComponentsData() {
            const componentsToSend = editComponents.map(c => ({
                product_id: c.product_id,
                quantity: c.quantity
            }));
            
            // Находим или создаем поле components_data
            let componentsField = document.getElementById('edit_components_data');
            
            // Если поле не найдено, создаем его
            if (!componentsField) {
                componentsField = document.createElement('input');
                componentsField.type = 'hidden';
                componentsField.name = 'components_data';
                componentsField.id = 'edit_components_data';
                editProductForm.appendChild(componentsField);
            }
            
            // Записываем JSON с компонентами
            componentsField.value = JSON.stringify(componentsToSend);
        }
        
        // Слушатели событий для редактирования
        if (editAddComponentBtn) {
            editAddComponentBtn.addEventListener('click', addEditComponent);
        }
        
        // Делегирование событий для удаления компонентов
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-edit-component')) {
                const componentId = parseInt(e.target.closest('.remove-edit-component').dataset.id);
                removeEditComponent(componentId);
            }
        });
        
        if (editComponentQuantityInput) {
            editComponentQuantityInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addEditComponent();
                }
            });
        }
        
        if (editProductForm) {
            editProductForm.addEventListener('submit', function(e) {
                prepareEditComponentsData();
            });
        }
        
        // Обработчик открытия модалки редактирования
        editProductModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button || !button.classList.contains('edit-product-btn')) return;
            
            // Загружаем компоненты из data-атрибута
            try {
                if (button.dataset.components && button.dataset.components !== 'null') {
                    const parsedComponents = JSON.parse(button.dataset.components);
                    
                    editComponents = parsedComponents.map(component => {
                        const cost = parseFloat(component.cost) || 0;
                        const quantity = parseFloat(component.quantity) || 0;
                        const unit = component.unit || '';
                        
                        // Упрощенный расчет как в создании
                        let total_cost = cost * quantity;
                        
                        return {
                            id: parseInt(component.id) || Date.now(),
                            product_id: parseInt(component.product_id) || 0,
                            product_name: component.product_name || 'Неизвестно',
                            quantity: quantity,
                            unit: unit,
                            cost: cost,
                            total_cost: parseFloat(total_cost.toFixed(4))
                        };
                    });
                } else {
                    editComponents = [];
                }
            } catch (e) {
                console.error('Ошибка парсинга компонентов:', e);
                editComponents = [];
            }
            
            // Обновляем UI
            populateEditComponentSelect();
            renderEditComponents();
            updateEditCostCalculation();
        });
        
        // Сброс данных при закрытии модалки
        editProductModal.addEventListener('hidden.bs.modal', function() {
            // Сброс данных
            editComponents = [];
            
            // Удаляем скрытое поле если оно было создано
            const existingField = document.getElementById('edit_components_data');
            if (existingField) {
                existingField.remove();
            }
            
            // Сбрасываем UI
            if (editNoComponentsMessage) {
                editNoComponentsMessage.style.display = 'block';
            }
            if (editComponentsList) {
                editComponentsList.innerHTML = '';
            }
            if (editComponentsCostInfo) {
                editComponentsCostInfo.style.display = 'none';
            }
            if (editComponentProductSelect) {
                editComponentProductSelect.innerHTML = '<option value="">Выберите продукт</option>';
            }
            
            // Сбрасываем подсказку себестоимости
            if (editCostSourceHint) {
                editCostSourceHint.textContent = 'Укажите или добавьте компоненты';
                editCostSourceHint.className = 'text-muted d-block';
            }
        });
        
        // Первоначальная инициализация
        setTimeout(() => {
            populateEditComponentSelect();
        }, 100);
    }
    
    // ========== ОБЩИЕ ФУНКЦИИ ==========
    
    // Автоматическое скрытие алертов через 5 секунд
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>




<style>
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}
</style>

@endsection