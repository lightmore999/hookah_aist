/**
 * HookahManager - управление кальянами для столов
 */
class HookahManager {
    constructor() {
        this.currentTableId = null;
        this.currentSaleId = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        console.log('HookahManager initialized');
    }
    
    bindEvents() {
        // Обработчик открытия модалки кальянов
        const saleHookahsModal = document.getElementById('saleHookahsModal');
        if (saleHookahsModal) {
            saleHookahsModal.addEventListener('show.bs.modal', (event) => {
                this.handleModalShow(event);
            });
            
            saleHookahsModal.addEventListener('hidden.bs.modal', () => {
                this.currentTableId = null;
                this.currentSaleId = null;
                this.resetForm();
            });
        }
        
        // Обработчик добавления кальяна
        document.addEventListener('click', (e) => {
            if (e.target && e.target.id === 'addHookahBtn') {
                e.preventDefault();
                this.addHookah();
            }
        });
        
        // Обработчик удаления кальяна
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-hookah-btn')) {
                const button = e.target.closest('.remove-hookah-btn');
                const hookahId = button.dataset.hookahId;
                
                if (!hookahId || !this.currentTableId) return;
                
                this.removeHookah(hookahId);
            }
        });
    }
    
    handleModalShow(event) {
        const button = event.relatedTarget;
        if (!button) return;
        
        this.currentTableId = button.getAttribute('data-table-id');
        const tableNumber = button.getAttribute('data-table-number');
        const guestName = button.getAttribute('data-guest-name');
        this.currentSaleId = button.getAttribute('data-sale-id');
        
        if (!this.currentTableId) {
            console.error('Table ID not found');
            this.showToast('warning', 'Внимание', 'ID стола не найден');
            return;
        }
        
        // Обновляем заголовок
        const titleElement = document.querySelector('#saleHookahsModalLabel');
        if (titleElement) {
            titleElement.textContent = `Кальяны для стола #${tableNumber} - ${guestName}`;
        }
        
        // Обновляем информацию
        const infoElement = document.querySelector('#saleHookahsInfo');
        if (infoElement) {
            infoElement.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                <strong>Продажа #${this.currentSaleId || 'Новая'}</strong> - ${guestName}
            `;
        }
        
        // Загружаем кальяны
        this.loadSaleHookahs();
    }
    
    async loadSaleHookahs() {
        if (!this.currentTableId) return;
        
        try {
            const data = await this.makeRequest(`/tables/${this.currentTableId}/get-sale-hookahs`);
            
            if (data.success) {
                this.updateHookahsTable(data.hookahs, data.total);
            } else {
                this.showToast('danger', 'Ошибка', data.message || 'Не удалось загрузить кальяны');
            }
        } catch (error) {
            console.error('Error loading hookahs:', error);
            this.showToast('danger', 'Ошибка', 'Не удалось загрузить данные');
        }
    }
    
    updateHookahsTable(hookahs, total) {
        const tbody = document.getElementById('hookahsTableBody');
        const totalElement = document.getElementById('hookahsTotalAmount');
        
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (hookahs.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="3" class="text-center text-muted py-4">
                    <i class="bi bi-cup-straw me-2"></i>
                    Кальяны не добавлены
                </td>
            `;
            tbody.appendChild(emptyRow);
        } else {
            hookahs.forEach(hookah => {
                const row = document.createElement('tr');
                row.id = `hookahRow${hookah.id}`;
                row.innerHTML = `
                    <td>${hookah.name}</td>
                    <td>${parseFloat(hookah.price).toFixed(0)} ₽</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger remove-hookah-btn" 
                                data-hookah-id="${hookah.id}"
                                title="Удалить">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
        
        if (totalElement) {
            totalElement.textContent = parseFloat(total).toFixed(0);
        }
    }
    
    async addHookah() {
        const hookahSelect = document.getElementById('hookahSelect');
        
        if (!this.currentTableId) {
            this.showToast('warning', 'Внимание', 'Стол не выбран');
            return;
        }
        
        const hookahId = hookahSelect.value;
        
        if (!hookahId) {
            this.showToast('warning', 'Внимание', 'Выберите кальян');
            hookahSelect.focus();
            return;
        }
        
        const requestData = {
            hookah_id: hookahId
        };
        
        console.log('Adding hookah to table:', this.currentTableId, 'Data:', requestData);
        console.log('CSRF token:', this.getCsrfToken()); // Для отладки
        
        try {
            const data = await this.makeRequest(`/tables/${this.currentTableId}/add-hookah`, {
                method: 'POST',
                body: JSON.stringify(requestData)
            });
            
            console.log('Hookah add response:', data);
            
            if (data.success) {
                this.showToast('success', 'Успех', 'Кальян добавлен');
                
                // Останавливаем таймер "требуется кальян"
                if (window.HookahTimerManager && window.HookahTimerManager.stopTimer) {
                    window.HookahTimerManager.stopTimer(this.currentTableId);
                }
                
                console.log('Updating table status to: opened_with_hookah');
                
                // Обновляем интерфейс стола
                this.updateTableInterface(this.currentTableId, {
                    status: 'opened_with_hookah',
                    hasHookah: true
                });
                
                // Обновляем сумму в ячейке стола
                if (data.newTotal !== undefined) {
                    setTimeout(() => {
                        this.updateTableTotal(this.currentTableId, data.newTotal);
                    }, 300);
                }
                
                // Закрываем модальное окно
                const modalElement = document.getElementById('saleHookahsModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        setTimeout(() => {
                            modal.hide();
                        }, 1000);
                    }
                }
                
                // Сбрасываем форму
                this.resetForm();
                
            } else {
                this.showToast('danger', 'Ошибка', data.message || 'Не удалось добавить кальян');
            }
        } catch (error) {
            console.error('Error adding hookah:', error);
            
            // Детальная обработка ошибки 419
            if (error.status === 419) {
                this.showToast('danger', 'Ошибка безопасности', 
                    'Сессия истекла. Пожалуйста, обновите страницу и попробуйте снова.');
                
                // Показываем детали для отладки
                console.error('CSRF Error Details:', {
                    token: this.getCsrfToken(),
                    url: error.url,
                    status: error.status
                });
            } else {
                this.showToast('danger', 'Ошибка', 'Не удалось добавить кальян. Статус: ' + error.status);
            }
        }
    }
    
    async removeHookah(hookahId) {
        if (!confirm('Вы уверены, что хотите удалить этот кальян?')) {
            return;
        }
        
        try {
            const data = await this.makeRequest(`/tables/${this.currentTableId}/remove-hookah/${hookahId}`, {
                method: 'DELETE'
            });
            
            if (data.success) {
                this.showToast('success', 'Успех', 'Кальян удален');
                
                // Удаляем строку из таблицы
                const row = document.getElementById(`hookahRow${hookahId}`);
                if (row) {
                    row.remove();
                }
                
                // Обновляем итоговую сумму в модалке
                const totalElement = document.getElementById('hookahsTotalAmount');
                if (totalElement && data.total !== undefined) {
                    totalElement.textContent = parseFloat(data.total).toFixed(0);
                }
                
                // Обновляем сумму в ячейке стола
                if (data.newTotal !== undefined) {
                    this.updateTableTotal(this.currentTableId, data.newTotal);
                }
                
                // Если кальянов не осталось, показываем сообщение
                const tbody = document.getElementById('hookahsTableBody');
                if (tbody && tbody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="bi bi-cup-straw me-2"></i>
                            Кальяны не добавлены
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
            } else {
                this.showToast('danger', 'Ошибка', data.message || 'Не удалось удалить кальян');
            }
        } catch (error) {
            console.error('Error removing hookah:', error);
            this.showToast('danger', 'Ошибка', 'Не удалось удалить кальян');
        }
    }
    
    updateTableInterface(tableId, data) {
        console.log('🔄 Обновляем стол', tableId, 'статус:', data.status);
        
        // Находим ячейку стола
        let targetCell = null;
        const allTds = document.querySelectorAll('td');
        
        for (const td of allTds) {
            const button = td.querySelector(`button[data-table-id="${tableId}"]`);
            if (button) {
                targetCell = td;
                break;
            }
        }
        
        if (!targetCell) {
            console.error(`❌ Ячейка для стола ${tableId} не найдена!`);
            return;
        }
        
        this.applyHookahStyles(targetCell, tableId, data);
    }
    
    applyHookahStyles(cell, tableId, data) {
        // Добавляем стиль border для таймера углей
        if (!cell.style.border.includes('2px solid #2196f3')) {
            cell.style.border = '2px solid #2196f3';
        }
        
        if (data.status === 'opened_with_hookah') {
            // Стол С кальяном - светло-голубой
            cell.style.backgroundColor = '#e0f7fa';
            
            // Обновляем бейдж
            this.updateBadge(cell, 'с кальяном');
            
            // Создаем placeholder для таймера углей
            this.createCoalTimerPlaceholder(cell, tableId);
            
            // Запускаем таймер углей
            this.startCoalTimer(tableId, cell);
            
        } else if (data.status === 'opened_without_hookah') {
            // Стол БЕЗ кальяна
            cell.style.backgroundColor = '#e8f5e9';
            this.updateBadge(cell, 'без кальяна');
            
            // Убираем placeholder
            const placeholderId = `coal-timer-placeholder-${tableId}`;
            const placeholder = document.getElementById(placeholderId);
            if (placeholder) {
                placeholder.remove();
            }
        }
        
        // Удаляем таймер "требуется кальян"
        if (window.HookahTimerManager && window.HookahTimerManager.stopTimer) {
            window.HookahTimerManager.stopTimer(tableId);
        }
    }
    
    updateBadge(cell, hookahStatus) {
        const headerDiv = cell.querySelector('.d-flex.justify-content-between.align-items-start.mb-1');
        if (!headerDiv) return;
        
        let badge = headerDiv.querySelector('.badge');
        
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge';
            headerDiv.appendChild(badge);
        }
        
        if (hookahStatus === 'с кальяном') {
            badge.textContent = 'Открытый стол (с кальяном)';
            badge.className = 'badge bg-info';
        } else {
            badge.textContent = 'Открытый стол (без кальяна)';
            badge.className = 'badge bg-success';
        }
    }
    
    createCoalTimerPlaceholder(cell, tableId) {
        const placeholderId = `coal-timer-placeholder-${tableId}`;
        
        // Удаляем старый placeholder если есть
        const oldPlaceholder = document.getElementById(placeholderId);
        if (oldPlaceholder) {
            oldPlaceholder.remove();
        }
        
        // Вставляем в правильное место
        const buttonContainer = cell.querySelector('.d-flex.gap-1.mb-2');
        if (buttonContainer) {
            const placeholderHtml = `<div id="${placeholderId}" style="min-height: 50px;"></div>`;
            buttonContainer.insertAdjacentHTML('afterend', placeholderHtml);
            return;
        }
    }
    
    startCoalTimer(tableId, cell) {
        console.log('🔥 Запускаем таймер углей для стола:', tableId);
        
        setTimeout(() => {
            if (typeof window.CoalTimerSystem !== 'undefined') {
                if (window.CoalTimerSystem.onHookahAdded) {
                    window.CoalTimerSystem.onHookahAdded(tableId);
                } else {
                    this.directCoalTimerStart(tableId, cell);
                }
            } else {
                this.directCoalTimerStart(tableId, cell);
            }
        }, 300);
    }
    
    directCoalTimerStart(tableId, cell) {
        const placeholderId = `coal-timer-placeholder-${tableId}`;
        const placeholder = document.getElementById(placeholderId);
        
        if (placeholder) {
            const timerHtml = `
                <div class="coal-timer-block mb-2" data-table-id="${tableId}">
                    <div class="alert alert-info alert-dismissible fade show p-1 mb-1" role="alert" style="font-size: 0.8rem;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-fire me-1"></i>
                                <span>Угли: <strong class="coal-time-display">15:00</strong> 
                                <span class="badge bg-success ms-1">Смен: 0</span></span>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-warning refresh-coal-btn" 
                                        data-table-id="${tableId}"
                                        title="Обновить угли">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger close-coal-timer-btn"
                                        data-table-id="${tableId}"
                                        title="Закрыть таймер">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            placeholder.innerHTML = timerHtml;
            
            // Сохраняем в localStorage
            localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
            localStorage.setItem(`coal_timer_count_${tableId}`, '0');
            localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        }
    }
    
    updateTableTotal(tableId, newTotal) {
        // Находим все ячейки стола
        const cells = document.querySelectorAll(`td`);
        
        cells.forEach(cell => {
            const button = cell.querySelector(`button[data-table-id="${tableId}"]`);
            if (!button) return;
            
            // Ищем контейнер с таймером и суммой
            const timerSumContainer = cell.querySelector('.d-flex.justify-content-between.align-items-center.mb-2.bg-light.rounded.p-2');
            
            if (timerSumContainer) {
                const sumElement = timerSumContainer.querySelector('.text-end .badge.bg-success.fs-6');
                if (sumElement) {
                    sumElement.textContent = this.numberFormat(newTotal) + ' ₽';
                }
            } else {
                // Ищем существующий бейдж с суммой
                let existingSum = cell.querySelector('.badge.bg-success.fs-6');
                if (existingSum) {
                    existingSum.textContent = this.numberFormat(newTotal) + ' ₽';
                }
            }
            
            // Обновляем данные в кнопке "Закрыть стол"
            const closeButton = cell.querySelector('button[data-bs-target="#closeSaleModal"]');
            if (closeButton) {
                closeButton.setAttribute('data-total', newTotal);
            }
        });
    }
    
    resetForm() {
        const hookahSelect = document.getElementById('hookahSelect');
        if (hookahSelect) hookahSelect.value = '';
    }
    
    // Вспомогательные методы
    async makeRequest(url, options = {}) {
        // Пробуем использовать общий TableManager
        if (window.TableManager && window.TableManager.makeRequest) {
            return window.TableManager.makeRequest(url, options);
        }
        
        // Fallback на собственную реализацию с CSRF токеном
        const csrfToken = this.getCsrfToken();
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            this.showToast('danger', 'Ошибка безопасности', 'Не найден CSRF токен');
            throw new Error('CSRF token not found');
        }
        
        const defaultOptions = {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin' // Важно для отправки cookies
        };
        
        const mergedOptions = { ...defaultOptions, ...options };
        
        console.log('Making request to:', url, 'with CSRF token');
        
        const response = await fetch(url, mergedOptions);
        
        if (!response.ok) {
            const error = new Error(`HTTP error! status: ${response.status}`);
            error.status = response.status;
            
            // Пробуем получить текст ошибки
            try {
                const text = await response.text();
                error.text = text;
            } catch (e) {
                // Игнорируем ошибку получения текста
            }
            
            throw error;
        }
        
        return await response.json();
    }

    // Новый метод для получения CSRF токена
    getCsrfToken() {
        // Пробуем получить из мета-тега
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }
        
        // Пробуем получить из формы
        const formToken = document.querySelector('input[name="_token"]');
        if (formToken) {
            return formToken.value;
        }
        
        // Пробуем получить из window.Laravel
        if (window.Laravel && window.Laravel.csrfToken) {
            return window.Laravel.csrfToken;
        }
        
        return null;
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
    
    numberFormat(number) {
        return parseFloat(number).toLocaleString('ru-RU', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }
}

// Экспорт
window.HookahManager = HookahManager;