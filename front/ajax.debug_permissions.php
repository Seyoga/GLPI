<?php
/**
 * Debug: check user permissions for ITILFollowup.
 * Usage: .../ajax.debug_permissions.php?ticket_id=27
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$json_err = function ($msg, $detail = null) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error'   => $msg,
        'detail'  => $detail,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
};

set_error_handler(function ($errno, $errstr, $file, $line) use (&$json_err) {
    $json_err('php_error', ['errno' => $errno, 'errstr' => $errstr, 'file' => $file, 'line' => $line]);
});
register_shutdown_function(function () use (&$json_err) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (headers_sent()) return;
        $json_err('fatal', $e);
    }
});

ob_start();
define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
if (!is_file(GLPI_ROOT . '/inc/includes.php')) {
    ob_end_clean();
    $json_err('glpi_not_found', GLPI_ROOT);
}
include(GLPI_ROOT . '/inc/includes.php');
restore_error_handler();
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

$logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.cursor';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . DIRECTORY_SEPARATOR . 'debug.log';

$log = function (array $p) use ($logFile) {
    $p['timestamp'] = $p['timestamp'] ?? time();
    $p['sessionId'] = $p['sessionId'] ?? 'debug-session';
    @file_put_contents($logFile, json_encode($p, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
};

try {
    Session::checkLoginUser();
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'auth',
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $user_id    = (int)Session::getLoginUserID();
    $profile_id = (int)($_SESSION['glpiactiveprofile']['id'] ?? 0);
    $profile    = (string)($_SESSION['glpiactiveprofile']['name'] ?? '');

    $raw = (int)($_SESSION['glpiactiveprofile']['itilfollowup'] ?? 0);
    $decoded = [
        'SEEPRIVATE (1)'      => (bool)($raw & 1),
        'ADDMYTICKET (2)'     => (bool)($raw & 2),
        'ADD_AS_FOLLOWUP (4)' => (bool)($raw & 4),
        'ADDALLTICKET (8)'    => (bool)($raw & 8),
        'SEEPUBLIC (16)'      => (bool)($raw & 16),
        'ADDGROUPTICKET (32)' => (bool)($raw & 32),
    ];

    $followup_canView = false;
    $followup_canCreate = false;
    if (class_exists('ITILFollowup')) {
        $followup_canView   = (bool)ITILFollowup::canView();
        $followup_canCreate = (bool)ITILFollowup::canCreate();
    }

    $out = [
        'success'             => true,
        'user_id'             => $user_id,
        'profile_id'          => $profile_id,
        'profile_name'        => $profile,
        'itilfollowup_raw'    => $raw,
        'itilfollowup_decoded'=> $decoded,
        'ITILFollowup::canView'   => $followup_canView,
        'ITILFollowup::canCreate' => $followup_canCreate,
    ];

    $log(['runId' => 'pre-fix', 'hypothesisId' => 'H5', 'location' => 'ajax.debug_permissions.php', 'message' => 'permissions', 'data' => $out]);

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'runtime',
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
