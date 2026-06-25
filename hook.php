<?php

function plugin_customhelpdesk_install() {
    global $DB;
    
    $table = 'glpi_plugin_customhelpdesk_configs';
    
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int(11) NOT NULL PRIMARY KEY,
            `user_profiles` TEXT NULL,
            `specialist_profiles` TEXT NULL,
            `user_subcategories` TEXT NULL,
            `specialist_subcategories` TEXT NULL,
            `bgu_category_id` INT(11) NULL,
            `zkgu_category_id` INT(11) NULL,
            `entity_managers` TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->queryOrDie($query, $DB->error());
    }
    
    // Создаем таблицу для оценок задач
    $ratings_table = 'glpi_plugin_customhelpdesk_ticket_ratings';
    if (!$DB->tableExists($ratings_table)) {
        $query = "CREATE TABLE `$ratings_table` (
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `tickets_id` int(11) UNSIGNED NOT NULL,
            `users_id` int(11) UNSIGNED NOT NULL,
            `rating` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_ticket_user` (`tickets_id`, `users_id`),
            INDEX `idx_tickets_id` (`tickets_id`),
            INDEX `idx_users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->queryOrDie($query, $DB->error());
    }
    
    // Миграция для таблицы configs: если таблица существует, обновляем структуру
    if ($DB->tableExists($table)) {
        // Проверяем наличие старых полей
        if ($DB->fieldExists($table, 'profiles')) {
            // Добавляем новые поля, если их нет
            if (!$DB->fieldExists($table, 'user_profiles')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `user_profiles` TEXT NULL AFTER `id`");
            }
            if (!$DB->fieldExists($table, 'specialist_profiles')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_profiles` TEXT NULL AFTER `user_profiles`");
            }
            
            // Копируем данные из profiles в user_profiles
            $iterator = $DB->request([
                'FROM' => $table,
                'LIMIT' => 1
            ]);
            if (count($iterator)) {
                $config = $iterator->current();
                if (isset($config['profiles'])) {
                    $DB->update($table, [
                        'user_profiles' => $config['profiles']
                    ], ['id' => 1]);
                }
            }
            
            // Удаляем старое поле
            $DB->query("ALTER TABLE `$table` DROP COLUMN `profiles`");
        } else {
            // Если поля нет, просто добавляем новые
            if (!$DB->fieldExists($table, 'user_profiles')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `user_profiles` TEXT NULL AFTER `id`");
            }
            if (!$DB->fieldExists($table, 'specialist_profiles')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_profiles` TEXT NULL AFTER `user_profiles`");
            }
            if (!$DB->fieldExists($table, 'user_subcategories')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `user_subcategories` TEXT NULL AFTER `specialist_profiles`");
            }
            if (!$DB->fieldExists($table, 'specialist_subcategories')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_subcategories` TEXT NULL AFTER `user_subcategories`");
            }
            if (!$DB->fieldExists($table, 'bgu_category_id')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `bgu_category_id` INT(11) NULL AFTER `specialist_subcategories`");
            }
            if (!$DB->fieldExists($table, 'zkgu_category_id')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `zkgu_category_id` INT(11) NULL AFTER `bgu_category_id`");
            }
            if (!$DB->fieldExists($table, 'entity_managers')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `entity_managers` TEXT NULL AFTER `zkgu_category_id`");
            }
        }
    }
    
    return true;
}

function plugin_customhelpdesk_uninstall() {
    global $DB;
    
    $table = 'glpi_plugin_customhelpdesk_configs';
    
    if ($DB->tableExists($table)) {
        $DB->queryOrDie("DROP TABLE `$table`", $DB->error());
    }
    
    // Удаляем таблицу оценок
    $ratings_table = 'glpi_plugin_customhelpdesk_ticket_ratings';
    if ($DB->tableExists($ratings_table)) {
        $DB->queryOrDie("DROP TABLE `$ratings_table`", $DB->error());
    }
    
    return true;
}

/**
 * МЕТОД 1: Хук post_init - вызывается после инициализации фреймворка
 * Самый ранний хук, можно использовать для подготовки данных
 */
function plugin_customhelpdesk_post_init() {
    // Проверяем профиль пользователя и сохраняем в сессии для быстрого доступа
    if (function_exists('plugin_customhelpdesk_check_profile_allowed')) {
        $profile_check = plugin_customhelpdesk_check_profile_allowed();
        if ($profile_check !== false) {
            $_SESSION['customhelpdesk_profile'] = $profile_check;
        }
    }
    
    // Проверяем профиль специалиста и сохраняем в сессии для быстрого доступа
    if (function_exists('plugin_customhelpdesk_check_specialist_profile_allowed')) {
        $specialist_check = plugin_customhelpdesk_check_specialist_profile_allowed();
        if ($specialist_check !== false) {
            $_SESSION['customhelpdesk_specialist_profile'] = $specialist_check;
        }
    }
}

/**
 * МЕТОД 2: Хук html_head - добавляет стили в head страницы.
 * Добавляет небольшой inline CSS ПРЯМО в head для ускоренного применения до рендеринга.
 *
 * ВАЖНО: В GLPI хук html_head может не вызываться на всех страницах или вызываться слишком поздно,
 * поэтому для специалистов основной CSS подключается через add_css (critical.specialists.css.php).
 */
function plugin_customhelpdesk_html_head() {
    
    // Проверяем профиль пользователя (используем кэш из сессии, если есть)
    $profile_check = false;
    if (isset($_SESSION['customhelpdesk_profile'])) {
        $profile_check = $_SESSION['customhelpdesk_profile'];
    } elseif (function_exists('plugin_customhelpdesk_check_profile_allowed')) {
        $profile_check = plugin_customhelpdesk_check_profile_allowed();
    }
    
    // Проверяем профиль специалиста (используем кэш из сессии, если есть)
    $specialist_check = false;
    if (isset($_SESSION['customhelpdesk_specialist_profile'])) {
        $specialist_check = $_SESSION['customhelpdesk_specialist_profile'];
    } elseif (function_exists('plugin_customhelpdesk_check_specialist_profile_allowed')) {
        $specialist_check = plugin_customhelpdesk_check_specialist_profile_allowed();
    }
    
    $is_user = ($profile_check !== false) && ($profile_check['is_user'] ?? false);
    $is_specialist = ($specialist_check !== false) && ($specialist_check['is_specialist'] ?? false);
    
    // #region agent log: html_head invocation (hypothesis H-head)
    try {
        $logPayload = [
            'sessionId'    => 'debug-session',
            'runId'        => 'closed-fouc-1',
            'hypothesisId' => 'H-head',
            'location'     => 'hook.php:plugin_customhelpdesk_html_head',
            'message'      => 'plugin_customhelpdesk_html_head executed',
            'data'         => [
                'request_uri'   => $_SERVER['REQUEST_URI'] ?? '',
                'is_user'       => $is_user,
                'is_specialist' => $is_specialist,
            ],
            'timestamp'    => time(),
        ];
        $logLine = json_encode($logPayload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $logFile = __DIR__ . DIRECTORY_SEPARATOR . '.cursor' . DIRECTORY_SEPARATOR . 'debug.log';
        @file_put_contents($logFile, $logLine, FILE_APPEND);
    } catch (Throwable $e) {
        // игнорируем ошибки логирования
    }
    // #endregion
    
    // Добавляем глобальную JavaScript переменную с путем к плагину (для всех профилей)
    $plugin_web_dir = Plugin::getWebDir('customhelpdesk');
    echo '<script type="text/javascript">';
    echo 'window.CUSTOMHELPDESK_PLUGIN_PATH = ' . json_encode($plugin_web_dir, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';';
    echo '</script>';
    
    if ($is_user) {
        // Добавляем inline стили прямо в head
        echo '<style id="customhelpdesk-critical-inline-head">';
        echo '/* CUSTOMHELPDESK: User inline styles - applied early */';
        echo 'body,';
        echo '.page,';
        echo '.page-body.container-fluid {';
        echo '  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;';
        echo '  background-attachment: fixed !important;';
        echo '}';
        echo 'body {';
        echo '  min-height: 100vh !important;';
        echo '}';

        // Для страницы закрытых заявок дополнительно сразу скрываем элементы sidebar,
        // чтобы не было "мигания" пунктов меню слева при загрузке.
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/plugins/customhelpdesk/front/closed_tickets.php') !== false) {
            echo 'aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),';
            echo 'aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child),';
            echo '.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),';
            echo '.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child),';
            echo '.sidebar .navbar-nav .nav-item:not(:first-child),';
            echo '.sidebar .navbar-nav > .nav-item:not(:first-child),';
            echo '.navbar-vertical .navbar-nav .nav-item:not(:first-child),';
            echo '.navbar-vertical .navbar-nav > .nav-item:not(:first-child),';
            echo '.navbar.navbar-vertical .navbar-nav .nav-item:not(:first-child),';
            echo '.navbar.navbar-vertical .navbar-nav > .nav-item:not(:first-child),';
            echo '.navbar.navbar-vertical.navbar-expand-lg .navbar-nav .nav-item:not(:first-child),';
            echo '.navbar.navbar-vertical.navbar-expand-lg .navbar-nav > .nav-item:not(:first-child) {';
            echo '  display: none !important;';
            echo '  visibility: hidden !important;';
            echo '  opacity: 0 !important;';
            echo '  height: 0 !important;';
            echo '  overflow: hidden !important;';
            echo '  pointer-events: none !important;';
            echo '  width: 0 !important;';
            echo '  margin: 0 !important;';
            echo '  padding: 0 !important;';
            echo '}';
        }

        echo '</style>';
        
        // Также добавляем ссылки на CSS файлы для дополнительных стилей
        $critical_css_path = Plugin::getWebDir('customhelpdesk') . '/css/critical.css.php';
        $css_path = Plugin::getWebDir('customhelpdesk') . '/css/custom.css';
        $version = PLUGIN_CUSTOMHELPDESK_VERSION;
        
        echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($critical_css_path) . '?v=' . $version . '" id="custom-helpdesk-critical-css">';
        echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_path) . '?v=' . $version . '" id="custom-helpdesk-css">';
    } elseif ($is_specialist) {
        // Добавляем inline стили в head для специалистов
        // Эти стили применяются до рендеринга страницы и помогают избежать FOUC
        echo '<style id="customhelpdesk-critical-inline-head-specialists">';
        echo '/* CUSTOMHELPDESK: Specialist inline styles - applied early */';
        echo '/* Скрываем RSS ленту на странице central.php */';
        echo '.nav-item a[title="RSS лента"],';
        echo '.nav-item a[data-bs-original-title="RSS лента"],';
        echo '.nav-item:has(a[title="RSS лента"]),';
        echo '.nav-item:has(a[data-bs-original-title="RSS лента"]) {';
        echo '  display: none !important;';
        echo '  visibility: hidden !important;';
        echo '  opacity: 0 !important;';
        echo '  height: 0 !important;';
        echo '  overflow: hidden !important;';
        echo '  pointer-events: none !important;';
        echo '  width: 0 !important;';
        echo '  margin: 0 !important;';
        echo '  padding: 0 !important;';
        echo '}';
        echo '/* Скрываем "Личные каналы RSS" из dropdown меню */';
        echo '.dropdown-item[href*="rssfeed.php"],';
        echo '.dropdown-item[title="Личные каналы RSS"],';
        echo '.dropdown-item:has(a[href*="rssfeed.php"]),';
        echo 'a.dropdown-item[href*="rssfeed.php"],';
        echo 'a.dropdown-item[title="Личные каналы RSS"],';
        echo '.dropdown-item:has(i.ti-rss),';
        echo '.dropdown-item:has(.ti-rss) {';
        echo '  display: none !important;';
        echo '  visibility: hidden !important;';
        echo '  opacity: 0 !important;';
        echo '  height: 0 !important;';
        echo '  overflow: hidden !important;';
        echo '  pointer-events: none !important;';
        echo '  width: 0 !important;';
        echo '  margin: 0 !important;';
        echo '  padding: 0 !important;';
        echo '}';
        echo '</style>';
    }
}

/**
 * МЕТОД 3: Хук display_central - для центральной страницы
 * Добавляет стили на центральную страницу
 */
function plugin_customhelpdesk_display_central() {
    // Проверяем профиль
    $profile_check = false;
    if (isset($_SESSION['customhelpdesk_profile'])) {
        $profile_check = $_SESSION['customhelpdesk_profile'];
    } elseif (function_exists('plugin_customhelpdesk_check_profile_allowed')) {
        $profile_check = plugin_customhelpdesk_check_profile_allowed();
    }
    
    if ($profile_check === false) {
        return;
    }
    
    $is_user = $profile_check['is_user'] ?? false;
    
    // Инъектируем стили в начало страницы
    if ($is_user) {
        echo '<style id="customhelpdesk-display-central">';
        echo 'body, .page, .page-body.container-fluid {';
        echo '  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;';
        echo '  background-attachment: fixed !important;';
        echo '}';
        echo '</style>';
    }
}


/**
 * МЕТОД 3: Хук pre_show_item - инъекция стилей перед отображением элементов
 * Получает массив с параметрами: ['item' => CommonDBTM, 'options' => array]
 */
function plugin_customhelpdesk_pre_show_item($params = []) {
    // Проверяем, что параметры переданы
    if (empty($params) || !isset($params['item'])) {
        return;
    }
    
    // Проверяем профиль (используем кэш из сессии, если есть)
    $profile_check = false;
    if (isset($_SESSION['customhelpdesk_profile'])) {
        $profile_check = $_SESSION['customhelpdesk_profile'];
    } elseif (function_exists('plugin_customhelpdesk_check_profile_allowed')) {
        $profile_check = plugin_customhelpdesk_check_profile_allowed();
    }
    
    if ($profile_check === false) {
        return; // Профиль не разрешен
    }
    
    // Получаем тип элемента
    $item = $params['item'];
    $itemtype = $item::getType();
    
    // Применяем стили только на определенных страницах
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $is_relevant_page = (
        strpos($request_uri, 'helpdesk.public.php') !== false ||
        strpos($request_uri, 'tracking.injector.php') !== false ||
        strpos($request_uri, 'ticket.form.php') !== false ||
        strpos($request_uri, 'closed_tickets.php') !== false ||
        strpos($request_uri, 'central.php') !== false
    );
    
    if (!$is_relevant_page) {
        return;
    }
    
    // Генерируем inline стили для немедленного применения
    $is_user = $profile_check['is_user'] ?? false;
    
    // Стили для пользователей (серый градиент)
    if ($is_user) {
        echo '<style id="customhelpdesk-pre-show-user">';
        echo 'body, .page, .page-body.container-fluid {';
        echo '  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;';
        echo '  background-attachment: fixed !important;';
        echo '}';
        echo '</style>';
    }
}