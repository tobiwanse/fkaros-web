/* SkyView Service Worker — push notifications */
console.log('SkyView Service Worker registered');
const SKYVIEW_NOTIFICATION_ICON = '/app/plugins/skywin-hub/assets/img/icon-192.png';
const SKYVIEW_NOTIFICATION_BADGE = '/app/plugins/skywin-hub/assets/img/icon-192.png';

function stripPushMessagePrefix(text) {
  return String(text || '').replace(/^\[(alert|warning|info)\]\s*[,:\-]?\s*/i, '').trim();
}

function normalizePushNotificationBody(text) {
  // Split on newlines, semicolons, and before [alert|warning|info] tags.
  return String(text || '')
    .split(/\n|;|(?=\[(alert|warning|info)\])/i)
    .map((part) => stripPushMessagePrefix(part))
    .filter(Boolean)
    .join('\n');
}

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let title = 'SkyView';
  let body = 'Ny lift tillagd!';
  let tag = 'skyview-push';
  let data = {};

  if (event.data) {
    try {
      const payload = event.data.json();
      title = payload.title || title;
      body = normalizePushNotificationBody(payload.body || body) || body;
      tag = payload.tag || tag;
      data = payload.data || data;
    } catch (_) {
      body = normalizePushNotificationBody(event.data.text() || body) || body;
    }
  }

  body = normalizePushNotificationBody(body) || body;

  event.waitUntil(
    (() => {
      const notificationOpts = {
        body,
        tag,
        icon: SKYVIEW_NOTIFICATION_ICON,
        badge: SKYVIEW_NOTIFICATION_BADGE,
        renotify: true,
        vibrate: [100, 50, 100],
        data,
      };
      const fallbackOpts = {
        ...notificationOpts,
        icon: undefined,
        badge: undefined,
      };

      return self.registration
        .showNotification(title, notificationOpts)
        .catch(() => self.registration.showNotification(title, fallbackOpts));
    })()
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      for (const client of clients) {
        if ('focus' in client) {
          return client.focus();
        }
      }
      return self.clients.openWindow('/');
    })
  );
});


