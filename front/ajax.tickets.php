<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
Session::checkLoginUser();

header('Content-Type: application/json');

global $CFG_GLPI;

// Получаем заявки текущего пользователя
$ticket = new Ticket();
$user_id = Session::getLoginUserID();

// Ищем все заявки, созданные текущим пользователем
// Используем поиск через TicketUser, так как связь many-to-many
$ticket_user = new Ticket_User();
$ticket_users = $ticket_user->find([
    'users_id' => $user_id,
    'type' => 1  // 1 = REQUESTER (инициатор)
]);

$ticket_ids = [];
foreach ($ticket_users as $tu) {
    $ticket_ids[] = $tu['tickets_id'];
}

$tickets = [];
if (!empty($ticket_ids)) {
    // Используем прямой SQL запрос для поиска по массиву ID
    global $DB;
    $iterator = $DB->request([
        'FROM' => 'glpi_tickets',
        'WHERE' => [
            'id' => $ticket_ids
        ],
        'ORDER' => 'date_mod DESC',
        'LIMIT' => 100
    ]);
    
    foreach ($iterator as $row) {
        $tickets[] = $row;
    }
}

// Получаем оценки для всех заявок
$ratings_table = 'glpi_plugin_customhelpdesk_ticket_ratings';
$ratings = [];
if (!empty($ticket_ids)) {
    $ratings_iterator = $DB->request([
        'FROM' => $ratings_table,
        'WHERE' => [
            'tickets_id' => $ticket_ids,
            'users_id' => $user_id
        ]
    ]);
    
    foreach ($ratings_iterator as $rating) {
        $ratings[$rating['tickets_id']] = (int)$rating['rating'];
    }
}

$result = [];
foreach ($tickets as $t) {
    $status = Ticket::getStatus($t['status']);
    // Заменяем "В ожидании" на "Примите задачу"
    if ($status === 'В ожидании') {
        $status = 'Примите задачу';
    }
    $priority = Ticket::getPriorityName($t['priority']);
    
    // Форматируем дату
    $date = date('d.m.Y H:i', strtotime($t['date_creation']));
    
    // Получаем оценку для этой заявки
    $rating = isset($ratings[$t['id']]) ? $ratings[$t['id']] : null;
    
    $result[] = [
        'id' => $t['id'],
        'name' => $t['name'],
        'status' => $status,
        'status_id' => $t['status'],
        'priority' => $priority,
        'date' => $date,
        'url' => $CFG_GLPI['root_doc'] . '/front/ticket.form.php?id=' . $t['id'],
        'rating' => $rating
    ];
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

