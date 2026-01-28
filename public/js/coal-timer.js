// public/js/coal-timer.js
console.log('=== COAL TIMER SYSTEM LOADED ===');

class CoalTimerSystem {
    constructor() {
        this.coalTimers = {};
        this.persistentNotifications = {};
        console.log('✅ CoalTimerSystem created');
        this.init();
    }

    init() {
        console.log('🔄 Initializing CoalTimerSystem...');
        this.initEventListeners();
        this.cleanupOldTimers();
        this.restoreAllTimers();
        this.restorePersistentNotifications();
    }

    // Восстанавливаем персистентные уведомления
    restorePersistentNotifications() {
        const expiredNotifications = JSON.parse(localStorage.getItem('coal_expired_notifications') || '[]');
        console.log(`🔔 Restoring ${expiredNotifications.length} persistent notifications`);
        
        expiredNotifications.forEach(data => {
            this.createPersistentNotification(data.tableId, data.tableName, data.guestName);
        });
    }

    // Сохраняем список уведомлений
    saveNotifications() {
        const notifications = Object.values(this.persistentNotifications).map(notif => ({
            tableId: notif.tableId,
            tableName: notif.tableName,
            guestName: notif.guestName
        }));
        localStorage.setItem('coal_expired_notifications', JSON.stringify(notifications));
    }

    // Создаем персистентное уведомление
    createPersistentNotification(tableId, tableName = '?', guestName = 'Клиент') {
        if (this.persistentNotifications[tableId]) {
            return;
        }

        const notificationId = `coal-persistent-${tableId}`;
        const existingNotification = document.getElementById(notificationId);
        if (existingNotification) {
            return;
        }

        const notificationHtml = `
            <div id="${notificationId}" class="toast show mb-2" role="alert" style="min-width: 300px;">
                <div class="toast-header bg-danger text-white">
                    <i class="bi bi-fire me-2"></i>
                    <strong class="me-auto">Время углей истекло!</strong>
                    <small>Сейчас</small>
                    <button type="button" class="btn-close btn-close-white" 
                            onclick="window.CoalTimerSystem.closePersistentNotification('${tableId}')"></button>
                </div>
                <div class="toast-body">
                    <div class="fw-bold mb-1">Стол ${tableName}</div>
                    <div class="text-muted small mb-2">${guestName}</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-danger">СРОЧНО!</span>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="window.CoalTimerSystem.refreshCoalTimerFromNotification('${tableId}')">
                            <i class="bi bi-arrow-clockwise me-1"></i>Обновить угли
                        </button>
                    </div>
                </div>
            </div>
        `;

        const container = this.getNotificationsContainer();
        container.insertAdjacentHTML('beforeend', notificationHtml);

        this.persistentNotifications[tableId] = {
            tableId,
            tableName,
            guestName,
            elementId: notificationId
        };
        
        this.saveNotifications();
        
        console.log(`🔔 Created persistent notification for table ${tableId}`);
    }

    // Получаем контейнер для уведомлений
    getNotificationsContainer() {
        let container = document.getElementById('coal-notifications-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'coal-notifications-container';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '9999';
            container.style.maxHeight = '70vh';
            container.style.overflowY = 'auto';
            document.body.appendChild(container);
        }
        return container;
    }

    // Закрыть персистентное уведомление
    closePersistentNotification(tableId) {
        const notifData = this.persistentNotifications[tableId];
        if (!notifData) return;

        const element = document.getElementById(notifData.elementId);
        if (element) {
            element.remove();
        }

        delete this.persistentNotifications[tableId];
        this.saveNotifications();
        
        console.log(`🔕 Closed persistent notification for table ${tableId}`);
    }

    // Обновить угли из уведомления
    refreshCoalTimerFromNotification(tableId) {
        this.refreshCoalTimer(tableId);
        this.closePersistentNotification(tableId);
    }

    initEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.refresh-coal-btn')) {
                const btn = e.target.closest('.refresh-coal-btn');
                const tableId = btn.dataset.tableId;
                this.refreshCoalTimer(tableId);
                e.preventDefault();
            }

            if (e.target.closest('.close-coal-timer-btn')) {
                const btn = e.target.closest('.close-coal-timer-btn');
                const tableId = btn.dataset.tableId;
                this.closeCoalTimer(tableId);
                e.preventDefault();
            }

            if (e.target.closest('.open-coal-timer-btn')) {
                const btn = e.target.closest('.open-coal-timer-btn');
                const tableId = btn.dataset.tableId;
                this.openCoalTimer(tableId);
                e.preventDefault();
            }
        });
    }

    // Восстановление всех таймеров
    restoreAllTimers() {
        console.log('🔄 Restoring coal timers from localStorage...');
        
        Object.values(this.coalTimers).forEach(timer => {
            if (timer.interval) clearInterval(timer.interval);
        });
        this.coalTimers = {};

        setTimeout(() => {
            this.findAndRestoreAllTableTimers();
        }, 500);
    }

    // Поиск и восстановление таймеров для всех столов с кальянами
    findAndRestoreAllTableTimers() {
        const tableCells = document.querySelectorAll('td[style*="border: 2px solid #2196f3"]');
        console.log(`Found ${tableCells.length} table cells to check`);

        tableCells.forEach((cell) => {
            const statusBadge = cell.querySelector('.badge');
            if (!statusBadge) return;

            const statusText = statusBadge.textContent.trim();
            if (!statusText.includes('с кальяном')) return;

            const tableIdBtn = cell.querySelector('button[data-table-id]');
            if (!tableIdBtn) return;

            const tableId = tableIdBtn.dataset.tableId;
            console.log(`Found table with hookah: ${tableId}, restoring timer...`);
            
            this.checkAndRestoreTimer(tableId, cell);
        });
    }

    // Проверка и восстановление таймера
    checkAndRestoreTimer(tableId, cell) {
        const isVisible = localStorage.getItem(`coal_timer_visible_${tableId}`) === 'true';
        
        if (isVisible) {
            const startTime = localStorage.getItem(`coal_timer_start_${tableId}`);
            const count = parseInt(localStorage.getItem(`coal_timer_count_${tableId}`)) || 0;
            
            if (startTime) {
                const now = Date.now();
                const elapsedSeconds = Math.floor((now - parseInt(startTime)) / 1000);
                const timeLeft = Math.max(0, (15 * 60) - elapsedSeconds);
                
                this.showCoalTimer(tableId, cell, timeLeft, count);
                
                if (timeLeft <= 0) {
                    this.showExpiredNotification(tableId, cell);
                }
            } else {
                this.showCoalTimer(tableId, cell, 15 * 60, count);
            }
        } else {
            this.showOpenButton(tableId, cell);
        }
    }

    // Показать блок с активным таймером
    showCoalTimer(tableId, cell, timeLeft = 15 * 60, count = 0) {
        const oldBlock = cell.querySelector('.coal-timer-block');
        if (oldBlock) oldBlock.remove();

        let placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (!placeholder) {
            const firstDiv = cell.querySelector('div');
            if (firstDiv) {
                const placeholderHtml = `<div id="coal-timer-placeholder-${tableId}" style="min-height: 50px;"></div>`;
                firstDiv.insertAdjacentHTML('afterend', placeholderHtml);
                placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
            }
        }

        localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
        if (!localStorage.getItem(`coal_timer_count_${tableId}`)) {
            localStorage.setItem(`coal_timer_count_${tableId}`, count.toString());
        }

        let countColor = 'success';
        if (count >= 3 && count <= 4) countColor = 'warning';
        if (count >= 5) countColor = 'danger';

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        const timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        const html = `
            <div class="coal-timer-block mb-2" data-table-id="${tableId}">
                <div class="alert alert-info alert-dismissible fade show p-1 mb-1" role="alert" style="font-size: 0.8rem;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-fire me-1"></i>
                            <span>Угли: <strong class="coal-time-display">${timeDisplay}</strong> 
                            <span class="badge bg-${countColor} ms-1">Смен: ${count}</span></span>
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

        if (placeholder) {
            placeholder.innerHTML = html;
        } else {
            const backupPlaceholder = cell.querySelector('.d-flex.gap-1.mb-2');
            if (backupPlaceholder) {
                backupPlaceholder.insertAdjacentHTML('afterend', html);
            } else {
                const firstDiv = cell.querySelector('div');
                if (firstDiv) {
                    firstDiv.insertAdjacentHTML('afterend', html);
                }
            }
        }

        if (timeLeft > 0) {
            this.startCoalTimer(tableId, timeLeft);
        } else {
            this.updateDisplayForExpired(tableId);
        }
    }

    // Показать уведомление об истекшем времени
    showExpiredNotification(tableId, cell) {
        const tableName = cell.querySelector('button[data-table-name]')?.dataset.tableName || '?';
        const guestName = cell.querySelector('.text-truncate strong')?.textContent?.trim() || 'Клиент';
        
        this.createPersistentNotification(tableId, tableName, guestName);
        
        if (typeof showToast === 'function') {
            showToast('danger', 'Время углей истекло!', `Стол ${tableName} (${guestName}) - требуется замена углей`);
        }
    }

    // Показать только кнопку открытия
    showOpenButton(tableId, cell) {
        const oldBlock = cell.querySelector('.coal-timer-block');
        if (oldBlock) oldBlock.remove();

        let placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (!placeholder) {
            const firstDiv = cell.querySelector('div');
            if (firstDiv) {
                const placeholderHtml = `<div id="coal-timer-placeholder-${tableId}" style="min-height: 50px;"></div>`;
                firstDiv.insertAdjacentHTML('afterend', placeholderHtml);
                placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
            }
        }

        const html = `
            <div class="coal-timer-block mb-2" data-table-id="${tableId}">
                <button type="button" class="btn btn-sm btn-outline-info open-coal-timer-btn w-100"
                        data-table-id="${tableId}">
                    <i class="bi bi-fire me-1"></i> Открыть таймер углей
                </button>
            </div>
        `;

        if (placeholder) {
            placeholder.innerHTML = html;
        } else {
            const backupPlaceholder = cell.querySelector('.d-flex.gap-1.mb-2');
            if (backupPlaceholder) {
                backupPlaceholder.insertAdjacentHTML('afterend', html);
            }
        }
    }

    // Запустить таймер
    startCoalTimer(tableId, timeLeft) {
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
        }

        if (!localStorage.getItem(`coal_timer_start_${tableId}`)) {
            localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        }

        this.coalTimers[tableId] = {
            timeLeft: timeLeft,
            interval: setInterval(() => {
                if (this.coalTimers[tableId] && this.coalTimers[tableId].timeLeft > 0) {
                    this.coalTimers[tableId].timeLeft--;
                    this.updateDisplay(tableId, this.coalTimers[tableId].timeLeft);
                } else {
                    clearInterval(this.coalTimers[tableId].interval);
                    this.updateDisplayForExpired(tableId);
                }
            }, 1000)
        };

        const elapsed = (15 * 60) - timeLeft;
        localStorage.setItem(`coal_timer_start_${tableId}`, (Date.now() - (elapsed * 1000)).toString());
    }

    // Обновить отображение таймера
    updateDisplay(tableId, timeLeft) {
        const block = document.querySelector(`.coal-timer-block[data-table-id="${tableId}"]`);
        if (!block) return;

        const timeDisplay = block.querySelector('.coal-time-display');
        if (!timeDisplay) return;

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timeDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        const alert = block.querySelector('.alert');
        if (alert) {
            alert.classList.remove('alert-info', 'alert-warning', 'alert-danger');
            
            if (timeLeft < 60) {
                alert.classList.add('alert-danger');
            } else if (timeLeft < 180) {
                alert.classList.add('alert-warning');
            } else {
                alert.classList.add('alert-info');
            }
        }
    }

    // Обновить отображение когда время вышло
    updateDisplayForExpired(tableId) {
        const block = document.querySelector(`.coal-timer-block[data-table-id="${tableId}"]`);
        if (!block) return;

        const timeDisplay = block.querySelector('.coal-time-display');
        if (timeDisplay) {
            timeDisplay.textContent = '00:00';
            timeDisplay.classList.add('text-decoration-line-through');
        }

        const alert = block.querySelector('.alert');
        if (alert) {
            alert.classList.remove('alert-info', 'alert-warning');
            alert.classList.add('alert-danger');
        }

        const cell = this.findTableCell(tableId);
        if (cell) {
            this.showExpiredNotification(tableId, cell);
        }
    }

    // Обновить угли (сбросить таймер)
    refreshCoalTimer(tableId) {
        console.log(`🔄 Refreshing coal timer for table ${tableId}`);
        
        let count = parseInt(localStorage.getItem(`coal_timer_count_${tableId}`)) || 0;
        count++;
        localStorage.setItem(`coal_timer_count_${tableId}`, count.toString());
        
        localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
        }
        
        const cell = this.findTableCell(tableId);
        if (cell) {
            this.showCoalTimer(tableId, cell, 15 * 60, count);
            
            const tableName = cell.querySelector('button[data-table-name]')?.dataset.tableName || '?';
            const guestName = cell.querySelector('.text-truncate strong')?.textContent?.trim() || 'Клиент';
            
            if (typeof showToast === 'function') {
                showToast('success', 'Угли обновлены', `Стол ${tableName}: таймер сброшен на 15 минут (смена #${count})`);
            }
        }
    }

    // Закрыть таймер
    closeCoalTimer(tableId) {
        console.log(`❌ Closing coal timer for table ${tableId}`);
        
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
            delete this.coalTimers[tableId];
        }
        
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'false');
        
        this.closePersistentNotification(tableId);
        
        const cell = this.findTableCell(tableId);
        if (cell) {
            this.showOpenButton(tableId, cell);
        }
        
        if (typeof showToast === 'function') {
            showToast('info', 'Таймер закрыт', 'Таймер углей скрыт');
        }
    }

    // Открыть таймер
    openCoalTimer(tableId) {
        console.log(`🔓 Opening coal timer for table ${tableId}`);
        
        localStorage.setItem(`coal_timer_count_${tableId}`, '0');
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
        
        const cell = this.findTableCell(tableId);
        if (cell) {
            this.showCoalTimer(tableId, cell, 15 * 60, 0);
        }
        
        if (typeof showToast === 'function') {
            showToast('info', 'Таймер открыт', 'Таймер углей запущен на 15 минут');
        }
    }

    // Найти ячейку стола по ID
    findTableCell(tableId) {
        console.log(`🔍 [CoalTimer] Ищем ячейку для стола ${tableId}`);
        
        const placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (placeholder) {
            const cell = placeholder.closest('td');
            if (cell) {
                console.log(`✅ [CoalTimer] Найдена ячейка через placeholder`);
                return cell;
            }
        }
        
        const buttons = document.querySelectorAll(`button[data-table-id="${tableId}"]`);
        console.log(`🔍 [CoalTimer] Найдено кнопок с tableId=${tableId}: ${buttons.length}`);
        
        for (const button of buttons) {
            const cell = button.closest('td');
            if (cell) {
                console.log(`✅ [CoalTimer] Найдена ячейка через кнопку`);
                return cell;
            }
        }
        
        const borderStyles = [
            'border: 2px solid #2196f3',
            'border:2px solid #2196f3',
            'border: 2px solid rgb(33, 150, 243)',
            'border:2px solid rgb(33, 150, 243)'
        ];
        
        for (const borderStyle of borderStyles) {
            const cells = document.querySelectorAll(`td[style*="${borderStyle}"]`);
            for (const cell of cells) {
                const button = cell.querySelector(`button[data-table-id="${tableId}"]`);
                if (button) {
                    console.log(`✅ [CoalTimer] Найдена ячейка через стиль border`);
                    return cell;
                }
            }
        }
        
        const allCells = document.querySelectorAll('td');
        for (const cell of allCells) {
            const button = cell.querySelector(`button[data-table-id="${tableId}"]`);
            if (button) {
                console.log(`✅ [CoalTimer] Найдена ячейка через полный поиск`);
                return cell;
            }
        }
        
        console.error(`❌ [CoalTimer] Ячейка не найдена для tableId: ${tableId}`);
        return null;
    }

    // Вызывается при добавлении кальяна
    onHookahAdded(tableId) {
        console.log(`🔥 HOOKAH ADDED: Adding coal timer for table ${tableId}`);
        this.initializeCoalTimer(tableId);
    }
    
    // Инициализировать таймер углей
    initializeCoalTimer(tableId) {
        console.log(`⚡ INITIALIZE COAL TIMER for table ${tableId}`);
        
        localStorage.setItem(`coal_timer_count_${tableId}`, '0');
        localStorage.setItem(`coal_timer_visible_${tableId}`, 'true');
        localStorage.setItem(`coal_timer_start_${tableId}`, Date.now().toString());
        
        setTimeout(() => {
            const cell = this.findTableCell(tableId);
            if (cell) {
                console.log(`✅ Ячейка найдена для таймера углей, tableId: ${tableId}`);
                this.showCoalTimer(tableId, cell, 15 * 60, 0);
                
                if (typeof showToast === 'function') {
                    showToast('success', 'Таймер углей запущен', 'Таймер углей запущен на 15 минут');
                }
            } else {
                console.error(`❌ Ячейка не найдена для tableId: ${tableId}`);
                
                setTimeout(() => {
                    const cell2 = this.findTableCell(tableId);
                    if (cell2) {
                        console.log(`✅ Ячейка найдена при повторной попытке`);
                        this.showCoalTimer(tableId, cell2, 15 * 60, 0);
                    }
                }, 500);
            }
        }, 300);
    }

    // Очистить все данные таймера
    clearCoalTimer(tableId) {
        console.log(`🧹 Clearing coal timer for table ${tableId}`);
        
        if (this.coalTimers[tableId] && this.coalTimers[tableId].interval) {
            clearInterval(this.coalTimers[tableId].interval);
            delete this.coalTimers[tableId];
        }
        
        localStorage.removeItem(`coal_timer_visible_${tableId}`);
        localStorage.removeItem(`coal_timer_start_${tableId}`);
        localStorage.removeItem(`coal_timer_count_${tableId}`);
        
        this.closePersistentNotification(tableId);
        
        const placeholder = document.getElementById(`coal-timer-placeholder-${tableId}`);
        if (placeholder) {
            placeholder.remove();
        }
        
        const block = document.querySelector(`.coal-timer-block[data-table-id="${tableId}"]`);
        if (block) {
            block.remove();
        }
    }

    // Очистка устаревших таймеров
    cleanupOldTimers() {
        const now = Date.now();
        const oneDayMs = 24 * 60 * 60 * 1000;
        let removedCount = 0;
        
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            
            if (key.startsWith('coal_timer_start_')) {
                try {
                    const startTime = parseInt(localStorage.getItem(key));
                    if (now - startTime > oneDayMs) {
                        const tableId = key.replace('coal_timer_start_', '');
                        localStorage.removeItem(key);
                        localStorage.removeItem(`coal_timer_visible_${tableId}`);
                        localStorage.removeItem(`coal_timer_count_${tableId}`);
                        
                        this.closePersistentNotification(tableId);
                        removedCount++;
                    }
                } catch (error) {
                    console.error('Error cleaning up coal timer:', error);
                }
            }
        }
        
        const notifications = JSON.parse(localStorage.getItem('coal_expired_notifications') || '[]');
        const updatedNotifications = notifications.filter(notif => {
            const isOld = (now - (localStorage.getItem(`coal_timer_start_${notif.tableId}`) || 0)) > oneDayMs;
            if (isOld) {
                this.closePersistentNotification(notif.tableId);
            }
            return !isOld;
        });
        
        localStorage.setItem('coal_expired_notifications', JSON.stringify(updatedNotifications));
        
        if (removedCount > 0) {
            console.log(`🧹 Removed ${removedCount} old coal timers from localStorage`);
        }
    }

    // Принудительное восстановление всех таймеров
    forceRestoreAllTimers() {
        console.log('🔄 Force restoring all coal timers...');
        this.restoreAllTimers();
    }

    updateTableStatus(tableId, newStatus) {
        console.log(`CoalTimerSystem: Updating table ${tableId} to ${newStatus}`);
        
        const cell = this.findTableCell(tableId);
        if (!cell) return;
        
        if (newStatus === 'opened_with_hookah') {
            const isVisible = localStorage.getItem(`coal_timer_visible_${tableId}`) === 'true';
            
            if (isVisible) {
                this.checkAndRestoreTimer(tableId, cell);
            } else {
                this.initializeCoalTimer(tableId);
            }
        } else if (newStatus === 'opened_without_hookah') {
            this.closeCoalTimer(tableId);
        }
    }
    
    // Восстановить таймеры для всех столов с кальянами
    restoreTimersForAllTablesWithHookah() {
        console.log('🔍 Searching for all tables with hookah...');
        
        const tableCells = document.querySelectorAll('td[style*="border: 2px solid #2196f3"]');
        
        tableCells.forEach(cell => {
            const statusBadge = cell.querySelector('.badge');
            if (!statusBadge) return;
            
            const statusText = statusBadge.textContent.trim();
            if (statusText.includes('с кальяном')) {
                const tableIdBtn = cell.querySelector('button[data-table-id]');
                if (tableIdBtn) {
                    const tableId = tableIdBtn.dataset.tableId;
                    console.log(`Found table with hookah: ${tableId}`);
                    this.checkAndRestoreTimer(tableId, cell);
                }
            }
        });
    }
}

// Глобальная инициализация
let coalTimerSystem = null;

function initCoalTimerSystem() {
    if (!coalTimerSystem) {
        coalTimerSystem = new CoalTimerSystem();
        console.log('✅ CoalTimerSystem initialized successfully');
    }
    return coalTimerSystem;
}

// Экспорт в глобальную область
window.CoalTimerSystem = {
    init: initCoalTimerSystem,
    instance: () => coalTimerSystem,
    forceRestore: () => {
        if (coalTimerSystem) {
            coalTimerSystem.forceRestoreAllTimers();
        }
    },
    onHookahAdded: (tableId) => {
        if (coalTimerSystem) {
            coalTimerSystem.onHookahAdded(tableId);
        } else {
            console.error('CoalTimerSystem not initialized');
        }
    },
    initializeCoalTimer: (tableId) => {
        if (coalTimerSystem) {
            coalTimerSystem.initializeCoalTimer(tableId);
        } else {
            console.error('CoalTimerSystem not initialized');
        }
    },
    closePersistentNotification: (tableId) => {
        if (coalTimerSystem) {
            coalTimerSystem.closePersistentNotification(tableId);
        }
    },
    refreshCoalTimerFromNotification: (tableId) => {
        if (coalTimerSystem) {
            coalTimerSystem.refreshCoalTimerFromNotification(tableId);
        }
    },
    clearAllNotifications: () => {
        if (coalTimerSystem) {
            const notifications = JSON.parse(localStorage.getItem('coal_expired_notifications') || '[]');
            notifications.forEach(notif => {
                coalTimerSystem.closePersistentNotification(notif.tableId);
            });
        }
    }
};

// Автоматическая инициализация
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            initCoalTimerSystem();
        }, 1000);
    });
} else {
    setTimeout(() => {
        initCoalTimerSystem();
    }, 1000);
}

console.log('✅ Coal Timer System ready');