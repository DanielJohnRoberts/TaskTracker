# Top 3 Tasks

A Laravel 12 MVP for a dashboard-first personal task resurfacing system. It keeps the main surface focused on the three tasks most likely to need attention, with quick capture, simple task actions, and browser notification nudges.

This is designed as an internal tool. Public registration is disabled; admins create users from the app.

The app also includes demo seed data for the default admin and basic user accounts, a visual reporting screen at `/reports`, and PWA/Web Push notifications for mobile/background nudges.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL 8+ or MariaDB 10.6+
- Server-rendered Blade views
- Plain CSS and minimal JavaScript
- PWA manifest, service worker, and Web Push subscriptions

## Core Logic

The dashboard excludes completed and blocked tasks. Snoozed tasks are hidden until `snoozed_until` passes, then they are automatically released back to `active`.

Top 3 ranking is handled in `App\Models\Task::scopeRanked()`:

1. Urgent tasks first.
2. Then higher priority.
3. Then tasks due soonest.
4. Then tasks with the oldest `last_nudged_at`, `updated_at`, or `created_at`.

The reminder endpoint is `GET /api/check-reminders`. It only checks the logged-in user's tasks, returns at most three nudges, updates `last_nudged_at`, increments `nudge_count`, and escalates tasks when a hard deadline is close or the task has been ignored for too long.

Browser notifications require HTTPS or `localhost`. The dashboard still polls while open, and the app now also supports server-triggered Web Push through a service worker for better mobile/background delivery.

## Mobile Push

The dashboard button enables notifications and stores a browser push subscription for the logged-in user.

For local development, the checked-in `.env` already has generated VAPID keys. For a deployed host:

```bash
php artisan webpush:keys
```

Add the printed `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, and `VAPID_SUBJECT` values to `.env`.

To send due push notifications, run:

```bash
php artisan tasks:send-push
```

Laravel schedules this command every five minutes. On a production host, add the normal Laravel scheduler cron:

```bash
* * * * * cd /path/to/top3-tasks && php artisan schedule:run >> /dev/null 2>&1
```

Mobile notes:

- Android Chrome can receive Web Push after the user grants permission.
- iPhone/iPad requires iOS/iPadOS 16.4+ and works best after adding the site to the Home Screen.
- A real HTTPS domain is needed outside `localhost`.

## Reports

The `/reports` page shows each logged-in user's own task data:

- Total, open, completed, overdue, due-soon, and nudge counts
- Completed tasks over the last seven days
- Status, priority, and source distributions
- Deadline pressure and most-nudged task lists

## Roles

- `admin` - can use the task dashboard and create or edit users at `/admin/users`.
- `user` - can use the task dashboard only.

Seeded local accounts:

- Admin: `admin@example.com` / `password`
- Basic user: `user@example.com` / `password`

Running `php artisan db:seed` adds example tasks for both accounts. The seed is idempotent, so repeating it will not duplicate those demo tasks.

## XAMPP Setup

1. Start Apache and MySQL from XAMPP.
2. Create a database named `top3_tasks`.
3. Copy `.env.example` to `.env` if `.env` does not exist.
4. Set these values in `.env`:

```env
APP_NAME="Top 3 Tasks"
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=top3_tasks
DB_USERNAME=root
DB_PASSWORD=
```

5. Install dependencies and prepare the app:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
```

If PHP is not on your PATH on macOS XAMPP, use:

```bash
/Applications/XAMPP/xamppfiles/bin/php artisan migrate
/Applications/XAMPP/xamppfiles/bin/php artisan db:seed
/Applications/XAMPP/xamppfiles/bin/php artisan serve
```

6. Run the app:

```bash
php artisan serve
```

Then open `http://127.0.0.1:8000`.

## Basic Web Host Setup

1. Upload the project files.
2. Point the web root to the `public` directory.
3. Create a MySQL database and user.
4. Copy `.env.example` to `.env` and update the database settings.
5. Run:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
```

If the host does not allow shell access, import `database/schema.sql` in phpMyAdmin and ask the host to run Composer, or build the vendor directory locally and upload it.

## Proxmox LXC Setup

The repo includes a Proxmox host bootstrap script at `scripts/proxmox-lxc-install.sh`. It creates a Debian 12 LXC, installs Nginx, PHP, MariaDB, Composer, clones this Git repo, runs migrations/seeds, and enables the Laravel scheduler for push notifications.

Run it on the Proxmox host as root:

```bash
REPO_URL=https://github.com/your-user/top3-tasks.git ./scripts/proxmox-lxc-install.sh
```

Useful overrides:

```bash
VMID=240 HOSTNAME=top3-tasks APP_URL=https://tasks.example.com REPO_URL=https://github.com/your-user/top3-tasks.git ./scripts/proxmox-lxc-install.sh
```

For private repositories, use a deploy token URL or configure SSH access inside the LXC before cloning. For mobile push outside `localhost`, place the app behind HTTPS and set `APP_URL` to that public HTTPS URL.

## Main Files

- `app/Models/Task.php` - task fields, dashboard eligibility, ranking, snooze release
- `app/Http/Controllers/AuthController.php` - login and logout
- `app/Http/Controllers/Admin/UserController.php` - admin-only user creation and role editing
- `app/Http/Controllers/DashboardController.php` - dashboard queries and counts
- `app/Http/Controllers/TaskController.php` - capture, edit, complete, urgent, snooze, block, delete
- `app/Http/Controllers/ReminderController.php` - notification polling endpoint
- `app/Http/Controllers/PushSubscriptionController.php` - saves browser push subscriptions
- `app/Http/Controllers/ReportController.php` - visual task reporting
- `app/Console/Commands/SendDuePushNotifications.php` - sends due Web Push nudges
- `app/Console/Commands/GenerateWebPushKeys.php` - creates VAPID key pairs
- `resources/views/dashboard.blade.php` - dashboard and quick-add form
- `resources/views/reports/show.blade.php` - report visuals
- `resources/views/tasks/_card.blade.php` - task actions
- `public/assets/app.js` - notification permission and reminder polling
- `public/sw.js` - service worker for push notification display
- `public/manifest.webmanifest` - PWA manifest
- `scripts/proxmox-lxc-install.sh` - Proxmox LXC installer
- `public/assets/style.css` - responsive UI
- `database/schema.sql` - MySQL schema

## Testing

```bash
php artisan test
```
