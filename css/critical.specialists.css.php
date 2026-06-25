<?php
/**
 * CSS файл с ранними стилями для специалистов плагина CustomHelpdesk
 * Генерирует CSS на лету для устранения FOUC
 * 
 * Этот файл должен быть доступен напрямую через URL:
 * /glpi/plugins/customhelpdesk/css/critical.specialists.css.php
 */

// Загружаем GLPI
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Устанавливаем заголовки для CSS ПЕРЕД любой проверкой
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['glpiactiveprofile']['id'])) {
    echo '/* User not authenticated */';
    exit;
}

// Проверяем, что класс доступен
if (!class_exists('PluginCustomhelpdeskConfig')) {
    echo '/* Plugin class not found */';
    exit;
}

$current_profile_id = (int)$_SESSION['glpiactiveprofile']['id'];

// Получаем конфигурацию плагина
$config = PluginCustomhelpdeskConfig::getConfig();

if (empty($config)) {
    echo '/* Configuration not found */';
    exit;
}

// Проверяем профили специалистов
$specialist_profiles = [];
if (isset($config['specialist_profiles']) && !empty($config['specialist_profiles'])) {
    $decoded = json_decode($config['specialist_profiles'], true);
    $specialist_profiles = is_array($decoded) ? $decoded : [];
}

// Проверяем, входит ли текущий профиль в разрешенные
$is_specialist = in_array($current_profile_id, $specialist_profiles);

if (!$is_specialist) {
    // Профиль не разрешен - возвращаем пустой CSS
    echo '/* Profile not allowed - standard GLPI interface */';
    exit;
}

// Генерируем ранний CSS для специалистов
?>
/* Ранние стили для специалистов плагина CustomHelpdesk */
/* Подключаются до остальных стилей и скриптов, чтобы избежать FOUC */

/* Скрываем только ссылку RSS ленты на странице central.php (не весь пункт меню) */
a[title="RSS лента"],
a[data-bs-original-title="RSS лента"] {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем "Личные каналы RSS" из dropdown меню */
.dropdown-item[href*="rssfeed.php"],
.dropdown-item[title="Личные каналы RSS"],
.dropdown-item:has(a[href*="rssfeed.php"]),
a.dropdown-item[href*="rssfeed.php"],
a.dropdown-item[title="Личные каналы RSS"],
.dropdown-item:has(i.ti-rss),
.dropdown-item:has(.ti-rss) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем пункт меню "Проблемы" в выпадающем меню "Поддержка" (только саму ссылку, не весь пункт) */
a.dropdown-item[href*="/front/problem.php"],
a.dropdown-item[href*="problem.php"][title="Проблемы"],
.dropdown-item[title="Проблемы"] {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем вкладки на странице ticket.form.php для специалистов */
/* Вкладка "Объекты" (ТОЛЬКО во вкладках .nav.nav-tabs, не в главном меню) */
.nav.nav-tabs .nav-item a[title="Объекты"],
.nav.nav-tabs .nav-item a[data-bs-original-title="Объекты"],
.nav.nav-tabs .nav-item:has(a[title="Объекты"]),
.nav.nav-tabs .nav-item:has(a[data-bs-original-title="Объекты"]),
.nav.nav-tabs .nav-item a[href*="Item_Ticket"],
.nav.nav-tabs .nav-item a[data-bs-target*="Item_Ticket"] {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Вкладка "Затраты" (ТОЛЬКО во вкладках .nav.nav-tabs, не в главном меню) */
.nav.nav-tabs .nav-item a[title="Затраты"],
.nav.nav-tabs .nav-item a[data-bs-original-title="Затраты"],
.nav.nav-tabs .nav-item:has(a[title="Затраты"]),
.nav.nav-tabs .nav-item:has(a[data-bs-original-title="Затраты"]),
.nav.nav-tabs .nav-item a[href*="TicketCost"],
.nav.nav-tabs .nav-item a[data-bs-target*="TicketCost"] {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Вкладка "Проблемы" (ТОЛЬКО во вкладках .nav.nav-tabs, не в главном меню) */
.nav.nav-tabs .nav-item a[title="Проблемы"],
.nav.nav-tabs .nav-item a[data-bs-original-title="Проблемы"],
.nav.nav-tabs .nav-item:has(a[title="Проблемы"]),
.nav.nav-tabs .nav-item:has(a[data-bs-original-title="Проблемы"]),
.nav.nav-tabs .nav-item a[href*="Problem_Ticket"],
.nav.nav-tabs .nav-item a[data-bs-target*="Problem_Ticket"] {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем поля формы на странице ticket.form.php для специалистов */
/* Поле "Тип" */
label[for^="type_"],
label[for^="dropdown_type"],
select[name="type"],
select[id^="dropdown_type"],
.form-field:has(label[for^="type_"]),
.form-field:has(label[for^="dropdown_type"]),
.form-field:has(select[name="type"]),
.form-field:has(select[id^="dropdown_type"]) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем select2 контейнер для поля "Тип" */
select[name="type"] + span.select2,
select[id^="dropdown_type"] + span.select2 {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Поле "Источник запросов" */
label[for^="dropdown_requesttypes_id_"],
label[for^="requesttypes_id"],
select[name="requesttypes_id"],
select[id^="dropdown_requesttypes_id"],
.form-field:has(label[for^="dropdown_requesttypes_id_"]),
.form-field:has(label[for^="requesttypes_id"]),
.form-field:has(select[name="requesttypes_id"]),
.form-field:has(select[id^="dropdown_requesttypes_id"]) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем select2 контейнер для поля "Источник запросов" */
select[name="requesttypes_id"] + span.select2,
select[id^="dropdown_requesttypes_id"] + span.select2 {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем кнопку с подсказкой для "Источник запросов" */
span[id^="tooltip"][class*="fa-info"]:has(+ div[id^="comment_requesttypes_id"]) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Поле "Влияние" */
label[for^="impact_"],
label[for^="dropdown_impact"],
select[name="impact"],
select[id^="dropdown_impact"],
.form-field:has(label[for^="impact_"]),
.form-field:has(label[for^="dropdown_impact"]),
.form-field:has(select[name="impact"]),
.form-field:has(select[id^="dropdown_impact"]) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем select2 контейнер для поля "Влияние" */
select[name="impact"] + span.select2,
select[id^="dropdown_impact"] + span.select2 {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Поле "Приоритет" */
label[for^="priority_"],
label[for^="dropdown_priority"],
select[name="priority"],
select[id^="dropdown_priority"],
.form-field:has(label[for^="priority_"]),
.form-field:has(label[for^="dropdown_priority"]),
.form-field:has(select[name="priority"]),
.form-field:has(select[id^="dropdown_priority"]) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем select2 контейнер для поля "Приоритет" */
select[name="priority"] + span.select2,
select[id^="dropdown_priority"] + span.select2 {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем аккордеон "Объекты" целиком */
.accordion-item#items-heading,
.accordion-item:has(#items-heading),
.accordion-item:has(button[data-bs-target="#items"]),
.accordion-item:has(h2#items-heading),
.accordion-item:has(.accordion-body#items),
.accordion-item:has(.accordion-body.accordion-items),
#items-heading,
#items,
.accordion-collapse#items,
.accordion-collapse:has(#items),
.accordion-body#items,
.accordion-body.accordion-items,
.accordion-body:has(#itemAddForm),
.accordion-body:has([id^="itemAddForm"]),
.accordion-body:has(#tracking_my_devices),
.accordion-body:has(#tracking_all_devices) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Дополнительно скрываем по заголовку и кнопке */
.accordion-header#items-heading,
.accordion-header:has(#items-heading),
button[data-bs-target="#items"],
button[aria-controls="items"],
h2#items-heading,
h2:has(button[data-bs-target="#items"]) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем содержимое аккордеона "Объекты" */
#itemAddForm,
[id^="itemAddForm"],
#tracking_my_devices,
#tracking_all_devices,
[id^="tracking_all_devices"],
[id^="tracking_my_devices"],
[id^="dropdown_my_items"],
[id^="dropdown_itemtype"],
[id^="item_ticket_selection_information"],
[id^="results_itemtype"],
a[href^="javascript:itemAction"],
a[href*="itemAction"],
.btn:has(i.fa-plus):has(span:contains("Добавить")),
input[name="items_id"],
/* Скрываем все элементы внутри #items */
#items *,
.accordion-body#items *,
.accordion-body.accordion-items *,
.accordion-collapse#items * {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Скрываем кнопки "Связать меня с этой заявкой" для специалистов */
/* Только кнопки с формами addme_as_requester_ и addme_as_observer_ */
button[form^="addme_as_requester_"],
button[form^="addme_as_observer_"] {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
}

