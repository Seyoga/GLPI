<?php
/**
 * Debug: log DOM stats (followup count, visible) from ticket.form.
 * GET: ticket_id, n_followup, n_visible, n_timeline
 */
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include(GLPI_ROOT . "/inc/includes.php");

header('Content-Type: application/json; charset=utf-8');

try {
    Session::checkLoginUser();
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'auth']);
    exit;
}

$ticket_id   = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;
$n_followup  = isset($_GET['n_followup']) ? (int)$_GET['n_followup'] : -1;
$n_visible   = isset($_GET['n_visible']) ? (int)$_GET['n_visible'] : -1;
$n_timeline  = isset($_GET['n_timeline']) ? (int)$_GET['n_timeline'] : -1;

$logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.cursor';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . DIRECTORY_SEPARATOR . 'debug.log';

$line = json_encode([
    'timestamp' => time(),
    'sessionId' => 'debug-session',
    'runId' => 'dom-probe',
    'hypothesisId' => 'H-dom',
    'location' => 'ajax.debug_dom.php',
    'message' => 'ticket form DOM probe',
    'data' => [
        'user_id' => (int)Session::getLoginUserID(),
        'ticket_id' => $ticket_id,
        'n_followup' => $n_followup,
        'n_visible' => $n_visible,
        'n_timeline' => $n_timeline,
    ],
], JSON_UNESCAPED_UNICODE) . "\n";

@file_put_contents($logFile, $line, FILE_APPEND);
echo json_encode(['ok' => true]);
