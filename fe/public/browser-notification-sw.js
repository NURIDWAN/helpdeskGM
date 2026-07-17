self.addEventListener("push", (event) => {
  let payload = {};

  try {
    payload = event.data ? event.data.json() : {};
  } catch (error) {
    payload = {
      title: "Update Helpdesk",
      body: event.data ? event.data.text() : "Ada update baru.",
      url: "/admin/dashboard",
    };
  }

  const title = payload.title || "Update Helpdesk";
  const options = {
    body: payload.body || "Ada update baru.",
    icon: "/favicon.ico",
    badge: "/favicon.ico",
    tag: payload.tag || `activity-${payload.id || Date.now()}`,
    data: {
      url: payload.url || "/admin/dashboard",
    },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();

  const targetUrl = new URL(event.notification.data?.url || "/admin/dashboard", self.location.origin).href;

  event.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ("focus" in client) {
          client.navigate(targetUrl);
          return client.focus();
        }
      }

      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl);
      }

      return undefined;
    }),
  );
});
