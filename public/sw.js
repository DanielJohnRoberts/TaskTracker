self.addEventListener('push', (event) => {
    let data = {};

    if (event.data) {
        try {
            data = event.data.json();
        } catch (error) {
            data = {
                title: 'Task nudge',
                body: event.data.text(),
            };
        }
    }

    const title = data.title || 'Task nudge';
    const options = {
        body: data.body || 'A task needs your attention.',
        icon: '/icon.svg',
        badge: '/icon.svg',
        tag: data.tag || 'top3-task',
        renotify: false,
        data: {
            url: data.url || '/dashboard',
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data?.url || '/dashboard';

    event.waitUntil((async () => {
        const windowClients = await clients.matchAll({
            type: 'window',
            includeUncontrolled: true,
        });

        for (const client of windowClients) {
            if ('focus' in client) {
                await client.focus();
                client.navigate(targetUrl);
                return;
            }
        }

        if (clients.openWindow) {
            await clients.openWindow(targetUrl);
        }
    })());
});
