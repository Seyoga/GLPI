<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", UPDATE);

if (isset($_POST['update_config'])) {
    $user_profiles = $_POST['user_profiles'] ?? [];
    $user_subcategories = $_POST['user_subcategories'] ?? [];
    $specialist_profiles = $_POST['specialist_profiles'] ?? [];
    $specialist_subcategories = $_POST['specialist_subcategories'] ?? [];
    $bgu_category_id = $_POST['bgu_category_id'] ?? null;
    $zkgu_category_id = $_POST['zkgu_category_id'] ?? null;
    $entity_managers_raw = $_POST['entity_managers'] ?? [];
    
    // Преобразуем в массивы, если пришли как строки
    if (!is_array($user_profiles)) {
        $user_profiles = [];
    }
    if (!is_array($user_subcategories)) {
        $user_subcategories = [];
    }
    if (!is_array($specialist_profiles)) {
        $specialist_profiles = [];
    }
    if (!is_array($specialist_subcategories)) {
        $specialist_subcategories = [];
    }
    
    // Преобразуем значения в целые числа для профилей
    $user_profiles = array_map('intval', $user_profiles);
    $specialist_profiles = array_map('intval', $specialist_profiles);
    
    // Преобразуем ID категорий
    $bgu_category_id = !empty($bgu_category_id) ? (int)$bgu_category_id : null;
    $zkgu_category_id = !empty($zkgu_category_id) ? (int)$zkgu_category_id : null;

    // Нормализуем данные о менеджерах по организациям
    $entity_managers_normalized = [];
    if (is_array($entity_managers_raw)) {
        foreach ($entity_managers_raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entity_id = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
            $manager_name = isset($row['name']) ? trim($row['name']) : '';
            $manager_phone = isset($row['phone']) ? trim($row['phone']) : '';

            // entity_id может быть 0 (глобальный менеджер по умолчанию),
            // но полностью пустые строки игнорируем
            if ($entity_id === 0 && $manager_name === '' && $manager_phone === '') {
                continue;
            }

            $entity_managers_normalized[] = [
                'entity_id' => $entity_id,
                'name'      => $manager_name,
                'phone'     => $manager_phone,
            ];
        }
    }
    
    // Преобразуем ключи user_subcategories в целые числа
    $user_subcategories_normalized = [];
    foreach ($user_subcategories as $profile_id => $subcategory) {
        $profile_id = (int)$profile_id;
        if (in_array($subcategory, ['full', 'bgu', 'zkgu'])) {
            $user_subcategories_normalized[$profile_id] = $subcategory;
        }
    }
    
    // Преобразуем ключи specialist_subcategories в целые числа
    $specialist_subcategories_normalized = [];
    foreach ($specialist_subcategories as $profile_id => $subcategory) {
        $profile_id = (int)$profile_id;
        if (in_array($subcategory, ['full', 'bgu', 'zkgu'])) {
            $specialist_subcategories_normalized[$profile_id] = $subcategory;
        }
    }
    
    // Проверяем, что данные не пустые перед сохранением (хотя бы один тип профилей должен быть выбран)
    if (empty($user_profiles) && empty($specialist_profiles)) {
        Session::addMessageAfterRedirect('Внимание: Не выбрано ни одного профиля. Настройки не сохранены.', false, WARNING);
        Html::back();
        exit;
    }
    
    $result = PluginCustomhelpdeskConfig::setConfig($user_profiles, $user_subcategories_normalized, $bgu_category_id, $zkgu_category_id, $specialist_profiles, $specialist_subcategories_normalized, $entity_managers_normalized);
    
    if ($result) {
        // Получаем сохраненную конфигурацию для проверки
        $saved_config = PluginCustomhelpdeskConfig::getConfig();
        
        // Дополнительная проверка сохраненных данных
        $saved_user = json_decode($saved_config['user_profiles'] ?? '[]', true) ?: [];
        $saved_specialist = json_decode($saved_config['specialist_profiles'] ?? '[]', true) ?: [];
        
        // Сравниваем количество профилей (не сами массивы, так как порядок может отличаться)
        $saved_user_count = count($saved_user);
        $user_count = count($user_profiles);
        $saved_specialist_count = count($saved_specialist);
        $specialist_count = count($specialist_profiles);
        
        // Проверяем сохранение категорий
        $saved_bgu_category_id = isset($saved_config['bgu_category_id']) ? (int)$saved_config['bgu_category_id'] : null;
        $saved_zkgu_category_id = isset($saved_config['zkgu_category_id']) ? (int)$saved_config['zkgu_category_id'] : null;
        
        // Нормализуем для сравнения (NULL и 0 считаем одинаковыми)
        $bgu_saved = ($saved_bgu_category_id == $bgu_category_id) || 
                     (empty($saved_bgu_category_id) && empty($bgu_category_id));
        $zkgu_saved = ($saved_zkgu_category_id == $zkgu_category_id) || 
                      (empty($saved_zkgu_category_id) && empty($zkgu_category_id));
        
        if ($saved_user_count !== $user_count || $saved_specialist_count !== $specialist_count || !$bgu_saved || !$zkgu_saved) {
            Session::addMessageAfterRedirect('Ошибка: Данные не сохранились корректно. Проверьте логи.', false, ERROR);
        } else {
            $msg = 'Настройки успешно сохранены.';
            if ($user_count > 0) {
                $msg .= ' Пользователи: ' . $user_count;
            }
            if ($specialist_count > 0) {
                $msg .= ' Специалисты: ' . $specialist_count;
            }
            if ($bgu_category_id) {
                $msg .= ', БГУ категория: ' . $bgu_category_id;
            }
            if ($zkgu_category_id) {
                $msg .= ', ЗКГУ категория: ' . $zkgu_category_id;
            }
            Session::addMessageAfterRedirect($msg, false, INFO);
        }
    } else {
        Session::addMessageAfterRedirect('Ошибка при сохранении настроек. Проверьте, что плагин установлен корректно и таблица glpi_plugin_customhelpdesk_configs существует.', false, ERROR);
    }
    
    Html::back();
} else {
    Html::header('Custom Helpdesk Configuration', $_SERVER['PHP_SELF'], 'config', 'plugins');
    
    PluginCustomhelpdeskConfig::showConfigForm();
    
    Html::footer();
}