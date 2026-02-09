/**
 * ProductManager - управление товарами для столов с фильтрацией
 */
class ProductManager {
    constructor() {
        this.currentTableId = null;
        this.currentSaleId = null;
        this.allProducts = [];
        this.filteredProducts = [];
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeProducts();
        console.log('ProductManager initialized');
    }
    
    initializeProducts() {
        const productSelect = document.getElementById('productSelect');
        if (productSelect) {
            // Собираем все товары в массив
            productSelect.querySelectorAll('option').forEach(option => {
                if (option.value) {
                    this.allProducts.push({
                        element: option,
                        id: option.value,
                        text: option.textContent.toLowerCase(),
                        category: option.dataset.category || 'null',
                        categoryName: option.dataset.categoryName || 'Без категории',
                        price: parseFloat(option.dataset.price) || 0,
                        unit: option.dataset.unit || 'шт',
                        isComposite: option.dataset.isComposite === '1',
                        available: parseFloat(option.dataset.available) || 0,
                        disabled: option.disabled
                    });
                }
            });
            
            console.log('Загружено товаров:', this.allProducts.length);
        }
    }
    
    bindEvents() {
        // Обработчик открытия модалки товаров
        const saleProductsModal = document.getElementById('saleProductsModal');
        if (saleProductsModal) {
            saleProductsModal.addEventListener('show.bs.modal', (event) => {
                this.handleModalShow(event);
            });
            
            saleProductsModal.addEventListener('hidden.bs.modal', () => {
                this.currentTableId = null;
                this.currentSaleId = null;
                this.resetProductForm();
            });
        }
        
        // Обработчик выбора товара
        const productSelect = document.getElementById('productSelect');
        if (productSelect) {
            productSelect.addEventListener('change', () => {
                this.handleProductSelectChange();
            });
        }
        
        // Обработчик добавления товара
        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'addProductBtn') {
                e.preventDefault();
                this.addProduct();
            }
        });
        
        // Обработчик удаления товара
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-product-btn')) {
                const button = e.target.closest('.remove-product-btn');
                const itemId = button.dataset.itemId;
                
                if (!itemId || !this.currentTableId) return;
                
                this.removeProduct(itemId);
            }
        });
        
        // Обработчики фильтрации
        const categoryFilter = document.getElementById('categoryFilterProducts');
        const searchInput = document.getElementById('searchTableProduct');
        const clearSearchBtn = document.getElementById('clearTableSearch');
        
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => {
                this.filterProducts();
            });
        }
        
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.filterProducts();
            });
        }
        
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }
    }
    
    handleModalShow(event) {
        const button = event.relatedTarget;
        if (!button) return;
        
        this.currentTableId = button.getAttribute('data-table-id');
        this.currentSaleId = button.getAttribute('data-sale-id');
        
        if (!this.currentTableId) {
            console.error('Table ID not found');
            this.showToast('warning', 'Внимание', 'ID стола не найден');
            return;
        }
        
        // Загружаем данные через AJAX
        this.loadSaleItems();
        
        // Применяем фильтрацию товаров
        setTimeout(() => {
            this.filterProducts();
        }, 100);
    }
    
    handleProductSelectChange() {
        const productSelect = document.getElementById('productSelect');
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        
        // Получаем элементы DOM для нового блока отображения
        const selectedProductName = document.getElementById('selectedProductName');
        const selectedProductDetails = document.getElementById('selectedProductDetails');
        const selectedProductPrice = document.getElementById('selectedProductPrice');
        const selectedProductStock = document.getElementById('selectedProductStock');
        const selectedProductAvailable = document.getElementById('selectedProductAvailable');
        const selectedProductUnit = document.getElementById('selectedProductUnit');
        const priceInput = document.getElementById('productPrice');
        const quantityHint = document.getElementById('quantityHint');
        const quantityInput = document.getElementById('productQuantity');
        
        // Если товар не выбран
        if (!selectedOption || !selectedOption.value) {
            this.hideAvailabilityWarning();
            
            // Сбрасываем блок отображения
            if (selectedProductName) selectedProductName.textContent = 'Товар не выбран';
            if (selectedProductDetails) selectedProductDetails.textContent = 'Выберите товар из списка';
            if (selectedProductPrice) selectedProductPrice.textContent = '- ₽';
            if (selectedProductStock) selectedProductStock.style.display = 'none';
            
            // Сбрасываем поля формы
            if (priceInput) priceInput.value = '';
            if (quantityInput) quantityInput.value = '1';
            if (quantityHint) {
                quantityHint.textContent = '';
                quantityHint.className = 'text-muted';
            }
            
            return;
        }
        
        // Получаем данные из выбранного товара
        const unit = selectedOption.dataset.unit || 'шт';
        const price = selectedOption.dataset.price || 0;
        const available = parseFloat(selectedOption.dataset.available) || 0;
        const fullText = selectedOption.textContent;
        const productName = fullText.split(' (')[0].trim();
        const categoryName = selectedOption.dataset.categoryName || 'Без категории';
        
        // Обновляем блок отображения выбранного товара
        if (selectedProductName) selectedProductName.textContent = productName;
        if (selectedProductDetails) selectedProductDetails.textContent = categoryName;
        if (selectedProductPrice) selectedProductPrice.textContent = parseFloat(price).toFixed(2) + ' ₽';
        
        // Обновляем информацию о наличии
        if (selectedProductStock && selectedProductAvailable && selectedProductUnit) {
            if (available > 0) {
                selectedProductAvailable.textContent = available;
                selectedProductUnit.textContent = unit;
                selectedProductStock.style.display = 'block';
            } else {
                selectedProductStock.style.display = 'none';
            }
        }
        
        // Заполняем поле цены
        if (priceInput && price) {
            priceInput.value = parseFloat(price).toFixed(2);
        }
        
        // Обновляем подсказку для количества
        if (quantityHint) {
            if (unit === 'шт') {
                quantityHint.textContent = 'Количество должно быть целым числом';
                quantityHint.className = 'text-info';
            } else {
                quantityHint.textContent = `Единица измерения: ${unit}`;
                quantityHint.className = 'text-muted';
            }
        }
        
        // Устанавливаем правильные атрибуты для поля количества
        if (quantityInput) {
            if (unit === 'шт') {
                quantityInput.step = '1';
                quantityInput.min = '1';
            } else {
                quantityInput.step = '0.001';
                quantityInput.min = '0.001';
            }
        }
        
        // Показываем информацию о доступности
        this.showAvailabilityInfo(available, unit);
    }
    
    showAvailabilityInfo(available, unit) {
        const availabilityInfo = document.getElementById('productAvailabilityInfo');
        const availabilityMessage = document.getElementById('availabilityMessage');
        
        if (!availabilityInfo || !availabilityMessage) return;
        
        if (available <= 0) {
            availabilityInfo.style.display = 'block';
            availabilityInfo.className = 'alert alert-danger mt-2 p-2';
            availabilityMessage.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i> Товар отсутствует на складе`;
        } else {
            availabilityInfo.style.display = 'block';
            availabilityInfo.className = 'alert alert-success mt-2 p-2';
            availabilityMessage.innerHTML = `<i class="bi bi-check-circle me-1"></i> Доступно: ${available} ${unit}`;
        }
    }
    
    hideAvailabilityWarning() {
        const availabilityInfo = document.getElementById('productAvailabilityInfo');
        if (availabilityInfo) {
            availabilityInfo.style.display = 'none';
        }
    }
    
    filterProducts() {
        const categoryFilter = document.getElementById('categoryFilterProducts');
        const searchInput = document.getElementById('searchTableProduct');
        const productSelect = document.getElementById('productSelect');
        
        if (!categoryFilter || !searchInput || !productSelect) {
            console.error('Элементы фильтрации не найдены');
            return;
        }
        
        const selectedCategory = categoryFilter.value;
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        // Показываем все опции
        for (let i = 1; i < productSelect.options.length; i++) {
            productSelect.options[i].style.display = '';
        }
        
        let visibleCount = 0;
        let firstVisibleOption = null;
        
        // Фильтруем товары
        for (let i = 1; i < productSelect.options.length; i++) {
            const option = productSelect.options[i];
            if (!option.value) continue;
            
            const category = option.dataset.category || 'null';
            const text = option.textContent.toLowerCase();
            
            let shouldShow = true;
            
            // Фильтр по категории
            if (selectedCategory !== 'all') {
                if (selectedCategory === 'null') {
                    shouldShow = category === 'null';
                } else {
                    shouldShow = category === selectedCategory;
                }
            }
            
            // Фильтр по поиску
            if (shouldShow && searchTerm) {
                shouldShow = text.includes(searchTerm);
            }
            
            // Показываем/скрываем элемент
            if (shouldShow) {
                option.style.display = '';
                visibleCount++;
                
                if (!firstVisibleOption) {
                    firstVisibleOption = option;
                }
            } else {
                option.style.display = 'none';
            }
        }
        
        // Автоматически выбираем первый видимый товар если есть фильтры
        if ((selectedCategory !== 'all' || searchTerm) && firstVisibleOption) {
            productSelect.value = firstVisibleOption.value;
            this.handleProductSelectChange();
        }
        
        // Сообщение если ничего не найдено
        this.showNoProductsWarning(visibleCount === 0 && productSelect.options.length > 1);
    }
    
    showNoProductsWarning(show) {
        const productSelect = document.getElementById('productSelect');
        if (!productSelect) return;
        
        const existingWarning = productSelect.parentNode.querySelector('.alert-warning:not(#productAvailabilityInfo)');
        
        if (show && !existingWarning) {
            const warningDiv = document.createElement('div');
            warningDiv.className = 'alert alert-warning mt-2';
            warningDiv.textContent = 'Товары не найдены';
            productSelect.parentNode.insertBefore(warningDiv, productSelect.nextSibling);
        } else if (existingWarning && !show) {
            existingWarning.remove();
        }
    }
    
    clearSearch() {
        const searchInput = document.getElementById('searchTableProduct');
        if (searchInput) {
            searchInput.value = '';
            this.filterProducts();
        }
    }
    
    async loadSaleItems() {
        if (!this.currentTableId) return;
        
        try {
            const data = await this.makeRequest(`/tables/${this.currentTableId}/get-sale-items`);
            
            if (data.success) {
                this.updateSaleItemsTable(data.items, data.total);
                this.updateModalInfo(data);
            } else {
                this.showToast('danger', 'Ошибка', data.message || 'Не удалось загрузить товары');
            }
        } catch (error) {
            console.error('Error loading sale items:', error);
            this.showToast('danger', 'Ошибка', 'Не удалось загрузить данные');
        }
    }
    
    updateModalInfo(data) {
        const titleElement = document.getElementById('saleProductsModalLabel');
        const infoElement = document.getElementById('saleProductsInfo');
        
        if (titleElement && data.tableInfo) {
            titleElement.textContent = `Товары для стола #${data.tableInfo.tableNumber} - ${data.tableInfo.guestName}`;
        }
        
        if (infoElement) {
            infoElement.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                <strong>Продажа #${this.currentSaleId || data.saleId || 'Новая'}</strong> - ${data.tableInfo?.guestName || 'Клиент'}
            `;
        }
    }
    
    updateSaleItemsTable(items, total) {
        const tbody = document.getElementById('productsTableBody');
        const totalElement = document.getElementById('totalAmount');
        
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (items.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-cart-x me-2"></i>
                    Товары не добавлены
                </td>
            `;
            tbody.appendChild(emptyRow);
        } else {
            items.forEach(item => {
                const row = document.createElement('tr');
                row.id = `productRow${item.id}`;
                row.innerHTML = `
                    <td>${item.product_name}</td>
                    <td>
                        <span class="fw-bold">${parseFloat(item.quantity).toLocaleString('ru-RU')}</span>
                        <small class="text-muted ms-1">${item.unit}</small>
                    </td>
                    <td>${parseFloat(item.unit_price).toFixed(2)} ₽</td>
                    <td>${parseFloat(item.total).toFixed(2)} ₽</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger remove-product-btn" 
                                data-item-id="${item.id}"
                                title="Удалить">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        if (totalElement) {
            totalElement.textContent = parseFloat(total).toFixed(2);
        }
    }
    
    async addProduct() {
        const productSelect = document.getElementById('productSelect');
        const quantityInput = document.getElementById('productQuantity');
        const priceInput = document.getElementById('productPrice');
        
        if (!this.currentTableId) {
            this.showToast('warning', 'Внимание', 'Стол не выбран');
            return;
        }
        
        const productId = productSelect.value;
        const quantity = parseFloat(quantityInput.value);
        const price = parseFloat(priceInput.value);
        
        // Валидация
        if (!productId) {
            this.showToast('warning', 'Внимание', 'Выберите товар');
            productSelect.focus();
            return;
        }
        
        if (!quantity || quantity <= 0) {
            this.showToast('warning', 'Внимание', 'Введите корректное количество');
            quantityInput.focus();
            return;
        }
        
        if (!price || price <= 0) {
            this.showToast('warning', 'Внимание', 'Введите корректную цену');
            priceInput.focus();
            return;
        }
        
        // Проверка для штучных товаров
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const unit = selectedOption.dataset.unit;
        
        if (unit === 'шт' && !Number.isInteger(quantity)) {
            this.showToast('warning', 'Внимание', 'Для штучных товаров количество должно быть целым числом');
            quantityInput.value = Math.round(quantity);
            return;
        }
        
        // Проверка доступности товара
        const available = parseFloat(selectedOption.dataset.available) || 0;
        if (available < quantity) {
            if (available > 0) {
                const addAvailable = confirm(`Доступно только ${available} ${unit}. Добавить это количество?`);
                if (addAvailable) {
                    quantityInput.value = available;
                    return this.addProduct(); // Рекурсивный вызов с обновленным количеством
                }
                return;
            } else {
                this.showToast('warning', 'Внимание', 'Товар отсутствует на складе');
                return;
            }
        }
        
        const requestData = {
            product_id: productId,
            quantity: quantity,
            unit_price: price
        };
        
        console.log('Adding product:', requestData);
        
        // Показываем индикатор загрузки
        const addBtn = document.getElementById('addProductBtn');
        const originalHtml = addBtn.innerHTML;
        addBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Добавление...';
        addBtn.disabled = true;
        
        try {
            const data = await this.makeRequest(`/tables/${this.currentTableId}/add-product`, {
                method: 'POST',
                body: JSON.stringify(requestData)
            });
            
            if (data.success) {
                this.showToast('success', 'Успех', 'Товар добавлен');
                
                // Обновляем список товаров
                this.loadSaleItems();
                
                // Обновляем сумму в ячейке стола
                if (data.newTotal !== undefined && window.TableManager && window.TableManager.updateTableTotal) {
                    window.TableManager.updateTableTotal(this.currentTableId, data.newTotal);
                }
                
                // Сбрасываем форму
                this.resetProductForm();
            } else {
                this.showToast('danger', 'Ошибка', data.message || 'Не удалось добавить товар');
                
                // Предлагаем добавить доступное количество
                if (data.details && data.details.can_add_max && data.details.available > 0) {
                    if (confirm(`Доступно только ${data.details.available} ${data.details.unit}. Добавить это количество?`)) {
                        quantityInput.value = data.details.available;
                        setTimeout(() => this.addProduct(), 100);
                    }
                }
            }
        } catch (error) {
            console.error('Error adding product:', error);
            
            // Обработка ошибки недостатка товара
            if (error.data && error.data.message && error.data.message.includes('Недостаточно товара')) {
                this.showStockWarning(error.data, selectedOption);
            } else {
                this.showToast('danger', 'Ошибка', 'Не удалось добавить товар: ' + error.message);
            }
        } finally {
            // Восстанавливаем кнопку
            addBtn.innerHTML = originalHtml;
            addBtn.disabled = false;
        }
    }
    
    async removeProduct(itemId) {
        if (!confirm('Вы уверены, что хотите удалить этот товар?')) {
            return;
        }
        
        try {
            const data = await this.makeRequest(`/tables/${this.currentTableId}/remove-product/${itemId}`, {
                method: 'DELETE'
            });
            
            if (data.success) {
                this.showToast('success', 'Успех', 'Товар удален');
                
                // Удаляем строку из таблицы
                const row = document.getElementById(`productRow${itemId}`);
                if (row) {
                    row.remove();
                }
                
                // Обновляем итоговую сумму в модалке
                const totalElement = document.getElementById('totalAmount');
                if (totalElement && data.total !== undefined) {
                    totalElement.textContent = parseFloat(data.total).toFixed(2);
                }
                
                // Обновляем сумму в ячейке стола
                if (data.newTotal !== undefined && window.TableManager && window.TableManager.updateTableTotal) {
                    window.TableManager.updateTableTotal(this.currentTableId, data.newTotal);
                }
                
                // Если товаров не осталось, показываем сообщение
                const tbody = document.getElementById('productsTableBody');
                if (tbody && tbody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-cart-x me-2"></i>
                            Товары не добавлены
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
            } else {
                this.showToast('danger', 'Ошибка', data.message || 'Не удалось удалить товар');
            }
        } catch (error) {
            console.error('Error removing product:', error);
            this.showToast('danger', 'Ошибка', 'Не удалось удалить товар');
        }
    }
    
    resetProductForm() {
        // Получаем все необходимые элементы
        const productSelect = document.getElementById('productSelect');
        const quantityInput = document.getElementById('productQuantity');
        const priceInput = document.getElementById('productPrice');
        const quantityHint = document.getElementById('quantityHint');
        const selectedProductName = document.getElementById('selectedProductName');
        const selectedProductDetails = document.getElementById('selectedProductDetails');
        const selectedProductPrice = document.getElementById('selectedProductPrice');
        const selectedProductStock = document.getElementById('selectedProductStock');
        
        // Сбрасываем select
        if (productSelect) {
            productSelect.value = '';
        }
        
        // Сбрасываем поля ввода
        if (quantityInput) {
            quantityInput.value = '1';
            quantityInput.step = 'any';
            quantityInput.min = '0.001';
        }
        
        if (priceInput) {
            priceInput.value = '';
        }
        
        // Сбрасываем подсказку
        if (quantityHint) {
            quantityHint.textContent = '';
            quantityHint.className = 'text-muted';
        }
        
        // Сбрасываем блок отображения выбранного товара
        if (selectedProductName) {
            selectedProductName.textContent = 'Товар не выбран';
        }
        
        if (selectedProductDetails) {
            selectedProductDetails.textContent = 'Выберите товар из списка';
        }
        
        if (selectedProductPrice) {
            selectedProductPrice.textContent = '- ₽';
        }
        
        if (selectedProductStock) {
            selectedProductStock.style.display = 'none';
        }
        
        // Скрываем предупреждение о доступности
        this.hideAvailabilityWarning();
    }
    
    showStockWarning(errorData, selectedOption) {
        const productName = selectedOption.text.split(' - ')[0];
        const unit = selectedOption.dataset.unit;
        const available = errorData.available || errorData.details?.available || 0;
        const requested = errorData.requested || errorData.details?.requested || 0;
        
        let errorMessage = `Недостаточно товара на складе:<br>`;
        errorMessage += `<strong>${productName}</strong><br>`;
        errorMessage += `Запрошено: <span class="text-danger">${requested} ${unit}</span><br>`;
        errorMessage += `Доступно: <span class="text-success">${available} ${unit}</span>`;
        
        this.showCustomToast('danger', 'Недостаточно товара', errorMessage, true);
    }
    
    showCustomToast(type, title, message, isHtml = false) {
        if (window.TableManager && window.TableManager.showCustomToast) {
            window.TableManager.showCustomToast(type, title, message, isHtml);
            return;
        }
        
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast" role="alert">
                <div class="toast-header bg-${type} text-white">
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body ${isHtml ? '' : 'text-bg-' + type}">
                    ${isHtml ? message : `<strong>${title}:</strong> ${message}`}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: isHtml ? false : true,
            delay: isHtml ? 8000 : 3000
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
    // Вспомогательные методы
    async makeRequest(url, options = {}) {
        if (window.TableManager && window.TableManager.makeRequest) {
            return window.TableManager.makeRequest(url, options);
        }
        
        // Если TableManager не доступен, используем fetch
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
        }
        
        return await response.json();
    }
    
    showToast(type, title, message) {
        if (window.TableManager && window.TableManager.showToast) {
            window.TableManager.showToast(type, title, message);
            return;
        }
        
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}:</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
}

// Экспорт
window.ProductManager = ProductManager;