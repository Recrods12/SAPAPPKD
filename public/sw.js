const CACHE = "sapa-ppkd-shell-v3";
const OFFLINE = "/offline.html";
self.addEventListener("install", (event) =>
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) =>
                cache.addAll([
                    OFFLINE,
                    "/pwa/manifest.webmanifest",
                    "/pwa/icon/192",
                    "/pwa/icon/512",
                ]),
            )
            .then(() => self.skipWaiting()),
    ),
);
self.addEventListener("activate", (event) =>
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== CACHE)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    ),
);
self.addEventListener("fetch", (event) => {
    if (event.request.method !== "GET" || event.request.mode !== "navigate")
        return;
    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
});
