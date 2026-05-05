#!/usr/bin/env bash
set -Eeuo pipefail

# Create a Proxmox LXC and install Top 3 Tasks from a Git repository.
# Run this on the Proxmox host as root:
#   ./scripts/proxmox-lxc-install.sh

VMID="${VMID:-230}"
HOSTNAME="${HOSTNAME:-top3-tasks}"
REPO_URL="${REPO_URL:-https://github.com/DanielJohnRoberts/TaskTracker.git}"
BRANCH="${BRANCH:-main}"
TEMPLATE_STORAGE="${TEMPLATE_STORAGE:-local}"
CONTAINER_STORAGE="${CONTAINER_STORAGE:-local-lvm}"
BRIDGE="${BRIDGE:-vmbr0}"
IP_CONFIG="${IP_CONFIG:-dhcp}"
CORES="${CORES:-1}"
MEMORY="${MEMORY:-1024}"
SWAP="${SWAP:-512}"
ROOTFS_SIZE="${ROOTFS_SIZE:-8}"
APP_DIR="${APP_DIR:-/var/www/top3-tasks}"
DB_NAME="${DB_NAME:-top3_tasks}"
DB_USER="${DB_USER:-top3_tasks}"
SEED_DEMO_DATA="${SEED_DEMO_DATA:-1}"

require() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "Missing required command: $1" >&2
        exit 1
    }
}

if [[ -z "$REPO_URL" ]]; then
    echo "REPO_URL is required, for example:" >&2
    echo "  REPO_URL=https://github.com/DanielJohnRoberts/TaskTracker.git $0" >&2
    exit 1
fi

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Run this script as root on the Proxmox host." >&2
    exit 1
fi

require pct
require pveam
require openssl

if pct status "$VMID" >/dev/null 2>&1; then
    echo "LXC $VMID already exists. Choose another VMID or destroy the existing container first." >&2
    exit 1
fi

echo "Finding Debian 12 LXC template..."
pveam update >/dev/null
TEMPLATE="${TEMPLATE:-$(pveam available --section system | awk '/debian-12-standard/ {print $2}' | tail -n 1)}"

if [[ -z "$TEMPLATE" ]]; then
    echo "Could not find a Debian 12 template from pveam." >&2
    exit 1
fi

if ! pveam list "$TEMPLATE_STORAGE" | awk -F/ '{print $NF}' | grep -qx "$TEMPLATE"; then
    echo "Downloading template $TEMPLATE to $TEMPLATE_STORAGE..."
    pveam download "$TEMPLATE_STORAGE" "$TEMPLATE"
fi

echo "Creating LXC $VMID ($HOSTNAME)..."
pct create "$VMID" "$TEMPLATE_STORAGE:vztmpl/$TEMPLATE" \
    --hostname "$HOSTNAME" \
    --cores "$CORES" \
    --memory "$MEMORY" \
    --swap "$SWAP" \
    --rootfs "$CONTAINER_STORAGE:$ROOTFS_SIZE" \
    --net0 "name=eth0,bridge=$BRIDGE,ip=$IP_CONFIG" \
    --features nesting=1,keyctl=1 \
    --unprivileged 1 \
    --ostype debian \
    --onboot 1 \
    --start 1

echo "Waiting for network..."
sleep 8

echo "Installing OS packages..."
pct exec "$VMID" -- bash -lc "apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y nginx mariadb-server git curl unzip ca-certificates cron composer php-cli php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-bcmath php-gd php-intl"

echo "Cloning application..."
pct exec "$VMID" -- bash -lc "rm -rf '$APP_DIR' && git clone --branch '$BRANCH' '$REPO_URL' '$APP_DIR'"

echo "Installing PHP dependencies..."
pct exec "$VMID" -- bash -lc "cd '$APP_DIR' && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader"

DB_PASS="$(openssl rand -hex 16)"
APP_KEY="$(openssl rand -base64 32 | sed 's#^#base64:#')"
CONTAINER_IP="$(pct exec "$VMID" -- bash -lc "hostname -I | awk '{print \$1}'" | tr -d '\r')"
APP_URL="${APP_URL:-http://$CONTAINER_IP}"
VAPID_SUBJECT="${VAPID_SUBJECT:-$APP_URL}"

echo "Generating Web Push keys..."
VAPID_ENV="$(pct exec "$VMID" -- bash -lc "cd '$APP_DIR' && php artisan webpush:keys --no-ansi | grep -E '^VAPID_(PUBLIC_KEY|PRIVATE_KEY)='")"

echo "Configuring application environment..."
pct exec "$VMID" -- bash -lc "cat > '$APP_DIR/.env' <<'ENV'
APP_NAME=\"Top 3 Tasks\"
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=$APP_URL

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
FILESYSTEM_DISK=local

$VAPID_ENV
VAPID_SUBJECT=$VAPID_SUBJECT
ENV"

echo "Creating database..."
pct exec "$VMID" -- bash -lc "systemctl enable --now mariadb && mysql -e \"CREATE DATABASE IF NOT EXISTS \\\`$DB_NAME\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS'; GRANT ALL PRIVILEGES ON \\\`$DB_NAME\\\`.* TO '$DB_USER'@'127.0.0.1'; FLUSH PRIVILEGES;\""

echo "Running migrations..."
pct exec "$VMID" -- bash -lc "cd '$APP_DIR' && php artisan migrate --force"

if [[ "$SEED_DEMO_DATA" == "1" ]]; then
    echo "Seeding demo users and example tasks..."
    pct exec "$VMID" -- bash -lc "cd '$APP_DIR' && php artisan db:seed --force"
fi

echo "Configuring Nginx and scheduler..."
pct exec "$VMID" -- bash -lc "cat > /etc/nginx/sites-available/top3-tasks <<'NGINX'
server {
    listen 80;
    server_name _;
    root $APP_DIR/public;
    index index.php index.html;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
ln -sf /etc/nginx/sites-available/top3-tasks /etc/nginx/sites-enabled/top3-tasks
rm -f /etc/nginx/sites-enabled/default
chown -R www-data:www-data '$APP_DIR/storage' '$APP_DIR/bootstrap/cache'
cd '$APP_DIR' && php artisan config:cache && php artisan route:cache
cat > /etc/cron.d/top3-tasks <<'CRON'
* * * * * www-data cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1
CRON
systemctl enable --now php8.2-fpm nginx cron
nginx -t
systemctl reload nginx"

echo
echo "Top 3 Tasks is installed."
echo "URL: $APP_URL"
if [[ "$SEED_DEMO_DATA" == "1" ]]; then
    echo "Admin login: admin@example.com / password"
    echo "Basic login: user@example.com / password"
    echo "Change these passwords after first login."
fi
echo
echo "For mobile push outside localhost, put the app behind HTTPS and use the same public URL as APP_URL."
