<?php

include_once __DIR__ . '/hook.php';

define('PLUGIN_CUSTOMHELPDESK_VERSION', '1.0.1');


function plugin_init_customhelpdesk() {
    global $PLUGIN_HOOKS;
    
    $PLUGIN_HOOKS['csrf_compliant']['customhelpdesk'] = true;
    
    // Добавляем пункт в меню конфигурации
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['customhelpdesk'] = 'front/config.form.php';
    }
    
    // Проверяем профиль ПЕРЕД загрузкой CSS и JavaScript
    // Для админов не загружаем ничего - стандартный интерфейс GLPI
    $profile_check = plugin_customhelpdesk_check_profile_allowed();
    $specialist_check = plugin_customhelpdesk_check_specialist_profile_allowed();
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $is_ticket_form = (strpos($request_uri, 'ticket.form.php') !== false);
    $is_entity_form = (strpos($request_uri, 'entity.form.php') !== false);
    
    if ($profile_check !== false) {
        // Профиль разрешен (пользователь) - загружаем CSS и JavaScript
        // Загружаем JavaScript файл для пользователей
        $PLUGIN_HOOKS['add_javascript']['customhelpdesk'] = [
            'js/custom.users.js'
        ];

        // Добавляем ранний CSS через хук add_css.
        // Файл critical.css.php сам проверяет профиль и возвращает пустой CSS для неразрешенных профилей.
        // Важно: critical.css.php должен загружаться последним, чтобы его стили имели приоритет.
        $PLUGIN_HOOKS['add_css']['customhelpdesk'] = [
            'css/custom.css',
            'css/critical.css.php'
        ];
    } elseif ($specialist_check !== false) {
        // Профиль специалиста - загружаем JavaScript и ранний CSS для специалистов
        // Загружаем JavaScript файл для специалистов
        $PLUGIN_HOOKS['add_javascript']['customhelpdesk'] = [
            'js/custom.specialists.js'
        ];

        // Добавляем CSS через хук add_css.
        // Используем отдельный файл с ранними стилями для специалистов.
        $PLUGIN_HOOKS['add_css']['customhelpdesk'] = [
            'css/critical.specialists.css.php'
        ];
    } elseif ($is_ticket_form) {
        // Админ на ticket.form — только скрипт отображения оценки (без интерфейса пользователей)
        $PLUGIN_HOOKS['add_javascript']['customhelpdesk'] = [
            'js/custom.rating.js'
        ];
    }

    // Загружаем скрипт на странице Организации для всех (включая админов)
    if ($is_entity_form) {
        if (!isset($PLUGIN_HOOKS['add_javascript']['customhelpdesk'])) {
            $PLUGIN_HOOKS['add_javascript']['customhelpdesk'] = [];
        }
        $PLUGIN_HOOKS['add_javascript']['customhelpdesk'][] = 'js/custom.entity.js';
    }
    // Для админов на других страницах ничего не загружаем
    
    // Хуки применяются для разрешенных профилей (пользователи и специалисты) и для админов на ticket.form (путь к плагину для блока оценки)
    if ($profile_check !== false || $specialist_check !== false || $is_ticket_form) {
        // МЕТОД 1: Хук post_init - вызывается после инициализации фреймворка (раньше всего)
        if ($profile_check !== false || $specialist_check !== false) {
            $PLUGIN_HOOKS['post_init']['customhelpdesk'] = 'plugin_customhelpdesk_post_init';
        }
        
       
        $PLUGIN_HOOKS['html_head']['customhelpdesk'] = 'plugin_customhelpdesk_html_head';
    
        if ($profile_check !== false) {
            $PLUGIN_HOOKS['display_central']['customhelpdesk'] = 'plugin_customhelpdesk_display_central';
        }
        
        // Применяется для всех типов элементов (Ticket, Computer, и т.д.)
        if ($profile_check !== false) {
            $PLUGIN_HOOKS['pre_show_item']['customhelpdesk'] = [
                'Ticket' => 'plugin_customhelpdesk_pre_show_item',
                'Computer' => 'plugin_customhelpdesk_pre_show_item',
                'User' => 'plugin_customhelpdesk_pre_show_item',
            ];
        }
    } // <--- ВОТ ЭТОЙ СКОБКИ У ТЕБЯ НЕ БЫЛО! ОНА ЗАКРЫВАЕТ БЛОК ВЫШЕ.

    // --- ПЕРЕХВАТ СОХРАНЕНИЯ ОРГАНИЗАЦИИ (ТЕПЕРЬ ДОСТУПЕН ДЛЯ АДМИНОВ) ---
    if (!isset($PLUGIN_HOOKS['pre_item_update']['customhelpdesk'])) {
        $PLUGIN_HOOKS['pre_item_update']['customhelpdesk'] = [];
    }
    if (!isset($PLUGIN_HOOKS['item_add']['customhelpdesk'])) {
        $PLUGIN_HOOKS['item_add']['customhelpdesk'] = [];
    }

    $PLUGIN_HOOKS['pre_item_update']['customhelpdesk']['Entity'] = 'plugin_customhelpdesk_force_save_entity_hours';
    $PLUGIN_HOOKS['item_add']['customhelpdesk']['Entity'] = 'plugin_customhelpdesk_force_save_entity_hours';

    Plugin::registerClass('PluginCustomhelpdeskConfig');
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, 'tracking.injector.php') !== false || 
        (strpos($request_uri, 'helpdesk.public.php') !== false && strpos($request_uri, 'create_ticket=1') !== false)) {
        // Устанавливаем обработчик ошибок для подавления конкретного предупреждения
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // Подавляем только предупреждение "Array to string conversion" в Twig Template
            if ($errno === E_WARNING && 
                strpos($errstr, 'Array to string conversion') !== false &&
                strpos($errfile, 'Template.php') !== false) {
                return true; // Подавляем это предупреждение
            }
            // Для всех остальных ошибок используем стандартный обработчик
            return false;
        }, E_WARNING);
    }
}

function plugin_version_customhelpdesk() {
    return [
        'name'           => 'Custom Interface',
        'version'        => PLUGIN_CUSTOMHELPDESK_VERSION,
        'author'         => 'РИЦ',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
                'max' => '10.99'
            ]
        ]
    ];
}

function plugin_customhelpdesk_check_prerequisites() {
    return true;
}

function plugin_customhelpdesk_check_config() {
    return true;
}

/**
 * Проверяет, разрешен ли текущий профиль для применения стилей
 * @return bool|array Возвращает false если не разрешен, или массив с информацией о профиле
 */
function plugin_customhelpdesk_check_profile_allowed() {
    // Проверяем, что пользователь авторизован
    if (!isset($_SESSION['glpiactiveprofile']['id'])) {
        return false;
    }
    
    // Проверяем, что класс доступен
    if (!class_exists('PluginCustomhelpdeskConfig')) {
        return false;
    }
    
    $current_profile_id = (int)$_SESSION['glpiactiveprofile']['id'];
    
    // Получаем конфигурацию плагина
    $config = PluginCustomhelpdeskConfig::getConfig();
    
    if (empty($config)) {
        return false; // Конфигурация не настроена
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
        return false; // Профиль не разрешен - стандартный интерфейс GLPI
    }
    
    return [
        'is_user' => $is_user,
        'profile_id' => $current_profile_id
    ];
      }

/**
 * Проверяет, является ли текущий профиль специалистом
 * @return bool|array Возвращает false если не специалист, или массив с информацией о профиле
 */
function plugin_customhelpdesk_check_specialist_profile_allowed() {
    // Проверяем, что пользователь авторизован
    if (!isset($_SESSION['glpiactiveprofile']['id'])) {
        return false;
      }

    // Проверяем, что класс доступен
    if (!class_exists('PluginCustomhelpdeskConfig')) {
        return false;
      }

    $current_profile_id = (int)$_SESSION['glpiactiveprofile']['id'];
    
    // Получаем конфигурацию плагина
    $config = PluginCustomhelpdeskConfig::getConfig();
    
    if (empty($config)) {
        return false; // Конфигурация не настроена
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
        return false; // Профиль не разрешен
    }
    
    return [
        'is_specialist' => $is_specialist,
        'profile_id' => $current_profile_id
    ];
          }
          
/**
 * Добавляет inline CSS стили в head страницы для пользователей
 * Это устраняет FOUC (Flash of Unstyled Content) - стили применяются ДО рендеринга
 */
function plugin_customhelpdesk_add_inline_styles() {
    // Проверяем, разрешен ли профиль для применения стилей
    $profile_check = plugin_customhelpdesk_check_profile_allowed();
    if ($profile_check === false) {
        // Профиль не разрешен - не применяем стили (стандартный интерфейс GLPI)
        return '';
    }
    
    // Получаем путь к критическому CSS PHP файлу
    $critical_css_path = Plugin::getWebDir('customhelpdesk') . '/css/critical.css.php';
    $critical_css_path_with_version = $critical_css_path . '?v=' . PLUGIN_CUSTOMHELPDESK_VERSION;
    
    // Получаем путь к обычному CSS файлу
    $css_path = Plugin::getWebDir('customhelpdesk') . '/css/custom.css';
    $css_path_with_version = $css_path . '?v=' . PLUGIN_CUSTOMHELPDESK_VERSION;
        
    // Формируем HTML с ссылками на CSS файлы
    $styles = '
    <link rel="stylesheet" type="text/css" href="' . htmlspecialchars($critical_css_path_with_version) . '" id="custom-helpdesk-critical-css">
    <link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_path_with_version) . '" id="custom-helpdesk-css">
    ';
    
    echo $styles;
}