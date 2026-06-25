<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", UPDATE);

$table = 'glpi_plugin_customhelpdesk_configs';
global $DB;

echo "<h2>Миграция базы данных</h2>";

// Проверяем наличие таблицы
if (!$DB->tableExists($table)) {
    echo "<p style='color: red;'>Таблица не существует. Переустановите плагин.</p>";
    exit;
}

echo "<p>Таблица существует.</p>";

// Проверяем наличие полей
$has_profiles = $DB->fieldExists($table, 'profiles');
$has_user_profiles = $DB->fieldExists($table, 'user_profiles');
$has_specialist_profiles = $DB->fieldExists($table, 'specialist_profiles');
$has_user_subcategories = $DB->fieldExists($table, 'user_subcategories');

echo "<p>Поле 'profiles': " . ($has_profiles ? 'существует' : 'не существует') . "</p>";
echo "<p>Поле 'user_profiles': " . ($has_user_profiles ? 'существует' : 'не существует') . "</p>";
echo "<p>Поле 'specialist_profiles': " . ($has_specialist_profiles ? 'существует' : 'не существует') . "</p>";
echo "<p>Поле 'user_subcategories': " . ($has_user_subcategories ? 'существует' : 'не существует') . "</p>";

// Выполняем миграцию
if ($has_profiles && (!$has_user_profiles || !$has_specialist_profiles)) {
    echo "<h3>Выполняем миграцию...</h3>";
    
    // Добавляем новые поля
    if (!$has_user_profiles) {
        try {
            $DB->query("ALTER TABLE `$table` ADD COLUMN `user_profiles` TEXT NULL AFTER `id`");
            echo "<p style='color: green;'>Поле 'user_profiles' добавлено.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Ошибка при добавлении 'user_profiles': " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$has_specialist_profiles) {
        try {
            $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_profiles` TEXT NULL AFTER `user_profiles`");
            echo "<p style='color: green;'>Поле 'specialist_profiles' добавлено.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Ошибка при добавлении 'specialist_profiles': " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$has_user_subcategories) {
        try {
            $DB->query("ALTER TABLE `$table` ADD COLUMN `user_subcategories` TEXT NULL AFTER `specialist_profiles`");
            echo "<p style='color: green;'>Поле 'user_subcategories' добавлено.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Ошибка при добавлении 'user_subcategories': " . $e->getMessage() . "</p>";
        }
    }
    
    // Копируем данные
    $iterator = $DB->request([
        'FROM' => $table,
        'LIMIT' => 1
    ]);
    if (count($iterator)) {
        $config = $iterator->current();
        if (isset($config['profiles'])) {
            try {
                $DB->update($table, [
                    'user_profiles' => $config['profiles']
                ], ['id' => 1]);
                echo "<p style='color: green;'>Данные скопированы из 'profiles' в 'user_profiles'.</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Ошибка при копировании данных: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    // Удаляем старое поле
    try {
        $DB->query("ALTER TABLE `$table` DROP COLUMN `profiles`");
        echo "<p style='color: green;'>Старое поле 'profiles' удалено.</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Ошибка при удалении 'profiles': " . $e->getMessage() . "</p>";
    }
    
} elseif (!$has_user_profiles || !$has_specialist_profiles) {
    echo "<h3>Добавляем недостающие поля...</h3>";
    
    if (!$has_user_profiles) {
        try {
            $DB->query("ALTER TABLE `$table` ADD COLUMN `user_profiles` TEXT NULL AFTER `id`");
            echo "<p style='color: green;'>Поле 'user_profiles' добавлено.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$has_specialist_profiles) {
        try {
            $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_profiles` TEXT NULL AFTER `user_profiles`");
            echo "<p style='color: green;'>Поле 'specialist_profiles' добавлено.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Ошибка: " . $e->getMessage() . "</p>";
        }
    }
} else {
    echo "<p style='color: green;'>Миграция не требуется. Все поля на месте.</p>";
}

// Показываем текущую конфигурацию
echo "<h3>Текущая конфигурация:</h3>";
$config = PluginCustomhelpdeskConfig::getConfig();
if (!empty($config)) {
    echo "<pre>";
    print_r($config);
    echo "</pre>";
    
    // Показываем декодированные данные
    $user_profiles = [];
    if (isset($config['user_profiles']) && !empty($config['user_profiles'])) {
        $user_profiles = json_decode($config['user_profiles'], true) ?: [];
    }
    $specialist_profiles = [];
    if (isset($config['specialist_profiles']) && !empty($config['specialist_profiles'])) {
        $specialist_profiles = json_decode($config['specialist_profiles'], true) ?: [];
    }
    
    echo "<h4>Декодированные данные:</h4>";
    echo "<p><strong>Профили пользователей:</strong> ";
    if (empty($user_profiles)) {
        echo "пусто";
    } else {
        echo implode(', ', $user_profiles);
    }
    echo "</p>";
    echo "<p><strong>Профили специалистов:</strong> ";
    if (empty($specialist_profiles)) {
        echo "пусто";
    } else {
        echo implode(', ', $specialist_profiles);
    }
    echo "</p>";
} else {
    echo "<p>Конфигурация пуста. <strong>Это нормально после миграции.</strong> Нужно заново сохранить настройки в форме конфигурации.</p>";
}

echo "<p><a href='" . Plugin::getWebDir('customhelpdesk') . "/front/config.form.php'>Вернуться к настройкам</a></p>";

