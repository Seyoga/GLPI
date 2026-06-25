<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
Session::checkLoginUser();

header('Content-Type: application/json');

if (!isset($_POST['ticket_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID заявки']);
    exit;
}

$ticket_id = (int)$_POST['ticket_id'];
$ticket = new Ticket();

if (!$ticket->getFromDB($ticket_id)) {
    echo json_encode(['success' => false, 'message' => 'Заявка не найдена']);
    exit;
}

// Проверяем, что заявка принадлежит текущему пользователю (как инициатор)
$user_id = Session::getLoginUserID();
$ticket_user = new Ticket_User();
$ticket_users = $ticket_user->find([
    'tickets_id' => $ticket_id,
    'users_id' => $user_id,
    'type' => 1  // 1 = REQUESTER (инициатор)
]);

if (empty($ticket_users)) {
    echo json_encode(['success' => false, 'message' => 'Нет доступа к этой заявке']);
    exit;
}

// Закрываем заявку (статус 6 = закрыто)
$update = [
    'id' => $ticket_id,
    'status' => Ticket::CLOSED
];

if ($ticket->update($update)) {
    echo json_encode(['success' => true, 'message' => 'Заявка успешно закрыта']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при закрытии заявки']);
}

