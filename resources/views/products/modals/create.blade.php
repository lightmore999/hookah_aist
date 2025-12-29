<div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createProductModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Добавить товар
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form action="{{ route('products.store') }}" method="POST" id="createProductForm">
                @csrf
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Левая колонка - Основные данные -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-bold">Название товара *</label>
                                <input type="text" 
                                    class="form-control @error('name') is-invalid @enderror" 
                                    id="name" 
                                    name="name" 
                                    value="{{ old('name') }}" 
                                    placeholder="Например: Табак Al Fakher яблоко" 
                                    required
                                    autofocus>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="price" class="form-label fw-bold">Цена (₽) *</label>
                                    <div class="input-group">
                                        <input type="number" 
                                            min="0" 
                                            step="0.01"
                                            class="form-control @error('price') is-invalid @enderror" 
                                            id="price" 
                                            name="price" 
                                            value="{{ old('price') }}" 
                                            placeholder="0.00" 
                                            required>
                                        <span class="input-group-text">₽</span>
                                        @error('price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-text">Цена за указанную единицу</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cost" class="form-label fw-bold">Себестоимость (₽) *</label>
                                    <div class="input-group">
                                        <input type="number" 
                                            min="0" 
                                            step="0.01"
                                            class="form-control @error('cost') is-invalid @enderror" 
                                            id="cost" 
                                            name="cost" 
                                            value="{{ old('cost') }}" 
                                            placeholder="0.00" 
                                            required>
                                        <span class="input-group-text">₽</span>
                                        @error('cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="text-muted d-block" id="cost_source_hint">Укажите или добавьте компоненты</small>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="unit" class="form-label fw-bold">Единица измерения *</label>
                                    <select name="unit" id="unit" 
                                        class="form-select @error('unit') is-invalid @enderror" 
                                        required>
                                        <option value="">Выберите единицу</option>
                                        <option value="шт" {{ old('unit') == 'шт' ? 'selected' : '' }}>Штуки (шт)</option>
                                        <option value="г" {{ old('unit') == 'г' ? 'selected' : '' }}>Граммы (г)</option>
                                        <option value="мл" {{ old('unit') == 'мл' ? 'selected' : '' }}>Миллилитры (мл)</option>
                                        <option value="кг" {{ old('unit') == 'кг' ? 'selected' : '' }}>Килограммы (кг)</option>
                                        <option value="л" {{ old('unit') == 'л' ? 'selected' : '' }}>Литры (л)</option>
                                    </select>
                                    @error('unit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="product_category_id" class="form-label fw-bold">Категория *</label>
                                <select name="product_category_id" id="product_category_id" 
                                    class="form-select @error('product_category_id') is-invalid @enderror" 
                                    required>
                                    <option value="">Выберите категорию</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('product_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('product_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="article_number" class="form-label">Артикул</label>
                                    <input type="text" 
                                        class="form-control @error('article_number') is-invalid @enderror" 
                                        id="article_number" 
                                        name="article_number" 
                                        value="{{ old('article_number') }}" 
                                        placeholder="Артикул поставщика">
                                    @error('article_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="barcode" class="form-label">Штрихкод</label>
                                    <input type="text" 
                                        class="form-control @error('barcode') is-invalid @enderror" 
                                        id="barcode" 
                                        name="barcode" 
                                        value="{{ old('barcode') }}" 
                                        placeholder="Штрихкод">
                                    @error('barcode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Правая колонка - Компоненты -->
                        <div class="col-md-6">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="bi bi-list-check me-2"></i>Компоненты продукта (не обязательно)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="components-list-container">
                                        <div class="mb-3">
                                            <h6>Добавленные компоненты:</h6>
                                            <div id="components-list" class="mb-3">
                                                <div class="text-muted small" id="no-components-message">
                                                    Нет добавленных компонентов
                                                </div>
                                            </div>
                                            
                                            <div class="alert alert-secondary py-2" id="components-cost-info" style="display: none;">
                                                <div class="d-flex justify-content-between">
                                                    <span>Стоимость компонентов:</span>
                                                    <strong id="components-total-cost">0.00 ₽</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card border-dashed">
                                        <div class="card-body">
                                            <h6 class="mb-3">Добавить компонент</h6>
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-8">
                                                    <select class="form-select form-select-sm" id="component_product_id">
                                                        <option value="">Выберите продукт</option>
                                                        @foreach($allProducts as $product)
                                                            <option value="{{ $product->id }}" 
                                                                    data-unit="{{ $product->unit }}" 
                                                                    data-cost="{{ $product->cost }}">
                                                                {{ $product->name }} ({{ $product->unit }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" 
                                                           class="form-control form-control-sm" 
                                                           id="component_quantity" 
                                                           step="0.001" 
                                                           min="0.001" 
                                                           placeholder="Кол-во">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="add-component-btn">
                                                <i class="bi bi-plus-circle me-1"></i>Добавить компонент
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="components-data" style="display: none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Создать товар</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.getElementById('createProductForm');
    const componentsList = document.getElementById('components-list');
    const noComponentsMessage = document.getElementById('no-components-message');
    const componentsCostInfo = document.getElementById('components-cost-info');
    const componentsTotalCost = document.getElementById('components-total-cost');
    const componentProductSelect = document.getElementById('component_product_id');
    const componentQuantityInput = document.getElementById('component_quantity');
    const addComponentBtn = document.getElementById('add-component-btn');
    const costInput = document.getElementById('cost');
    const costSourceHint = document.getElementById('cost_source_hint');
    
    let components = [];
    let componentsDataField = null;
    
    // Упрощенная функция расчета стоимости компонента
    function calculateComponentCost(componentCost, quantity) {
        return parseFloat((componentCost * quantity).toFixed(4));
    }
    
    // Создаем скрытое поле для данных компонентов
    function createComponentsDataField() {
        if (!componentsDataField) {
            componentsDataField = document.createElement('input');
            componentsDataField.type = 'hidden';
            componentsDataField.name = 'components_data';
            componentsDataField.id = 'components_data_field';
            productForm.appendChild(componentsDataField);
        }
        return componentsDataField;
    }
    
    // Добавление компонента
    function addComponent() {
        const productId = componentProductSelect.value;
        const quantity = parseFloat(componentQuantityInput.value);
        
        if (!productId || !quantity || quantity <= 0) {
            return; // Никаких уведомлений
        }
        
        const selectedOption = componentProductSelect.options[componentProductSelect.selectedIndex];
        const productName = selectedOption.textContent.split(' (')[0];
        const productUnit = selectedOption.dataset.unit;
        const productCost = parseFloat(selectedOption.dataset.cost) || 0;
        
        // Проверка на дублирование
        if (components.some(c => c.product_id == productId)) {
            return; // Никаких уведомлений
        }
        
        // Упрощенный расчет стоимости
        const totalCost = calculateComponentCost(productCost, quantity);
        
        const component = {
            id: Date.now(),
            product_id: parseInt(productId),
            product_name: productName,
            quantity: quantity,
            unit: productUnit,
            cost: productCost,
            total_cost: totalCost
        };
        
        components.push(component);
        renderComponents();
        updateCostCalculation();
        
        // Очистка формы
        componentQuantityInput.value = '';
        componentProductSelect.value = '';
    }
    
    // Удаление компонента
    function removeComponent(componentId) {
        components = components.filter(c => c.id !== componentId);
        renderComponents();
        updateCostCalculation();
    }
    
    // Отображение списка компонентов
    function renderComponents() {
        componentsList.innerHTML = '';
        
        if (components.length === 0) {
            noComponentsMessage.style.display = 'block';
            componentsCostInfo.style.display = 'none';
            return;
        }
        
        noComponentsMessage.style.display = 'none';
        
        components.forEach(component => {
            const componentElement = document.createElement('div');
            componentElement.className = 'card mb-2';
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
                                class="btn btn-danger btn-sm remove-component" 
                                data-id="${component.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            componentsList.appendChild(componentElement);
        });
        
        componentsCostInfo.style.display = 'block';
    }
    
    // Обновление расчета себестоимости
    function updateCostCalculation() {
        const totalComponentsCost = components.reduce((sum, c) => sum + c.total_cost, 0);
        
        if (totalComponentsCost > 0) {
            componentsTotalCost.textContent = totalComponentsCost.toFixed(2) + ' ₽';
            
            // Автоматически устанавливаем себестоимость из компонентов
            costInput.value = totalComponentsCost.toFixed(2);
            costSourceHint.textContent = 'Рассчитана из компонентов';
            costSourceHint.className = 'text-success d-block';
        } else {
            costSourceHint.textContent = 'Укажите или добавьте компоненты';
            costSourceHint.className = 'text-muted d-block';
        }
    }
    
    // Подготовка данных компонентов для отправки
    function prepareComponentsData() {
        const componentsDataField = createComponentsDataField();
        const componentsToSend = components.map(c => ({
            product_id: c.product_id,
            quantity: c.quantity
        }));
        
        componentsDataField.value = JSON.stringify(componentsToSend);
    }
    
    // Слушатели событий
    addComponentBtn.addEventListener('click', addComponent);
    
    componentsList.addEventListener('click', function(e) {
        if (e.target.closest('.remove-component')) {
            const componentId = parseInt(e.target.closest('.remove-component').dataset.id);
            removeComponent(componentId);
        }
    });
    
    componentQuantityInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addComponent();
        }
    });
    
    productForm.addEventListener('submit', function(e) {
        prepareComponentsData();
    });
    
    // Сброс данных при открытии модалки
    const modal = document.getElementById('createProductModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function() {
            // Сброс данных
            components = [];
            renderComponents();
            costSourceHint.textContent = 'Укажите или добавьте компоненты';
            costSourceHint.className = 'text-muted d-block';
        });
    }
});
</script>

<style>
.border-dashed {
    border: 2px dashed #dee2e6 !important;
}
.card-body {
    padding: 0.75rem !important;
}
.small {
    font-size: 0.85rem !important;
}
</style>