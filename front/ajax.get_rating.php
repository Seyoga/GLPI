<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
try {
    Session::checkLoginUser();
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['has_rating' => false, 'rating' => null, 'message' => 'Ошибка авторизации: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    die();
}

header('Content-Type: application/json');

$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if ($ticket_id <= 0) {
    echo json_encode(['has_rating' => false, 'rating' => null, 'message' => 'Неверный ID заявки'], JSON_UNESCAPED_UNICODE);
    die();
}

global $DB;

$ratings_table = 'glpi_plugin_customhelpdesk_ticket_ratings';

// Проверяем, существует ли таблица
if (!$DB->tableExists($ratings_table)) {
    http_response_code(500);
    echo json_encode([
        'has_rating' => false,
        'rating' => null,
        'message' => 'Таблица оценок не найдена. Переустановите плагин.'
    ], JSON_UNESCAPED_UNICODE);
    die();
}

// Оценка по заявке: возвращаем любую сохранённую оценку по tickets_id (для отображения и пользователю, и админу)
$iterator = $DB->request([
    'FROM' => $ratings_table,
    'WHERE' => [
        'tickets_id' => $ticket_id
    ],
    'LIMIT' => 1
]);

if (count($iterator)) {
    $rating = $iterator->current();
    echo json_encode([
        'has_rating' => true,
        'rating' => (int)$rating['rating']
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'has_rating' => false,
        'rating' => null
    ], JSON_UNESCAPED_UNICODE);
}
die();
