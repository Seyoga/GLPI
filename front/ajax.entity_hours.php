<?php
include ("../../../inc/includes.php");

// Возвращаем JSON
header('Content-Type: application/json; charset=UTF-8');

// Проверяем авторизацию
Session::checkLoginUser();

$entityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$response = ['work_hours' => '', 'lunch_hours' => ''];

if ($entityId > 0) {
    global $DB;

    // Достаем кастомные поля через query builder (параметризованный запрос, без конкатенации SQL)
    $iterator = $DB->request([
        'SELECT' => ['plugin_customhelpdesk_work_hours', 'plugin_customhelpdesk_lunch_hours'],
        'FROM'   => 'glpi_entities',
        'WHERE'  => ['id' => $entityId],
    ]);

    if (count($iterator) > 0) {
        $row = $iterator->current();
        $response['work_hours'] = $row['plugin_customhelpdesk_work_hours'];
        $response['lunch_hours'] = $row['plugin_customhelpdesk_lunch_hours'];
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);