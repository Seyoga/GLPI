var isProfileAllowed = null;
var checkProfileInProgress = false;
var checkProfileCallbacks = [];

function getGLPIRootDoc() {
  if (typeof CFG_GLPI !== 'undefined' && CFG_GLPI.root_doc) {
    return CFG_GLPI.root_doc;
  }
  
  if (typeof window !== 'undefined' && window.location) {
    var pathname = window.location.pathname || '';
    var frontIndex = pathname.indexOf('/front/');
    if (frontIndex !== -1) {
      var rootDoc = pathname.substring(0, frontIndex);
      return rootDoc || '/';
    }
    var glpiIndex = pathname.indexOf('/');
    if (glpiIndex !== -1) {
      return pathname.substring(0, glpiIndex + 5);
    }
  }
  if (typeof document !== 'undefined') {
    var baseTag = document.querySelector('base');
    if (baseTag && baseTag.href) {
      try {
        var baseUrl = new URL(baseTag.href);
        var basePath = baseUrl.pathname;
        if (basePath.endsWith('/')) {
          basePath = basePath.slice(0, -1);
        }
        return basePath || '';
      } catch (e) {}
    }
  }
  return '';
}

function joinRootPath(rootDoc, path) {
  var root = rootDoc || '';
  if (root === '/') {
    root = '';
  }
  if (root.endsWith('/')) {
    root = root.slice(0, -1);
  }
  var p = path || '';
  if (p.charAt(0) !== '/') {
    p = '/' + p;
  }
  return root + p;
}

var PLUGIN_PATH = (function() {
  if (typeof window.CUSTOMHELPDESK_PLUGIN_PATH !== 'undefined') {
    return window.CUSTOMHELPDESK_PLUGIN_PATH;
  }
  if (typeof document !== 'undefined' && document.currentScript) {
    var scriptSrc = document.currentScript.src;
    var match = scriptSrc.match(/(.*\/plugins\/customhelpdesk\/)/);
    if (match) {
      return match[1].replace(/\/$/, '');
    }
  }
  return '/glpi/plugins/customhelpdesk';
})();

(function() {
  try {
    var style = document.createElement("style");
    style.id = "custom-helpdesk-critical-styles-immediate-users";
    
    var css = `
      /* Базовые стили для фона и основных контейнеров интерфейса пользователей */
      body { background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important; background-attachment: fixed !important; min-height: 100vh !important; }
      .page, .page-body, .page-body.container-fluid, .container-fluid { background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important; background-attachment: fixed !important; }
      header.navbar, header.navbar.navbar-expand-lg { background: linear-gradient(90deg, #ffffff 0%, #f8f9fa 50%, #f1f3f5 100%) !important; background-attachment: fixed !important; border: none !important; box-shadow: none !important; }
      #grid_1957401110, .masonry_grid { display: none !important; visibility: hidden !important; opacity: 0 !important; height: 0 !important; overflow: hidden !important; pointer-events: none !important; }
      #tabspanel { display: none !important; visibility: hidden !important; opacity: 0 !important; height: 0 !important; overflow: hidden !important; pointer-events: none !important; }
    `;
    try {
      var href = window.location.href || "";
      var path = window.location.pathname || "";
      if (href.indexOf("/plugins/customhelpdesk/front/closed_tickets.php") !== -1 ||
          path.indexOf("closed_tickets.php") !== -1) {
        css += `
          aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),
          aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child),
          .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),
          .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child),
          .sidebar .navbar-nav .nav-item:not(:first-child),
          .sidebar .navbar-nav > .nav-item:not(:first-child),
          .navbar-vertical .navbar-nav .nav-item:not(:first-child),
          .navbar-vertical .navbar-nav > .nav-item:not(:first-child),
          .navbar.navbar-vertical .navbar-nav .nav-item:not(:first-child),
          .navbar.navbar-vertical .navbar-nav > .nav-item:not(:first-child),
          .navbar.navbar-vertical.navbar-expand-lg .navbar-nav .nav-item:not(:first-child),
          .navbar.navbar-vertical.navbar-expand-lg .navbar-nav > .nav-item:not(:first-child) {
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
        `;
      }
    } catch (e) {}
    style.textContent = css;
    var head = document.head || document.getElementsByTagName("head")[0] || document.documentElement;
    if (head) {
      head.insertBefore(style, head.firstChild);
      if (document.body) {
        document.body.style.setProperty("background", "linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%)", "important");
        document.body.style.setProperty("background-attachment", "fixed", "important");
        document.body.style.setProperty("min-height", "100vh", "important");
      }
    }
  } catch(e) {}
})();

function addCustomStyles() {
  if ($('#custom-helpdesk-styles').length > 0) {
    return;
  }
  
    var customCSS = `
    /* Скрыть элемент "Удалено" */
    a.list-group-item[href*="is_deleted=1"] {
          display: none !important;
        }

    /* Скрыть маленькие кнопки создания тикетов */
    a.btn-sm.btn-outline-secondary[href*="helpdesk.public.php"] {
          display: none !important;
        }

    /* Альтернативный вариант - скрыть все маленькие кнопки создания */
    a.btn-sm.btn-outline-secondary {
        display: none !important;
    }

    /* Стили для большой кнопки создания тикета - удалены, кнопки больше не используются */

    /* Стили для таблицы заявок */
    #user-tickets-card {
        margin-top: 20px;
    }

    #user-tickets-card .table {
        margin-bottom: 0;
        font-size: 14px !important;
    }

    #user-tickets-card .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        font-size: 14px !important;
        padding: 10px 8px;
    }

    #user-tickets-card .table tbody td {
        font-size: 14px !important;
        padding: 10px 8px;
    }

    #user-tickets-card .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    #user-tickets-card .table tbody tr.ticket-clickable:hover {
        background-color: #e7f3ff;
        cursor: pointer;
    }

    #user-tickets-card .table tbody td a {
        color: #333;
        text-decoration: none;
        font-size: 14px !important;
    }

    #user-tickets-card .table tbody td a:hover {
        color: #007bff;
        text-decoration: underline;
    }

    /* Увеличенный размер для заголовка "Мои заявки" */
    #user-tickets-card .card-header h4 {
        font-size: 20px !important;
        padding: 14px 18px;
    }

    #user-tickets-card .card-header h4 i {
        font-size: 20px;
        margin-right: 8px;
    }

    /* Увеличенный размер для бейджей статусов */
    #user-tickets-card .badge {
        font-size: 13px !important;
        padding: 5px 10px;
    }

    /* Увеличенный размер для кнопки просмотра закрытых заявок */
    #user-tickets-card .btn {
        font-size: 14px !important;
        padding: 8px 18px;
    }

    /* Стили для таблицы закрытых заявок (closed_tickets.php) */
    .card .table {
        font-size: 15px !important;
    }

    .card .table thead th {
        font-size: 15px !important;
        padding: 10px 8px;
        font-weight: 600;
    }

    .card .table tbody td {
        font-size: 15px !important;
        padding: 10px 8px;
    }

    .card .table .badge {
        font-size: 14px !important;
        padding: 5px 10px;
    }

    /* Увеличенный размер для оценок в закрытых заявках */
    .card .table tbody td:nth-child(5) {
        font-size: 16px !important;
    }

    /* Увеличенный размер для заголовка "Закрытые заявки" */
    .card-header h3 {
        font-size: 20px !important;
        padding: 14px 18px;
    }

    .card-header h3 i {
        font-size: 20px;
        margin-right: 8px;
    }

    /* Увеличенный размер для кнопки "Вернуться к заявкам" */
    .card-footer .btn {
        font-size: 15px !important;
        padding: 8px 18px;
    }

    /* Стили для кнопки закрытия выбранной заявки - удалены, кнопки больше не используются */

    /* Стили для блока контактов */
    #custom-helpdesk-contacts {
        font-family: Arial, sans-serif;
    }

    #custom-helpdesk-contacts h5 {
        font-size: 16px;
        font-weight: 600;
    }

    #custom-helpdesk-contacts strong {
        color: #333;
        font-size: 14px;
    }

    /* Стили для кнопки закрытия задачи */
    #custom-close-ticket-btn {
        text-align: center;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin: 20px 0;
    }

    #btn-close-ticket {
        min-width: 200px;
    }

    #btn-close-ticket:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Стили для формы оценки */
    #ticket-rating-modal .rating-stars {
        user-select: none;
    }

    #ticket-rating-modal .rating-star {
        display: inline-block;
        transition: all 0.2s ease;
    }

    #ticket-rating-modal .rating-star:hover {
        transform: scale(1.3) !important;
    }

    #ticket-rating-modal .modal-body {
        padding: 30px;
    }

    #ticket-rating-modal .rating-text {
        font-size: 18px;
        font-weight: 600;
        margin-top: 15px;
    }

    /* Стили для категорий */
    #categories-card {
        margin-top: 20px;
    }

    .category-group {
        margin-bottom: 30px;
    }

    /* Увеличенные стили для заголовков категорий */
    .category-toggle {
        font-weight: 600;
        text-align: left;
        font-size: 16px !important;
        padding: 12px 16px !important;
        min-height: 48px;
        display: flex;
        align-items: center;
        line-height: 1.4;
    }

    .category-toggle i {
        margin-right: 10px;
        transition: transform 0.3s ease;
        font-size: 16px;
    }

    /* Увеличенные стили для списка категорий (белый фон) */
    .category-list {
        max-height: 400px;
        overflow-y: auto;
        padding: 12px;
        background-color: #ffffff !important;
        border-radius: 6px;
        margin-top: 10px;
        border: 1px solid #e1e5e9;
    }
    
    /* Стили для скроллбара в списке категорий */
    .category-list::-webkit-scrollbar {
        width: 8px;
    }
    
    .category-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .category-list::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    .category-list::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Увеличенные отступы для элементов категорий */
    .category-item {
        margin-bottom: 8px;
    }

    /* Увеличенные стили для кнопок подкатегорий */
    .category-select {
        transition: all 0.2s ease;
        font-size: 15px !important;
        padding: 10px 14px !important;
        min-height: 44px;
        display: flex;
        align-items: center;
        line-height: 1.4;
        white-space: normal;
        word-wrap: break-word;
    }

    .category-select:hover {
        transform: translateX(5px);
    }

    .category-select.btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    #selected-category-info {
        border-left: 4px solid #007bff;
        font-size: 15px;
        padding: 10px 14px;
    }
    
    /* Увеличенный размер для заголовка карточки категорий */
    #categories-card .card-header h4 {
        font-size: 20px !important;
        padding: 14px 18px;
    }
    
    #categories-card .card-header h4 i {
        font-size: 20px;
        margin-right: 8px;
    }

    /* Скрыть поля на странице ticket.form.php */
    /* Поле "Тип" */
    label[for^="type_"],
    select[name="type"],
    select[id^="dropdown_type"],
    .form-field:has(label[for^="type_"]),
    .form-field:has(select[name="type"]),
    .form-field:has(select[id^="dropdown_type"]) {
        display: none !important;
    }

    /* Скрыть select2 контейнер для поля "Тип" */
    select[name="type"] + span.select2,
    select[id^="dropdown_type"] + span.select2 {
        display: none !important;
    }

    /* Поле "Согласование" */
    label[for^="global_validation_"] {
        display: none !important;
    }

    /* Скрыть аккордеон "Участники" */
    #heading-actor,
    button[data-bs-target="#actors"] {
        display: none !important;
    }

    /* Скрыть аккордеон "Связанные заявки" */
    #linked_tickets-heading {
        display: none !important;
    }

    /* Скрыть поле "Наблюдатели" */
    label[for^="observer_"],
    select[data-actor-type="observer"],
    .form-field:has(label[for^="observer_"]),
    .form-field:has(select[data-actor-type="observer"]) {
        display: none !important;
    }

    /* Скрыть select2 контейнер для поля "Наблюдатели" */
    select[data-actor-type="observer"] + span.select2 {
        display: none !important;
    }

    /* Скрыть поле "Категория" */
    label[for^="dropdown_itilcategories_id_"],
    select[name="itilcategories_id"],
    select[id^="dropdown_itilcategories_id"],
    .form-field:has(label[for^="dropdown_itilcategories_id_"]),
    .form-field:has(select[name="itilcategories_id"]) {
        display: none !important;
    }

    /* Скрыть select2 контейнер для поля "Категория" */
    select[name="itilcategories_id"] + span.select2,
    select[id^="dropdown_itilcategories_id"] + span.select2 {
        display: none !important;
    }

    /* Стили для sidebar - цвет фона будет установлен через JavaScript */
    /* Скрыть кнопки в sidebar кроме первой (иконка GLPI) */
    .sidebar .navbar-nav .nav-item:not(:first-child) {
        display: none !important;
    }

    /* Скрыть все элементы меню кроме первого (логотип GLPI) */
    .sidebar .navbar-nav > .nav-item:not(:first-child) {
        display: none !important;
    }

    /* Скрыть кнопку "Свернуть меню" */
    .sidebar .reduce-menu {
        display: none !important;
    }

    /* Скрыть кнопку "Главная" в sidebar */
    .sidebar .nav-item[data-bs-original-title="Главная"],
    .sidebar .nav-item[title="Главная"] {
        display: none !important;
    }

    /* Скрыть breadcrumb "Заявки" на ticket.form.php */
    .breadcrumb-item a[href*="ticket.php"][title="Заявки"],
    .breadcrumb-item a[href*="/front/ticket.php"] {
        display: none !important;
    }
    .breadcrumb-item:has(a[href*="ticket.php"][title="Заявки"]) {
        display: none !important;
    }

    /* Скрыть кнопки навбара на ticket.form.php */
    .nav.navbar-nav a[href*="helpdesk.public.php?create_ticket=1"][title="Добавить"],
    .nav.navbar-nav a[href*="ticket.php"][title="Поиск"],
    .nav.navbar-nav a.show-saved-searches[data-itemtype="ticket"][title="Список"] {
        display: none !important;
    }
    .nav-item:has(a[href*="helpdesk.public.php?create_ticket=1"][title="Добавить"]),
    .nav-item:has(a[href*="ticket.php"][title="Поиск"]),
    .nav-item:has(a.show-saved-searches[data-itemtype="ticket"][title="Список"]) {
        display: none !important;
    }

    /* Элегантный дизайн для header (плашка сверху) с согласованным градиентом */
    header.navbar {
        background: linear-gradient(90deg, #ffffff 0%, #f8f9fa 50%, #f1f3f5 100%) !important;
        border-bottom: 1px solid #e9ecef !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
    }

    /* Стили для breadcrumb - цвет фона как у страницы */
    header.navbar .breadcrumb {
        background-color: transparent !important;
        background: transparent !important;
    }

    /* Стили для navbar-brand - размер 192x79 */
    .sidebar .navbar-brand {
        width: 192px !important;
        height: 79px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
    }

    /* Стили для логотипа внутри navbar-brand - увеличенный размер вдвое (200x110 вместо 100x55) */
    .sidebar .navbar-brand .glpi-logo,
    .sidebar .navbar-brand span.glpi-logo {
        width: 200px !important;
        height: 110px !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        max-width: 100% !important;
        max-height: 100% !important;
    }
    
    /* Контейнер navbar-brand с overflow для скрытия переполнения */
    .sidebar .navbar-brand {
        overflow: hidden !important;
    }

    /* Единый согласованный градиент для всех страниц helpdesk (для плавного перехода) */
    body:has(.page-body.container-fluid),
    body:has([href*="helpdesk.public.php"]),
    body:has([href*="tracking.injector.php"]),
    body:has([href*="ticket.form.php"]),
    body:has([href*="closed_tickets.php"]) {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
        background-attachment: fixed !important;
        min-height: 100vh !important;
    }

    /* Применяем единый согласованный градиент к основным контейнерам страниц */
    .page,
    .page-body.container-fluid {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
        background-attachment: fixed !important;
    }

    /* Единый согласованный градиент для страниц helpdesk.public.php, tracking.injector.php, ticket.form.php, closed_tickets.php */
    body[data-page*="helpdesk"],
    body[data-page*="tracking"],
    body[data-page*="ticket"],
    body:has([href*="/front/helpdesk.public.php"]),
    body:has([href*="/front/tracking.injector.php"]),
    body:has([href*="/front/ticket.form.php"]),
    body:has([href*="closed_tickets.php"]) {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
        background-attachment: fixed !important;
        min-height: 100vh !important;
    }
    
    /* Sidebar с ИДЕНТИЧНЫМ градиентом как у page-body (чтобы не было стыка) */
    .sidebar,
    .navbar-vertical,
    aside.navbar {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
        background-attachment: fixed !important;
    }
    
    /* Элегантный дизайн для карточек - белые с чистыми тенями */
    .card {
        background-color: #ffffff !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06), 0 2px 4px rgba(0, 0, 0, 0.04) !important;
        border: 1px solid #e1e5e9 !important;
        border-radius: 8px !important;
    }
    
    .card-header {
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%) !important;
        border-bottom: 1px solid #e1e5e9 !important;
        padding: 16px 20px !important;
    }
    
    .card-body {
        background-color: #ffffff !important;
    }
    
    /* Элегантный дизайн для таблиц */
    .table {
        background-color: #ffffff !important;
    }
    
    .table thead th {
        background: linear-gradient(180deg, #fafbfc 0%, #f5f7fa 100%) !important;
        border-bottom: 1px solid #e1e5e9 !important;
        color: #1a1f3a !important;
        font-weight: 600 !important;
    }
    
    .table tbody tr {
        border-bottom: 1px solid #f0f2f5 !important;
    }
    
    .table tbody tr:hover {
        background-color: #fafbfc !important;
    }
    
    /* Приятный жёлтый дизайн для кнопок категорий (более тёмный и приглушённый) */
    .btn-outline-primary.category-toggle,
    .category-toggle.btn-outline-primary {
        background: linear-gradient(180deg, #f4d03f 0%, #f39c12 100%) !important;
        border: 1px solid #d68910 !important;
        color: #1a1f3a !important;
        font-weight: 600 !important;
    }
    
    .btn-outline-primary.category-toggle:hover,
    .category-toggle.btn-outline-primary:hover {
        background: linear-gradient(180deg, #f39c12 0%, #d68910 100%) !important;
        border-color: #b9770e !important;
        box-shadow: 0 2px 8px rgba(211, 137, 16, 0.25) !important;
        color: #1a1f3a !important;
    }
    
    /* Белые кнопки подкатегорий */
    .btn-outline-secondary.category-select,
    .category-select.btn-outline-secondary {
        background: #ffffff !important;
        border: 1px solid #e1e5e9 !important;
        color: #1a1f3a !important;
    }
    
    .btn-outline-secondary.category-select:hover,
    .category-select.btn-outline-secondary:hover {
        background: #f8f9fa !important;
        border-color: #d1d9e6 !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        color: #1a1f3a !important;
    }
  `;
  $('<style id="custom-helpdesk-styles">').text(customCSS).appendTo('head');
}

// Функция для проверки профиля пользователя
function checkProfile(callback) {
  if (callback) {
    checkProfileCallbacks.push(callback);
  }
  
  if (isProfileAllowed !== null) {
    checkProfileCallbacks.forEach(function(cb) {
      cb(isProfileAllowed);
    });
    checkProfileCallbacks = [];
    return;
  }
  
  if (checkProfileInProgress) {
    return;
  }
  
  checkProfileInProgress = true;
  
  $.ajax({
    url: PLUGIN_PATH + '/front/ajax.check_profile.php',
    method: 'GET',
    dataType: 'json',
    cache: false,
    success: function(data) {
      isProfileAllowed = data.is_user === true;
      if (data.is_user && data.user_subcategory) {
        window.userSubcategory = data.user_subcategory;
      } else {
        window.userSubcategory = 'full';
      }
      
      // Вызываем все накопленные callbacks
      checkProfileCallbacks.forEach(function(cb) {
        cb(isProfileAllowed);
      });
      checkProfileCallbacks = [];
      checkProfileInProgress = false;
    },
    error: function(xhr, status, error) {
      if (xhr.status !== 403 && xhr.status !== 0) {
        console.warn('Ошибка при проверке профиля:', status, error, 'Статус:', xhr.status);
      }
      isProfileAllowed = false;
      
      // Вызываем все накопленные callbacks
      checkProfileCallbacks.forEach(function(cb) {
        cb(false);
      });
      checkProfileCallbacks = [];
      checkProfileInProgress = false;
    }
  });
}

$(document).ready(function() {
  checkProfile(function(allowed) {
    if (!allowed) {
      return;
    }
    addCustomStyles();
    
    function applyPinkGradient() {
      var gradientStartTime = performance.now ? performance.now() : Date.now();
      var unifiedGradient = 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%)';
      var headerGradient = 'linear-gradient(90deg, #ffffff 0%, #f8f9fa 50%, #f1f3f5 100%)';
      $('body').css({
        'background': unifiedGradient,
        'background-attachment': 'fixed',
        'min-height': '100vh'
      });
      $('.page, .page-body.container-fluid').css({
        'background': unifiedGradient,
        'background-attachment': 'fixed'
      });
      $('.sidebar, .navbar-vertical').css({
        'background': unifiedGradient,
        'background-attachment': 'fixed'
      });
      $('header.navbar').css({
        'background': headerGradient,
        'background-attachment': 'fixed'
      });
      
      var gradientEndTime = performance.now ? performance.now() : Date.now();
    }
    
    // Применяем фон сразу при загрузке страницы
    applyPinkGradient();
    
    // --- Настройка Sidebar и Header (глобальная функция для всех страниц) ---
    function customizeSidebar() {
      // Применяем розоватый градиент
      applyPinkGradient();
      
      // Устанавливаем стили для header
      $('header.navbar').css({
        'border': 'none',
        'box-shadow': 'none',
        '-webkit-box-shadow': 'none',
        '-moz-box-shadow': 'none'
      });
      
      $('header.navbar .breadcrumb').css({
        'background-color': 'transparent',
        'background': 'transparent'
      });
      $('.sidebar .navbar-brand').css({
        'width': '192px',
        'height': '79px',
        'display': 'flex',
        'align-items': 'center',
        'justify-content': 'center',
        'padding': '0',
        'overflow': 'hidden'
      });
      $('.sidebar .navbar-brand .glpi-logo, .sidebar .navbar-brand span.glpi-logo').css({
        'width': '200px',
        'height': '110px',
        'background-size': 'contain',
        'background-repeat': 'no-repeat',
        'background-position': 'center',
        'max-width': '100%',
        'max-height': '100%'
      });
      $('.sidebar .navbar-nav .nav-item').each(function(index) {
        var $item = $(this);
        if (index > 0) {
          $item.hide();
        }
      });
      $('.sidebar .navbar-nav > .nav-item').not(':first').hide();
      $('.sidebar .reduce-menu').hide();
      $('.sidebar .navbar-nav .nav-link').not('.navbar-brand').each(function() {
        var $link = $(this);
        if (!$link.hasClass('navbar-brand') && !$link.closest('.navbar-brand').length) {
          $link.closest('.nav-item').hide();
        }
      });
      $('.sidebar .nav-item[data-bs-original-title="Главная"]').hide();
      $('.sidebar .nav-item[title="Главная"]').hide();
      $('.sidebar .nav-item').has('a[title="Главная"]').hide();
      $('.sidebar .nav-item').has('a[href*="helpdesk.public.php"]').not(':first').hide();
    }
    if (window.location.href.includes('/front/helpdesk.public.php')) {
    function hideTicketsCard() {
      $('#grid_1957401110').hide();
      $('.masonry_grid').hide();
    }
    hideTicketsCard();
    $('head').append('<style>#grid_1957401110, .masonry_grid{display:none!important}</style>');
    var cardObserver = new MutationObserver(function() {
      hideTicketsCard();
    });
    cardObserver.observe(document.body, { childList: true, subtree: true });
    setTimeout(function(){ cardObserver.disconnect(); }, 5000);
    if (window.location.search.indexOf('create_ticket=1') === -1) {
      
      var selectedCategoryId = null;
      var selectedCategoryName = null;
      function escapeHtml(text) {
        if (!text) return '';
        var map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
      }

      function loadCategories() {
        var pluginPath = PLUGIN_PATH + '/front/ajax.categories.php';
        
        $.ajax({
          url: pluginPath,
          method: 'GET',
          dataType: 'json',
          success: function(categories) {
            // Используем новые ключи 'bgu' и 'zkgu' вместо хардкода названий
            if (categories && (categories['bgu'] || categories['zkgu'])) {
              displayCategories(categories);
            } else {
              // Показываем пустые колонки
              displayCategories({'bgu': [], 'zkgu': [], '_meta': {}});
            }
          },
          error: function(xhr, status, error) {
            console.error('Ошибка при загрузке категорий:', status, error);
            console.error('Ответ сервера:', xhr.responseText);
            // Показываем пустые колонки даже при ошибке
            displayCategories({'bgu': [], 'zkgu': [], '_meta': {}});
          }
        });
      }

      function displayCategories(categories) {
        // Проверяем, что categories существует
        if (!categories) {
          categories = {'bgu': [], 'zkgu': [], '_meta': {}};
        }
        if (!categories['bgu']) {
          categories['bgu'] = [];
        }
        if (!categories['zkgu']) {
          categories['zkgu'] = [];
        }
        if (!categories['_meta']) {
          categories['_meta'] = {};
        }

        // Получаем названия главных категорий из метаданных
        var bguCategoryName = categories['_meta']['bgu_category_name'] || 'БГУ';
        var zkguCategoryName = categories['_meta']['zkgu_category_name'] || 'ЗКГУ';

        // Фильтруем категории в зависимости от подкатегории пользователя
        var userSubcategory = window.userSubcategory || 'full';
        
        if (userSubcategory === 'bgu') {
          // Показываем только категории БГУ
          categories['zkgu'] = [];
        } else if (userSubcategory === 'zkgu') {
          // Показываем только категории ЗКГУ
          categories['bgu'] = [];
        } else {
          // Полный пользователь - показываем все категории
        }

        // Заполняем колонку БГУ
        var $bguList = $('#category-list-1c-buhgalteriya');
        if ($bguList.length) {
          $bguList.empty();
          // Обновляем название кнопки
          var $bguToggle = $('.category-toggle[data-group="1c-buhgalteriya"]');
          if ($bguToggle.length && bguCategoryName) {
            $bguToggle.html('<i class="fas fa-chevron-down"></i> ' + escapeHtml(bguCategoryName));
          }
          
          if (categories['bgu'] && categories['bgu'].length > 0) {
            categories['bgu'].forEach(function(cat) {
              var catName = cat.name || 'Без названия';
              var catId = cat.id || 0;
              var catHtml = `
                <div class="category-item mb-2">
                  <button class="btn btn-outline-secondary w-100 text-start category-select" 
                          data-category-id="${catId}" 
                          data-category-name="${escapeHtml(catName)}"
                          data-group="1c-buhgalteriya">
                    ${escapeHtml(catName)}
                  </button>
                </div>
    `;
              $bguList.append(catHtml);
            });
          } else {
            $bguList.html('<p class="text-muted" style="font-size: 15px; padding: 10px;">Нет категорий</p>');
          }
        }

        // Заполняем колонку ЗКГУ
        var $zkguList = $('#category-list-1c-zarplata-kadry');
        if ($zkguList.length) {
          $zkguList.empty();
          // Обновляем название кнопки
          var $zkguToggle = $('.category-toggle[data-group="1c-zarplata-kadry"]');
          if ($zkguToggle.length && zkguCategoryName) {
            $zkguToggle.html('<i class="fas fa-chevron-down"></i> ' + escapeHtml(zkguCategoryName));
          }
          
          if (categories['zkgu'] && categories['zkgu'].length > 0) {
            categories['zkgu'].forEach(function(cat) {
              var catName = cat.name || 'Без названия';
              var catId = cat.id || 0;
              var catHtml = `
                <div class="category-item mb-2">
                  <button class="btn btn-outline-secondary w-100 text-start category-select" 
                          data-category-id="${catId}" 
                          data-category-name="${escapeHtml(catName)}"
                          data-group="1c-zarplata-kadry">
                    ${escapeHtml(catName)}
                  </button>
                </div>
              `;
              $zkguList.append(catHtml);
            });
          } else {
            $zkguList.html('<p class="text-muted" style="font-size: 15px; padding: 10px;">Нет категорий</p>');
          }
        }

      }

      // Сначала показываем пустые колонки категорий
      function showEmptyCategories() {
        var userSubcategory = window.userSubcategory || 'full';
        var showBGU = (userSubcategory === 'full' || userSubcategory === 'bgu');
        var showZKGU = (userSubcategory === 'full' || userSubcategory === 'zkgu');
        
        // Определяем класс для колонок в зависимости от количества видимых категорий
        var colClass = 'col-md-6';
        if (!showBGU || !showZKGU) {
          colClass = 'col-md-12'; // Если показываем только одну категорию, занимаем всю ширину
        }
        
        var emptyCategoriesHtml = `
          <div class="card mt-4" id="categories-card">
            <div class="card-header">
              <h4 class="mb-0"><i class="fas fa-list"></i> Категории заявок</h4>
            </div>
            <div class="card-body">
              <div class="row">
        `;
        
        // Колонка БГУ (название будет обновлено после загрузки категорий)
        if (showBGU) {
          emptyCategoriesHtml += `
                <div class="${colClass}">
                  <div class="category-group">
                    <button class="btn btn-outline-primary w-100 mb-2 category-toggle" data-group="1c-buhgalteriya">
                      <i class="fas fa-chevron-down"></i> БГУ
                    </button>
                    <div class="category-list" id="category-list-1c-buhgalteriya" style="display: none;">
                      <p class="text-muted" style="font-size: 15px; padding: 10px;">Загрузка...</p>
                    </div>
                  </div>
                </div>
          `;
        }
        
        // Колонка ЗКГУ (название будет обновлено после загрузки категорий)
        if (showZKGU) {
          emptyCategoriesHtml += `
                <div class="${colClass}">
                  <div class="category-group">
                    <button class="btn btn-outline-primary w-100 mb-2 category-toggle" data-group="1c-zarplata-kadry">
                      <i class="fas fa-chevron-down"></i> ЗКГУ
                    </button>
                    <div class="category-list" id="category-list-1c-zarplata-kadry" style="display: none;">
                      <p class="text-muted" style="font-size: 15px; padding: 10px;">Загрузка...</p>
                    </div>
                  </div>
                </div>
          `;
        }
        
        emptyCategoriesHtml += `
              </div>
            </div>
          </div>
        `;

        // Находим контейнер для добавления категорий
        var $container = $(".masonry_grid").first().closest(".container-fluid");
        if (!$container.length) {
          $container = $(".container-fluid").last();
        }
        if (!$container.length) {
          $container = $("main").first();
        }
        if (!$container.length) {
          $container = $(".page-content, .content, #page, .content-wrapper").first();
        }
        if (!$container.length) {
          $container = $("body > .wrapper, body > .page-wrapper, body > div.container-fluid").first();
        }
        if ($container.length) {
          $container.prepend(emptyCategoriesHtml);
        } else {
          var $header = $("header, .header, .navbar").first();
          if ($header.length) {
            $header.after(emptyCategoriesHtml);
          } else {
            $("body").prepend(emptyCategoriesHtml);
      }
        }

        // Обработчики для развертывания/свертывания категорий
        $(document).on('click', '.category-toggle', function() {
          var group = $(this).data('group');
          var $list = $('#category-list-' + group);
          var $icon = $(this).find('i');
          
          if ($list.is(':visible')) {
            $list.slideUp();
            $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
          } else {
            $list.slideDown();
            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
          }
        });

        // Обработчики для выбора категории - автоматически создаем заявку при выборе
        $(document).on('click', '.category-select', function() {
          // Получаем данные категории
          var categoryId = $(this).data('category-id');
          var categoryName = $(this).data('category-name');
          
          // Проверяем, что категория выбрана
          if (!categoryId) {
            console.error('ID категории не найден');
            return;
          }
          
          // Сохраняем выбранную категорию в sessionStorage для использования в форме
          sessionStorage.setItem('selectedCategoryId', categoryId);
          if (categoryName) {
            sessionStorage.setItem('selectedCategoryName', categoryName);
          }
          
          // Переходим на страницу создания заявки через стандартный механизм GLPI
          // helpdesk.public.php автоматически перенаправит на tracking.injector.php
          // Переход происходит очень быстро (практически мгновенно), так что промежуточный шаг незаметен
          var rootDoc = getGLPIRootDoc();
          var url = joinRootPath(rootDoc, 'front/helpdesk.public.php?create_ticket=1');
          if (categoryId) {
            url += '&itilcategories_id=' + encodeURIComponent(categoryId);
          }
          
          // Переходим на страницу создания заявки
          window.location.href = url;
        });
      }

      // Показываем пустые колонки сразу
      showEmptyCategories();

      // Загружаем категории и заполняем колонки
      setTimeout(loadCategories, 300);

      // --- Загружаем и отображаем заявки пользователя ---
      function loadUserTickets() {
        var pluginPath = PLUGIN_PATH + '/front/ajax.tickets.php';
        
        $.ajax({
          url: pluginPath,
          method: 'GET',
          dataType: 'json',
          success: function(tickets) {
            if (tickets && tickets.length > 0) {
              // displayTickets теперь фильтрует закрытые задачи и показывает кнопку для их просмотра
              displayTickets(tickets);
            } else {
              displayNoTickets();
            }
            // Кнопки больше не добавляем - заявка создается автоматически при выборе категории
          },
          error: function() {
            console.error('Ошибка при загрузке заявок');
            displayNoTickets();
            // Кнопки больше не добавляем
          }
        });
      }

      // Переменные для хранения выбранной заявки больше не нужны - заявки открываются сразу при клике

      function displayTickets(tickets) {
        // Фильтруем закрытые задачи (status_id = 6)
        var activeTickets = tickets.filter(function(ticket) {
          return ticket.status_id != 6; // Исключаем закрытые
        });
        
        var closedTicketsCount = tickets.length - activeTickets.length;
        
        var ticketsHtml = `
          <div class="card mt-4" id="user-tickets-card">
            <div class="card-header">
              <h4><i class="fas fa-tasks"></i> Мои заявки</h4>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Текущий этап</th>
                      <th>Задача</th>
                      <th>Дата создания</th>
                    </tr>
                  </thead>
                  <tbody>
        `;

        if (activeTickets.length > 0) {
          activeTickets.forEach(function(ticket) {
            var statusClass = getStatusClass(ticket.status_id);
            // Заменяем "В ожидании" на "Примите задачу" если статус пришел как "В ожидании"
            var displayStatus = ticket.status === 'В ожидании' ? 'Примите задачу' : ticket.status;
            // Все строки кликабельны, независимо от статуса
            ticketsHtml += `
              <tr class="ticket-row ticket-clickable" 
                  data-ticket-id="${ticket.id}" 
                  data-ticket-url="${ticket.url}"
                  style="cursor: pointer;">
                <td>
                  <span class="badge ${statusClass}">${displayStatus}</span>
                </td>
                <td>
                  ${escapeHtml(ticket.name)}
                </td>
                <td>${ticket.date}</td>
              </tr>
            `;
          });
        } else {
          ticketsHtml += `
            <tr>
              <td colspan="3" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                У вас нет активных заявок
              </td>
            </tr>
          `;
        }

        ticketsHtml += `
                  </tbody>
                </table>
              </div>
        `;
        
        // Добавляем кнопку для просмотра закрытых задач, если они есть
        if (closedTicketsCount > 0) {
          ticketsHtml += `
              <div class="text-center mt-3">
                <a  href="${PLUGIN_PATH}/front/closed_tickets.php" 
                   class="btn btn-outline-secondary">
                  <i class="fas fa-archive"></i> Просмотреть закрытые заявки (${closedTicketsCount})
          </a>
        </div>
      `;
        }
        
        ticketsHtml += `
            </div>
          </div>
        `;

        // Находим контейнер для добавления таблицы
        var $container = $(".masonry_grid").first().closest(".container-fluid");
        if (!$container.length) {
          $container = $(".container-fluid").last();
        }
        if (!$container.length) {
          $container = $("main").first();
        }
        if ($container.length) {
          $container.append(ticketsHtml);
      } else {
          $("body").append(ticketsHtml);
        }

        // Обработчик клика на строку заявки - сразу открываем заявку
        $(document).on('click', '.ticket-clickable', function(e) {
          var $row = $(this);
          var ticketUrl = $row.data('ticket-url');
          
          // Если есть URL, переходим на страницу заявки
          if (ticketUrl) {
            window.location.href = ticketUrl;
          }
        });
      }

      function displayNoTickets() {
        var noTicketsHtml = `
          <div class="card mt-4" id="user-tickets-card">
            <div class="card-body text-center py-5">
              <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
              <p class="text-muted">У вас пока нет заявок</p>
            </div>
          </div>
        `;

        // Находим контейнер для добавления
        var $container = $(".masonry_grid").first().closest(".container-fluid");
        if (!$container.length) {
          $container = $(".container-fluid").last();
        }
        if (!$container.length) {
          $container = $("main").first();
        }
        if ($container.length) {
          $container.append(noTicketsHtml);
        } else {
          $("body").append(noTicketsHtml);
        }
      }

      // Функция addCreateTicketButton() удалена - кнопки больше не нужны
      // Заявка создается автоматически при выборе категории

      function getStatusClass(statusId) {
        var classes = {
          1: 'bg-secondary',      // Новая
          2: 'bg-info',           // В работе (назначена)
          3: 'bg-info',           // В работе (запланирована)
          4: 'bg-warning',       // В ожидании
          5: 'bg-success',       // Решена
          6: 'bg-dark'           // Закрыто
        };
        return classes[statusId] || 'bg-secondary';
      }

      // Загружаем заявки после небольшой задержки
      setTimeout(loadUserTickets, 500);

      // --- Добавляем блок с контактами менеджера по организации справа снизу ---
      function renderManagerContacts(manager) {
        if (!manager) {
          return;
        }
        var name = manager.name || '';
        var phone = manager.phone || '';

        if (!name && !phone) {
          return;
        }

        var safeName = $('<div>').text(name).html();
        var safePhone = $('<div>').text(phone).html();

        var contactsHtml = `
        <div id="custom-helpdesk-contacts" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); padding: 20px; max-width: 300px;">
          <h5 style="margin-bottom: 15px; color: #333;">
            <i class="fas fa-user-tie"></i> Ваш менеджер
          </h5>
          <div style="margin-bottom: 10px;">
            <strong>` + safeName + `</strong>
            <i class="fas fa-question-circle text-primary" style="margin-left: 5px; cursor: pointer;" title="менеджер"></i>
          </div>
          <div style="color: #666; font-size: 14px;">
            <div>
              <i class="fas fa-mobile-alt"></i> ` + safePhone + `
            </div>
          </div>
        </div>
        `;

        $('body').append(contactsHtml);
      }

      // Загружаем информацию о менеджере для текущей организации через AJAX
      $.ajax({
        url: PLUGIN_PATH + '/front/ajax.entity_manager.php',
        method: 'GET',
        dataType: 'json',
        cache: false,
        success: function(response) {
          if (response && response.success && response.manager) {
            renderManagerContacts(response.manager);
          }
        },
        error: function() {
          // В случае ошибки ничего не показываем
        }
      });
    }
    
    // Применяем настройки sidebar сразу для helpdesk.public.php
    customizeSidebar();
    
    // Применяем с небольшой задержкой на случай динамической загрузки
    setTimeout(customizeSidebar, 500);
    setTimeout(customizeSidebar, 1000);
    setTimeout(customizeSidebar, 2000);
    
    // Наблюдаем за изменениями DOM на случай динамической загрузки (включая tracking.injector.php)
    var sidebarObserver = new MutationObserver(function() {
      customizeSidebar();
    });
    
    // Наблюдаем за изменениями в body для отслеживания динамической загрузки
    if (document.body) {
      sidebarObserver.observe(document.body, {
        childList: true,
        subtree: true
      });
      
      // Отключаем наблюдатель через 30 секунд
      setTimeout(function() {
        sidebarObserver.disconnect();
      }, 30000);
    }
    
    // Дополнительно применяем настройки при изменении URL (для tracking.injector.php)
    var lastUrl = location.href;
    setInterval(function() {
      var currentUrl = location.href;
      if (currentUrl !== lastUrl) {
        lastUrl = currentUrl;
        setTimeout(customizeSidebar, 100);
        setTimeout(customizeSidebar, 500);
        setTimeout(customizeSidebar, 1000);
      }
    }, 1000);

    // --- Новая часть: скрыть поля формы в режиме создания заявки (create_ticket=1) ---
    if (window.location.search.indexOf('create_ticket=1') !== -1 || window.location.href.indexOf('?create_ticket=1') !== -1) {

      // --- Предзаполнение категории из выбранной на главной странице ---
      function setSelectedCategory() {
        try {
          // Получаем категорию из URL параметра (стандартный механизм GLPI) или из sessionStorage
          var urlParams = new URLSearchParams(window.location.search);
          var categoryId = urlParams.get('itilcategories_id') || sessionStorage.getItem('selectedCategoryId');
          
          if (!categoryId) {
            return; // Нет категории для установки
          }
          
          // Преобразуем в число
          categoryId = parseInt(categoryId, 10);
          if (isNaN(categoryId) || categoryId <= 0) {
            return;
          }
          
          // Ждем загрузки select2
          var attempts = 0;
          var maxAttempts = 30;
          
          function setCategory() {
            attempts++;
            
            // Ищем select для категории (может быть с разными ID)
            var $categorySelect = $('select[name="itilcategories_id"], select[id^="dropdown_itilcategories_id"]');
            
            if (!$categorySelect.length) {
              if (attempts < maxAttempts) {
                setTimeout(setCategory, 300);
              }
              return;
            }
            
            // Проверяем, что select2 инициализирован
            if (!$categorySelect.data('select2')) {
              if (attempts < maxAttempts) {
                setTimeout(setCategory, 300);
              }
              return;
            }
            
            // Проверяем, не установлена ли уже категория
            var currentValue = $categorySelect.val();
            if (currentValue == categoryId) {
              // Категория уже установлена
              sessionStorage.removeItem('selectedCategoryId');
              sessionStorage.removeItem('selectedCategoryName');
              return;
            }
            
              // Используем стандартный метод GLPI setValue для установки категории
              // Это метод, который уже есть в коде GLPI для работы с select2 через AJAX
              try {
                // Вызываем событие setValue, которое обработает GLPI
                $categorySelect.trigger('setValue', [categoryId]);
                
                // Скрываем поля после установки категории (форма может обновиться)
                setTimeout(function() {
                  if (typeof window.hideTypeField === 'function') {
                    window.hideTypeField();
                  }
                  hideObserverField();
                  hideCategoryField();
                }, 500);
                setTimeout(function() {
                  if (typeof window.hideTypeField === 'function') {
                    window.hideTypeField();
                  }
                  hideObserverField();
                  hideCategoryField();
                }, 1500);
                setTimeout(function() {
                  hideCategoryField();
                }, 2500);
                
                // Очищаем sessionStorage после установки
                setTimeout(function() {
                  sessionStorage.removeItem('selectedCategoryId');
                  sessionStorage.removeItem('selectedCategoryName');
                }, 2000);
                
            } catch (e) {
              // Пробуем прямой способ
              try {
                $categorySelect.val(categoryId).trigger('change.select2');
              } catch (e2) {
              }
            }
          }
          
          // Начинаем попытки установки после небольшой задержки
          setTimeout(setCategory, 1000);
          
        } catch (e) {
          console.error('Ошибка в setSelectedCategory:', e);
        }
      }
      
      // Устанавливаем категорию после загрузки страницы
      $(document).ready(function() {
        setTimeout(setSelectedCategory, 1500);
      });
      
      // Дополнительная попытка после полной загрузки
      $(window).on('load', function() {
        setTimeout(setSelectedCategory, 1000);
      });

      // Функция для скрытия поля "Тип" (делаем доступной глобально)
      window.hideTypeField = function() {
        // Ищем все поля "Тип" по разным селекторам
        $('label[for^="type_"]').each(function() {
          var $label = $(this);
          var forAttr = $label.attr('for') || '';
          
          // Скрываем метку
          $label.hide();
          
          // Скрываем весь контейнер поля
          var $fieldContainer = $label.closest('.form-field, .row.col-12.mb-2');
          if ($fieldContainer.length) {
            $fieldContainer.hide();
          }
          
          // Скрываем select
          var $select = $('select[name="type"], select[id^="dropdown_type"], select#' + forAttr);
          if ($select.length) {
            $select.hide();
            // Скрываем select2 контейнер
            $select.nextAll('span.select2').hide();
            // Скрываем контейнер select
            $select.closest('.field-container, .col-lg-9, .col-sm-9').hide();
          }
        });
        
        // Также скрываем по select напрямую
        $('select[name="type"], select[id^="dropdown_type"]').each(function() {
          var $select = $(this);
          $select.hide();
          $select.nextAll('span.select2').hide();
          $select.closest('.form-field, .row.col-12.mb-2, .field-container').hide();
        });
      }
      
      // Функция для скрытия поля "Наблюдатели"
      function hideObserverField() {
        // Ищем поле "Наблюдатели" по label с for^="observer_"
        $('label[for^="observer_"]').each(function() {
          var $label = $(this);
          var forAttr = $label.attr('for') || '';
          
          // Скрываем метку
          $label.hide();
          
          // Скрываем весь контейнер поля
          var $fieldContainer = $label.closest('.form-field, .row.col-12.mb-2');
          if ($fieldContainer.length) {
            $fieldContainer.hide();
          }
          
          // Скрываем select с data-actor-type="observer"
          var $select = $('select[data-actor-type="observer"], select#' + forAttr);
          if ($select.length) {
            $select.hide();
            // Скрываем select2 контейнер
            $select.nextAll('span.select2').hide();
            // Скрываем контейнер select
            $select.closest('.field-container, .col-lg-9, .col-sm-9').hide();
          }
        });
        
        // Также скрываем по select напрямую
        $('select[data-actor-type="observer"]').each(function() {
          var $select = $(this);
          $select.hide();
          $select.nextAll('span.select2').hide();
          $select.closest('.form-field, .row.col-12.mb-2, .field-container').hide();
        });
      }
      
      // Функция для скрытия поля "Категория"
      function hideCategoryField() {
        // Ищем поле "Категория" по label с for^="dropdown_itilcategories_id_"
        $('label[for^="dropdown_itilcategories_id_"]').each(function() {
          var $label = $(this);
          var forAttr = $label.attr('for') || '';
          
          // Проверяем, что это действительно поле категории (текст метки содержит "Категория")
          var labelText = $label.text().trim();
          if (labelText.indexOf('Категория') === -1 && labelText.indexOf('Category') === -1) {
            return; // Пропускаем, если это не поле категории
          }
          
          // Скрываем метку
          $label.hide();
          
          // Скрываем весь контейнер поля
          var $fieldContainer = $label.closest('.form-field, .row.col-12.mb-2');
          if ($fieldContainer.length) {
            $fieldContainer.hide();
          }
          
          // Скрываем select
          var $select = $('select[name="itilcategories_id"], select[id^="dropdown_itilcategories_id"], select#' + forAttr);
          if ($select.length) {
            $select.hide();
            // Скрываем select2 контейнер
            $select.nextAll('span.select2').hide();
            // Скрываем контейнер select
            $select.closest('.field-container, .col-lg-9, .col-sm-9').hide();
          }
        });
        
        // Также скрываем по select напрямую
        $('select[name="itilcategories_id"], select[id^="dropdown_itilcategories_id"]').each(function() {
          var $select = $(this);
          // Проверяем, что это поле категории (ищем label рядом)
          var $label = $('label[for="' + $select.attr('id') + '"]');
          if ($label.length) {
            var labelText = $label.text().trim();
            if (labelText.indexOf('Категория') === -1 && labelText.indexOf('Category') === -1) {
              return; // Пропускаем, если это не поле категории
            }
          }
          
          $select.hide();
          $select.nextAll('span.select2').hide();
          $select.closest('.form-field, .row.col-12.mb-2, .field-container').hide();
        });
      }
      
      // Скрываем поля сразу
      hideTypeField();
      hideObserverField();
      
      // Скрываем поле категории только если категория уже выбрана (из sessionStorage)
      var categoryId = sessionStorage.getItem('selectedCategoryId');
      if (categoryId) {
        // Категория будет установлена, скрываем поле после установки
        setTimeout(function() {
          hideCategoryField();
        }, 2000); // Даем время на установку категории
      }
      
      // Наблюдаем за изменениями DOM, чтобы скрывать поля при динамическом обновлении
      var typeObserver = new MutationObserver(function(mutations) {
        hideTypeField();
        hideObserverField();
        
        // Скрываем поле категории, если категория уже установлена
        var $categorySelect = $('select[name="itilcategories_id"], select[id^="dropdown_itilcategories_id"]');
        if ($categorySelect.length && $categorySelect.val() && $categorySelect.val() !== '0' && $categorySelect.val() !== '') {
          hideCategoryField();
        }
      });
      
      // Наблюдаем за изменениями в body
      typeObserver.observe(document.body, {
        childList: true,
        subtree: true
      });
      
      // Отключаем наблюдатель через 30 секунд (чтобы не нагружать систему)
      setTimeout(function() {
        typeObserver.disconnect();
      }, 30000);

      // Скрыть только конкретное поле "Наблюдатели" (по тексту метки и связанной select2)
      $('.card').each(function() {
        var $card = $(this);

        // Найти метку с текстом "Наблюдатели" (устойчиво к пробелам и вложенным элементам)
        var $obsLabel = $card.find('label').filter(function() {
          return ($(this).text() || '').replace(/\s+/g,' ').trim().match(/^Наблюдател/i);
        }).first();

        if ($obsLabel.length) {
          var forAttr = $obsLabel.attr('for') || '';

          // Скрываем саму метку и её ближайший контейнер (.form-field)
          $obsLabel.hide();
          $obsLabel.closest('.form-field, .col-lg-3, .col-sm-3').hide();

          // Попробуем найти исходный select по атрибуту for / имени / id
          var $select = $card.find('select#' + forAttr)
                         .add($card.find('select[id*="' + forAttr.replace(/^\D+/,'') + '"]'))
                         .add($card.find('select[name*="observer"]'))
                         .first();

          if ($select.length) {
            // скрыть сам select
            $select.hide();
            // скрыть контейнер, где находится select (обычно .form-field или .col-lg-9)
            $select.closest('.form-field, .col-lg-9, .col-sm-9').hide();

            // найти связанный Select2-контейнер рядом и скрыть его
            var $sel2 = $select.nextAll('span.select2.select2-container').first();
            if (!$sel2.length) {
              // fallback: найти select2 внутри той же карточки, ближайший по расположению
              $sel2 = $card.find('span.select2.select2-container').filter(function() {
                return $(this).closest('.form-field').prev().find('label[for="' + forAttr + '"]').length;
              }).first();
            }
            if ($sel2.length) {
              $sel2.hide();
              $sel2.closest('.form-field, .col-lg-9, .col-sm-9').hide();
            }
          } else {
            // если select не найден — прячем ближайший select2-контейнер справа от метки
            $obsLabel.closest('.form-row, .row, .form-field').find('span.select2.select2-container').first().hide();
          }
        }
      });

      // Точно скрыть select2 контейнер только для поля "Наблюдатели"
      $('.card').each(function() {
        var $card = $(this);

        var $obsLabel = $card.find('label').filter(function() {
          return ($(this).text() || '').replace(/\s+/g,' ').trim().match(/^Наблюдател/i);
        }).first();
        if (!$obsLabel.length) { return; }

        var forAttr = $obsLabel.attr('for') || '';

        // Найти исходный select (если он есть)
        var $select = $card.find('select#' + forAttr)
                        .add($card.find('select[name*="observer"]'))
                        .add($card.find('select[name="observers[]"]'))
                        .first();

        if ($select.length) {
          // чаще всего select2 контейнер следует после исходного select
          var $sel2 = $select.nextAll('span.select2.select2-container').first();
          if (!$sel2.length) {
            // fallback: найти внутри карточки select2 с actor-field
            $sel2 = $card.find('span.select2.select2-container').filter(function() {
              return $(this).find('.actor-field').length;
            }).first();
          }
          if ($sel2.length) {
            $sel2.hide();
            $sel2.closest('.form-field, .col-lg-9, .col-sm-9').hide();
          }
        } else {
          // если исходного select нет — скрыть по классу actor-field внутри карточки
          var $sel2b = $card.find('span.select2-selection.actor-field').closest('span.select2.select2-container').first();
          if ($sel2b.length) {
            $sel2b.hide();
            $sel2b.closest('.form-field, .col-lg-9, .col-sm-9').hide();
          }
        }
      });

    }
    } // Конец блока helpdesk.public.php
    
    // Обработка для tracking.injector.php
    if (window.location.href.includes('/front/tracking.injector.php')) {
      // Применяем настройки sidebar и header
      customizeSidebar();
      
      // Применяем с небольшой задержкой на случай динамической загрузки
      setTimeout(customizeSidebar, 500);
      setTimeout(customizeSidebar, 1000);
      setTimeout(customizeSidebar, 2000);
      
      // Обработка параметра itilcategories_id из URL (при прямом переходе)
      // Это нужно, чтобы категория автоматически выбиралась при переходе
      function setCategoryFromUrl() {
        try {
          var urlParams = new URLSearchParams(window.location.search);
          var categoryId = urlParams.get('itilcategories_id') || sessionStorage.getItem('selectedCategoryId');
          
          if (!categoryId) {
            return; // Нет категории для установки
          }
          
          // Преобразуем в число
          categoryId = parseInt(categoryId, 10);
          if (isNaN(categoryId) || categoryId <= 0) {
            return;
          }
          
          // Ждем загрузки select2
          var attempts = 0;
          var maxAttempts = 30;
          
          function setCategory() {
            attempts++;
            
            // Ищем select для категории (может быть с разными ID)
            var $categorySelect = $('select[name="itilcategories_id"], select[id^="dropdown_itilcategories_id"]');
            
            if (!$categorySelect.length) {
              if (attempts < maxAttempts) {
                setTimeout(setCategory, 300);
              }
              return;
            }
            
            // Проверяем, что select2 инициализирован
            if (!$categorySelect.data('select2')) {
              if (attempts < maxAttempts) {
                setTimeout(setCategory, 300);
              }
              return;
            }
            
            // Проверяем, не установлена ли уже категория
            var currentValue = $categorySelect.val();
            if (currentValue == categoryId) {
              // Категория уже установлена
              sessionStorage.removeItem('selectedCategoryId');
              sessionStorage.removeItem('selectedCategoryName');
              return;
            }
            
            // Используем стандартный метод GLPI setValue для установки категории
            try {
              $categorySelect.trigger('setValue', [categoryId]);
              
              // Очищаем sessionStorage после установки
              setTimeout(function() {
                sessionStorage.removeItem('selectedCategoryId');
                sessionStorage.removeItem('selectedCategoryName');
              }, 2000);
            } catch (e) {
              // Пробуем прямой способ
              try {
                $categorySelect.val(categoryId).trigger('change.select2');
              } catch (e2) {
              }
            }
          }
          
          // Начинаем попытки установки после небольшой задержки
          setTimeout(setCategory, 1000);
        } catch (e) {
          // Игнорируем ошибки
        }
      }
      
      // Вызываем функцию установки категории
      setCategoryFromUrl();
      
      // Флаг для отслеживания отправки формы
      var formSubmitted = false;
      var redirectExecuted = false;
      var initialUrl = window.location.href;
      
      // Функция для проверки успешного создания заявки и редиректа
      function checkTicketCreatedAndRedirect() {
        // Если редирект уже выполнен, не проверяем снова
        if (redirectExecuted) {
          return;
        }
        
        var foundSuccess = false;
        var currentUrl = window.location.href;
        
        // Проверяем, изменился ли URL после отправки формы
        if (formSubmitted && currentUrl !== initialUrl) {
          foundSuccess = true;
        }
        
        // Проверяем по URL параметрам
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('id') || urlParams.has('_added') || currentUrl.includes('_added')) {
          foundSuccess = true;
        }
        
        // Проверяем наличие элементов успеха - более агрессивный поиск
        var $body = $('body');
        
        // Ищем любые иконки галочки
        var $checkIcons = $body.find('i.fa-check, i.fa-check-circle, i.ti-check, i.ti.ti-check, .fa-check, .fa-check-circle, .ti-check');
        if ($checkIcons.length > 0) {
          $checkIcons.each(function() {
            var $icon = $(this);
            // Если иконка видима и находится в основном контенте
            if ($icon.is(':visible')) {
              var $parent = $icon.closest('.page-body, .container-fluid, main, .content, .card, .card-body');
              if ($parent.length > 0) {
                foundSuccess = true;
                return false; // break
              }
            }
          });
        }
        
        // Ищем сообщения об успехе
        if ($body.find('.alert-success, .text-success, [class*="success"]').length > 0) {
          foundSuccess = true;
        }
        
        // Проверяем наличие текста об успешном создании
        var bodyText = $body.text();
        if (bodyText.indexOf('создан') !== -1 || bodyText.indexOf('успешно') !== -1 || bodyText.indexOf('добавлен') !== -1) {
          // Но только если это не форма создания
          if ($body.find('form').length === 0 || $body.find('button[name="add"]').length === 0) {
            foundSuccess = true;
          }
        }
        
        // Если найдена страница успеха, делаем редирект
        if (foundSuccess && !redirectExecuted) {
          redirectExecuted = true;
          // Небольшая задержка, чтобы пользователь увидел, что заявка создана
          setTimeout(function() {
            var rootDoc = getGLPIRootDoc();
            window.location.href = joinRootPath(rootDoc, 'front/helpdesk.public.php');
          }, 500);
        }
      }
      
      // Перехватываем отправку формы
      $(document).on('submit', 'form', function(e) {
        var $form = $(this);
        // Проверяем, что это форма создания заявки
        var $submitBtn = $form.find('button[name="add"], button[type="submit"]');
        if ($submitBtn.length > 0) {
          var btnText = ($submitBtn.find('span').text() || $submitBtn.text() || '').toLowerCase();
          if (btnText.indexOf('отправить') !== -1 || btnText.indexOf('сообщение') !== -1 || btnText.indexOf('создать') !== -1) {
            formSubmitted = true;
            initialUrl = window.location.href;
            
            // Начинаем проверку после отправки формы
            setTimeout(function() {
              checkTicketCreatedAndRedirect();
            }, 500);
            setTimeout(function() {
              checkTicketCreatedAndRedirect();
            }, 1000);
            setTimeout(function() {
              checkTicketCreatedAndRedirect();
            }, 1500);
            setTimeout(function() {
              checkTicketCreatedAndRedirect();
            }, 2000);
            setTimeout(function() {
              checkTicketCreatedAndRedirect();
            }, 3000);
          }
        }
      });
      
      // Проверяем сразу при загрузке страницы (на случай если уже на странице успеха)
      checkTicketCreatedAndRedirect();
      
      // Проверяем с задержками на случай динамической загрузки
      setTimeout(checkTicketCreatedAndRedirect, 500);
      setTimeout(checkTicketCreatedAndRedirect, 1000);
      setTimeout(checkTicketCreatedAndRedirect, 2000);
      
      // Наблюдаем за изменениями DOM для отслеживания появления страницы успеха
      var redirectObserver = new MutationObserver(function() {
        if (formSubmitted || redirectExecuted) {
          checkTicketCreatedAndRedirect();
        }
      });
      
      if (document.body) {
        redirectObserver.observe(document.body, {
          childList: true,
          subtree: true
        });
        
        // Отключаем наблюдатель через 15 секунд (защитное отключение)
        setTimeout(function() {
          redirectObserver.disconnect();
        }, 15000);
      }
      
      // Также отслеживаем изменения URL (на случай если страница перезагружается)
      var lastUrl = location.href;
      var urlCheckInterval = setInterval(function() {
        if (location.href !== lastUrl) {
          lastUrl = location.href;
          if (formSubmitted) {
            setTimeout(function() {
              checkTicketCreatedAndRedirect();
            }, 500);
          }
        }
        // Останавливаем проверку через 10 секунд
        if (redirectExecuted) {
          clearInterval(urlCheckInterval);
        }
      }, 200);
      
      // Останавливаем проверку URL через 15 секунд
      setTimeout(function() {
        clearInterval(urlCheckInterval);
      }, 15000);
    }
  }); // Конец callback checkProfile для helpdesk.public.php
  
  // Проверяем профиль для ticket.form.php
  checkProfile(function(allowed) {
    if (!allowed) {
      // Профиль не разрешен, не применяем функционал
      return;
    }
    
    // Добавляем CSS стили только для разрешенных профилей
    addCustomStyles();

  // Скрыть левую панель вкладок (ul#tabspanel) только на странице ticket.form.php
  if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
    // моментальное скрытие (если уже в DOM)
    $('#tabspanel').hide();

    // CSS-страховка на случай краткой отрисовки
    $('head').append('<style>#tabspanel{display:none!important}</style>');

    // Наблюдатель на случай динамической вставки через AJAX
    var tabsObserver = new MutationObserver(function() {
      var el = document.getElementById('tabspanel');
      if (el && window.getComputedStyle(el).display !== 'none') {
        el.style.display = 'none';
      }
    });
    tabsObserver.observe(document.body, { childList: true, subtree: true });

    // Отключаем наблюдатель через 5 секунд (защитное отключение)
    setTimeout(function(){ tabsObserver.disconnect(); }, 5000);

    // --- Скрыть поле "Тип" ---
    function hideTypeField() {
      // Скрываем поле "Тип" по label с for^="type_"
      $('label[for^="type_"]').each(function() {
        var $label = $(this);
        // Скрываем весь form-field
        var $formField = $label.closest('.form-field');
        if ($formField.length) {
          $formField.hide();
        } else {
          // Если form-field не найден, скрываем label и связанные элементы
          $label.hide();
          var forAttr = $label.attr('for');
          // Скрываем связанный select
          $('select[name="type"], select[id^="dropdown_type"], select#' + forAttr).hide();
          // Скрываем select2 контейнер
          $('select[name="type"], select[id^="dropdown_type"]').nextAll('span.select2').hide();
          // Скрываем field-container
          $label.nextAll('.field-container').hide();
        }
      });
    }

    // --- Скрыть поле "Согласование" ---
    function hideGlobalValidationField() {
      // Скрываем поле "Согласование" по label с for^="global_validation_"
      $('label[for^="global_validation_"]').each(function() {
        var $label = $(this);
        // Скрываем весь form-field
        var $formField = $label.closest('.form-field');
        if ($formField.length) {
          $formField.hide();
        } else {
          // Если form-field не найден, скрываем label и связанные элементы
          $label.hide();
          $label.nextAll('.field-container').hide();
        }
      });
    }

    // --- Скрыть аккордеон "Участники" ---
    function hideActorsAccordion() {
      // Скрываем весь accordion-item с id="heading-actor"
      var $actorsItem = $('#heading-actor').closest('.accordion-item');
      if ($actorsItem.length) {
        $actorsItem.hide();
      } else {
        // Альтернативный способ: скрываем по кнопке
        $('button[data-bs-target="#actors"]').closest('.accordion-item').hide();
      }
      // Также скрываем содержимое аккордеона
      $('#actors').closest('.accordion-item').hide();
    }

    // --- Скрыть аккордеон "Связанные заявки" ---
    function hideLinkedTicketsAccordion() {
      // Скрываем accordion-item с id="linked_tickets-heading"
      var $linkedItem = $('#linked_tickets-heading').closest('.accordion-item');
      if ($linkedItem.length) {
        $linkedItem.hide();
      }
      // Также скрываем содержимое аккордеона
      $('#linked_tickets').closest('.accordion-item').hide();
    }

    // Выполняем скрытие
    hideTypeField();
    hideGlobalValidationField();
    hideActorsAccordion();
    hideLinkedTicketsAccordion();

    // Наблюдатель для динамически добавляемых элементов
    var fieldsObserver = new MutationObserver(function() {
      hideTypeField();
      hideGlobalValidationField();
      hideActorsAccordion();
      hideLinkedTicketsAccordion();
    });
    fieldsObserver.observe(document.body, { childList: true, subtree: true });
    setTimeout(function(){ fieldsObserver.disconnect(); }, 10000);

    // --- Добавляем кнопку "Закрыть задачу" ---
    function addCloseTicketButton() {
      // Проверяем, что кнопка еще не добавлена
      if ($('#custom-close-ticket-btn').length) {
        return;
      }

      // Получаем ID заявки из URL
      var urlParams = new URLSearchParams(window.location.search);
      var ticketId = urlParams.get('id');

      if (!ticketId) {
        return;
      }

      // Проверяем статус заявки - не показываем кнопку, если уже закрыта
      var statusBadge = $('.badge').filter(function() {
        return $(this).text().trim().toLowerCase().includes('закрыт');
      });

      if (statusBadge.length > 0) {
        return; // Заявка уже закрыта
      }

      var closeButton = `
        <div id="custom-close-ticket-btn" style="margin: 20px 0; text-align: center;">
          <button type="button" class="btn btn-danger btn-lg" id="btn-close-ticket" data-ticket-id="${ticketId}">
            <i class="fas fa-times-circle"></i> Закрыть задачу
          </button>
        </div>
      `;

      // Пытаемся найти место для вставки кнопки (после формы или перед футером)
      var $form = $('form').first();
      if ($form.length) {
        $form.after(closeButton);
      } else {
        $('.container-fluid').last().append(closeButton);
      }

      // Обработчик клика на кнопку закрытия
      $(document).on('click', '#btn-close-ticket', function() {
        var ticketId = $(this).data('ticket-id');
        
        if (!confirm('Вы уверены, что хотите закрыть эту задачу?')) {
          return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Закрытие...');

        $.ajax({
          url: PLUGIN_PATH + '/front/ajax.close_ticket.php',
          method: 'POST',
          data: {
            ticket_id: ticketId
          },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              alert(response.message);
              // Перезагружаем страницу
              window.location.reload();
            } else {
              alert('Ошибка: ' + response.message);
              $btn.prop('disabled', false).html('<i class="fas fa-times-circle"></i> Закрыть задачу');
            }
          },
          error: function() {
            alert('Ошибка при закрытии задачи. Попробуйте обновить страницу.');
            $btn.prop('disabled', false).html('<i class="fas fa-times-circle"></i> Закрыть задачу');
          }
        });
      });
    }

    // Добавляем кнопку с задержкой, чтобы форма успела загрузиться
    setTimeout(addCloseTicketButton, 1000);

    // Также пытаемся добавить при изменении DOM (на случай AJAX загрузки)
    var closeBtnObserver = new MutationObserver(function() {
      if (!$('#custom-close-ticket-btn').length) {
        addCloseTicketButton();
      }
    });
    closeBtnObserver.observe(document.body, { childList: true, subtree: true });
    setTimeout(function() { closeBtnObserver.disconnect(); }, 10000);
    
    // --- Добавляем отображение оценки на странице тикета в правую боковую панель ---
    var ratingDisplayInProgress = false; // Флаг для предотвращения дублирования
    
    function displayTicketRating() {
      // Проверяем, что блок оценки еще не добавлен и запрос не выполняется
      if ($('#custom-ticket-rating-display').length || ratingDisplayInProgress) {
        return;
      }
      
      // Получаем ID заявки из URL
      var urlParams = new URLSearchParams(window.location.search);
      var ticketId = urlParams.get('id');
      
      if (!ticketId) {
        return;
      }
      
      // Устанавливаем флаг, что запрос выполняется
      ratingDisplayInProgress = true;
      
      // Загружаем оценку для этого тикета
      $.ajax({
        url: PLUGIN_PATH + '/front/ajax.get_rating.php',
        method: 'GET',
        data: {
          ticket_id: ticketId
        },
        dataType: 'json',
        success: function(response) {
          // Проверяем еще раз перед добавлением (на случай, если добавилось за время запроса)
          if ($('#custom-ticket-rating-display').length) {
            ratingDisplayInProgress = false;
            return;
          }
          
          if (response.has_rating && response.rating) {
            var emojis = ['', '😞', '😐', '🙂', '😊', '😍'];
            var emoji = emojis[response.rating] || '⭐';
            
            // Оформление идентично разделу "Заявка" - accordion формат
            var ratingHtml = `
              <div id="custom-ticket-rating-display" class="accordion-item">
                <h2 class="accordion-header" id="heading-rating-item">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#item-rating" aria-expanded="true" aria-controls="ticket-rating">
                    <i class="ti ti-star me-1 item-icon"></i>
                    <span class="item-title">
                      Оценка выполнения задачи
                    </span>
                  </button>
                </h2>
                <div id="item-rating" class="accordion-collapse collapse show" aria-labelledby="heading-rating-item">
                  <div class="accordion-body row m-0 mt-n2">
                    <div class="form-field row col-12 mb-2">
                      <div class="col-12 text-center">
                        <div style="font-size: 48px; margin-bottom: 10px;">
                          ${emoji}
                        </div>
                        <div style="font-size: 16px; color: #343a40; font-weight: 500;">
                          ${response.rating} из 5
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            `;
            
            // Ищем правую боковую панель
            var $rightSide = $('.itil-right-side');
            
            if ($rightSide.length) {
              // Ищем форму внутри правой панели или первый accordion-item
              var $firstAccordion = $rightSide.find('.accordion-item').first();
              if ($firstAccordion.length) {
                // Вставляем после первого accordion-item (раздел "Заявка")
                $firstAccordion.after(ratingHtml);
              } else {
                // Если не нашли accordion, ищем форму и вставляем после неё
                var $form = $rightSide.find('form').first();
                if ($form.length) {
                  $form.after(ratingHtml);
                } else {
                  // Если ничего не нашли, вставляем в начало правой панели
                  $rightSide.prepend(ratingHtml);
                }
              }
            } else {
              // Если правой панели нет, ищем альтернативное место
              var $cardFooter = $('.card-footer');
              if ($cardFooter.length) {
                $cardFooter.before(ratingHtml);
              } else {
                // Последний вариант - добавляем после формы
                var $form = $('form').first();
                if ($form.length) {
                  $form.after(ratingHtml);
                }
              }
            }
          }
          
          ratingDisplayInProgress = false;
        },
        error: function() {
          ratingDisplayInProgress = false;
        }
      });
    }
    
    // Добавляем отображение оценки с задержкой (правая панель может загружаться динамически)
    setTimeout(function() {
      if (!$('#custom-ticket-rating-display').length) {
        displayTicketRating();
      }
    }, 1000);
    
    // Также пытаемся добавить при изменении DOM (особенно когда загружается правая панель)
    var ratingObserver = new MutationObserver(function() {
      // Проверяем, появилась ли правая панель и раздел "Заявка", и нет ли еще блока оценки
      if ($('.itil-right-side').length && $('#item-main').length && !$('#custom-ticket-rating-display').length && !ratingDisplayInProgress) {
        displayTicketRating();
      }
    });
    ratingObserver.observe(document.body, { childList: true, subtree: true });
    setTimeout(function() { ratingObserver.disconnect(); }, 15000);
  }

  // Добавьте это рядом с другими проверками для ticket.form.php (или в конце блока, где скрываете поля)
  if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
    // Скрыть только метку с точным for="type_663680550"
    $('label[for="type_663680550"]').hide();
  }

  // Скрыть поле "Источник запросов" на странице ticket.form.php
  if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
    
    (function hideRequestTypeField() {
      function hideField() {
        // Найти метку по тексту "Источник запросов"
        $('label.col-form-label').filter(function() {
          return ($(this).text() || '').replace(/\s+/g,' ').trim().match(/Источник запросов/i);
        }).each(function() {
          var $label = $(this);
          var forAttr = $label.attr('for') || '';
          
          // Скрыть саму метку
          $label.hide();
          
          // Найти связанный select по id
          var $select = $('select[name="requesttypes_id"]')
                        .add($('select#' + forAttr.replace('dropdown_', '')))
                        .add($('select[id*="requesttypes_id"]'))
                        .first();
          
          if ($select.length) {
            // Скрыть select
            $select.hide();
            
            // Найти и скрыть связанный select2 контейнер
            var $sel2 = $select.nextAll('span.select2.select2-container').first();
            if ($sel2.length) {
              $sel2.hide();
            }
            
            // Скрыть весь контейнер поля
            $label.closest('.form-field, .row.mb-2').hide();
            $select.closest('.form-field, .col-lg-9, .col-sm-9').hide();
          }
        });
        
        // Дополнительная страховка: скрыть по select напрямую
        $('select[name="requesttypes_id"]').each(function() {
          $(this).hide();
          $(this).nextAll('span.select2.select2-container').first().hide();
          $(this).closest('.form-field, .row.mb-2').hide();
        });
      }
      
      // Первоначальное скрытие
      hideField();
      
      // Наблюдатель на случай динамической подгрузки
      var observer = new MutationObserver(hideField);
      observer.observe(document.body, { childList: true, subtree: true });
      
      // Отключить наблюдатель через 7 секунд
      setTimeout(function() { observer.disconnect(); }, 7000);
    })();
    
    // --- Настройка Sidebar и Header для ticket.form.php ---
    if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
      // Функция для применения настроек sidebar на ticket.form.php
      function customizeSidebarForTicketForm() {
        // Получаем цвет фона из page-body.container-fluid и применяем к sidebar
        var $pageBody = $('.page-body.container-fluid');
        var bgColor = null;
        
        if ($pageBody.length) {
          // Пробуем получить цвет через getComputedStyle
          var computedStyle = window.getComputedStyle($pageBody[0]);
          bgColor = computedStyle.backgroundColor;
          
          // Если цвет прозрачный или не определен, пробуем получить из body или page
          if (!bgColor || bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
            var $page = $('.page');
            if ($page.length) {
              var pageBg = window.getComputedStyle($page[0]).backgroundColor;
              if (pageBg && pageBg !== 'rgba(0, 0, 0, 0)' && pageBg !== 'transparent') {
                bgColor = pageBg;
              }
            }
          }
          
          // Если все еще не получили цвет, пробуем из body
          if (!bgColor || bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
            var bodyBg = window.getComputedStyle(document.body).backgroundColor;
            if (bodyBg && bodyBg !== 'rgba(0, 0, 0, 0)' && bodyBg !== 'transparent') {
              bgColor = bodyBg;
            }
          }
        }
        
        // Применяем цвет к sidebar
        if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
          $('.sidebar, .navbar-vertical').css({
            'background-color': bgColor,
            'background': bgColor
          });
        } else {
          // Fallback на стандартный цвет фона
          $('.sidebar, .navbar-vertical').css({
            'background-color': '#f8f9fa',
            'background': '#f8f9fa'
          });
        }
        
        // Устанавливаем цвет header как цвет фона страницы
        if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
          $('header.navbar').css({
            'background-color': bgColor,
            'background': bgColor,
            'border': 'none',
            'box-shadow': 'none',
            '-webkit-box-shadow': 'none',
            '-moz-box-shadow': 'none'
          });
          $('header.navbar .breadcrumb').css({
            'background-color': 'transparent',
            'background': 'transparent'
          });
        } else {
          $('header.navbar').css({
            'background-color': '#f8f9fa',
            'background': '#f8f9fa',
            'border': 'none',
            'box-shadow': 'none',
            '-webkit-box-shadow': 'none',
            '-moz-box-shadow': 'none'
          });
          $('header.navbar .breadcrumb').css({
            'background-color': 'transparent',
            'background': 'transparent'
          });
        }
        
        // Скрываем все кнопки в sidebar кроме первого элемента (иконка GLPI)
        $('.sidebar .navbar-nav .nav-item').each(function(index) {
          var $item = $(this);
          // Оставляем только первый элемент (иконка GLPI для перехода на главное меню)
          if (index > 0) {
            $item.hide();
          }
        });
        
        // Дополнительно скрываем все элементы меню кроме первого
        $('.sidebar .navbar-nav > .nav-item').not(':first').hide();
        
        // Скрываем кнопку "Свернуть меню"
        $('.sidebar .reduce-menu').hide();
        
        // Скрываем все ссылки в меню кроме navbar-brand
        $('.sidebar .navbar-nav .nav-link').not('.navbar-brand').each(function() {
          var $link = $(this);
          if (!$link.hasClass('navbar-brand') && !$link.closest('.navbar-brand').length) {
            $link.closest('.nav-item').hide();
          }
        });
        
        // Скрываем кнопку "Главная" в sidebar
        $('.sidebar .nav-item[data-bs-original-title="Главная"]').hide();
        $('.sidebar .nav-item[title="Главная"]').hide();
        $('.sidebar .nav-item').has('a[title="Главная"]').hide();
      }
      
      // Применяем настройки sidebar и header
      customizeSidebarForTicketForm();
      
      // Применяем с небольшой задержкой на случай динамической загрузки
      setTimeout(customizeSidebarForTicketForm, 500);
      setTimeout(customizeSidebarForTicketForm, 1000);
      setTimeout(customizeSidebarForTicketForm, 2000);
      setTimeout(customizeSidebarForTicketForm, 3000);
      
      // Скрываем breadcrumb "Заявки"
      function hideBreadcrumbTickets() {
        // Различные варианты селекторов для breadcrumb
        $('a[href*="ticket.php"][title="Заявки"]').closest('.breadcrumb-item').hide();
        $('a[href*="/front/ticket.php"][title="Заявки"]').closest('.breadcrumb-item').hide();
        $('.breadcrumb-item:has(a[href*="ticket.php"][title="Заявки"])').hide();
        $('.breadcrumb-item:has(a[href*="/front/ticket.php"])').hide();
        $('a[href*="ticket.php"]').filter(function() {
          return $(this).attr('title') === 'Заявки' || $(this).text().trim() === 'Заявки';
        }).closest('.breadcrumb-item').hide();
      }
      
      // Скрываем кнопки навбара: "Добавить", "Поиск", "Список"
      function hideNavbarButtons() {
        // Скрываем кнопку "Добавить"
        $('a[href*="helpdesk.public.php?create_ticket=1"][title="Добавить"]').closest('.nav-item').hide();
        $('a[href*="helpdesk.public.php?create_ticket=1"]').filter(function() {
          return $(this).attr('title') === 'Добавить' || $(this).find('span').text().trim() === 'Добавить';
        }).closest('.nav-item').hide();
        
        // Скрываем кнопку "Поиск"
        $('a[href*="ticket.php"][title="Поиск"]').closest('.nav-item').hide();
        $('a[href*="/glpi/front/ticket.php"][title="Поиск"]').closest('.nav-item').hide();
        $('a[title="Поиск"]').filter(function() {
          return $(this).find('i').hasClass('ti-search') || $(this).find('span').text().trim() === 'Поиск';
        }).closest('.nav-item').hide();
        
        // Скрываем кнопку "Список"
        $('a.show-saved-searches[data-itemtype="ticket"][title="Список"]').closest('.nav-item').hide();
        $('a.show-saved-searches[data-itemtype="ticket"]').filter(function() {
          return $(this).attr('title') === 'Список' || $(this).find('span').text().trim() === 'Список';
        }).closest('.nav-item').hide();
        
        // Скрываем весь контейнер с кнопками, если все кнопки скрыты
        var $navContainer = $('.nav.navbar-nav.border-start');
        if ($navContainer.length) {
          var visibleItems = $navContainer.find('.nav-item:visible').length;
          if (visibleItems === 0) {
            $navContainer.hide();
          }
        }
      }
      
      // Применяем скрытие сразу
      hideBreadcrumbTickets();
      hideNavbarButtons();
      
      // Применяем с задержкой на случай динамической загрузки
      setTimeout(hideBreadcrumbTickets, 500);
      setTimeout(hideNavbarButtons, 500);
      setTimeout(hideBreadcrumbTickets, 1000);
      setTimeout(hideNavbarButtons, 1000);
      
      // Функция для изменения текста кнопок "Отказать" и "Одобрить"
      function changeTicketButtonsText() {
        // Ищем кнопку "Отказать" (name="add_reopen")
        $('button[name="add_reopen"]').each(function() {
          var $button = $(this);
          var $span = $button.find('span');
          if ($span.length && ($span.text().trim() === 'Отказать' || $span.text().trim() === 'Refuse')) {
            $span.text('Не решена');
          }
        });
        
        // Ищем кнопку "Одобрить" (name="add_close")
        $('button[name="add_close"]').each(function() {
          var $button = $(this);
          var $span = $button.find('span');
          if ($span.length && ($span.text().trim() === 'Одобрить' || $span.text().trim() === 'Approve')) {
            $span.text('Решена');
          }
        });
      }
      
      // Функция для показа формы оценки
      function showRatingForm(ticketId) {
        // Проверяем, не показана ли уже форма
        if ($('#ticket-rating-modal').length > 0) {
          return;
        }
        
        // Создаем модальное окно с формой оценки
        var ratingModal = `
          <div class="modal fade" id="ticket-rating-modal" tabindex="-1" role="dialog" aria-labelledby="ratingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="ratingModalLabel">
                    <i class="fas fa-star"></i> Оцените выполнение задачи
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <p class="text-center mb-4">Пожалуйста, оцените качество выполнения задачи:</p>
                  <div class="rating-stars text-center mb-4" style="font-size: 48px;">
                    <span class="rating-star" data-rating="1" style="cursor: pointer; margin: 0 5px; opacity: 0.3; transition: all 0.2s;">😞</span>
                    <span class="rating-star" data-rating="2" style="cursor: pointer; margin: 0 5px; opacity: 0.3; transition: all 0.2s;">😐</span>
                    <span class="rating-star" data-rating="3" style="cursor: pointer; margin: 0 5px; opacity: 0.3; transition: all 0.2s;">🙂</span>
                    <span class="rating-star" data-rating="4" style="cursor: pointer; margin: 0 5px; opacity: 0.3; transition: all 0.2s;">😊</span>
                    <span class="rating-star" data-rating="5" style="cursor: pointer; margin: 0 5px; opacity: 0.3; transition: all 0.2s;">😍</span>
                  </div>
                  <div class="text-center">
                    <p id="rating-text" class="text-muted mb-0" style="font-size: 16px; min-height: 24px;">Выберите оценку</p>
                  </div>
                  <input type="hidden" id="selected-rating" value="0">
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                  <button type="button" class="btn btn-primary" id="save-rating-btn" disabled>
                    <i class="fas fa-save"></i> Сохранить оценку
                  </button>
                </div>
              </div>
            </div>
          </div>
        `;
        
        $('body').append(ratingModal);
        
        var $modal = $('#ticket-rating-modal');
        var selectedRating = 0;
        
        // Обработчики для звезд (смайликов)
        $modal.find('.rating-star').on('mouseenter', function() {
          var rating = $(this).data('rating');
          highlightStars(rating);
        });
        
        $modal.find('.rating-star').on('mouseleave', function() {
          if (selectedRating > 0) {
            highlightStars(selectedRating);
          } else {
            $modal.find('.rating-star').css('opacity', '0.3');
            $modal.find('.rating-star').css('transform', 'scale(1)');
          }
        });
        
        $modal.find('.rating-star').on('click', function() {
          selectedRating = $(this).data('rating');
          $('#selected-rating').val(selectedRating);
          highlightStars(selectedRating);
          updateRatingText(selectedRating);
          $('#save-rating-btn').prop('disabled', false);
        });
        
        function highlightStars(rating) {
          $modal.find('.rating-star').each(function() {
            var starRating = $(this).data('rating');
            if (starRating <= rating) {
              $(this).css('opacity', '1');
              $(this).css('transform', 'scale(1.2)');
            } else {
              $(this).css('opacity', '0.3');
              $(this).css('transform', 'scale(1)');
            }
          });
        }
        
        function updateRatingText(rating) {
          var texts = {
            1: 'Очень плохо',
            2: 'Плохо',
            3: 'Нормально',
            4: 'Хорошо',
            5: 'Отлично'
          };
          $('#rating-text').text(texts[rating] || 'Выберите оценку');
        }
        
        // Обработчик сохранения оценки
        $('#save-rating-btn').on('click', function() {
          if (selectedRating === 0) {
            return;
          }
          
          var $btn = $(this);
          $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Сохранение...');
          
          // Получаем CSRF токен из формы на странице
          var csrfToken = '';
          var $form = $('form').first();
          
          if ($form.length) {
            var $csrfInput = $form.find('input[name="_glpi_csrf_token"]');
            if ($csrfInput.length) {
              csrfToken = $csrfInput.val();
            }
          }
          
          // Если не нашли в форме, пробуем другие варианты
          if (!csrfToken) {
            csrfToken = $('meta[name="csrf-token"]').attr('content') || 
                        $('input[name="_glpi_csrf_token"]').val() || 
                        $('input[name="csrf_token"]').val() || '';
          }
          
          var postData = {
            ticket_id: ticketId,
            rating: selectedRating
          };
          
          // Добавляем CSRF токен только если он найден
          if (csrfToken) {
            postData._glpi_csrf_token = csrfToken;
          }
          
          $.ajax({
            url: PLUGIN_PATH + '/front/ajax.save_rating.php',
            method: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response, textStatus, xhr) {
              if (response.success) {
                // Закрываем модальное окно
                $modal.modal('hide');
                
                // Продолжаем стандартное действие кнопки "Решена"
                // Находим оригинальную кнопку и отправляем форму
                setTimeout(function() {
                  var $originalBtn = $('button[name="add_close"]');
                  if ($originalBtn.length) {
                    // Отправляем форму
                    var $form = $originalBtn.closest('form');
                    if ($form.length) {
                      // Добавляем скрытое поле для триггера действия
                      if (!$form.find('input[name="add_close"]').length) {
                        $form.append('<input type="hidden" name="add_close" value="1">');
                      }
                      $form.submit();
                    } else {
                      // Если формы нет, создаем временную форму
                      var tempForm = $('<form>', {
                        'method': 'POST',
                        'action': window.location.href
                      });
                      tempForm.append('<input type="hidden" name="id" value="' + ticketId + '">');
                      tempForm.append('<input type="hidden" name="add_close" value="1">');
                      $('body').append(tempForm);
                      tempForm.submit();
                    }
                  }
                }, 300);
              } else {
                alert('Ошибка: ' + (response.message || 'Не удалось сохранить оценку'));
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Сохранить оценку');
              }
            },
            error: function(xhr, status, error) {
              // Проверяем, пустой ли ответ
              if (!xhr.responseText || xhr.responseText.trim() === '') {
                alert('Ошибка: Сервер вернул пустой ответ. Проверьте логи PHP для деталей.');
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Сохранить оценку');
                return;
              }
              
              // Пытаемся распарсить JSON ответ, если есть
              try {
                var errorResponse = JSON.parse(xhr.responseText);
                alert('Ошибка при сохранении оценки: ' + (errorResponse.message || error || 'Неизвестная ошибка'));
              } catch (e) {
                alert('Ошибка при сохранении оценки (HTTP ' + xhr.status + '): ' + (error || 'Неизвестная ошибка'));
              }
              
              $btn.prop('disabled', false).html('<i class="fas fa-save"></i> Сохранить оценку');
            }
          });
        });
        
        // Показываем модальное окно
        $modal.modal('show');
        
        // Удаляем модальное окно после закрытия
        $modal.on('hidden.bs.modal', function() {
          $modal.remove();
        });
      }
      
      // Перехватываем клик на кнопку "Решена" (add_close)
      $(document).on('click', 'button[name="add_close"]', function(e) {
        // Получаем ID заявки из URL
        var urlParams = new URLSearchParams(window.location.search);
        var ticketId = urlParams.get('id');
        
        if (!ticketId) {
          // Пробуем получить из формы
          var $form = $(this).closest('form');
          if ($form.length) {
            var ticketIdInput = $form.find('input[name="id"]');
            if (ticketIdInput.length) {
              ticketId = ticketIdInput.val();
            }
          }
        }
        
        if (ticketId) {
          // Всегда останавливаем стандартное действие и показываем форму оценки
          e.preventDefault();
          e.stopPropagation();
          // При повторном закрытии задачи окно оценки показывается всегда;
          // предыдущая оценка будет перезаписана при сохранении (ajax.save_rating.php поддерживает update)
          showRatingForm(ticketId);
          return false;
        }
      });
      
      // Функция для замены статуса "В ожидании" на "Примите задачу"
      function changeStatusText() {
        // Ищем все badge элементы и другие элементы со статусом
        $('.badge, span.badge, td .badge, [class*="status"]').each(function() {
          var $element = $(this);
          var text = $element.text().trim();
          
          // Проверяем, содержит ли элемент точно текст "В ожидании"
          if (text === 'В ожидании') {
            $element.text('Примите задачу');
          }
        });
        
        // Также ищем в других местах, где может отображаться статус
        $('td, div, span, label').each(function() {
          var $element = $(this);
          var text = $element.text().trim();
          
          // Проверяем, содержит ли элемент только текст "В ожидании" (без дочерних элементов)
          if (text === 'В ожидании' && $element.children().length === 0) {
            $element.text('Примите задачу');
          }
        });
      }
      
      // Применяем изменение текста кнопок сразу
      changeTicketButtonsText();
      changeStatusText();
      
      // Применяем с задержкой на случай динамической загрузки
      setTimeout(changeTicketButtonsText, 500);
      setTimeout(changeStatusText, 500);
      setTimeout(changeTicketButtonsText, 1000);
      setTimeout(changeStatusText, 1000);
      setTimeout(changeTicketButtonsText, 2000);
      setTimeout(changeStatusText, 2000);
      
      // Наблюдаем за изменениями DOM
      var ticketFormObserver = new MutationObserver(function() {
        hideBreadcrumbTickets();
        hideNavbarButtons();
        customizeSidebarForTicketForm();
        changeTicketButtonsText();
        changeStatusText();
      });
      
      if (document.body) {
        ticketFormObserver.observe(document.body, {
          childList: true,
          subtree: true
        });
        
        // Отключаем наблюдатель через 30 секунд
        setTimeout(function() {
          ticketFormObserver.disconnect();
        }, 30000);
      }
    }
    
    } // Конец блока ticket.form.php
  }); // Конец callback checkProfile для ticket.form.php
}); // Конец $(document).ready

// Проверяем профиль для closed_tickets.php
checkProfile(function(allowed) {
  if (!allowed) {
    return;
  }
  
  // Добавляем CSS стили только для разрешенных профилей
  addCustomStyles();
  
  // --- Настройка Sidebar и Header для closed_tickets.php ---
  if (window.location.href.indexOf('/plugins/customhelpdesk/front/closed_tickets.php') !== -1 ||
      window.location.pathname.indexOf('/plugins/customhelpdesk/front/closed_tickets.php') !== -1 ||
      window.location.pathname.indexOf('closed_tickets.php') !== -1) {
    
    function applyCustomizations() {
      // Применяем серо-белый градиент (как в helpdesk.public.php) с максимальным приоритетом
      var gradient = 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%)';
      
      // Применяем градиент к body с !important через setProperty
      if (document.body) {
        document.body.style.setProperty('background', gradient, 'important');
        document.body.style.setProperty('background-attachment', 'fixed', 'important');
        document.body.style.setProperty('min-height', '100vh', 'important');
      }
      
      // Применяем градиент к основным контейнерам с !important
      $('.page, .page-body.container-fluid').each(function() {
        this.style.setProperty('background', gradient, 'important');
        this.style.setProperty('background-attachment', 'fixed', 'important');
      });
      
      // Применяем градиент к sidebar и header с !important
      $('.sidebar, .navbar-vertical, header.navbar').each(function() {
        this.style.setProperty('background', gradient, 'important');
        this.style.setProperty('background-attachment', 'fixed', 'important');
        this.style.setProperty('border', 'none', 'important');
        this.style.setProperty('box-shadow', 'none', 'important');
      });
      
      $('header.navbar .breadcrumb').css({
        'background-color': 'transparent',
        'background': 'transparent'
      });
      
      $('.sidebar .navbar-brand').css({
        'width': '192px',
        'height': '79px',
        'display': 'flex',
        'align-items': 'center',
        'justify-content': 'center',
        'padding': '0',
        'overflow': 'hidden'
      });
      
      $('.sidebar .navbar-brand .glpi-logo, .sidebar .navbar-brand span.glpi-logo').css({
        'width': '200px',
        'height': '110px',
        'background-size': 'contain',
        'background-repeat': 'no-repeat',
        'background-position': 'center',
        'max-width': '100%',
        'max-height': '100%'
      });
      
      $('.sidebar .navbar-nav .nav-item').each(function(index) {
        if (index > 0) {
          $(this).hide();
        }
      });
      
      $('.sidebar .navbar-nav > .nav-item').not(':first').hide();
      $('.sidebar .reduce-menu').hide();
      $('.sidebar .nav-item[data-bs-original-title="Главная"]').hide();
      $('.sidebar .nav-item[title="Главная"]').hide();
      $('.sidebar .nav-item').has('a[title="Главная"]').hide();
    }
    
    function hideBreadcrumbTickets() {
      // Скрываем breadcrumb "Заявки"
      $('a[href*="ticket.php"][title="Заявки"]').closest('.breadcrumb-item').hide();
      $('a[href*="/front/ticket.php"][title="Заявки"]').closest('.breadcrumb-item').hide();
      $('.breadcrumb-item:has(a[href*="ticket.php"][title="Заявки"])').hide();
      $('a[href*="ticket.php"]').filter(function() {
        return $(this).attr('title') === 'Заявки' || $(this).text().trim() === 'Заявки';
      }).closest('.breadcrumb-item').hide();
      
      // Скрываем breadcrumb "Поддержка"
      $('a[href*="ticket.php"][title="Поддержка"]').closest('.breadcrumb-item').hide();
      $('a[title="Поддержка"]').filter(function() {
        return $(this).text().trim().indexOf('Поддержка') !== -1 || $(this).find('i').hasClass('ti-headset');
      }).closest('.breadcrumb-item').hide();
    }
    
    function hideNavbarButtons() {
      // Скрываем кнопку "Добавить"
      $('a[href*="ticket.form.php"][title="Добавить"]').closest('.nav-item').hide();
      $('a[href*="ticket.form.php"]').filter(function() {
        return $(this).attr('title') === 'Добавить' || $(this).find('span').text().trim() === 'Добавить';
      }).closest('.nav-item').hide();
      
      // Скрываем кнопку "Поиск"
      $('a[href*="ticket.php"][title="Поиск"]').closest('.nav-item').hide();
      $('a[title="Поиск"]').filter(function() {
        return $(this).find('i').hasClass('ti-search') || $(this).find('span').text().trim() === 'Поиск';
      }).closest('.nav-item').hide();
      
      // Скрываем кнопку "Список"
      $('a.show-saved-searches[data-itemtype="Ticket"][title="Список"]').closest('.nav-item').hide();
      $('a.show-saved-searches[data-itemtype="Ticket"]').filter(function() {
        return $(this).attr('title') === 'Список' || $(this).find('span').text().trim() === 'Список';
      }).closest('.nav-item').hide();
      
      // Скрываем кнопку "Общий Канбан"
      $('a[href*="showglobalkanban=1"][title="Общий Канбан"]').closest('.nav-item').hide();
      $('a[href*="showglobalkanban=1"]').filter(function() {
        return $(this).attr('title') === 'Общий Канбан' || $(this).find('span').text().trim() === 'Общий Канбан';
      }).closest('.nav-item').hide();
      
      // Скрываем весь контейнер с кнопками navbar
      var $navContainer = $('.nav.navbar-nav.border-start.border-left');
      if ($navContainer.length) {
        $navContainer.hide();
      }
      // Также скрываем по другим возможным селекторам
      $('.nav.navbar-nav.border-start').hide();
    }
    
    applyCustomizations();
    hideBreadcrumbTickets();
    hideNavbarButtons();
    
    setTimeout(applyCustomizations, 500);
    setTimeout(hideBreadcrumbTickets, 500);
    setTimeout(hideNavbarButtons, 500);
    setTimeout(applyCustomizations, 1000);
    setTimeout(hideBreadcrumbTickets, 1000);
    setTimeout(hideNavbarButtons, 1000);
    setTimeout(applyCustomizations, 2000);
    
    var closedTicketsObserver = new MutationObserver(function() {
      applyCustomizations();
      hideBreadcrumbTickets();
      hideNavbarButtons();
    });
    
    if (document.body) {
      closedTicketsObserver.observe(document.body, {
        childList: true,
        subtree: true
      });
      
      setTimeout(function() {
        closedTicketsObserver.disconnect();
      }, 30000);
    }
  }
}); // Конец callback checkProfile для closed_tickets.php

// Обернуть оставшийся код в проверку профиля
checkProfile(function(allowed) {
  if (!allowed) {
    return;
  }

  // Добавляем CSS стили только для разрешенных профилей
  addCustomStyles();

(function() {
  function hideLabelInExactField() {
    $('div.form-field.row.col-12.mb-2').find('label[for="type_663680550"]').each(function() {
      $(this).hide();
    });
  }

  if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
    // первоначальная попытка спрятать
    hideLabelInExactField();

    // наблюдатель на случай динамической подгрузки
    var observer = new MutationObserver(function() {
      hideLabelInExactField();
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // отключить наблюдатель через 7 секунд
    setTimeout(function() { observer.disconnect(); }, 7000);
  }
})();

// Скрыть поле "Влияние" на странице ticket.form.php
if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
  
  (function hideImpactField() {
    function hideField() {
      // Найти метку по тексту "Влияние"
      $('label.col-form-label').filter(function() {
        return ($(this).text() || '').replace(/\s+/g,' ').trim().match(/^Влияние$/i);
      }).each(function() {
        var $label = $(this);
        var forAttr = $label.attr('for') || '';
        
        // Скрыть саму метку
        $label.hide();
        
        // Найти связанный select по name="impact"
        var $select = $('select[name="impact"]')
                      .add($('select#' + forAttr.replace('dropdown_', '')))
                      .add($('select[id*="impact"]'))
                      .first();
        
        if ($select.length) {
          // Скрыть select
          $select.hide();
          
          // Найти и скрыть связанный select2 контейнер
          var $sel2 = $select.nextAll('span.select2.select2-container').first();
          if ($sel2.length) {
            $sel2.hide();
          }
          
          // Скрыть весь контейнер поля
          $label.closest('.form-field, .row.mb-2').hide();
          $select.closest('.field-container, .col-xxl-8').hide();
        }
      });
      
      // Дополнительная страховка: скрыть по select напрямую
      $('select[name="impact"]').each(function() {
        $(this).hide();
        $(this).nextAll('span.select2.select2-container').first().hide();
        $(this).closest('.form-field, .row.mb-2').hide();
      });
    }
    
    // Первоначальное скрытие
    hideField();
    
    // Наблюдатель на случай динамической подгрузки
    var observer = new MutationObserver(hideField);
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Отключить наблюдатель через 7 секунд
    setTimeout(function() { observer.disconnect(); }, 7000);
  })();
  
}

// Скрыть поле "Приоритет" на странице ticket.form.php
if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1) {
  
  (function hidePriorityField() {
    function hideField() {
      // Найти метку по тексту "Приоритет"
      $('label.col-form-label').filter(function() {
        return ($(this).text() || '').replace(/\s+/g,' ').trim().match(/^Приоритет$/i);
      }).each(function() {
        var $label = $(this);
        var forAttr = $label.attr('for') || '';
        
        // Скрыть саму метку
        $label.hide();
        
        // Найти связанный select по name="priority"
        var $select = $('select[name="priority"]')
                      .add($('select#' + forAttr.replace('dropdown_', '')))
                      .add($('select[id*="priority"]'))
                      .first();
        
        if ($select.length) {
          // Скрыть select
          $select.hide();
          
          // Найти и скрыть связанный select2 контейнер
          var $sel2 = $select.nextAll('span.select2.select2-container').first();
          if ($sel2.length) {
            $sel2.hide();
          }
          
          // Скрыть весь контейнер поля
          $label.closest('.form-field, .row.mb-2').hide();
          $select.closest('.field-container, .col-xxl-8').hide();
        }
      });
      
      // Дополнительная страховка: скрыть по select напрямую
      $('select[name="priority"]').each(function() {
        $(this).hide();
        $(this).nextAll('span.select2.select2-container').first().hide();
        $(this).closest('.form-field, .row.mb-2').hide();
      });
    }
    
    // Первоначальное скрытие
    hideField();
    
    // Наблюдатель на случай динамической подгрузки
    var observer = new MutationObserver(hideField);
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Отключить наблюдатель через 7 секунд
    setTimeout(function() { observer.disconnect(); }, 7000);
  })();
  
}

  // Эти функции выполняются только для разрешенных профилей
(function hideServiceLevelsSection() {
  function hideSvc() {
    var $h = $('#service-levels-heading');
    if (!$h.length) { return; }

    // скрыть сам заголовок
    $h.hide();

    // попытаться скрыть весь accordion-item
    var $item = $h.closest('.accordion-item');
    if ($item.length) {
      $item.hide();
      return;
    }

    // fallback: скрыть ближайший блок содержимого после заголовка
    var $collapse = $h.nextAll('.accordion-collapse, .accordion-body').first();
    if ($collapse.length) {
      $collapse.hide();
    }
  }

  if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1 ||
      window.location.href.indexOf('?create_ticket=1') !== -1) {
    hideSvc();
    var mo = new MutationObserver(hideSvc);
    mo.observe(document.body, { childList: true, subtree: true });
    setTimeout(function() { mo.disconnect(); }, 7000);
  }
})();

(function hideExactTypeLabel2004050098() {
  function hideLabel() {
    $('div.form-field.row.col-12.mb-2').find('label[for="type_2004050098"]').each(function() {
      $(this).hide();
    });
  }

  if (window.location.pathname.indexOf('/front/ticket.form.php') !== -1 ||
      window.location.href.indexOf('?create_ticket=1') !== -1) {
    hideLabel();
    var mo = new MutationObserver(hideLabel);
    mo.observe(document.body, { childList: true, subtree: true });
    setTimeout(function() { mo.disconnect(); }, 7000);
  }
})();
}); // Конец callback checkProfile
