// simple-selection.js - ВЕРСИЯ ПОЛНОГО КОНТРОЛЯ
console.log('🔥 SIMPLE-SELECTION - ЗАХВАТЫВАЕМ УПРАВЛЕНИЕ!');

// =============== ГЛОБАЛЬНЫЙ STATE ===============
const STATE = {
    selecting: false,
    startCell: null,
    currentCells: [],
    tableId: null,
    mouseIsDown: false,
    lastX: 0,
    lastY: 0
};

// =============== СИЛОВЫЕ МЕТОДЫ ===============

// 1. УНИЧТОЖАЕМ ВСЕ КОНКУРЕНТЫ
function destroyCompetition() {
    console.log('💣 Уничтожаем конкурентов...');
    
    const table = document.querySelector('table.table-bordered');
    if (!table) {
        console.log('⚠️ Таблица не найдена, будет поиск позже');
        return;
    }
    
    // Создаем клон таблицы БЕЗ обработчиков
    const newTable = table.cloneNode(true);
    table.parentNode.replaceChild(newTable, table);
    
    console.log('✅ Таблица очищена от обработчиков');
}

// 2. МОЖНО ЛИ ВЫБРАТЬ ЯЧЕЙКУ?
function canSelect(cell) {
    if (!cell || cell.tagName !== 'TD') return false;
    
    // Должна быть ПУСТАЯ
    if (cell.textContent.trim() !== '') return false;
    
    // Не должна иметь rowspan/colspan
    if (cell.hasAttribute('rowspan') || cell.hasAttribute('colspan')) return false;
    
    // Должна иметь нужные атрибуты
    if (!cell.dataset.tableId || !cell.dataset.time || !cell.dataset.row) return false;
    
    return true;
}

// 3. ПОЛУЧИТЬ ЯЧЕЙКИ МЕЖДУ ДВУМЯ
function getCellsBetween(start, end) {
    if (!start || !end || start.dataset.tableId !== end.dataset.tableId) {
        return [];
    }
    
    const tableId = start.dataset.tableId;
    const startRow = parseInt(start.dataset.row);
    const endRow = parseInt(end.dataset.row);
    
    const minRow = Math.min(startRow, endRow);
    const maxRow = Math.max(startRow, endRow);
    
    const cells = [];
    
    for (let row = minRow; row <= maxRow; row++) {
        const cell = document.querySelector(
            `td[data-table-id="${tableId}"][data-row="${row}"]`
        );
        
        if (cell && canSelect(cell)) {
            cells.push(cell);
        } else {
            return [];
        }
    }
    
    return cells;
}

// 4. ОЧИСТКА ВЫДЕЛЕНИЯ
function clearSelection() {
    console.log('🧹 Очищаем выделение');
    
    document.querySelectorAll('.cell-selected, .cell-highlight').forEach(cell => {
        cell.classList.remove('cell-selected', 'cell-highlight');
    });
    
    STATE.selecting = false;
    STATE.startCell = null;
    STATE.currentCells = [];
    STATE.tableId = null;
    STATE.mouseIsDown = false;
}

// 5. ПОКАЗАТЬ МОДАЛКУ
function showCreateTableModal(cells) {
    if (!cells || cells.length === 0) {
        console.log('❌ Нет ячеек для стола');
        return;
    }
    
    console.log('🎯 Показываем модалку для', cells.length, 'ячеек');
    
    const first = cells[0];
    const last = cells[cells.length - 1];
    
    const tableId = first.dataset.tableId;
    const tableName = first.dataset.tableName;
    const startTime = first.dataset.time;
    const endTime = last.dataset.time;
    const duration = cells.length * 30;
    
    console.log('📊 Данные:', { tableId, tableName, startTime, endTime, duration });
    
    // Заполняем форму
    const tableSelect = document.getElementById('table_name_id');
    if (tableSelect) {
        tableSelect.value = tableId;
    }
    
    const timeSelect = document.getElementById('booking_time');
    if (timeSelect) {
        // Форматируем время
        let time = startTime;
        if (time.length === 4) time = '0' + time;
        if (!time.includes(':')) time = time + ':00';
        
        for (let i = 0; i < timeSelect.options.length; i++) {
            if (timeSelect.options[i].value === time) {
                timeSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    const durationSelect = document.getElementById('duration');
    if (durationSelect) {
        const durations = [60, 90, 120, 150, 180, 210, 240, 270, 300, 330, 360];
        let closest = 120;
        
        for (const d of durations) {
            if (Math.abs(d - duration) < Math.abs(closest - duration)) {
                closest = d;
            }
        }
        
        durationSelect.value = closest;
    }
    
    // Добавляем информационное сообщение
    const oldInfo = document.getElementById('selectionInfo');
    if (oldInfo) oldInfo.remove();
    
    const hours = Math.floor(duration / 60);
    const minutes = duration % 60;
    let timeText = `${hours} час`;
    if (minutes > 0) timeText += ` ${minutes} мин`;
    
    const info = document.createElement('div');
    info.id = 'selectionInfo';
    info.className = 'alert alert-info mb-3';
    info.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-info-circle me-2"></i>
                <strong>Выбрано:</strong> Стол ${tableName}, время: ${startTime}-${endTime} (${timeText})
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    const modalBody = document.querySelector('#createTableModal .modal-body');
    if (modalBody) {
        modalBody.insertBefore(info, modalBody.firstChild);
    }
    
    // Показываем модалку
    const modal = document.getElementById('createTableModal');
    if (modal) {
        try {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } catch (e) {
            console.error('❌ Ошибка открытия модалки:', e);
        }
    }
}

// =============== ЯДЕРНЫЕ ОБРАБОТЧИКИ ===============

// КЛИК МЫШЬЮ (захватываем ВСЁ)
function handleMouseDown(e) {
    console.log('🖱️ MOUSE DOWN захвачен!');
    
    // ТОЛЬКО левая кнопка
    if (e.button !== 0) return;
    
    // Находим ячейку
    const cell = e.target.closest('td');
    if (!cell || !canSelect(cell)) {
        console.log('❌ Нельзя выбрать эту ячейку');
        return;
    }
    
    // ОСТАНАВЛИВАЕМ ВСЁ
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    
    console.log('🎯 НАЧИНАЕМ ВЫДЕЛЕНИЕ:', {
        table: cell.dataset.tableName,
        time: cell.dataset.time,
        row: cell.dataset.row
    });
    
    // Начинаем выделение
    STATE.selecting = true;
    STATE.mouseIsDown = true;
    STATE.startCell = cell;
    STATE.tableId = cell.dataset.tableId;
    STATE.lastX = e.clientX;
    STATE.lastY = e.clientY;
    
    // Очищаем старое
    clearSelection();
    
    // Выделяем первую ячейку
    cell.classList.add('cell-selected');
    STATE.currentCells = [cell];
    
    // Вешаем ГЛОБАЛЬНЫЕ обработчики
    document.addEventListener('mousemove', handleMouseMove, true);
    document.addEventListener('mouseup', handleMouseUp, true);
    
    console.log('✅ Состояние:', STATE);
}

// ДВИЖЕНИЕ МЫШИ
function handleMouseMove(e) {
    if (!STATE.mouseIsDown || !STATE.selecting) return;
    
    const cell = e.target.closest('td');
    if (!cell || cell === STATE.startCell) return;
    
    // Убираем временное выделение
    document.querySelectorAll('.cell-highlight').forEach(c => {
        c.classList.remove('cell-highlight');
    });
    
    // Если та же таблица и можно выбрать
    if (canSelect(cell) && cell.dataset.tableId === STATE.tableId) {
        const cells = getCellsBetween(STATE.startCell, cell);
        cells.forEach(c => c.classList.add('cell-highlight'));
    }
}

// ОТПУСКАНИЕ МЫШИ
function handleMouseUp(e) {
    console.log('🖱️ MOUSE UP - завершаем выделение');
    
    // Снимаем флаги
    STATE.mouseIsDown = false;
    
    // Убираем глобальные обработчики
    document.removeEventListener('mousemove', handleMouseMove, true);
    document.removeEventListener('mouseup', handleMouseUp, true);
    
    if (!STATE.selecting || !STATE.startCell) {
        console.log('⚠️ Нет активного выделения');
        return;
    }
    
    // Убираем временное выделение
    document.querySelectorAll('.cell-highlight').forEach(c => {
        c.classList.remove('cell-highlight');
    });
    
    // Находим конечную ячейку
    const endCell = document.elementFromPoint(e.clientX, e.clientY)?.closest('td');
    const finalCell = endCell && canSelect(endCell) ? endCell : STATE.startCell;
    
    // Получаем все ячейки
    let finalCells = [];
    if (finalCell.dataset.tableId === STATE.tableId) {
        finalCells = getCellsBetween(STATE.startCell, finalCell);
    }
    
    // Если не получилось, берем хотя бы одну
    if (finalCells.length === 0) {
        finalCells = [STATE.startCell];
    }
    
    console.log(`✅ Выделено ${finalCells.length} ячеек`);
    
    // Постоянное выделение
    finalCells.forEach(c => {
        c.classList.remove('cell-highlight');
        c.classList.add('cell-selected');
    });
    
    STATE.currentCells = finalCells;
    
    // Показываем модалку
    setTimeout(() => {
        showCreateTableModal(finalCells);
    }, 150);
    
    // Сбрасываем состояние
    STATE.selecting = false;
    STATE.startCell = null;
}

// ПРОСТОЙ КЛИК (если не было перетаскивания)
function handleClick(e) {
    // Если было перетаскивание - игнорируем
    if (STATE.selecting || STATE.mouseIsDown) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    
    const cell = e.target.closest('td');
    if (!cell || !canSelect(cell)) return;
    
    console.log('👆 Простой клик по ячейке');
    
    e.preventDefault();
    e.stopPropagation();
    
    clearSelection();
    
    cell.classList.add('cell-selected');
    STATE.currentCells = [cell];
    STATE.tableId = cell.dataset.tableId;
    
    setTimeout(() => {
        showCreateTableModal([cell]);
    }, 150);
}

// КЛИК ВНЕ ТАБЛИЦЫ
function handleOutsideClick(e) {
    const isTableCell = e.target.closest('table.table-bordered td');
    if (!isTableCell && STATE.currentCells.length > 0) {
        console.log('🧹 Клик вне таблицы - очищаем');
        clearSelection();
    }
}

// =============== ИНИЦИАЛИЗАЦИЯ ===============

function initNuclearSelection() {
    console.log('⚡ ЯДЕРНАЯ ИНИЦИАЛИЗАЦИЯ...');
    
    // Ждем полной загрузки
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupNuclear);
    } else {
        setupNuclear();
    }
}

function setupNuclear() {
    console.log('💥 ЗАХВАТЫВАЕМ ТАБЛИЦУ...');
    
    // Даем время другим скриптам "умереть"
    setTimeout(() => {
        // 1. Уничтожаем конкурентов
        destroyCompetition();
        
        // 2. Ищем таблицу
        const table = document.querySelector('table.table-bordered tbody');
        if (!table) {
            console.log('❌ Таблица не найдена, повтор через 1 сек');
            setTimeout(setupNuclear, 1000);
            return;
        }
        
        console.log('✅ Таблица найдена');
        
        // 3. Убеждаемся что у ячеек есть нужные классы
        const cells = document.querySelectorAll('td');
        cells.forEach(cell => {
            if (canSelect(cell) && !cell.classList.contains('table-cell-selectable')) {
                cell.classList.add('table-cell-selectable');
            }
        });
        
        // 4. ВЕШАЕМ НАШИ ОБРАБОТЧИКИ С ВЫСШИМ ПРИОРИТЕТОМ
        table.addEventListener('mousedown', handleMouseDown, true);
        table.addEventListener('click', handleClick, true);
        
        // 5. Клик вне таблицы
        document.addEventListener('click', handleOutsideClick);
        
        // 6. Очистка при закрытии модалки
        const modal = document.getElementById('createTableModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', clearSelection);
        }
        
        // 7. Тестовый лог
        setTimeout(() => {
            const selectable = document.querySelectorAll('.table-cell-selectable');
            console.log(`📊 Готово! ${selectable.length} ячеек для выделения`);
            
            if (selectable.length > 0) {
                console.log('🔍 Пример ячейки:', selectable[0].dataset);
            }
        }, 500);
        
        console.log('🎉 СИСТЕМА ВЫДЕЛЕНИЯ АКТИВИРОВАНА!');
    }, 500);
}

// =============== АВТОЗАПУСК ===============
initNuclearSelection();

// =============== ГЛОБАЛЬНЫЙ ДОСТУП ===============
window.NuclearSelection = {
    init: initNuclearSelection,
    clear: clearSelection,
    test: () => {
        const cells = document.querySelectorAll('.table-cell-selectable');
        if (cells.length > 0) {
            showCreateTableModal([cells[0]]);
        }
    },
    debug: () => {
        console.log('🧪 DEBUG STATE:', STATE);
        console.log('Ячеек выделено:', STATE.currentCells.length);
    }
};

console.log('🚀 Nuclear Selection System READY FOR ACTION!');