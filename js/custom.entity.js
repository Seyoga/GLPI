$(document).ready(function() {
    // Убеждаемся, что мы на странице организации
    if (window.location.href.indexOf('entity.form.php') === -1) return;

    // Надежно определяем путь к плагину
    var PLUGIN_PATH = (function() {
        if (typeof window.CUSTOMHELPDESK_PLUGIN_PATH !== 'undefined') {
            return window.CUSTOMHELPDESK_PLUGIN_PATH;
        }
        var path = window.location.pathname;
        var glpiIndex = path.indexOf('/front/');
        var root = glpiIndex !== -1 ? path.substring(0, glpiIndex) : '';
        return root + '/plugins/customhelpdesk';
    })();

    // Функция для безопасного получения ID организации (даже при AJAX переходах)
    function getEntityId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || $('input[name="id"]').val();
    }

    let isInjecting = false;

    // Главная функция инъекции полей
    function injectEntityHours() {
        // Защита от дублей: проверяем, нет ли уже наших полей (по спец. классу)
        if ($('.custom-entity-hours-row').length > 0 || isInjecting) return;

        // Ищем таблицу
        let targetTable = $('table.tab_cadre_fixe').first();
        
        // Проверяем, что таблица реально загрузилась и в ней есть строки
        if (targetTable.length === 0 || targetTable.find('tbody tr').length < 3) return;

        const entityId = getEntityId();
        if (!entityId) return;

        isInjecting = true; // Блокируем параллельные запросы

        $.ajax({
            url: PLUGIN_PATH + '/front/ajax.entity_hours.php',
            type: 'GET',
            cache: false,
            data: { id: entityId },
            success: function(response) {
                // На случай, если пока шел запрос, поля уже добавились
                if ($('.custom-entity-hours-row').length > 0) {
                    isInjecting = false;
                    return;
                }

                const data = typeof response === 'string' ? JSON.parse(response) : response;
                const workHours = data.work_hours || '';
                const lunchHours = data.lunch_hours || '';

                // Добавили класс custom-entity-hours-row для отслеживания дублей
                const customFieldsHtml = `
                    <tr class="tab_bg_1 custom-entity-hours-row">
                        <td>Часы работы</td>
                        <td>
                            <input type="text" name="plugin_customhelpdesk_work_hours" 
                                   value="${workHours}" class="form-control" 
                                   placeholder="Например: 09:00 - 18:00">
                        </td>
                        <td colspan="2"></td>
                    </tr>
                    <tr class="tab_bg_1 custom-entity-hours-row">
                        <td>Обеденное время</td>
                        <td>
                            <input type="text" name="plugin_customhelpdesk_lunch_hours" 
                                   value="${lunchHours}" class="form-control" 
                                   placeholder="Например: 13:00 - 14:00">
                        </td>
                        <td colspan="2"></td>
                    </tr>
                `;

                // Вставляем поля
                targetTable.find('tbody tr').eq(2).after(customFieldsHtml);
                isInjecting = false;
            },
            error: function() {
                isInjecting = false;
            }
        });
    }

    // 1. Пробуем запустить инъекцию сразу при загрузке (если таблица уже есть)
    injectEntityHours();

    // 2. MutationObserver, который будет следить за изменениями DOM.
    // Если пользователь перейдет в другую организацию или таблица подгрузится медленно, он это заметит.
    const observer = new MutationObserver(function() {
        if ($('table.tab_cadre_fixe').length > 0 && $('.custom-entity-hours-row').length === 0) {
            injectEntityHours();
        }
    });

    // Запускаем наблюдение за всем телом документа
    observer.observe(document.body, { childList: true, subtree: true });
});