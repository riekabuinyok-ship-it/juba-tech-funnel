/* ============================================
   MAIN.JS - Juba Tech Solution
   Clean, ES5-compatible build
   ============================================ */

(function() {
  'use strict';

  /* ----------------------------------------
     1. MOBILE DRAWER
     ---------------------------------------- */
  var toggle = document.querySelector('.mobile-toggle');
  var drawer = document.querySelector('.mobile-drawer');
  var overlay = document.querySelector('.mobile-drawer-overlay');
  var closeBtn = document.querySelector('.mobile-close');

  if (toggle && drawer) {
    var openDrawer = function() {
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      document.body.classList.add('menu-open');
      toggle.setAttribute('aria-expanded', 'true');
    };
    var closeDrawer = function() {
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('menu-open');
      toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', function() {
      if (drawer.classList.contains('is-open')) closeDrawer();
      else openDrawer();
    });
    if (overlay) overlay.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
        closeDrawer();
      }
    });

    var drawerLinks = drawer.querySelectorAll('a');
    for (var i = 0; i < drawerLinks.length; i++) {
      drawerLinks[i].addEventListener('click', function() {
        setTimeout(closeDrawer, 50);
      });
    }
  }

  /* ----------------------------------------
     2. TOAST NOTIFICATIONS
     ---------------------------------------- */
  var toastContainer = document.querySelector('.toast-container');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    toastContainer.setAttribute('aria-live', 'polite');
    toastContainer.setAttribute('aria-atomic', 'true');
    document.body.appendChild(toastContainer);
  }

  var TOAST_ICONS = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
    error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
    info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
  };

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function showToast(message, options) {
    options = options || {};
    var type = options.type || 'info';
    var title = options.title || '';
    var duration = typeof options.duration === 'number' ? options.duration : 4000;

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.setAttribute('role', 'status');

    var iconHtml = TOAST_ICONS[type] || TOAST_ICONS.info;

    toast.innerHTML =
      '<div class="toast-icon">' + iconHtml + '</div>' +
      '<div class="toast-body">' +
        (title ? '<div class="toast-title">' + escapeHtml(title) + '</div>' : '') +
        '<div class="toast-message">' + escapeHtml(message) + '</div>' +
      '</div>' +
      '<button type="button" class="toast-close" aria-label="Close">x</button>' +
      '<div class="toast-progress" style="animation-duration:' + duration + 'ms;"></div>';

    toastContainer.appendChild(toast);

    window.requestAnimationFrame(function() {
      window.requestAnimationFrame(function() {
        toast.classList.add('is-visible');
      });
    });

    var timeoutId = null;
    function dismiss() {
      if (timeoutId) clearTimeout(timeoutId);
      toast.classList.remove('is-visible');
      toast.classList.add('is-leaving');
      setTimeout(function() { toast.remove(); }, 300);
    }

    var closeX = toast.querySelector('.toast-close');
    if (closeX) closeX.addEventListener('click', dismiss);

    if (duration > 0) {
      timeoutId = setTimeout(dismiss, duration);
    }

    return { dismiss: dismiss };
  }

  window.showToast = showToast;

  /* ----------------------------------------
     3. PREVIEW MODAL
     ---------------------------------------- */
  var previewModal = document.getElementById('previewModal');
  if (previewModal) {
    var iframe = document.getElementById('previewIframe');
    var titleEl = previewModal.querySelector('.preview-modal-title');
    var openBtns = document.querySelectorAll('[data-preview]');
    var closeEls = previewModal.querySelectorAll('[data-preview-close]');

    var toEmbedUrl = function(url) {
      var id = null;
      var m = url.match(/\/d\/([a-zA-Z0-9_-]+)/);
      if (m) { id = m[1]; }
      else {
        m = url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
        if (m) id = m[1];
      }
      if (id) return 'https://drive.google.com/file/d/' + id + '/preview';
      return url;
    };

    var openModal = function(url, title) {
      if (titleEl && title) titleEl.textContent = 'Preview: ' + title;
      if (iframe) iframe.src = toEmbedUrl(url);
      previewModal.classList.add('is-open');
      previewModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('menu-open');
    };

    var closeModal = function() {
      previewModal.classList.remove('is-open');
      previewModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('menu-open');
      setTimeout(function() { if (iframe) iframe.src = ''; }, 200);
    };

    for (var p = 0; p < openBtns.length; p++) {
      (function(btn) {
        btn.addEventListener('click', function() {
          openModal(btn.getAttribute('data-preview'), btn.getAttribute('data-title'));
        });
      })(openBtns[p]);
    }

    for (var c = 0; c < closeEls.length; c++) {
      closeEls[c].addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && previewModal.classList.contains('is-open')) {
        closeModal();
      }
    });
  }

  /* ----------------------------------------
     4. ALL BOOKS VIEW TOGGLE
     ---------------------------------------- */
  var resultsGrid = document.getElementById('resultsGrid');
  if (resultsGrid) {
    var viewBtns = document.querySelectorAll('.view-btn');
    var savedView = localStorage.getItem('booksView') || 'grid';

    var applyView = function(view) {
      if (view === 'list') resultsGrid.classList.add('list-view');
      else resultsGrid.classList.remove('list-view');

      for (var i = 0; i < viewBtns.length; i++) {
        var isMatch = viewBtns[i].getAttribute('data-view') === view;
        if (isMatch) viewBtns[i].classList.add('active');
        else viewBtns[i].classList.remove('active');
      }
    };

    applyView(savedView);

    for (var v = 0; v < viewBtns.length; v++) {
      (function(btn) {
        btn.addEventListener('click', function() {
          var newView = btn.getAttribute('data-view');
          applyView(newView);
          localStorage.setItem('booksView', newView);
        });
      })(viewBtns[v]);
    }
  }

  /* ----------------------------------------
     5. WHATSAPP WIDGET
     ---------------------------------------- */
  var waWidget = document.getElementById('whatsappWidget');
  if (waWidget) {
    var waTrigger = document.getElementById('waTrigger');
    var waPanel = document.getElementById('waPanel');
    var waClose = document.getElementById('waClose');
    var waBadge = document.getElementById('waBadge');

    if (waTrigger && waPanel) {
      var openPanel = function() {
        waPanel.classList.add('is-open');
        waPanel.setAttribute('aria-hidden', 'false');
        waTrigger.classList.add('is-open');
        waTrigger.setAttribute('aria-expanded', 'true');
        if (waBadge) waBadge.hidden = true;
      };

      var closePanel = function() {
        waPanel.classList.remove('is-open');
        waPanel.setAttribute('aria-hidden', 'true');
        waTrigger.classList.remove('is-open');
        waTrigger.setAttribute('aria-expanded', 'false');
      };

      waTrigger.addEventListener('click', function() {
        if (waPanel.classList.contains('is-open')) closePanel();
        else openPanel();
      });

      if (waClose) waClose.addEventListener('click', closePanel);

      document.addEventListener('click', function(e) {
        if (!waWidget.contains(e.target) && waPanel.classList.contains('is-open')) {
          closePanel();
        }
      });

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && waPanel.classList.contains('is-open')) {
          closePanel();
        }
      });

      var waLinks = waPanel.querySelectorAll('a');
      for (var w = 0; w < waLinks.length; w++) {
        waLinks[w].addEventListener('click', function() {
          setTimeout(closePanel, 200);
        });
      }

      if (waBadge) {
        setTimeout(function() {
          if (!waPanel.classList.contains('is-open')) {
            waBadge.hidden = false;
          }
        }, 5000);
      }
    }
  }

  /* ----------------------------------------
     6. SERVER FLASH MESSAGES
     ---------------------------------------- */
  document.addEventListener('DOMContentLoaded', function() {
    if (window.__flashMessages && Array.isArray(window.__flashMessages)) {
      window.__flashMessages.forEach(function(f, i) {
        setTimeout(function() {
          showToast(f.message, {
            type: f.type || 'info',
            title: f.title || '',
            duration: 4500
          });
        }, i * 250);
      });
    }
  });

  /* ----------------------------------------
     7. WELCOME BAR
     ---------------------------------------- */
  var welcomeBar = document.getElementById('welcomeBar');
  var welcomeClose = document.getElementById('welcomeBarClose');
  if (welcomeBar && welcomeClose) {
    welcomeClose.addEventListener('click', function() {
      welcomeBar.classList.add('is-dismissing');
      var expires = new Date();
      expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
      document.cookie = 'welcome_bar_dismissed=1; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
      setTimeout(function() {
        welcomeBar.remove();
      }, 320);
    });
  }

  /* ----------------------------------------
     8. EXIT-INTENT MODAL (aggressive)
     ---------------------------------------- */
  var exitModal = document.getElementById('exitModal');
  if (exitModal) {
    var isDesktop = window.matchMedia('(min-width: 900px)').matches;
    var hasCookie = function(name) {
      return document.cookie.split(';').some(function(c) {
        return c.trim().indexOf(name + '=') === 0;
      });
    };
    if (isDesktop && !hasCookie('exit_shown')) {
      var armed = false;
      var shown = false;
      setTimeout(function() { armed = true; }, 5000);
      var openExitModal = function() {
        if (shown) return;
        if (!armed) return;
        if (exitModal.classList.contains('is-open')) return;
        shown = true;
        exitModal.classList.add('is-open');
        exitModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var expires = new Date();
        expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
        document.cookie = 'exit_shown=1; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
      };
      var closeExitModal = function() {
        exitModal.classList.remove('is-open');
        exitModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      };
      document.addEventListener('mouseout', function(e) {
        if (!e.relatedTarget && !e.toElement) {
          if (e.clientY <= 0 || e.clientX <= 0) openExitModal();
        }
      });
      document.addEventListener('mouseleave', function(e) {
        if (e.clientY < 5 || e.clientX < 5) openExitModal();
      });
      try {
        history.pushState({ exitGuard: true }, '', window.location.href);
        window.addEventListener('popstate', function() {
          if (!shown) {
            history.pushState({ exitGuard: true }, '', window.location.href);
            openExitModal();
          }
        });
      } catch (err) {}
      document.addEventListener('keydown', function(e) {
        var isClose = (e.ctrlKey || e.metaKey) && (e.key === 'w' || e.key === 'W');
        var isBack = e.altKey && e.key === 'ArrowLeft';
        if (isClose || isBack) {
          openExitModal();
        }
        if (e.key === 'Escape' && exitModal.classList.contains('is-open')) {
          closeExitModal();
        }
      });
      document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden' && !shown && armed) {
          try { sessionStorage.setItem('exit_pending', '1'); } catch (err) {}
        }
        if (document.visibilityState === 'visible' && !shown) {
          try {
            if (sessionStorage.getItem('exit_pending') === '1') {
              sessionStorage.removeItem('exit_pending');
              openExitModal();
            }
          } catch (err) {}
        }
      });
      setTimeout(function() {
        if (!shown) openExitModal();
      }, 45000);
      var closeEls = exitModal.querySelectorAll('[data-exit-close]');
      for (var i = 0; i < closeEls.length; i++) {
        closeEls[i].addEventListener('click', closeExitModal);
      }
      var copyBtn = exitModal.querySelector('.exit-copy-btn');
      if (copyBtn) {
        copyBtn.addEventListener('click', function() {
          var code = copyBtn.getAttribute('data-copy');
          var done = function() {
            if (window.showToast) {
              window.showToast('Coupon code copied: ' + code, { type: 'success', duration: 2500 });
            }
          };
          if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(done).catch(done);
          } else {
            var tmp = document.createElement('input');
            tmp.value = code;
            document.body.appendChild(tmp);
            tmp.select();
            try { document.execCommand('copy'); } catch (err) {}
            document.body.removeChild(tmp);
            done();
          }
        });
      }
    }
  }

  /* ----------------------------------------
     9. REVIEW PROMPT
     ---------------------------------------- */
  var reviewPrompt = document.getElementById('reviewPrompt');
  if (reviewPrompt) {
    setTimeout(function() {
      reviewPrompt.classList.add('is-open');
      reviewPrompt.setAttribute('aria-hidden', 'false');
    }, 6000);
    var promptId = reviewPrompt.getAttribute('data-prompt-id');
    var closePrompt = function() {
      reviewPrompt.classList.remove('is-open');
      reviewPrompt.setAttribute('aria-hidden', 'true');
    };
    var closeEls = reviewPrompt.querySelectorAll('[data-review-close]');
    for (var i = 0; i < closeEls.length; i++) {
      closeEls[i].addEventListener('click', closePrompt);
    }
    var snoozeBtn = reviewPrompt.querySelector('[data-review-snooze]');
    if (snoozeBtn) {
      snoozeBtn.addEventListener('click', function() {
        var fd = new FormData();
        fd.append('action', 'snooze');
        fd.append('prompt_id', promptId);
        fetch('review-prompt-action.php', { method: 'POST', body: fd })
          .then(function() {
            closePrompt();
            if (window.showToast) {
              window.showToast('We will remind you in 7 days.', { type: 'info', duration: 3000 });
            }
          })
          .catch(function() { closePrompt(); });
      });
    }
    var dismissBtn = reviewPrompt.querySelector('[data-review-dismiss]');
    if (dismissBtn) {
      dismissBtn.addEventListener('click', function() {
        var fd = new FormData();
        fd.append('action', 'dismiss');
        fd.append('prompt_id', promptId);
        fetch('review-prompt-action.php', { method: 'POST', body: fd })
          .then(function() {
            closePrompt();
            if (window.showToast) {
              window.showToast('Got it. We will not ask again.', { type: 'success', duration: 3000 });
            }
          })
          .catch(function() { closePrompt(); });
      });
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && reviewPrompt.classList.contains('is-open')) {
        closePrompt();
      }
    });
  }

  /* ----------------------------------------
     10. MOBILE FILTERS DRAWER
     ---------------------------------------- */
  var filtersToggle = document.getElementById('filtersToggle');
  var filtersSide = document.getElementById('filtersSide');
  var filtersClose = document.getElementById('filtersClose');
  var filtersOverlay = document.getElementById('filtersOverlay');
  var filtersToggleCount = document.getElementById('filtersToggleCount');
  if (filtersToggle && filtersSide) {
    var openFilters = function() {
      filtersSide.classList.add('is-open');
      if (filtersOverlay) {
        filtersOverlay.hidden = false;
        requestAnimationFrame(function() {
          filtersOverlay.classList.add('is-open');
        });
      }
      filtersToggle.classList.add('is-open');
      filtersToggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    };
    var closeFilters = function() {
      filtersSide.classList.remove('is-open');
      if (filtersOverlay) {
        filtersOverlay.classList.remove('is-open');
        setTimeout(function() { filtersOverlay.hidden = true; }, 250);
      }
      filtersToggle.classList.remove('is-open');
      filtersToggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    };
    filtersToggle.addEventListener('click', function() {
      if (filtersSide.classList.contains('is-open')) closeFilters();
      else openFilters();
    });
    if (filtersClose) filtersClose.addEventListener('click', closeFilters);
    if (filtersOverlay) filtersOverlay.addEventListener('click', closeFilters);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && filtersSide.classList.contains('is-open')) {
        closeFilters();
      }
    });
    if (filtersToggleCount) {
      var activeCount = 0;
      var params = new URLSearchParams(window.location.search);
      ['q', 'level', 'type', 'subject'].forEach(function(key) {
        if (params.get(key)) activeCount++;
      });
      if (activeCount > 0) {
        filtersToggleCount.textContent = activeCount;
        filtersToggleCount.hidden = false;
      }
    // Close drawer when a filter link inside is clicked
    if (filtersSide) {
      var filterLinks = filtersSide.querySelectorAll('a.filter-chip, a.subject-filter, a.clear-filters');
      for (var i = 0; i < filterLinks.length; i++) {
        filterLinks[i].addEventListener('click', function() {
          closeFilters();
        });
      }
    }
    }
  }

  /* ----------------------------------------
     11. NOTIFICATION BELL
     ---------------------------------------- */
  var notifBell = document.getElementById('notifBell');
  var notifPanel = document.getElementById('notifPanel');
  var notifWrap = document.getElementById('notifBellWrap');
  var notifBody = document.getElementById('notifPanelBody');
  if (notifBell && notifPanel) {
    var loadedOnce = false;
    var loadNotifications = function() {
      if (loadedOnce) return;
      loadedOnce = true;
      fetch('notifications-fetch.php', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.ok) return;
          if (data.items.length === 0) {
            notifBody.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
            return;
          }
          var html = '';
          data.items.forEach(function(item) {
            var cls = item.is_read ? '' : 'is-unread';
            html +=
              '<a class="notif-item ' + cls + '" href="' + (item.link || 'notifications.php') + '">' +
                '<div class="notif-item-icon">' + item.icon + '</div>' +
                '<div class="notif-item-body">' +
                  '<strong>' + escapeHtml(item.title) + '</strong>' +
                  '<p>' + escapeHtml(item.message) + '</p>' +
                  '<span class="notif-item-time">' + escapeHtml(item.created_at) + '</span>' +
                '</div>' +
              '</a>';
          });
          notifBody.innerHTML = html;
        })
        .catch(function() {
          notifBody.innerHTML = '<div class="notif-empty">Could not load notifications.</div>';
        });
    };
    var openNotif = function() {
      notifPanel.classList.add('is-open');
      notifPanel.setAttribute('aria-hidden', 'false');
      loadNotifications();
    };
    var closeNotif = function() {
      notifPanel.classList.remove('is-open');
      notifPanel.setAttribute('aria-hidden', 'true');
    };
    notifBell.addEventListener('click', function(e) {
      e.stopPropagation();
      if (notifPanel.classList.contains('is-open')) closeNotif();
      else openNotif();
    });
    document.addEventListener('click', function(e) {
      if (notifWrap && !notifWrap.contains(e.target)) {
        closeNotif();
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeNotif();
    });
  }

  /* ----------------------------------------
     12. ADMIN MOBILE DRAWER
     ---------------------------------------- */
  var adminToggle = document.getElementById('adminMobileToggle');
  var adminDrawer = document.getElementById('adminMobileDrawer');
  if (adminToggle && adminDrawer) {
    var closeEls = adminDrawer.querySelectorAll('[data-admin-close]');
    var openAdmin = function() {
      adminDrawer.classList.add('is-open');
      adminDrawer.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };
    var closeAdmin = function() {
      adminDrawer.classList.remove('is-open');
      adminDrawer.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };
    adminToggle.addEventListener('click', openAdmin);
    for (var i = 0; i < closeEls.length; i++) {
      closeEls[i].addEventListener('click', closeAdmin);
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && adminDrawer.classList.contains('is-open')) {
        closeAdmin();
      }
    });
  }

})();
