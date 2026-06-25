<?php

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
include (GLPI_ROOT . "/inc/includes.php");

// Проверка сессии
Session::checkLoginUser();

// Буферизуем вывод заголовка, чтобы вставить стили прямо в <head>
ob_start();
Html::header(__('Закрытые заявки', 'customhelpdesk'), $_SERVER['PHP_SELF'], "helpdesk", "ticket");
$header_html = ob_get_clean();

// Стили для правильного градиента и скрытия пунктов sidebar (как в helpdesk.public.php)
$closed_tickets_inline_style = '<style>
  body {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
    background-attachment: fixed !important;
    min-height: 100vh !important;
  }
  .page, .page-body, .page-body.container-fluid, .container-fluid {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
    background-attachment: fixed !important;
  }
  /* Стили для sidebar - применяются ДО рендеринга */
  .sidebar,
  .navbar-vertical,
  .navbar.navbar-vertical,
  .navbar.navbar-vertical.navbar-expand-lg,
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top,
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 25%, #f1f3f5 50%, #e9ecef 75%, #dee2e6 100%) !important;
    background-attachment: fixed !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* На странице closed_tickets.php полностью скрываем левый sidebar,
     чтобы его элементы не успевали "мигнуть" при загрузке страницы. */
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    pointer-events: none !important;
    width: 0 !important;
  }

  /* Отдельный контейнер для логотипа, который мы клонируем из sidebar.
     Логотип показываем в левом верхнем углу контента. */
  #customhelpdesk-closed-logo {
    position: fixed;
    top: -10px;
    left: 1px;
    width: 220px;
    height: 110px;
    z-index: 1100;
    max-width: 100%;
    max-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: auto;
  }
  #customhelpdesk-closed-logo .navbar-brand {
    width: 192px;
    height: 79px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    overflow: hidden;
  }
  /* Стили для скрытия элементов sidebar на этой странице */
  /* Скрываем все элементы меню sidebar кроме первого (логотип GLPI) */
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child),
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child),
  .sidebar .navbar-nav .nav-item:not(:first-child),
  .sidebar .navbar-nav > .nav-item:not(:first-child),
  .navbar-vertical .navbar-nav .nav-item:not(:first-child),
  .navbar-vertical .navbar-nav > .nav-item:not(:first-child),
  .navbar.navbar-vertical .navbar-nav .nav-item:not(:first-child),
  .navbar.navbar-vertical .navbar-nav > .nav-item:not(:first-child),
  .navbar.navbar-vertical.navbar-expand-lg .navbar-nav .nav-item:not(:first-child),
  .navbar.navbar-vertical.navbar-expand-lg .navbar-nav > .nav-item:not(:first-child),
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-item:not(:first-child),
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav > .nav-item:not(:first-child) {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    pointer-events: none !important;
    width: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    position: absolute !important;
    left: -9999px !important;
    clip: rect(0, 0, 0, 0) !important;
  }
  /* Скрываем кнопку "Свернуть меню" - максимально агрессивно */
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .reduce-menu,
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .reduce-menu,
  .sidebar .reduce-menu,
  .navbar-vertical .reduce-menu,
  .navbar.navbar-vertical .reduce-menu,
  .navbar.navbar-vertical.navbar-expand-lg .reduce-menu,
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .reduce-menu {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    pointer-events: none !important;
    width: 0 !important;
    position: absolute !important;
    left: -9999px !important;
    clip: rect(0, 0, 0, 0) !important;
  }
  /* Скрываем кнопку "Главная" в sidebar - максимально агрессивно */
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .nav-item[data-bs-original-title="Главная"],
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .nav-item[title="Главная"],
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .nav-item[data-bs-original-title="Главная"],
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .nav-item[title="Главная"],
  .sidebar .nav-item[data-bs-original-title="Главная"],
  .sidebar .nav-item[title="Главная"],
  .navbar-vertical .nav-item[data-bs-original-title="Главная"],
  .navbar-vertical .nav-item[title="Главная"],
  .navbar.navbar-vertical .nav-item[data-bs-original-title="Главная"],
  .navbar.navbar-vertical .nav-item[title="Главная"],
  .navbar.navbar-vertical.navbar-expand-lg .nav-item[data-bs-original-title="Главная"],
  .navbar.navbar-vertical.navbar-expand-lg .nav-item[title="Главная"],
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .nav-item[data-bs-original-title="Главная"],
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .nav-item[title="Главная"] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
    pointer-events: none !important;
    width: 0 !important;
    position: absolute !important;
    left: -9999px !important;
    clip: rect(0, 0, 0, 0) !important;
  }
  /* Скрываем все ссылки в меню sidebar кроме navbar-brand - максимально агрессивно */
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-link:not(.navbar-brand),
  .navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-link:not(.navbar-brand),
  .sidebar .navbar-nav .nav-link:not(.navbar-brand),
  .navbar-vertical .navbar-nav .nav-link:not(.navbar-brand),
  .navbar.navbar-vertical .navbar-nav .nav-link:not(.navbar-brand),
  .navbar.navbar-vertical.navbar-expand-lg .navbar-nav .nav-link:not(.navbar-brand),
  aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-nav .nav-link:not(.navbar-brand) {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: absolute !important;
    left: -9999px !important;
    clip: rect(0, 0, 0, 0) !important;
  }
  /* Стили для header */
  header.navbar,
  header.navbar.navbar-expand-lg {
    background: linear-gradient(90deg, #ffffff 0%, #f8f9fa 50%, #f1f3f5 100%) !important;
    background-attachment: fixed !important;
    border: none !important;
    box-shadow: none !important;
  }
  /* Прозрачный фон для breadcrumb */
  header.navbar .breadcrumb {
    background-color: transparent !important;
    background: transparent !important;
  }
</style>';

// Вставляем стили перед </head>, чтобы они применялись до рендеринга sidebar
if (stripos($header_html, '</head>') !== false) {
    $header_html = preg_replace('~</head>~i', $closed_tickets_inline_style . '</head>', $header_html, 1);
} else {
    // fallback: выводим стили перед заголовком
    $header_html = $closed_tickets_inline_style . $header_html;
}


echo $header_html;

global $CFG_GLPI, $DB;

// Получаем закрытые заявки текущего пользователя
$user_id = Session::getLoginUserID();

// Ищем все заявки, созданные текущим пользователем
$ticket_user = new Ticket_User();
$ticket_users = $ticket_user->find([
    'users_id' => $user_id,
    'type' => 1  // 1 = REQUESTER (инициатор)
]);

$ticket_ids = [];
foreach ($ticket_users as $tu) {
    $ticket_ids[] = $tu['tickets_id'];
}

$closed_tickets = [];
if (!empty($ticket_ids)) {
    // Получаем только закрытые заявки (status = 6)
    $iterator = $DB->request([
        'FROM' => 'glpi_tickets',
        'WHERE' => [
            'id' => $ticket_ids,
            'status' => 6  // 6 = Закрыто
        ],
        'ORDER' => 'date_mod DESC',
        'LIMIT' => 100
    ]);
    
    foreach ($iterator as $row) {
        $closed_tickets[] = $row;
    }
}

// Получаем оценки для закрытых заявок
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

echo '<div class="container-fluid mt-4">';
// Контейнер для логотипа (лого клонируем из sidebar через JS)
// Здесь он используется как fallback; по факту логотип позиционируется фиксировано,
// поэтому этот див может оставаться пустым в потоке документа.
echo '<div id="customhelpdesk-closed-logo"></div>';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h3><i class="fas fa-archive"></i> Закрытые заявки</h3>';
echo '</div>';
echo '<div class="card-body">';

if (count($closed_tickets) > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-hover">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Текущий этап</th>';
    echo '<th>Задача</th>';
    echo '<th>Дата создания</th>';
    echo '<th>Дата закрытия</th>';
    echo '<th>Оценка</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($closed_tickets as $ticket) {
        $status = Ticket::getStatus($ticket['status']);
        $date_creation = date('d.m.Y H:i', strtotime($ticket['date_creation']));
        $date_closed = !empty($ticket['closedate']) ? date('d.m.Y H:i', strtotime($ticket['closedate'])) : '-';
        $ticket_url = $CFG_GLPI['root_doc'] . '/front/ticket.form.php?id=' . $ticket['id'];
        
        // Получаем оценку для этой заявки
        $rating = isset($ratings[$ticket['id']]) ? $ratings[$ticket['id']] : null;
        $rating_display = '-';
        if ($rating !== null) {
            $emojis = ['', '😞', '😐', '🙂', '😊', '😍'];
            $rating_display = $emojis[$rating] . ' (' . $rating . '/5)';
        }
        
        echo '<tr style="cursor: pointer;" onclick="window.location.href=\'' . htmlspecialchars($ticket_url) . '\'">';
        echo '<td><span class="badge bg-dark">' . htmlspecialchars($status) . '</span></td>';
        echo '<td>' . htmlspecialchars($ticket['name']) . '</td>';
        echo '<td>' . htmlspecialchars($date_creation) . '</td>';
        echo '<td>' . htmlspecialchars($date_closed) . '</td>';
        echo '<td style="text-align: center;">' . htmlspecialchars($rating_display) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    echo '<div class="text-center py-5">';
    echo '<i class="fas fa-inbox fa-3x text-muted mb-3"></i>';
    echo '<p class="text-muted">У вас нет закрытых заявок</p>';
    echo '</div>';
}

echo '</div>';
echo '<div class="card-footer">';
echo '<a href="' . $CFG_GLPI['root_doc'] . '/front/helpdesk.public.php" class="btn btn-secondary">';
echo '<i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Вернуться к заявкам';
echo '</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// JavaScript: клонируем логотип из sidebar в наш контейнер,
// чтобы левоe меню оставалось скрытым, но лого было видно на странице.
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
  try {
    var sidebarBrand = document.querySelector("aside.navbar.navbar-vertical.navbar-expand-lg.sticky-lg-top.sidebar .navbar-brand");
    var target = document.getElementById("customhelpdesk-closed-logo");
    if (sidebarBrand && target && !target.hasChildNodes()) {
      var clone = sidebarBrand.cloneNode(true);
      target.appendChild(clone);
      target.style.display = "flex";
    }
  } catch (e) {
    // игнорируем ошибки
  }
});
</script>';

Html::footer();

