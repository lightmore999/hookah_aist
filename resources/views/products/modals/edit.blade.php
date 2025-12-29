<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editProductModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Редактировать товар
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            
            <form id="editProductForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="modal-body">
                    <div class="row">
                        <!-- Левая колонка - Основные данные -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_product_name" class="form-label fw-bold">Название товара *</label>
                                <input type="text" 
                                    class="form-control" 
                                    id="edit_product_name" 
                                    name="name" 
                                    placeholder="Название товара" 
                                    required>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="edit_product_price" class="form-label fw-bold">Цена (₽) *</label>
                                    <div class="input-group">
                                        <input type="number" 
                                            min="0" 
                                            step="0.01"
                                            class="form-control" 
                                            id="edit_product_price" 
                                            name="price" 
                                            placeholder="0.00" 
                                            required>
                                        <span class="input-group-text">₽</span>
                                    </div>
                                    <div class="form-text">Цена за указанную единицу</div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit_product_cost" class="form-label fw-bold">Себестоимость (₽) *</label>
                                    <div class="input-group">
                                        <input type="number" 
                                            min="0" 
                                            step="0.01"
                                            class="form-control" 
                                            id="edit_product_cost" 
                                            name="cost" 
                                            placeholder="0.00" 
                                            required>
                                        <span class="input-group-text">₽</span>
                                    </div>
                                    <small class="text-muted d-block" id="edit_cost_source_hint">Себестоимость продукта</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_product_unit" class="form-label fw-bold">Единица измерения *</label>
                                <select name="unit" id="edit_product_unit" class="form-select" required>
                                    <option value="">Выберите единицу</option>
                                    <option value="шт">Штуки (шт)</option>
                                    <option value="г">Граммы (г)</option>
                                    <option value="мл">Миллилитры (мл)</option>
                                    <option value="кг">Килограммы (кг)</option>
                                    <option value="л">Литры (л)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit_product_category_id" class="form-label fw-bold">Категория *</label>
                                <select name="product_category_id" id="edit_product_category_id" class="form-select" required>
                                    <option value="">Выберите категорию</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="edit_product_article" class="form-label">Артикул</label>
                                    <input type="text" 
                                        class="form-control" 
                                        id="edit_product_article" 
                                        name="article_number" 
                                        placeholder="Артикул">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="edit_product_barcode" class="form-label">Штрихкод</label>
                                    <input type="text" 
                                        class="form-control" 
                                        id="edit_product_barcode" 
                                        name="barcode" 
                                        placeholder="Штрихкод">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Правая колонка - Компоненты -->
                        <div class="col-md-6">
                            <div class="card h-100 border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">
                                        <i class="bi bi-list-check me-2"></i>Компоненты продукта
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="edit_components_list_container">
                                        <div class="mb-3">
                                            <h6>Добавленные компоненты:</h6>
                                            <div id="edit_components_list" class="mb-3">
                                                <!-- Компоненты будут здесь -->
                                            </div>
                                            
                                            <div id="edit_no_components_message" class="text-muted small">
                                                Нет добавленных компонентов
                                            </div>
                                            
                                            <div class="alert alert-secondary py-2" id="edit_components_cost_info" style="display: none;">
                                                <div class="d-flex justify-content-between">
                                                    <span>Стоимость компонентов:</span>
                                                    <strong id="edit_components_total_cost">0.00 ₽</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card border-dashed">
                                        <div class="card-body">
                                            <h6 class="mb-3">Добавить компонент</h6>
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-8">
                                                    <select class="form-select form-select-sm" id="edit_component_product_id">
                                                        <option value="">Загрузка продуктов...</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" 
                                                        class="form-control form-control-sm" 
                                                        id="edit_component_quantity" 
                                                        step="0.001" 
                                                        min="0.001" 
                                                        placeholder="Кол-во">
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-warning w-100" id="edit_add_component_btn">
                                                <i class="bi bi-plus-circle me-1"></i>Добавить компонент
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-warning">Обновить товар</button>
                </div>
            </form>
        </div>
    </div>
</div>