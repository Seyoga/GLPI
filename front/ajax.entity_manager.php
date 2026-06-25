<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');

// Определяем сущность: из параметра или из активной сессии
$entity_id = 0;
if (isset($_GET['entities_id'])) {
    $entity_id = (int)$_GET['entities_id'];
}
if ($entity_id <= 0 && isset($_SESSION['glpiactive_entity'])) {
    $entity_id = (int)$_SESSION['glpiactive_entity'];
}

$config = PluginCustomhelpdeskConfig::getConfig();
$managers = [];
if (isset($config['entity_managers']) && !empty($config['entity_managers'])) {
    $decoded = json_decode($config['entity_managers'], true);
    $managers = is_array($decoded) ? $decoded : [];
}

$found_manager = null;

// Новый формат: массив записей [{entity_id, name, phone}, ...]
if (!empty($managers) && is_array($managers)) {
    $first = reset($managers);
    if (is_array($first) && array_key_exists('entity_id', $first)) {
        foreach ($managers as $row) {
            if (!is_array($row)) {
                continue;
            }
            $row_entity_id = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
            if ($row_entity_id === $entity_id) {
                $found_manager = $row;
                break;
            }
        }
    } else {
        // Старый формат: [entity_id => ['name' => ..., 'phone' => ...], ...]
        if ($entity_id > 0 && isset($managers[$entity_id]) && is_array($managers[$entity_id])) {
            $found_manager = [
                'entity_id' => $entity_id,
                'name'      => $managers[$entity_id]['name']  ?? '',
                'phone'     => $managers[$entity_id]['phone'] ?? '',
            ];
        }
    }
}

if ($found_manager !== null) {
    $name  = isset($found_manager['name'])  ? $found_manager['name']  : '';
    $phone = isset($found_manager['phone']) ? $found_manager['phone'] : '';

    echo json_encode([
        'success' => true,
        'entities_id' => $entity_id,
        'manager' => [
            'name'  => $name,
            'phone' => $phone,
        ]
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'entities_id' => $entity_id,
    'manager' => null,
]);

