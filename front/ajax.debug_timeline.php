<?php
/**
 * Debug endpoint: inspect Ticket timeline sources (followups/tasks).
 * Returns JSON with counts and minimal fields (no secrets, no PII).
 *
 * Usage (logged in):
 *   /glpi/plugins/customhelpdesk/front/ajax.debug_timeline.php?ticket_id=123
 */
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include(GLPI_ROOT . "/inc/includes.php");

Session::checkLoginUser();
header('Content-Type: application/json; charset=utf-8');

// ---- simple NDJSON logger (debug-mode) ----
function chd_debug_log(array $payload): void {
    try {
        $payload['timestamp'] = $payload['timestamp'] ?? (int)round(microtime(true) * 1000);
        $payload['sessionId'] = $payload['sessionId'] ?? 'debug-session';
        $line = json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $logFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.cursor' . DIRECTORY_SEPARATOR . 'debug.log';
        @file_put_contents($logFile, $line, FILE_APPEND);
    } catch (Throwable $e) {
        // ignore
    }
}

// ---- input ----
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ticket_id'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- access check: must be able to view ticket ----
$ticket = new Ticket();
$can_view = $ticket->getFromDB($ticket_id) && $ticket->can($ticket_id, READ);

chd_debug_log([
    'runId'        => 'pre-fix',
    'hypothesisId' => 'H3',
    'location'     => 'front/ajax.debug_timeline.php:access',
    'message'      => 'timeline debug access check',
    'data'         => [
        'ticket_id' => $ticket_id,
        'user_id'   => (int)Session::getLoginUserID(),
        'can_view'  => (bool)$can_view
    ],
]);

if (!$can_view) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

global $DB;

// ---- followups (glpi_itilfollowups) ----
$followups = [];
$followups_table = 'glpi_itilfollowups';
if ($DB->tableExists($followups_table)) {
    $it = $DB->request([
        'FROM'  => $followups_table,
        'WHERE' => [
            'itemtype' => 'Ticket',
            'items_id' => $ticket_id,
        ],
        'ORDER' => 'date ASC'
    ]);
    foreach ($it as $row) {
        $followups[] = [
            'id'         => (int)($row['id'] ?? 0),
            'date'       => (string)($row['date'] ?? ''),
            'is_private' => (int)($row['is_private'] ?? 0),
            'requesttypes_id' => isset($row['requesttypes_id']) ? (int)$row['requesttypes_id'] : null,
            'source'     => 'itilfollowup',
        ];
    }
}

// ---- tasks (glpi_tickettasks) ----
$tasks = [];
$tasks_table = 'glpi_tickettasks';
if ($DB->tableExists($tasks_table)) {
    $it = $DB->request([
        'FROM'  => $tasks_table,
        'WHERE' => [
            'tickets_id' => $ticket_id,
        ],
        'ORDER' => 'date ASC'
    ]);
    foreach ($it as $row) {
        $tasks[] = [
            'id'         => (int)($row['id'] ?? 0),
            'date'       => (string)($row['date'] ?? ''),
            'is_private' => (int)($row['is_private'] ?? 0),
            'state'      => isset($row['state']) ? (int)$row['state'] : null,
            'source'     => 'tickettask',
        ];
    }
}

// ---- summary logs for hypotheses ----
$private_followups = array_values(array_filter($followups, fn($f) => (int)$f['is_private'] === 1));
$private_tasks     = array_values(array_filter($tasks, fn($t) => (int)$t['is_private'] === 1));

chd_debug_log([
    'runId'        => 'pre-fix',
    'hypothesisId' => 'H1',
    'location'     => 'front/ajax.debug_timeline.php:followups',
    'message'      => 'followups loaded',
    'data'         => [
        'ticket_id' => $ticket_id,
        'count'     => count($followups),
        'private'   => count($private_followups),
    ],
]);
chd_debug_log([
    'runId'        => 'pre-fix',
    'hypothesisId' => 'H2',
    'location'     => 'front/ajax.debug_timeline.php:tasks',
    'message'      => 'tasks loaded',
    'data'         => [
        'ticket_id' => $ticket_id,
        'count'     => count($tasks),
        'private'   => count($private_tasks),
    ],
]);

echo json_encode([
    'success' => true,
    'ticket_id' => $ticket_id,
    'counts' => [
        'followups' => count($followups),
        'followups_private' => count($private_followups),
        'tasks' => count($tasks),
        'tasks_private' => count($private_tasks),
    ],
    'followups' => $followups,
    'tasks' => $tasks,
], JSON_UNESCAPED_UNICODE);

