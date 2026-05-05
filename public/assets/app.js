(function () {
    const notifyButton = document.getElementById('enableNotifications');
    const reminderUrl = document.body.dataset.reminderUrl;
    const dashboardUrl = document.body.dataset.dashboardUrl || '/dashboard';
    const pushStoreUrl = document.body.dataset.pushStoreUrl;
    const vapidPublicKey = document.body.dataset.vapidPublicKey;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    function notificationsAvailable() {
        return 'Notification' in window;
    }

    function pushAvailable() {
        return notificationsAvailable()
            && 'serviceWorker' in navigator
            && 'PushManager' in window
            && Boolean(vapidPublicKey)
            && window.isSecureContext;
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let index = 0; index < rawData.length; index += 1) {
            outputArray[index] = rawData.charCodeAt(index);
        }

        return outputArray;
    }

    async function getServiceWorkerRegistration() {
        if (!pushAvailable()) {
            return null;
        }

        return navigator.serviceWorker.register('/sw.js');
    }

    async function postSubscription(subscription) {
        if (!pushStoreUrl || !csrfToken) {
            return;
        }

        const payload = subscription.toJSON();
        const encodings = PushManager.supportedContentEncodings || ['aes128gcm'];
        payload.contentEncoding = encodings.includes('aes128gcm') ? 'aes128gcm' : encodings[0];

        const response = await fetch(pushStoreUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error('Push subscription was not saved.');
        }
    }

    async function subscribeForPush() {
        const registration = await getServiceWorkerRegistration();

        if (!registration) {
            return false;
        }

        const existingSubscription = await registration.pushManager.getSubscription();

        if (existingSubscription) {
            await postSubscription(existingSubscription);
            return true;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });

        await postSubscription(subscription);
        return true;
    }

    async function updateNotifyButton() {
        if (!notifyButton) {
            return;
        }

        if (!notificationsAvailable()) {
            notifyButton.textContent = 'Notifications unavailable';
            notifyButton.disabled = true;
            return;
        }

        if (Notification.permission === 'denied') {
            notifyButton.textContent = 'Notifications blocked';
            notifyButton.disabled = true;
            return;
        }

        if (!pushAvailable()) {
            notifyButton.textContent = Notification.permission === 'granted' ? 'Foreground notifications on' : 'Enable notifications';
            notifyButton.disabled = Notification.permission === 'granted';
            return;
        }

        if (Notification.permission !== 'granted') {
            notifyButton.textContent = 'Enable mobile push';
            notifyButton.disabled = false;
            return;
        }

        try {
            const registration = await getServiceWorkerRegistration();
            const subscription = await registration?.pushManager.getSubscription();

            notifyButton.textContent = subscription ? 'Mobile push on' : 'Enable mobile push';
            notifyButton.disabled = Boolean(subscription);
        } catch (error) {
            notifyButton.textContent = 'Enable notifications';
            notifyButton.disabled = false;
        }
    }

    async function requestPermission() {
        if (!notificationsAvailable()) {
            return;
        }

        const permission = await Notification.requestPermission();

        if (permission === 'granted' && pushAvailable()) {
            try {
                await subscribeForPush();
            } catch (error) {
                console.warn('Push subscription failed', error);
            }
        }

        await updateNotifyButton();
    }

    function canNotifyTask(taskId) {
        const key = `top3:last-notified:${taskId}`;
        const previous = Number(window.localStorage.getItem(key) || 0);
        const now = Date.now();
        const tenMinutes = 10 * 60 * 1000;

        if (now - previous < tenMinutes) {
            return false;
        }

        window.localStorage.setItem(key, String(now));
        return true;
    }

    function showNotification(task) {
        if (!notificationsAvailable() || Notification.permission !== 'granted' || !canNotifyTask(task.id)) {
            return;
        }

        const status = task.status === 'escalated' ? 'Escalated' : 'Task nudge';
        const dueText = task.due_at ? 'Due soon' : 'Still waiting';
        const notification = new Notification(status, {
            body: `${dueText}: ${task.title}`,
            tag: `top3-task-${task.id}`,
            renotify: false,
        });

        notification.onclick = function () {
            window.focus();
            window.location.href = dashboardUrl;
            notification.close();
        };
    }

    async function checkReminders() {
        if (!reminderUrl || !notificationsAvailable() || Notification.permission !== 'granted') {
            return;
        }

        try {
            const response = await fetch(reminderUrl, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            (data.tasks || []).forEach(showNotification);
        } catch (error) {
            console.warn('Reminder check failed', error);
        }
    }

    if (notifyButton) {
        notifyButton.addEventListener('click', requestPermission);
        updateNotifyButton();
    }

    if (pushAvailable()) {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.warn('Service worker registration failed', error);
        });
    }

    if (reminderUrl) {
        window.setTimeout(checkReminders, 10000);
        window.setInterval(checkReminders, 60000);
    }
})();
