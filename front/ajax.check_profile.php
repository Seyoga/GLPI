<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии с обработкой ошибок
try {
    Session::checkLoginUser();
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        'allowed' => false,
        'is_user' => false,
        'error' => 'Ошибка авторизации: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    die();
}

header('Content-Type: application/json');

// Получаем конфигурацию плагина
$config = PluginCustomhelpdeskConfig::getConfig();

// Получаем текущий профиль пользователя
$current_profile_id = 0;
if (isset($_SESSION['glpiactiveprofile']['id'])) {
    $current_profile_id = (int)$_SESSION['glpiactiveprofile']['id'];
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

// Проверяем подкатегории пользователей
$user_subcategories = [];
if (isset($config['user_subcategories']) && !empty($config['user_subcategories'])) {
    $decoded = json_decode($config['user_subcategories'], true);
    $user_subcategories = is_array($decoded) ? $decoded : [];
}

// Определяем тип пользователя
$is_user = in_array($current_profile_id, $user_profiles);

// Определяем подкатегорию пользователя
$user_subcategory = 'full'; // По умолчанию полный пользователь
if ($is_user && isset($user_subcategories[$current_profile_id])) {
    $user_subcategory = $user_subcategories[$current_profile_id];
}

$response = [
    'allowed' => $is_user,
    'is_user' => $is_user,
    'current_profile_id' => $current_profile_id,
    'user_profiles' => $user_profiles,
    'user_subcategory' => $user_subcategory
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

