/**
 * ProductManager - управление товарами для столов
 */
class ProductManager {
    constructor() {
        this.currentTableId = null;
        this.currentSaleId = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        console.log('ProductManager initialized');
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
    }
    
    handleProductSelectChange() {
        const productSelect = document.getElementById('productSelect');
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const unit = selectedOption.dataset.unit;
        const price = selectedOption.dataset.price;
        
        // Устанавливаем цену по умолчанию
        const priceInput = document.getElementById('productPrice');
        if (priceInput && price) {
            priceInput.value = price;
        }
        
        // Обновляем подсказку
        const hint = document.getElementById('quantityHint');
        if (hint) {
            if (unit === 'шт') {
                hint.textContent = 'Количество должно быть целым числом';
                hint.className = 'text-info';
            } else {
                hint.textContent = `Единица измерения: ${unit}`;
                hint.className = 'text-muted';
            }
        }
        
        // Устанавливаем правильный step для input
        const quantityInput = document.getElementById('productQuantity');
        if (quantityInput) {
            quantityInput.step = unit === 'шт' ? '1' : '0.001';
            quantityInput.min = unit === 'шт' ? '1' : '0.001';
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
        
        const requestData = {
            product_id: productId,
            quantity: quantity,
            unit_price: price
        };
        
        console.log('Adding product:', requestData);
        
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
            }
        } catch (error) {
            console.error('Error adding product:', error);
            
            // Обработка ошибки недостатка товара
            if (error.data && error.data.message && error.data.message.includes('Недостаточно товара')) {
                this.showStockWarning(error.data, selectedOption);
            } else {
                this.showToast('danger', 'Ошибка', 'Не удалось добавить товар');
            }
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
        const productSelect = document.getElementById('productSelect');
        const quantityInput = document.getElementById('productQuantity');
        const priceInput = document.getElementById('productPrice');
        const hint = document.getElementById('quantityHint');
        
        if (productSelect) productSelect.value = '';
        if (quantityInput) quantityInput.value = '1';
        if (priceInput) priceInput.value = '';
        if (hint) {
            hint.textContent = '';
            hint.className = 'text-muted';
        }
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
        
        throw new Error('TableManager.makeRequest not available');
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