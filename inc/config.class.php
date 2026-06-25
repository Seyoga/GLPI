<?php

class PluginCustomhelpdeskConfig extends CommonDBTM {
    
    static function getTypeName($nb = 0) {
        return 'Custom Helpdesk Configuration';
    }
    
    static function getConfig() {
        global $DB;
        
        $config = [];
        
        try {
            $iterator = $DB->request([
                'FROM' => 'glpi_plugin_customhelpdesk_configs',
                'LIMIT' => 1
            ]);
            
            if (count($iterator)) {
                $config = $iterator->current();
            }
        } catch (Exception $e) {
            // Таблица ещё не создана
            return [];
        }
        
        return $config;
    }
    
    static function setConfig($user_profiles = [], $user_subcategories = [], $bgu_category_id = null, $zkgu_category_id = null, $specialist_profiles = [], $specialist_subcategories = [], $entity_managers = []) {
        global $DB;
        
        try {
            // Проверяем, существует ли таблица
            $table = 'glpi_plugin_customhelpdesk_configs';
            if (!$DB->tableExists($table)) {
                error_log('CustomHelpdesk setConfig: Table does not exist');
                return false;
            }
            
            // Проверяем наличие всех необходимых полей и добавляем их, если их нет
            if (!$DB->fieldExists($table, 'user_profiles')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `user_profiles` TEXT NULL AFTER `id`");
            }
            if (!$DB->fieldExists($table, 'specialist_profiles')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_profiles` TEXT NULL AFTER `user_profiles`");
            }
            if (!$DB->fieldExists($table, 'user_subcategories')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `user_subcategories` TEXT NULL AFTER `specialist_profiles`");
            }
            if (!$DB->fieldExists($table, 'specialist_subcategories')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `specialist_subcategories` TEXT NULL AFTER `user_subcategories`");
            }
            if (!$DB->fieldExists($table, 'bgu_category_id')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `bgu_category_id` INT(11) NULL AFTER `specialist_subcategories`");
            }
            if (!$DB->fieldExists($table, 'zkgu_category_id')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `zkgu_category_id` INT(11) NULL AFTER `bgu_category_id`");
            }
            if (!$DB->fieldExists($table, 'entity_managers')) {
                $DB->query("ALTER TABLE `$table` ADD COLUMN `entity_managers` TEXT NULL AFTER `zkgu_category_id`");
            }
            
            $user_profiles_json = json_encode($user_profiles);
            $user_subcategories_json = json_encode($user_subcategories);
            $specialist_profiles_json = json_encode($specialist_profiles);
            $specialist_subcategories_json = json_encode($specialist_subcategories);
            $entity_managers_json = json_encode($entity_managers);
            
            // Правильно обрабатываем NULL значения для категорий
            // Если пришла пустая строка или 0, устанавливаем NULL
            if (empty($bgu_category_id) || $bgu_category_id === '' || $bgu_category_id === 0) {
                $bgu_category_id = null;
            } else {
                $bgu_category_id = (int)$bgu_category_id;
            }
            
            if (empty($zkgu_category_id) || $zkgu_category_id === '' || $zkgu_category_id === 0) {
                $zkgu_category_id = null;
            } else {
                $zkgu_category_id = (int)$zkgu_category_id;
            }
            
            // Проверяем, существует ли запись с id = 1
            $iterator = $DB->request([
                'FROM' => $table,
                'WHERE' => ['id' => 1],
                'LIMIT' => 1
            ]);
            
            $record_exists = (count($iterator) > 0);
            
            if ($record_exists) {
                // Обновляем существующую запись через прямой SQL (для правильной работы с NULL)
                // Используем подготовленные значения для NULL
                $bgu_value = $bgu_category_id !== null ? (int)$bgu_category_id : 'NULL';
                $zkgu_value = $zkgu_category_id !== null ? (int)$zkgu_category_id : 'NULL';
                
                $update_query = "UPDATE `$table` SET 
                    `user_profiles` = " . $DB->quote($user_profiles_json) . ",
                    `specialist_profiles` = " . $DB->quote($specialist_profiles_json) . ",
                    `user_subcategories` = " . $DB->quote($user_subcategories_json) . ",
                    `specialist_subcategories` = " . $DB->quote($specialist_subcategories_json) . ",
                    `bgu_category_id` = " . $bgu_value . ",
                    `zkgu_category_id` = " . $zkgu_value . ",
                    `entity_managers` = " . $DB->quote($entity_managers_json) . "
                    WHERE `id` = 1";
                
                error_log('CustomHelpdesk setConfig UPDATE query: ' . $update_query);
                
                // Используем queryOrDie для надежности
                try {
                    $DB->queryOrDie($update_query, $DB->error());
                    error_log('CustomHelpdesk setConfig UPDATE success');
                } catch (Exception $e) {
                    error_log('CustomHelpdesk setConfig UPDATE exception: ' . $e->getMessage());
                    return false;
                }
            } else {
                // Создаем новую запись через прямой SQL
                $bgu_value = $bgu_category_id !== null ? (int)$bgu_category_id : 'NULL';
                $zkgu_value = $zkgu_category_id !== null ? (int)$zkgu_category_id : 'NULL';
                
                $insert_query = "INSERT INTO `$table` 
                    (`id`, `user_profiles`, `specialist_profiles`, `user_subcategories`, `specialist_subcategories`, `bgu_category_id`, `zkgu_category_id`, `entity_managers`) 
                    VALUES (1, " . $DB->quote($user_profiles_json) . ", " . $DB->quote($specialist_profiles_json) . ", " . 
                    $DB->quote($user_subcategories_json) . ", " . $DB->quote($specialist_subcategories_json) . ", " . 
                    $bgu_value . ", " . $zkgu_value . ", " . $DB->quote($entity_managers_json) . ")";
                
                error_log('CustomHelpdesk setConfig INSERT query: ' . $insert_query);
                
                // Используем queryOrDie для надежности
                try {
                    $DB->queryOrDie($insert_query, $DB->error());
                    error_log('CustomHelpdesk setConfig INSERT success');
                } catch (Exception $e) {
                    error_log('CustomHelpdesk setConfig INSERT exception: ' . $e->getMessage());
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('CustomHelpdesk setConfig exception: ' . $e->getMessage());
            return false;
        }
    }
    
    static function showConfigForm() {
        global $CFG_GLPI;
        
        $config = self::getConfig();
        $selected_user_profiles = [];
        $user_subcategories = [];
        $selected_specialist_profiles = [];
        $specialist_subcategories = [];
        $entity_managers = [];
        
        if (isset($config['user_profiles'])) {
            $selected_user_profiles = json_decode($config['user_profiles'], true) ?: [];
        } elseif (isset($config['profiles'])) {
            // Обратная совместимость со старым форматом
            $selected_user_profiles = json_decode($config['profiles'], true) ?: [];
        }
        
        if (isset($config['user_subcategories'])) {
            $user_subcategories = json_decode($config['user_subcategories'], true) ?: [];
        }
        
        if (isset($config['specialist_profiles'])) {
            $selected_specialist_profiles = json_decode($config['specialist_profiles'], true) ?: [];
        }
        
        if (isset($config['specialist_subcategories'])) {
            $specialist_subcategories = json_decode($config['specialist_subcategories'], true) ?: [];
        }

        // Менеджеры по организациям: нормализуем в массив записей [{entity_id, name, phone}, ...]
        if (isset($config['entity_managers'])) {
            $raw_entity_managers = json_decode($config['entity_managers'], true) ?: [];
            $normalized_entity_managers = [];

            if (is_array($raw_entity_managers) && !empty($raw_entity_managers)) {
                // Определяем формат: новый (массив записей) или старый (entity_id => ['name','phone'])
                $first = reset($raw_entity_managers);

                if (is_array($first) && array_key_exists('entity_id', $first)) {
                    // Новый формат
                    foreach ($raw_entity_managers as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $entity_id = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
                        $name      = isset($row['name']) ? (string)$row['name'] : '';
                        $phone     = isset($row['phone']) ? (string)$row['phone'] : '';

                        if ($entity_id > 0 || $name !== '' || $phone !== '') {
                            $normalized_entity_managers[] = [
                                'entity_id' => $entity_id,
                                'name'      => $name,
                                'phone'     => $phone,
                            ];
                        }
                    }
                } else {
                    // Старый формат: [entity_id => ['name' => ..., 'phone' => ...], ...]
                    foreach ($raw_entity_managers as $entity_id => $manager) {
                        if (!is_array($manager)) {
                            continue;
                        }
                        $entity_id = (int)$entity_id;
                        $name      = isset($manager['name']) ? (string)$manager['name'] : '';
                        $phone     = isset($manager['phone']) ? (string)$manager['phone'] : '';

                        if ($entity_id > 0 || $name !== '' || $phone !== '') {
                            $normalized_entity_managers[] = [
                                'entity_id' => $entity_id,
                                'name'      => $name,
                                'phone'     => $phone,
                            ];
                        }
                    }
                }
            }

            $entity_managers = $normalized_entity_managers;
        }
        
        // Получаем все профили
        $profile = new Profile();
        $profiles = $profile->find();
        
        echo '<div class="container-fluid">';
        echo '<form method="post" action="' . Plugin::getWebDir('customhelpdesk') . '/front/config.form.php">';
        
        echo '<div class="card mt-4">';
        echo '<div class="card-header">';
        echo '<h3><i class="fas fa-cog"></i> Настройки Custom Helpdesk</h3>';
        echo '</div>';
        
        echo '<div class="card-body">';
        
        // Секция выбора главных категорий
        echo '<div class="mb-4">';
        echo '<h4 class="mb-3"><i class="fas fa-folder"></i> Главные категории для подкатегорий пользователей</h4>';
        echo '<p class="text-muted mb-3">Выберите главные (родительские) категории, подкатегории которых будут доступны пользователям БГУ и ЗКГУ:</p>';
        
        // Получаем все категории GLPI
        $itilcategory = new ITILCategory();
        $all_categories = $itilcategory->find([]);
        
        // Фильтруем только главные категории (без родителя)
        $parent_categories = [];
        foreach ($all_categories as $cat) {
            // Проверяем поле родителя - в GLPI это itilcategories_id
            $parent_id = 0;
            if (isset($cat['itilcategories_id'])) {
                $parent_id = (int)$cat['itilcategories_id'];
            } elseif (isset($cat['itilcategory_id'])) {
                $parent_id = (int)$cat['itilcategory_id'];
            } elseif (isset($cat['parent_id'])) {
                $parent_id = (int)$cat['parent_id'];
            }
            
            // Только категории без родителя (главные категории)
            if ($parent_id == 0) {
                $parent_categories[] = $cat;
            }
        }
        
        // Сортируем по имени
        usort($parent_categories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        // Получаем сохраненные ID категорий
        $saved_bgu_category_id = isset($config['bgu_category_id']) ? (int)$config['bgu_category_id'] : null;
        $saved_zkgu_category_id = isset($config['zkgu_category_id']) ? (int)$config['zkgu_category_id'] : null;
        
        echo '<div class="row mb-3">';
        echo '<div class="col-md-6">';
        echo '<label class="form-label"><strong>Категория БГУ:</strong></label>';
        echo '<select class="form-select" name="bgu_category_id" id="bgu_category_id">';
        echo '<option value="">-- Не выбрано --</option>';
        foreach ($parent_categories as $cat) {
            $selected = ($saved_bgu_category_id == $cat['id']) ? 'selected' : '';
            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . ' (ID: ' . $cat['id'] . ')</option>';
        }
        echo '</select>';
        echo '<small class="form-text text-muted">Подкатегории этой категории будут доступны пользователям БГУ</small>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<label class="form-label"><strong>Категория ЗКГУ:</strong></label>';
        echo '<select class="form-select" name="zkgu_category_id" id="zkgu_category_id">';
        echo '<option value="">-- Не выбрано --</option>';
        foreach ($parent_categories as $cat) {
            $selected = ($saved_zkgu_category_id == $cat['id']) ? 'selected' : '';
            echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . ' (ID: ' . $cat['id'] . ')</option>';
        }
        echo '</select>';
        echo '<small class="form-text text-muted">Подкатегории этой категории будут доступны пользователям ЗКГУ</small>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<hr class="my-4">';

        // Секция менеджеров по организациям (сразу после выбора категорий)
        echo '<div class="mb-4">';
        echo '<h4 class="mb-3"><i class="fas fa-building"></i> Менеджеры по организациям</h4>';
        echo '<p class="text-muted mb-3">Укажите для каких организаций показывать контакт менеджера в правом нижнем углу на helpdesk.public.php.</p>';

        echo '<div class="table-responsive">';
        echo '<table id="entity-managers-table" class="table table-hover table-sm align-middle">';
        echo '<thead class="table-light">';
        echo '<tr>';
        echo '<th style="width: 35%;">Организация</th>';
        echo '<th style="width: 35%;">ФИО менеджера</th>';
        echo '<th style="width: 20%;">Телефон</th>';
        echo '<th style="width: 10%;">Действия</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Если нет сохранённых записей — создаём одну пустую строку
        $rows = [];
        if (!empty($entity_managers) && is_array($entity_managers)) {
            $rows = $entity_managers;
        }
        if (empty($rows)) {
            $rows = [
                [
                    'entity_id' => 0,
                    'name'      => '',
                    'phone'     => '',
                ]
            ];
        }

        $row_index = 0;
        foreach ($rows as $row) {
            $entity_id = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
            $manager_name  = isset($row['name']) ? $row['name'] : '';
            $manager_phone = isset($row['phone']) ? $row['phone'] : '';

            echo '<tr data-index="' . $row_index . '">';

            // Организация
            echo '<td>';
            Dropdown::show('Entity', [
                'name'  => 'entity_managers[' . $row_index . '][entity_id]',
                'value' => $entity_id,
                'display_emptychoice' => true
            ]);
            echo '</td>';

            // ФИО менеджера
            echo '<td>';
            echo '<input type="text" class="form-control form-control-sm" ';
            echo 'name="entity_managers[' . $row_index . '][name]" ';
            echo 'value="' . Html::cleanInputText($manager_name) . '" ';
            echo 'placeholder="ФИО менеджера">';
            echo '</td>';

            // Телефон
            echo '<td>';
            echo '<input type="text" class="form-control form-control-sm" ';
            echo 'name="entity_managers[' . $row_index . '][phone]" ';
            echo 'value="' . Html::cleanInputText($manager_phone) . '" ';
            echo 'placeholder="+7 ...">';
            echo '</td>';

            // Действия
            echo '<td class="text-nowrap">';
            echo '<button type="button" class="btn btn-sm btn-outline-success entity-manager-add" title="Добавить строку"><i class="fas fa-plus"></i></button> ';
            echo '<button type="button" class="btn btn-sm btn-outline-danger entity-manager-remove" title="Удалить строку"><i class="fas fa-minus"></i></button>';
            echo '</td>';

            echo '</tr>';

            $row_index++;
        }

        echo '</tbody>';
        echo '</table>';
        echo '<small class="form-text text-muted">Чтобы добавить несколько организаций, используйте кнопки “+” и “–”.</small>';
        echo '</div>';

        echo '</div>';

        echo '<hr class="my-4">';

        // Секция для пользователей
        echo '<div class="mb-4">';
        echo '<h4 class="mb-3"><i class="fas fa-users"></i> Профили для пользователей</h4>';
        echo '<p class="text-muted mb-3">Выберите профили для применения пользовательского интерфейса и укажите подкатегорию доступа:</p>';
        
        // Компактная таблица
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover table-sm align-middle">';
        echo '<thead class="table-light">';
        echo '<tr>';
        echo '<th style="width: 40px;"><input type="checkbox" id="select-all-profiles" title="Выбрать все"></th>';
        echo '<th>Профиль</th>';
        echo '<th style="width: 300px;">Подкатегория</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($profiles as $prof) {
            $checked = in_array($prof['id'], $selected_user_profiles) ? 'checked' : '';
            $subcategory = isset($user_subcategories[$prof['id']]) ? $user_subcategories[$prof['id']] : 'full';
            
            echo '<tr>';
            // Чекбокс
            echo '<td>';
            echo '<input class="form-check-input user-profile-checkbox" type="checkbox" name="user_profiles[]" value="' . $prof['id'] . '" id="user_profile_' . $prof['id'] . '" ' . $checked . ' data-profile-id="' . $prof['id'] . '">';
            echo '</td>';
            // Название профиля
            echo '<td>';
            echo '<label class="form-check-label ms-2" for="user_profile_' . $prof['id'] . '" style="cursor: pointer;">';
            echo '<strong>' . htmlspecialchars($prof['name']) . '</strong>';
            echo ' <span class="text-muted small">(ID: ' . $prof['id'] . ')</span>';
            echo '</label>';
            echo '</td>';
            // Выбор подкатегории
            echo '<td>';
            echo '<select class="form-select form-select-sm user-subcategory-select" name="user_subcategories[' . $prof['id'] . ']" id="user_subcategory_' . $prof['id'] . '" style="display: ' . ($checked ? 'block' : 'none') . ';">';
            echo '<option value="full" ' . ($subcategory === 'full' ? 'selected' : '') . '>Полный пользователь (все категории)</option>';
            echo '<option value="bgu" ' . ($subcategory === 'bgu' ? 'selected' : '') . '>Пользователь БГУ (только 1С: Бухгалтерия)</option>';
            echo '<option value="zkgu" ' . ($subcategory === 'zkgu' ? 'selected' : '') . '>Пользователь ЗКГУ (только 1С: Зарплата и кадры)</option>';
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        
        echo '<hr class="my-4">';
        
        // Секция для специалистов
        echo '<div class="mb-4">';
        echo '<h4 class="mb-3"><i class="fas fa-user-tie"></i> Профили для специалистов</h4>';
        echo '<p class="text-muted mb-3">Выберите профили для применения интерфейса специалистов и укажите подкатегорию доступа:</p>';
        
        // Компактная таблица для специалистов
        echo '<div class="table-responsive">';
        echo '<table class="table table-hover table-sm align-middle">';
        echo '<thead class="table-light">';
        echo '<tr>';
        echo '<th style="width: 40px;"><input type="checkbox" id="select-all-specialist-profiles" title="Выбрать все"></th>';
        echo '<th>Профиль</th>';
        echo '<th style="width: 300px;">Подкатегория</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($profiles as $prof) {
            $checked = in_array($prof['id'], $selected_specialist_profiles) ? 'checked' : '';
            $subcategory = isset($specialist_subcategories[$prof['id']]) ? $specialist_subcategories[$prof['id']] : 'full';
            
            echo '<tr>';
            // Чекбокс
            echo '<td>';
            echo '<input class="form-check-input specialist-profile-checkbox" type="checkbox" name="specialist_profiles[]" value="' . $prof['id'] . '" id="specialist_profile_' . $prof['id'] . '" ' . $checked . ' data-profile-id="' . $prof['id'] . '">';
            echo '</td>';
            // Название профиля
            echo '<td>';
            echo '<label class="form-check-label ms-2" for="specialist_profile_' . $prof['id'] . '" style="cursor: pointer;">';
            echo '<strong>' . htmlspecialchars($prof['name']) . '</strong>';
            echo ' <span class="text-muted small">(ID: ' . $prof['id'] . ')</span>';
            echo '</label>';
            echo '</td>';
            // Выбор подкатегории
            echo '<td>';
            echo '<select class="form-select form-select-sm specialist-subcategory-select" name="specialist_subcategories[' . $prof['id'] . ']" id="specialist_subcategory_' . $prof['id'] . '" style="display: ' . ($checked ? 'block' : 'none') . ';">';
            echo '<option value="full" ' . ($subcategory === 'full' ? 'selected' : '') . '>Полный специалист (все категории)</option>';
            echo '<option value="bgu" ' . ($subcategory === 'bgu' ? 'selected' : '') . '>Специалист БГУ (только 1С: Бухгалтерия)</option>';
            echo '<option value="zkgu" ' . ($subcategory === 'zkgu' ? 'selected' : '') . '>Специалист ЗКГУ (только 1С: Зарплата и кадры)</option>';
            echo '</select>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
        
        // JavaScript для показа/скрытия выбора подкатегории, "Выбрать все" и динамических строк менеджеров
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Управление строками менеджеров по организациям
            var entityTable = document.getElementById("entity-managers-table");
            if (entityTable) {
                var tbody = entityTable.querySelector("tbody");

                function recalcEntityManagerRows() {
                    var rows = tbody.querySelectorAll("tr");
                    rows.forEach(function(row, index) {
                        row.setAttribute("data-index", index);
                        row.querySelectorAll("select, input").forEach(function(input) {
                            var name = input.getAttribute("name");
                            if (!name) {
                                return;
                            }
                            name = name.replace(/entity_managers\[\d+]/, "entity_managers[" + index + "]");
                            input.setAttribute("name", name);
                        });
                    });
                }

                recalcEntityManagerRows();

                tbody.addEventListener("click", function(event) {
                    var addBtn = event.target.closest(".entity-manager-add");
                    var removeBtn = event.target.closest(".entity-manager-remove");

                    if (addBtn) {
                        var row = addBtn.closest("tr");

                        // Считываем параметры select2 с исходного селекта строки,
                        // чтобы потом инициализировать клон с теми же настройками (ajax, поиск и т.п.)
                        var originalSelect = row.querySelector("select");
                        var originalSelect2Options = null;
                        if (typeof jQuery !== "undefined" && originalSelect) {
                            try {
                                var $orig = jQuery(originalSelect);
                                var origInstance = $orig.data("select2");
                                if (origInstance && origInstance.options && origInstance.options.options) {
                                    originalSelect2Options = jQuery.extend(true, {}, origInstance.options.options);
                                }
                            } catch (e) {
                                // безопасно игнорируем ошибки определения опций select2
                            }
                        }

                        var clone = row.cloneNode(true);

                        // Очищаем значения инпутов
                        clone.querySelectorAll("input").forEach(function(input) {
                            input.value = "";
                        });

                        // Убираем следы select2 у клона, чтобы переинициализировать его корректно
                        clone.querySelectorAll(".select2").forEach(function(container) {
                            if (container.parentNode) {
                                container.parentNode.removeChild(container);
                            }
                        });
                        clone.querySelectorAll("select").forEach(function(select) {
                            // сбрасываем значение
                            if (select.options.length) {
                                select.selectedIndex = 0;
                            }
                            // убираем классы/атрибуты select2, чтобы элемент был видимым и кликабельным
                            select.classList.remove("select2-hidden-accessible");
                            select.removeAttribute("data-select2-id");
                            select.removeAttribute("aria-hidden");
                            select.style.display = "";
                        });

                        tbody.appendChild(clone);
                        recalcEntityManagerRows();

                        // Переинициализируем select2 для селекта в клоне,
                        // чтобы он работал так же, как и в первой строке (с ajax и поиском)
                        if (typeof jQuery !== "undefined" && originalSelect2Options) {
                            var cloneSelect = clone.querySelector("select");
                            if (cloneSelect) {
                                try {
                                    jQuery(cloneSelect).select2(originalSelect2Options);
                                } catch (e) {
                                    // игнорируем ошибки и оставляем обычный select
                                }
                            }
                        }
                    } else if (removeBtn) {
                        var rows = tbody.querySelectorAll("tr");
                        if (rows.length <= 1) {
                            // Если строка одна, просто очищаем её
                            rows[0].querySelectorAll("input").forEach(function(input) {
                                input.value = "";
                            });
                            rows[0].querySelectorAll("select").forEach(function(select) {
                                if (select.options.length) {
                                    select.selectedIndex = 0;
                                }
                            });
                        } else {
                            var rowToRemove = removeBtn.closest("tr");
                            if (rowToRemove) {
                                rowToRemove.remove();
                                recalcEntityManagerRows();
                            }
                        }
                    }
                });
            }

            // Показать/скрыть подкатегорию при изменении чекбокса для пользователей
            document.querySelectorAll(".user-profile-checkbox").forEach(function(checkbox) {
                checkbox.addEventListener("change", function() {
                    var row = this.closest("tr");
                    var subcategorySelect = row.querySelector(".user-subcategory-select");
                    if (subcategorySelect) {
                        subcategorySelect.style.display = this.checked ? "block" : "none";
                    }
                });
            });
            
            // Показать/скрыть подкатегорию при изменении чекбокса для специалистов
            document.querySelectorAll(".specialist-profile-checkbox").forEach(function(checkbox) {
                checkbox.addEventListener("change", function() {
                    var row = this.closest("tr");
                    var subcategorySelect = row.querySelector(".specialist-subcategory-select");
                    if (subcategorySelect) {
                        subcategorySelect.style.display = this.checked ? "block" : "none";
                    }
                });
            });
            
            // "Выбрать все" для пользователей
            var selectAllUserCheckbox = document.getElementById("select-all-profiles");
            if (selectAllUserCheckbox) {
                selectAllUserCheckbox.addEventListener("change", function() {
                    var checkboxes = document.querySelectorAll(".user-profile-checkbox");
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = selectAllUserCheckbox.checked;
                        var row = checkbox.closest("tr");
                        var subcategorySelect = row.querySelector(".user-subcategory-select");
                        if (subcategorySelect) {
                            subcategorySelect.style.display = checkbox.checked ? "block" : "none";
                        }
                    });
                });
            }
            
            // "Выбрать все" для специалистов
            var selectAllSpecialistCheckbox = document.getElementById("select-all-specialist-profiles");
            if (selectAllSpecialistCheckbox) {
                selectAllSpecialistCheckbox.addEventListener("change", function() {
                    var checkboxes = document.querySelectorAll(".specialist-profile-checkbox");
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = selectAllSpecialistCheckbox.checked;
                        var row = checkbox.closest("tr");
                        var subcategorySelect = row.querySelector(".specialist-subcategory-select");
                        if (subcategorySelect) {
                            subcategorySelect.style.display = checkbox.checked ? "block" : "none";
                        }
                    });
                });
            }
        });
        </script>';
        
        echo '</div>';
        
        echo '<div class="card-footer">';
        echo '<button type="submit" name="update_config" class="btn btn-primary">';
        echo '<i class="fas fa-save"></i> Сохранить настройки';
        echo '</button>';
        echo '</div>';
        
        echo '</div>';
        
        Html::closeForm();
        echo '</div>';
    }
}