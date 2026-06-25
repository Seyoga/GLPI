<?php
/**
 * Скрипт для проверки логов PHP
 * Откройте этот файл в браузере: http://localhost/glpi/plugins/customhelpdesk/check_logs.php
 */

// Пути к логам WAMP
$log_paths = [
    'PHP Error Log' => 'C:\\wamp64\\logs\\php_error.log',
    'Apache Error Log' => 'C:\\wamp64\\logs\\apache_error.log',
    'Apache Access Log' => 'C:\\wamp64\\logs\\access.log'
];

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Проверка логов PHP</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}pre{background:#fff;padding:10px;border:1px solid #ddd;overflow:auto;max-height:500px;}</style>";
echo "</head><body><h1>Проверка логов PHP</h1>";

foreach ($log_paths as $name => $path) {
    echo "<h2>$name</h2>";
    echo "<p><strong>Путь:</strong> $path</p>";
    
    if (file_exists($path)) {
        $lines = file($path);
        $recent_lines = array_slice($lines, -50); // Последние 50 строк
        $customhelpdesk_lines = array_filter($lines, function($line) {
            return strpos($line, 'CUSTOMHELPDESK') !== false;
        });
        
        echo "<p><strong>Размер файла:</strong> " . number_format(filesize($path)) . " байт</p>";
        echo "<p><strong>Всего строк с CUSTOMHELPDESK:</strong> " . count($customhelpdesk_lines) . "</p>";
        
        if (!empty($customhelpdesk_lines)) {
            echo "<h3>Последние записи CUSTOMHELPDESK (последние 20):</h3>";
            echo "<pre>";
            $recent_custom = array_slice($customhelpdesk_lines, -20);
            foreach ($recent_custom as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        }
        
        echo "<h3>Последние 20 строк лога:</h3>";
        echo "<pre>";
        foreach (array_slice($lines, -20) as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>Файл не найден!</p>";
    }
    
    echo "<hr>";
}

echo "</body></html>";
?>

