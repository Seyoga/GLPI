/**
 * Отображение блока оценки по заявке на ticket.form.php.
 * Подключается только для админов (и других не-пользовательских профилей) на странице заявки,
 * чтобы видеть оценку, которую поставил пользователь, без загрузки интерфейса пользователей.
 */
(function() {
  var PLUGIN_PATH = (typeof window.CUSTOMHELPDESK_PLUGIN_PATH !== 'undefined')
    ? window.CUSTOMHELPDESK_PLUGIN_PATH
    : '/glpi/plugins/customhelpdesk';

  function onTicketForm() {
    return typeof window.location !== 'undefined' &&
      (window.location.pathname || '').indexOf('/front/ticket.form.php') !== -1;
  }

  if (!onTicketForm()) {
    return;
  }

  var ratingDisplayInProgress = false;

  function displayTicketRating() {
    if (document.getElementById('custom-ticket-rating-display') || ratingDisplayInProgress) {
      return;
    }
    var params = new URLSearchParams(window.location.search);
    var ticketId = params.get('id');
    if (!ticketId) {
      return;
    }
    ratingDisplayInProgress = true;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', PLUGIN_PATH + '/front/ajax.get_rating.php?ticket_id=' + encodeURIComponent(ticketId), true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) {
        return;
      }
      ratingDisplayInProgress = false;
      if (document.getElementById('custom-ticket-rating-display')) {
        return;
      }
      var response;
      try {
        response = JSON.parse(xhr.responseText || '{}');
      } catch (e) {
        return;
      }
      if (!response.has_rating || !response.rating) {
        return;
      }
      var emojis = ['', '😞', '😐', '🙂', '😊', '😍'];
      var emoji = emojis[response.rating] || '⭐';
      var ratingHtml =
        '<div id="custom-ticket-rating-display" class="accordion-item">' +
        '<h2 class="accordion-header" id="heading-rating-item">' +
        '<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#item-rating" aria-expanded="true" aria-controls="ticket-rating">' +
        '<i class="ti ti-star me-1 item-icon"></i><span class="item-title">Оценка выполнения задачи</span></button></h2>' +
        '<div id="item-rating" class="accordion-collapse collapse show" aria-labelledby="heading-rating-item">' +
        '<div class="accordion-body row m-0 mt-n2">' +
        '<div class="form-field row col-12 mb-2">' +
        '<div class="col-12 text-center">' +
        '<div style="font-size: 48px; margin-bottom: 10px;">' + emoji + '</div>' +
        '<div style="font-size: 16px; color: #343a40; font-weight: 500;">' + response.rating + ' из 5</div>' +
        '</div></div></div></div></div>';

      var rightSide = document.querySelector('.itil-right-side');
      var firstAccordion = rightSide ? rightSide.querySelector('.accordion-item') : null;
      if (rightSide && firstAccordion) {
        firstAccordion.insertAdjacentHTML('afterend', ratingHtml);
      } else if (rightSide) {
        var form = rightSide.querySelector('form');
        if (form) {
          form.insertAdjacentHTML('afterend', ratingHtml);
        } else {
          rightSide.insertAdjacentHTML('afterbegin', ratingHtml);
        }
      } else {
        var cardFooter = document.querySelector('.card-footer');
        if (cardFooter) {
          cardFooter.insertAdjacentHTML('beforebegin', ratingHtml);
        } else {
          var form = document.querySelector('form');
          if (form) {
            form.insertAdjacentHTML('afterend', ratingHtml);
          }
        }
      }
    };
    xhr.send();
  }

  function runWhenReady() {
    if (document.querySelector('.itil-right-side') && document.getElementById('item-main') &&
        !document.getElementById('custom-ticket-rating-display')) {
      displayTicketRating();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(runWhenReady, 500);
      setTimeout(runWhenReady, 1000);
      setTimeout(runWhenReady, 2000);
    });
  } else {
    setTimeout(runWhenReady, 500);
    setTimeout(runWhenReady, 1000);
    setTimeout(runWhenReady, 2000);
  }

  var observer = new MutationObserver(function() {
    runWhenReady();
  });
  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
    setTimeout(function() {
      observer.disconnect();
    }, 15000);
  }
})();
