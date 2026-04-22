/* SkyView Service Worker — push notifications */
console.log('SkyView Service Worker registered');
const SKYVIEW_NOTIFICATION_ICON = '/app/plugins/skywin-hub/assets/img/icon-192.png';
const SKYVIEW_NOTIFICATION_BADGE = '/app/plugins/skywin-hub/assets/img/icon-192.png';

function stripPushMessagePrefix(text) {
  return String(text || '').replace(/^\[(alert|warning|info)\]\s*[,:\-]?\s*/i, '').trim();
}

function splitPushNotificationBodies(text) {
  const parts = String(text || '')
    .split(';')
    .map((part) => stripPushMessagePrefix(part))
    .filter(Boolean);

  return parts.length > 0 ? parts : [stripPushMessagePrefix(text) || String(text || '').trim()].filter(Boolean);
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
      body = stripPushMessagePrefix(payload.body || body) || body;
      tag = payload.tag || tag;
      data = payload.data || data;
    } catch (_) {
      body = stripPushMessagePrefix(event.data.text() || body) || body;
    }
  }

  const bodies = splitPushNotificationBodies(body);

  event.waitUntil(
    Promise.all(bodies.map((bodyPart, index) => {
      const notificationOpts = {
        body: bodyPart,
        tag: bodies.length > 1 ? `${tag}-${index}` : tag,
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
    }))
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


