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

    // Экранирование значений перед вставкой в HTML-атрибуты (защита от XSS)
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    let isInjecting = false;
    let observer = null; // объявляем заранее, чтобы можно было отключить изнутри injectEntityHours

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
                    if (observer) { observer.disconnect(); }
                    return;
                }

                const data = typeof response === 'string' ? JSON.parse(response) : response;
                const workHours = escapeHtml(data.work_hours || '');
                const lunchHours = escapeHtml(data.lunch_hours || '');

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

                // ВАЖНО: поля вставлены один раз — больше не нужно следить за DOM.
                // Отключаем наблюдатель, чтобы не конфликтовать с родным JS GLPI,
                // который продолжает перестраивать ту же таблицу после загрузки/сохранения формы.
                if (observer) {
                    observer.disconnect();
                }
            },
            error: function() {
                isInjecting = false;
            }
        });
    }

    // 1. Пробуем запустить инъекцию сразу при загрузке (если таблица уже есть)
    injectEntityHours();

    // 2. MutationObserver — нужен только на случай, если таблица подгружается с задержкой
    // (или вкладку Entity открыли через AJAX-переход без полной перезагрузки страницы).
    // Как только поля вставлены один раз, наблюдатель сам отключается (см. выше),
    // чтобы не реагировать на собственные изменения DOM от родного JS GLPI после сабмита формы.
    observer = new MutationObserver(function() {
        if ($('table.tab_cadre_fixe').length > 0 && $('.custom-entity-hours-row').length === 0) {
            injectEntityHours();
        }
    });

    // Запускаем наблюдение за всем телом документа
    observer.observe(document.body, { childList: true, subtree: true });
});