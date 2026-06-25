// Глобальная переменная для хранения информации о разрешенном профиле специалиста
var isSpecialistProfileAllowed = null;

// Получаем путь к плагину из глобальной переменной (устанавливается в hook.php)
var PLUGIN_PATH = (typeof window.CUSTOMHELPDESK_PLUGIN_PATH !== 'undefined') 
  ? window.CUSTOMHELPDESK_PLUGIN_PATH 
  : '/glpi/plugins/customhelpdesk';

// Ранний блокирующий скрипт для интерфейса специалистов.
// Добавляет базовые стили до загрузки остальных скриптов и CSS, чтобы избежать FOUC.
(function() {
  try {
    // Создаем style элемент и добавляем его в head синхронно
    var style = document.createElement("style");
    style.id = "custom-helpdesk-critical-styles-immediate-specialists";
  style.textContent = `
      /* Базовые стили для интерфейса специалистов, подключаемые до остальных стилей */
      /* Скрываем RSS ленту на странице central.php */
      .nav-item a[title="RSS лента"],
      .nav-item a[data-bs-original-title="RSS лента"],
      .nav-item:has(a[title="RSS лента"]),
      .nav-item:has(a[data-bs-original-title="RSS лента"]) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        pointer-events: none !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }
      /* Скрываем "Личные каналы RSS" из dropdown меню */
      .dropdown-item[href*="rssfeed.php"],
      .dropdown-item[title="Личные каналы RSS"],
      .dropdown-item:has(a[href*="rssfeed.php"]),
      a.dropdown-item[href*="rssfeed.php"],
      a.dropdown-item[title="Личные каналы RSS"],
      .dropdown-item:has(i.ti-rss),
      .dropdown-item:has(.ti-rss) {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        pointer-events: none !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
      }
    `;
    
    // Добавляем style в head СРАЗУ, синхронно
    var head = document.head || document.getElementsByTagName("head")[0] || document.documentElement;
    if (head) {
      head.insertBefore(style, head.firstChild);
      
      // Применяем стили к элементам СРАЗУ, если они уже есть в DOM
      if (document.body) {
        // Ищем и скрываем RSS элементы сразу
        var rssLinks = document.querySelectorAll('.nav-item a[title="RSS лента"], .nav-item a[data-bs-original-title="RSS лента"]');
        for (var i = 0; i < rssLinks.length; i++) {
          var link = rssLinks[i];
          var navItem = link.closest('.nav-item');
          if (navItem) {
            navItem.style.setProperty('display', 'none', 'important');
            navItem.style.setProperty('visibility', 'hidden', 'important');
            navItem.style.setProperty('opacity', '0', 'important');
            navItem.style.setProperty('height', '0', 'important');
            navItem.style.setProperty('overflow', 'hidden', 'important');
            navItem.style.setProperty('pointer-events', 'none', 'important');
          }
        }
        
        // Ищем и скрываем dropdown элементы RSS сразу
        var dropdownItems = document.querySelectorAll('.dropdown-item[href*="rssfeed.php"], .dropdown-item[title="Личные каналы RSS"]');
        for (var j = 0; j < dropdownItems.length; j++) {
          var item = dropdownItems[j];
          item.style.setProperty('display', 'none', 'important');
          item.style.setProperty('visibility', 'hidden', 'important');
          item.style.setProperty('opacity', '0', 'important');
          item.style.setProperty('height', '0', 'important');
          item.style.setProperty('overflow', 'hidden', 'important');
          item.style.setProperty('pointer-events', 'none', 'important');
        }
      }
    }
    
    // Используем MutationObserver для моментального скрытия элементов при их появлении
    if (document.body) {
    var observer = new MutationObserver(function(mutations) {
        // Скрываем RSS ленту
        var rssLinks = document.querySelectorAll('.nav-item a[title="RSS лента"], .nav-item a[data-bs-original-title="RSS лента"]');
        for (var i = 0; i < rssLinks.length; i++) {
          var link = rssLinks[i];
          var navItem = link.closest('.nav-item');
          if (navItem && window.getComputedStyle(navItem).display !== 'none') {
            navItem.style.setProperty('display', 'none', 'important');
            navItem.style.setProperty('visibility', 'hidden', 'important');
            navItem.style.setProperty('opacity', '0', 'important');
            navItem.style.setProperty('height', '0', 'important');
            navItem.style.setProperty('overflow', 'hidden', 'important');
            navItem.style.setProperty('pointer-events', 'none', 'important');
          }
        }
        
        // Скрываем dropdown элементы RSS
        var dropdownItems = document.querySelectorAll('.dropdown-item[href*="rssfeed.php"], .dropdown-item[title="Личные каналы RSS"]');
        for (var j = 0; j < dropdownItems.length; j++) {
          var item = dropdownItems[j];
          if (window.getComputedStyle(item).display !== 'none') {
            item.style.setProperty('display', 'none', 'important');
            item.style.setProperty('visibility', 'hidden', 'important');
            item.style.setProperty('opacity', '0', 'important');
            item.style.setProperty('height', '0', 'important');
            item.style.setProperty('overflow', 'hidden', 'important');
            item.style.setProperty('pointer-events', 'none', 'important');
          }
        }
      });
      
      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
      
      // Отключаем наблюдатель через 10 секунд (защитное отключение)
      setTimeout(function() {
        observer.disconnect();
      }, 10000);
    }
  } catch(e) {
    // Игнорируем ошибки
  }
})();

// Функция для проверки профиля специалиста
function checkSpecialistProfile(callback) {
  // Если уже проверяли, используем кэш
  if (isSpecialistProfileAllowed !== null) {
    if (callback) callback(isSpecialistProfileAllowed);
    return;
  }
  
  // Проверяем через AJAX
  $.ajax({
    url: PLUGIN_PATH + '/front/ajax.check_specialist_profile.php',
    method: 'GET',
    dataType: 'json',
    cache: false,
    success: function(data) {
      isSpecialistProfileAllowed = data.is_specialist === true;
      if (callback) callback(isSpecialistProfileAllowed);
    },
    error: function(xhr, status, error) {
      console.error('Ошибка при проверке профиля специалиста:', status, error);
      // В случае ошибки не применяем функционал (безопаснее)
      isSpecialistProfileAllowed = false;
      if (callback) callback(false);
    }
  });
}

// Функция для отключения полей "Наблюдатель" и "Заявитель" для специалистов
function disableObserverField() {
  // Ищем все select элементы с data-actor-type="observer" и "requester"
  var actorSelects = document.querySelectorAll('select[data-actor-type="observer"], select[data-actor-type="requester"]');
  
  actorSelects.forEach(function(select) {
    // Отключаем сам select
    select.disabled = true;
    select.setAttribute('aria-disabled', 'true');
    
    // Отключаем select2 контейнер
    var select2Container = select.nextElementSibling;
    if (select2Container && select2Container.classList.contains('select2-container')) {
      var select2Selection = select2Container.querySelector('.select2-selection');
      if (select2Selection) {
        select2Selection.setAttribute('aria-disabled', 'true');
        select2Selection.setAttribute('tabindex', '-1');
        
        // Отключаем input для поиска
        var searchInput = select2Selection.querySelector('.select2-search__field');
        if (searchInput) {
          searchInput.disabled = true;
          searchInput.setAttribute('tabindex', '-1');
        }
      }
      
      // Отключаем select2 через jQuery, если доступен
      if (typeof $ !== 'undefined' && $.fn.select2) {
        try {
          $(select).select2('enable', false);
        } catch(e) {
          // Игнорируем ошибки, если select2 еще не инициализирован
        }
      }
    }
  });
  
  // Используем MutationObserver для отключения динамически добавляемых полей
  if (document.body) {
    var observerFieldObserver = new MutationObserver(function() {
      var actorSelects = document.querySelectorAll('select[data-actor-type="observer"]:not([disabled]), select[data-actor-type="requester"]:not([disabled])');
      actorSelects.forEach(function(select) {
        select.disabled = true;
        select.setAttribute('aria-disabled', 'true');
        
        var select2Container = select.nextElementSibling;
        if (select2Container && select2Container.classList.contains('select2-container')) {
          var select2Selection = select2Container.querySelector('.select2-selection');
          if (select2Selection) {
            select2Selection.setAttribute('aria-disabled', 'true');
            select2Selection.setAttribute('tabindex', '-1');
            
            var searchInput = select2Selection.querySelector('.select2-search__field');
            if (searchInput) {
              searchInput.disabled = true;
              searchInput.setAttribute('tabindex', '-1');
            }
          }
          
          if (typeof $ !== 'undefined' && $.fn.select2) {
            try {
              $(select).select2('enable', false);
            } catch(e) {
              // Игнорируем ошибки
            }
          }
        }
      });
    });
    
    observerFieldObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
    
    // Отключаем наблюдатель через 30 секунд
    setTimeout(function() {
      observerFieldObserver.disconnect();
    }, 30000);
  }
}

// Функция для скрытия RSS ленты и элементов RSS
function hideRSSFeed() {
  // Скрываем RSS ленту через различные селекторы
  // Прячем только саму ссылку RSS, не весь пункт меню
  $('.nav-item a[title="RSS лента"]').each(function() {
    $(this).css({
      display: 'none',
      visibility: 'hidden',
      opacity: 0,
      height: 0,
      overflow: 'hidden',
      'pointer-events': 'none'
    });
  });
  $('.nav-item a[data-bs-original-title="RSS лента"]').each(function() {
    $(this).css({
      display: 'none',
      visibility: 'hidden',
      opacity: 0,
      height: 0,
      overflow: 'hidden',
      'pointer-events': 'none'
    });
  });
  
  // Скрываем "Личные каналы RSS" из dropdown меню
  $('.dropdown-item[href*="rssfeed.php"]').hide();
  $('.dropdown-item[title="Личные каналы RSS"]').hide();
  $('a.dropdown-item[href*="rssfeed.php"]').hide();
  $('a.dropdown-item[title="Личные каналы RSS"]').hide();
  $('.dropdown-item:has(i.ti-rss)').hide();
  $('.dropdown-item:has(.ti-rss)').hide();
  
  // Также добавляем CSS для надежности
  if ($('#custom-helpdesk-specialist-rss-hide').length === 0) {
    $('<style id="custom-helpdesk-specialist-rss-hide">')
      .text(`
        a[title="RSS лента"],
        a[data-bs-original-title="RSS лента"] {
          display: none !important;
          visibility: hidden !important;
          opacity: 0 !important;
          height: 0 !important;
          overflow: hidden !important;
          pointer-events: none !important;
        }
        .dropdown-item[href*="rssfeed.php"],
        .dropdown-item[title="Личные каналы RSS"],
        a.dropdown-item[href*="rssfeed.php"],
        a.dropdown-item[title="Личные каналы RSS"],
        .dropdown-item:has(i.ti-rss),
        .dropdown-item:has(.ti-rss) {
          display: none !important;
          visibility: hidden !important;
          opacity: 0 !important;
          height: 0 !important;
          overflow: hidden !important;
          pointer-events: none !important;
        }
      `)
      .appendTo('head');
  }
}

// Применяем скрытие сразу, без ожидания проверки профиля (как в custom.users.js)
// Это устраняет мелькание - стили применяются мгновенно
(function() {
  // Функция для моментального скрытия RSS элементов
  function hideRSSImmediately() {
    // Используем нативный JavaScript для максимальной скорости
    var rssLinks = document.querySelectorAll('.nav-item a[title="RSS лента"], .nav-item a[data-bs-original-title="RSS лента"]');
    for (var i = 0; i < rssLinks.length; i++) {
      var link = rssLinks[i];
      // Скрываем только саму ссылку RSS, не весь пункт меню
      link.style.setProperty('display', 'none', 'important');
      link.style.setProperty('visibility', 'hidden', 'important');
      link.style.setProperty('opacity', '0', 'important');
      link.style.setProperty('height', '0', 'important');
      link.style.setProperty('overflow', 'hidden', 'important');
      link.style.setProperty('pointer-events', 'none', 'important');
    }
    
    var dropdownItems = document.querySelectorAll('.dropdown-item[href*="rssfeed.php"], .dropdown-item[title="Личные каналы RSS"]');
    for (var j = 0; j < dropdownItems.length; j++) {
      var item = dropdownItems[j];
      item.style.setProperty('display', 'none', 'important');
      item.style.setProperty('visibility', 'hidden', 'important');
      item.style.setProperty('opacity', '0', 'important');
      item.style.setProperty('height', '0', 'important');
      item.style.setProperty('overflow', 'hidden', 'important');
      item.style.setProperty('pointer-events', 'none', 'important');
    }
  }
  
  // Применяем сразу, если DOM уже загружен
  if (document.body) {
    hideRSSImmediately();
  }
  
  // Применяем при загрузке DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hideRSSImmediately);
  } else {
    // DOM уже загружен
    hideRSSImmediately();
  }
  
  // MutationObserver для моментального скрытия при появлении элементов
  if (document.body) {
    var immediateObserver = new MutationObserver(function() {
      hideRSSImmediately();
    });
    
    immediateObserver.observe(document.body, {
      childList: true,
      subtree: true
    });
    
    // Отключаем через 10 секунд
    setTimeout(function() {
      immediateObserver.disconnect();
    }, 10000);
  }
})();

// Основная логика при загрузке страницы (для дополнительной проверки и других функций)
$(document).ready(function() {
  // Проверяем профиль специалиста (для других функций, если понадобятся)
  checkSpecialistProfile(function(allowed) {
    if (!allowed) {
      // Профиль не разрешен, не применяем дополнительный функционал
      return;
    }
    
    // Профиль специалиста разрешен - применяем дополнительные изменения через jQuery
    hideRSSFeed();
    
    // Отключаем поле "Наблюдатель" для специалистов
    disableObserverField();
    
    // Также применяем отключение с задержкой на случай динамической загрузки
    setTimeout(disableObserverField, 500);
    setTimeout(disableObserverField, 1000);
    setTimeout(disableObserverField, 2000);
  });
});

