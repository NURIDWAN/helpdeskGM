import { axiosInstance } from "@/plugins/axios";

const POLL_INTERVAL_MS = 30000;
let pollTimer = null;
let currentUserId = null;

const cursorKey = (userId) => `browser_notification_last_seen_${userId}`;

const isSupported = () =>
  typeof window !== "undefined" &&
  "Notification" in window &&
  "serviceWorker" in navigator &&
  "PushManager" in window;

const urlBase64ToUint8Array = (base64String) => {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; i += 1) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
};

const maxNotificationId = (items) =>
  items.reduce((max, item) => Math.max(max, Number(item.id || 0)), 0);

export const markBrowserNotificationsSeen = (user, notifications) => {
  if (!user?.id || !notifications?.length) return;

  const maxId = maxNotificationId(notifications);
  if (maxId > 0) {
    localStorage.setItem(cursorKey(user.id), String(maxId));
  }
};

export const browserNotificationsSupported = isSupported;

export const fetchBrowserNotifications = async (params = {}) => {
  const response = await axiosInstance.get("/browser-notifications", { params });
  return response.data.data || [];
};

const registerServiceWorker = async () => {
  if (!isSupported()) return null;
  return navigator.serviceWorker.register("/browser-notification-sw.js");
};

const subscribeForPush = async () => {
  const registration = await registerServiceWorker();
  if (!registration) {
    return { subscribed: false, reason: "service_worker_unavailable" };
  }

  const keyResponse = await axiosInstance.get("/browser-notifications/vapid-public-key");
  const publicKey = keyResponse.data.data?.public_key;
  if (!publicKey) {
    return { subscribed: false, reason: "missing_vapid_key" };
  }

  let subscription = await registration.pushManager.getSubscription();
  if (!subscription) {
    subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(publicKey),
    });
  }

  await axiosInstance.post("/browser-notifications/subscribe", subscription.toJSON());
  return { subscribed: true, reason: "subscribed" };
};

const showBrowserNotification = async (notification) => {
  if (!isSupported() || Notification.permission !== "granted") return;

  const options = {
    body: notification.body,
    tag: notification.tag || `activity-${notification.id}`,
    icon: "/favicon.ico",
    badge: "/favicon.ico",
    data: {
      url: notification.url || "/admin/dashboard",
    },
  };

  const registration = await navigator.serviceWorker.getRegistration();
  if (registration) {
    await registration.showNotification(notification.title || "Update Helpdesk", options);
    return;
  }

  const browserNotification = new Notification(notification.title || "Update Helpdesk", options);
  browserNotification.onclick = () => {
    window.focus();
    window.location.href = options.data.url;
  };
};

const pollBrowserNotifications = async (user, { notify = true } = {}) => {
  if (!user?.id) return [];

  const afterId = Number(localStorage.getItem(cursorKey(user.id)) || 0);
  const notifications = await fetchBrowserNotifications({
    after_id: afterId || undefined,
    limit: 20,
  });

  if (!notifications.length) return [];

  markBrowserNotificationsSeen(user, notifications);

  if (notify && Notification.permission === "granted") {
    for (const notification of notifications) {
      await showBrowserNotification(notification);
    }
  }

  return notifications;
};

export const startBrowserNotificationPolling = async (user) => {
  stopBrowserNotificationPolling();

  if (!isSupported() || !user?.id) return;
  currentUserId = user.id;

  if (!localStorage.getItem(cursorKey(user.id))) {
    const latest = await fetchBrowserNotifications({ limit: 1 });
    markBrowserNotificationsSeen(user, latest);
  }

  pollTimer = window.setInterval(() => {
    if (document.visibilityState === "visible" && Notification.permission === "granted") {
      pollBrowserNotifications(user).catch((error) => {
        console.error("Gagal mengambil browser notification", error);
      });
    }
  }, POLL_INTERVAL_MS);
};

export const stopBrowserNotificationPolling = () => {
  if (pollTimer) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
  currentUserId = null;
};

export const enableBrowserNotifications = async (user) => {
  if (!isSupported() || !user?.id) {
    return { enabled: false, reason: "unsupported" };
  }

  const permission = Notification.permission === "default"
    ? await Notification.requestPermission()
    : Notification.permission;

  if (permission !== "granted") {
    return { enabled: false, reason: permission };
  }

  const subscriptionResult = await subscribeForPush();
  if (currentUserId !== user.id || !pollTimer) {
    await startBrowserNotificationPolling(user);
  }

  return {
    enabled: Boolean(subscriptionResult.subscribed),
    ...subscriptionResult,
  };
};
