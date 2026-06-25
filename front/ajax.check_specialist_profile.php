<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
Session::checkLoginUser();

header('Content-Type: application/json');

// Получаем конфигурацию плагина
$config = PluginCustomhelpdeskConfig::getConfig();

// Получаем текущий профиль пользователя
$current_profile_id = 0;
if (isset($_SESSION['glpiactiveprofile']['id'])) {
    $current_profile_id = (int)$_SESSION['glpiactiveprofile']['id'];
}

// Проверяем профили специалистов
$specialist_profiles = [];
if (isset($config['specialist_profiles']) && !empty($config['specialist_profiles'])) {
    $decoded = json_decode($config['specialist_profiles'], true);
    $specialist_profiles = is_array($decoded) ? $decoded : [];
}

// Проверяем подкатегории специалистов
$specialist_subcategories = [];
if (isset($config['specialist_subcategories']) && !empty($config['specialist_subcategories'])) {
    $decoded = json_decode($config['specialist_subcategories'], true);
    $specialist_subcategories = is_array($decoded) ? $decoded : [];
}

// Определяем тип пользователя
$is_specialist = in_array($current_profile_id, $specialist_profiles);

// Определяем подкатегорию специалиста
$specialist_subcategory = 'full'; // По умолчанию полный специалист
if ($is_specialist && isset($specialist_subcategories[$current_profile_id])) {
    $specialist_subcategory = $specialist_subcategories[$current_profile_id];
}

$response = [
    'allowed' => $is_specialist,
    'is_specialist' => $is_specialist,
    'current_profile_id' => $current_profile_id,
    'specialist_profiles' => $specialist_profiles,
    'specialist_subcategory' => $specialist_subcategory
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);

