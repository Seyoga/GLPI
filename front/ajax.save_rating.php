<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
try {
    Session::checkLoginUser();
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ошибка авторизации: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    die();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен'], JSON_UNESCAPED_UNICODE);
    die();
}

$ticket_id = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$user_id = Session::getLoginUserID();

// Валидация
if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID заявки'], JSON_UNESCAPED_UNICODE);
    die();
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Оценка должна быть от 1 до 5'], JSON_UNESCAPED_UNICODE);
    die();
}

// Проверяем, что пользователь является инициатором заявки
$ticket_user = new Ticket_User();
$ticket_users = $ticket_user->find([
    'tickets_id' => $ticket_id,
    'users_id' => $user_id,
    'type' => 1  // 1 = REQUESTER (инициатор)
]);

if (empty($ticket_users)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Вы не являетесь инициатором этой заявки'
    ], JSON_UNESCAPED_UNICODE);
    die();
}

global $DB;

// Сохраняем или обновляем оценку
$ratings_table = 'glpi_plugin_customhelpdesk_ticket_ratings';

// Проверяем, существует ли таблица
if (!$DB->tableExists($ratings_table)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Таблица оценок не найдена. Переустановите плагин.'
    ], JSON_UNESCAPED_UNICODE);
    die();
}

try {
    // Проверяем, существует ли уже оценка
    $iterator = $DB->request([
        'FROM' => $ratings_table,
        'WHERE' => [
            'tickets_id' => $ticket_id,
            'users_id' => $user_id
        ],
        'LIMIT' => 1
    ]);

    $existingRating = count($iterator);

    if ($existingRating) {
        // Обновляем существующую оценку
        $result = $DB->update($ratings_table, [
            'rating' => $rating
        ], [
            'tickets_id' => $ticket_id,
            'users_id' => $user_id
        ]);
        
        if (!$result) {
            $error = $DB->error();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка при обновлении оценки'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }
    } else {
        // Создаем новую оценку
        $result = $DB->insert($ratings_table, [
            'tickets_id' => $ticket_id,
            'users_id' => $user_id,
            'rating' => $rating
        ]);
        
        if (!$result) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Ошибка при сохранении оценки'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    // Выводим JSON
    echo json_encode([
        'success' => true,
        'message' => 'Оценка сохранена',
        'rating' => $rating
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('CustomHelpdesk save_rating: Ошибка - ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    die();
}
