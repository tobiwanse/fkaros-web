/**
 * Tandem view client.
 * Polls /skywin-hub/v1/tandem and replaces the section body with rendered HTML.
 * Reacts to changes in `data-skyview-date` on the SkyView root.
 */
(function () {
  'use strict';

  function mount(root) {
    const endpoint = root.getAttribute('data-tandem-endpoint') || '';
    if (!endpoint) return;

    const section = root.querySelector('.tandem-section');
    const body    = root.querySelector('.tandem-section__body');
    if (!section || !body) return;

    const SETTINGS_KEY    = 'skyview_settings';
    const fallbackRefresh = Math.max(15, parseInt(root.getAttribute('data-skyview-refresh') || '30', 10) || 30);
    const LEAVE_MS        = 400;
    let currentDate  = root.getAttribute('data-skyview-date') || '';
    let inFlight     = false;
    let timer        = null;

    function getRefreshSec() {
      try {
        const raw = localStorage.getItem(SETTINGS_KEY);
        if (raw) {
          const saved = JSON.parse(raw);
          if (saved && saved.autoRefreshEnabled === false) return 0;
          const n = Number(saved && saved.refreshIntervalSeconds);
          if (Number.isFinite(n) && n >= 0) {
            return n === 0 ? 0 : Math.max(5, n);
          }
        }
      } catch (_) { /* ignore */ }
      return fallbackRefresh;
    }

    function getLoadKeys(container) {
      return Array.from(container.querySelectorAll('.tandem-load[data-load-number]'))
        .map((el) => el.getAttribute('data-load-number'));
    }

    function markEntering(container) {
      container.querySelectorAll('.tandem-load').forEach((el, idx) => {
        el.style.setProperty('--card-index', String(idx));
        el.classList.add('tandem-load--animate');
      });
    }

    function applyHtml(html, forceFullAnimate) {
      // Parse incoming HTML in a detached container.
      const tmp = document.createElement('div');
      tmp.innerHTML = html;

      // If we're showing loading (date change) or have no current loads, swap immediately with entry animation.
      const oldKeys = getLoadKeys(body);
      const newKeys = getLoadKeys(tmp);
      const removed = body.querySelectorAll('.tandem-load[data-load-number]');
      const removedList = Array.from(removed).filter(
        (el) => newKeys.indexOf(el.getAttribute('data-load-number')) === -1
      );

      const swap = () => {
        body.innerHTML = html;
        if (forceFullAnimate) {
          markEntering(body);
        } else {
          // Mark only loads that weren't present before as entering.
          body.querySelectorAll('.tandem-load[data-load-number]').forEach((el, idx) => {
            const key = el.getAttribute('data-load-number');
            if (oldKeys.indexOf(key) === -1) {
              el.style.setProperty('--card-index', String(idx));
              el.classList.add('tandem-load--animate');
            }
          });
        }
      };

      if (forceFullAnimate || removedList.length === 0) {
        swap();
        return;
      }

      // Animate removed loads out, then swap.
      removedList.forEach((el) => {
        // Lock current height for a smooth collapse.
        el.style.setProperty('--tandem-load-h', el.offsetHeight + 'px');
        el.classList.add('tandem-load--leaving');
      });
      setTimeout(swap, LEAVE_MS);
    }

    async function fetchAndRender(showLoading) {
      if (inFlight) return;
      inFlight = true;
      if (showLoading) section.classList.add('is-loading');

      try {
        const url = new URL(endpoint, window.location.origin);
        if (currentDate) url.searchParams.set('date', currentDate);

        const res = await fetch(url.toString(), {
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);

        const data = await res.json();
        if (data && typeof data.html === 'string') {
          applyHtml(data.html, !!showLoading);
          section.dataset.tandemDate = data.date || currentDate;
        }
      } catch (err) {
        // Keep previous render on error; surface a small notice once.
        // eslint-disable-next-line no-console
        console.warn('Tandem fetch failed:', err);
      } finally {
        inFlight = false;
        section.classList.remove('is-loading');
      }
    }

    function startTimer() {
      stopTimer();
      const sec = getRefreshSec();
      if (sec <= 0) return; // Auto-refresh disabled in SkyView settings.
      timer = setTimeout(async () => {
        await fetchAndRender(false);
        startTimer(); // Re-read interval each cycle so settings changes apply.
      }, sec * 1000);
    }

    function stopTimer() {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
    }

    // React to date changes pushed by the main SkyView script.
    const observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === 'attributes' && m.attributeName === 'data-skyview-date') {
          const next = root.getAttribute('data-skyview-date') || '';
          if (next !== currentDate) {
            currentDate = next;
            fetchAndRender(true);
          }
        }
      }
    });
    observer.observe(root, { attributes: true, attributeFilter: ['data-skyview-date'] });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopTimer();
      } else {
        fetchAndRender(false);
        startTimer();
      }
    });

    // React to SkyView settings changes from other tabs.
    window.addEventListener('storage', (e) => {
      if (e.key === SETTINGS_KEY) {
        startTimer();
      }
    });

    // Initial render is already in the DOM (server-side). Just start polling.
    startTimer();
  }

  document.querySelectorAll('[data-tandem-endpoint]').forEach(mount);
})();
