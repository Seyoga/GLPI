<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
Session::checkLoginUser();

header('Content-Type: application/json');

// Получаем конфигурацию плагина для ID категорий
$config = PluginCustomhelpdeskConfig::getConfig();
$bgu_parent_id = isset($config['bgu_category_id']) ? (int)$config['bgu_category_id'] : null;
$zkgu_parent_id = isset($config['zkgu_category_id']) ? (int)$config['zkgu_category_id'] : null;

// Инициализируем результат
$result = [
    'bgu' => [],
    'zkgu' => []
];

// Если категории не настроены, возвращаем пустые массивы
if (!$bgu_parent_id && !$zkgu_parent_id) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем категории
$itilcategory = new ITILCategory();
// В GLPI метод find() не поддерживает ORDER напрямую, получаем все и сортируем вручную
$categories = $itilcategory->find([]);
// Сортируем по имени
usort($categories, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Если категорий нет, возвращаем пустые массивы
if (empty($categories)) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем названия главных категорий для отображения
$bgu_category_name = '';
$zkgu_category_name = '';
foreach ($categories as $cat) {
    if ($cat['id'] == $bgu_parent_id) {
        $bgu_category_name = $cat['name'];
    }
    if ($cat['id'] == $zkgu_parent_id) {
        $zkgu_category_name = $cat['name'];
    }
}

// Функция для проверки, является ли категория потомком "1С: Бухгалтерия" или "1С: Зарплата и кадры" (рекурсивно через массив)
function isChildOfParent($category_id, $target_parent_id, $categories_array, $depth = 0) {
    // Защита от бесконечной рекурсии
    if ($depth > 10) {
        return false;
    }
    
    // Находим категорию в массиве
    $current_cat = null;
    foreach ($categories_array as $cat) {
        if ($cat['id'] == $category_id) {
            $current_cat = $cat;
            break;
        }
    }
    
    if (!$current_cat) {
        return false;
    }
    
    // Пробуем разные варианты названия поля для родителя
    $parent_id = 0;
    if (isset($current_cat['itilcategories_id'])) {
        $parent_id = (int)$current_cat['itilcategories_id'];
    } elseif (isset($current_cat['itilcategory_id'])) {
        $parent_id = (int)$current_cat['itilcategory_id'];
    } elseif (isset($current_cat['parent_id'])) {
        $parent_id = (int)$current_cat['parent_id'];
    }
    
    // Если это целевой родитель
    if ($parent_id == $target_parent_id) {
        return true;
    }
    
    // Если нет родителя, значит это не потомок
    if ($parent_id == 0) {
        return false;
    }
    
    // Рекурсивно проверяем родителя
    return isChildOfParent($parent_id, $target_parent_id, $categories_array, $depth + 1);
}

// Теперь распределяем категории
foreach ($categories as $cat) {
    $category_name = $cat['name'];
    $category_id = $cat['id'];
    // Пробуем разные варианты названия поля для родителя
    $parent_id = 0;
    if (isset($cat['itilcategories_id'])) {
        $parent_id = (int)$cat['itilcategories_id'];
    } elseif (isset($cat['itilcategory_id'])) {
        $parent_id = (int)$cat['itilcategory_id'];
    } elseif (isset($cat['parent_id'])) {
        $parent_id = (int)$cat['parent_id'];
    }
    
    // Проверяем, не добавлена ли уже эта категория
    $bgu_ids = array_column($result['bgu'], 'id');
    $zkgu_ids = array_column($result['zkgu'], 'id');
    
    // Пропускаем родительские категории - показываем только подкатегории
    if ($category_id == $bgu_parent_id || $category_id == $zkgu_parent_id) {
        continue; // Пропускаем родительские категории
    }
    
    // Если это прямая подкатегория БГУ
    if ($bgu_parent_id !== null && $parent_id == $bgu_parent_id && !in_array($category_id, $bgu_ids)) {
        $result['bgu'][] = [
            'id' => $category_id,
            'name' => $category_name,
            'is_parent' => false
        ];
        continue;
    }
    // Если это прямая подкатегория ЗКГУ
    if ($zkgu_parent_id !== null && $parent_id == $zkgu_parent_id && !in_array($category_id, $zkgu_ids)) {
        $result['zkgu'][] = [
            'id' => $category_id,
            'name' => $category_name,
            'is_parent' => false
        ];
        continue;
    }
    // Проверяем рекурсивно, является ли категория потомком БГУ
    if ($bgu_parent_id !== null && !in_array($category_id, $bgu_ids) && isChildOfParent($category_id, $bgu_parent_id, $categories)) {
        $result['bgu'][] = [
            'id' => $category_id,
            'name' => $category_name,
            'is_parent' => false
        ];
        continue;
    }
    // Проверяем рекурсивно, является ли категория потомком ЗКГУ
    if ($zkgu_parent_id !== null && !in_array($category_id, $zkgu_ids) && isChildOfParent($category_id, $zkgu_parent_id, $categories)) {
        $result['zkgu'][] = [
            'id' => $category_id,
            'name' => $category_name,
            'is_parent' => false
        ];
        continue;
    }
}

// Добавляем метаинформацию (названия главных категорий)
$result['_meta'] = [
    'bgu_category_id' => $bgu_parent_id,
    'bgu_category_name' => $bgu_category_name,
    'zkgu_category_id' => $zkgu_parent_id,
    'zkgu_category_name' => $zkgu_category_name
];

echo json_encode($result, JSON_UNESCAPED_UNICODE);

