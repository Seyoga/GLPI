<?php
/**
 * CSS файл с ранними стилями для плагина CustomHelpdesk
 * Генерирует CSS на лету для устранения FOUC
 * 
 * Этот файл должен быть доступен напрямую через URL:
 * /glpi/plugins/customhelpdesk/css/critical.css.php
 */

// Загружаем GLPI (аналогично другим AJAX файлам плагина)
// __DIR__ = /glpi/plugins/customhelpdesk/css/
// dirname(__DIR__) = /glpi/plugins/customhelpdesk/
// dirname(dirname(__DIR__)) = /glpi/plugins/
// dirname(dirname(dirname(__DIR__))) = /glpi/
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// #region agent log: critical.css.php loaded (hypothesis H-critical)
try {
    $logPayload = [
        'sessionId'    => 'debug-session',
        'runId'        => 'closed-fouc-1',
        'hypothesisId' => 'H-critical',
        'location'     => 'css/critical.css.php',
        'message'      => 'critical.css.php executed',
        'data'         => [
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ],
        'timestamp'    => time(),
    ];
    $logLine = json_encode($logPayload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $logFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.cursor' . DIRECTORY_SEPARATOR . 'debug.log';
    @file_put_contents($logFile, $logLine, FILE_APPEND);
} catch (Throwable $e) {
    // игнорируем ошибки логирования
}
// #endregion

// Устанавливаем заголовки для CSS ПЕРЕД любой проверкой
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Проверяем, что пользователь авторизован (без строгой проверки, чтобы не блокировать загрузку CSS)
if (!isset($_SESSION['glpiactiveprofile']['id'])) {
    // Если пользователь не авторизован, возвращаем пустой CSS
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
    // Конфигурация не настроена - возвращаем пустой CSS
    echo '/* Configuration not found */';
    exit;
}

// Проверяем профили пользователей
$user_profiles = [];
if (isset($config['user_profiles']) && !empty($config['user_profiles'])) {
    $decoded = json_decode($config['user_profiles'], true);
    $user_profiles = is_array($decoded) ? $decoded : [];
} elseif (isset($config['profiles']) && !empty($config['profiles'])) {
    // Обратная совместимость со старым форматом
    $decoded = json_decode($config['profiles'], true);
    $user_profiles = is_array($decoded) ? $decoded : [];
}

// Проверяем, входит ли текущий профиль в разрешенные
$is_user = in_array($current_profile_id, $user_profiles);

if (!$is_user) {
    // Профиль не разрешен (админ или специалист) - возвращаем пустой CSS (стандартный интерфейс GLPI)
    // Админы и специалисты видят стандартный интерфейс GLPI БЕЗ каких-либо изменений через CSS
    // Специалисты используют отдельный JS файл custom.specialists.js
    echo '/* Profile not allowed - standard GLPI interface */';
    exit;
}

// Генерируем ранний CSS
?>
/* Ранние стили плагина CustomHelpdesk */
/* Подключаются до остальных стилей и скриптов, чтобы избежать FOUC */

/* Серо-белый градиент для body - применяется для всех разрешенных профилей */
body {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
  background-attachment: fixed !important;
  min-height: 100vh !important;
}

/* Применяем серо-белый градиент к основным контейнерам страниц - для всех разрешенных профилей */
.page,
.page-body,
.page-body.container-fluid,
.container-fluid {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
  background-attachment: fixed !important;
}

<?php if ($is_user): ?>
/* СКРЫВАЕМ только элементы меню sidebar для пользователей, но оставляем sidebar и лого видимыми */
/* Скрываем все элементы меню кроме navbar-brand (лого) */
.sidebar .navbar-nav .nav-item:not(:first-child),
.sidebar .navbar-nav > .nav-item:not(:first-child),
.navbar-vertical .navbar-nav .nav-item:not(:first-child),
.navbar-vertical .navbar-nav > .nav-item:not(:first-child),
.navbar.navbar-vertical .navbar-nav .nav-item:not(:first-child),
.navbar.navbar-vertical .navbar-nav > .nav-item:not(:first-child),
.navbar.navbar-vertical.navbar-expand-lg .navbar-nav .nav-item:not(:first-child),
.navbar.navbar-vertical.navbar-expand-lg .navbar-nav > .nav-item:not(:first-child),
aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),
aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child) {
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

/* Скрываем кнопку "Свернуть меню" */
.sidebar .reduce-menu,
.navbar-vertical .reduce-menu,
.navbar.navbar-vertical .reduce-menu,
.navbar.navbar-vertical.navbar-expand-lg .reduce-menu,
aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .reduce-menu {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  height: 0 !important;
  overflow: hidden !important;
  pointer-events: none !important;
  width: 0 !important;
}

/* Скрываем все ссылки в меню sidebar кроме navbar-brand (лого) */
.sidebar .navbar-nav .nav-link:not(.navbar-brand),
.navbar-vertical .navbar-nav .nav-link:not(.navbar-brand),
.navbar.navbar-vertical .navbar-nav .nav-link:not(.navbar-brand),
.navbar.navbar-vertical.navbar-expand-lg .navbar-nav .nav-link:not(.navbar-brand),
aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-link:not(.navbar-brand) {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  pointer-events: none !important;
}

/* Применяем серо-белый градиент к sidebar для пользователей (оставляем видимым) */
.sidebar,
.navbar-vertical,
aside.sidebar,
aside.navbar-vertical,
.navbar.navbar-vertical,
.navbar.navbar-vertical.navbar-expand-lg,
.navbar.navbar-vertical.sticky-lg-top,
aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
  background-attachment: fixed !important;
  border: none !important;
  box-shadow: none !important;
}
<?php endif; ?>

/* Все стили выше применяются только для разрешенных профилей (пользователи) */
/* Для специалистов используется отдельный JS файл custom.specialists.js */
/* Для админов CSS не загружается вообще, поэтому они видят стандартный интерфейс GLPI */

