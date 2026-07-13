# Деплой на VPS

Розрахований на звичайний Ubuntu/Debian VPS з nginx + PHP-FPM + MySQL, домен
з SSL (Let's Encrypt). Без Docker.

## 1. Вимоги на сервері

- PHP 8.2+ з розширеннями: `pdo_mysql`, `mbstring`, `curl`, `openssl`, `bcmath`, `xml`, `ctype`, `fileinfo`, `tokenizer`.
- MySQL 8+ (або MariaDB 10.6+).
- Composer 2, Node.js 18+ (тільки для збірки фронтенду під час деплою).
- nginx (або Apache) з PHP-FPM.
- Домен, що вказує на сервер, і сертифікат SSL (для Telegram webhook
  обов'язковий HTTPS).

## 2. Перше розгортання

```bash
cd /var/www
git clone <repo-url> velotor-ride
cd velotor-ride

composer install --no-dev --optimize-autoloader
npm install
npm run build

cp .env.example .env
php artisan key:generate
# відредагувати .env: APP_URL, DB_*, TELEGRAM_*, VELOTOR_TIMEZONE

php artisan migrate --force
php artisan db:seed --force   # опційно: демо-дані для першого запуску

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

`APP_ENV=production`, `APP_DEBUG=false` в `.env` на проді.

## 3. nginx (приклад)

```nginx
server {
    listen 443 ssl http2;
    server_name velotor.example.com;
    root /var/www/velotor-ride/public;

    ssl_certificate     /etc/letsencrypt/live/velotor.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/velotor.example.com/privkey.pem;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name velotor.example.com;
    return 301 https://$host$request_uri;
}
```

## 4. Cron (Laravel Scheduler)

Один запис у crontab користувача, від імені якого запущений сайт (наприклад
`www-data`):

```bash
* * * * * cd /var/www/velotor-ride && php artisan schedule:run >> /dev/null 2>&1
```

Це закриває тиждень щопонеділка о 00:00 (`week:close`), надсилає нагадування
в чат за 2 години та за 1 годину до закриття (`week:remind`, неділя 22:00 та
23:00 за `VELOTOR_TIMEZONE`), і виконує будь-які інші заплановані команди в
майбутньому. Черг/воркерів не потрібно.

## 5. Telegram webhook

1. У `.env` виставити `TELEGRAM_BOT_TOKEN` (від @BotFather) і
   `TELEGRAM_WEBHOOK_SECRET` (будь-який довгий випадковий рядок).
2. Зареєструвати вебхук у Telegram (один раз, з будь-якої машини):

```bash
curl -X POST "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook" \
  -d "url=https://velotor.example.com/telegram/webhook" \
  -d "secret_token=<TELEGRAM_WEBHOOK_SECRET>"
```

3. Перевірити: `curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"` —
   поле `url` має збігатися, `last_error_message` — порожнє.
4. В адмінці (`/admin/settings`) вказати `telegram_chat_id` — id чату, куди
   бот надсилатиме тижневі звіти (можна дізнатись, додавши бота в чат і
   подивившись `getUpdates`/лог `bot_message_logs`).

## 6. Анонс оновлення сайту в чаті

Щоб бот написав у чат гарне повідомлення "сайт оновлено" (наприклад, одразу
після деплою нової версії):

1. У `.env` виставити `TELEGRAM_ANNOUNCE_SECRET` (будь-який довгий випадковий
   рядок, окремий від `TELEGRAM_WEBHOOK_SECRET`).
2. Один раз перейти за посиланням у браузері (або `curl`):

```
https://ride.velotor.com.ua/telegram/announce?secret=<TELEGRAM_ANNOUNCE_SECRET>
```

Бот надішле в чат (`telegram_chat_id` з налаштувань) повідомлення з номером і
назвою поточної версії (береться з `CHANGELOG.md`) та посиланням на `/stat`.
Без правильного `secret` віддає 403.

## 7. Оновлення (деплой нової версії)

### Автоматично, через GitHub Actions (рекомендовано)

Кожен push у `main` збирає фронтенд на GitHub-раннері та викладає готову
збірку на сервер по SSH — сервер компілює лише `composer install` та
`php artisan migrate`, Node.js на проді не потрібен.

1. У репозиторії: **Settings → Secrets and variables → Actions** додати:
   - `DEPLOY_HOST` — хост/IP сервера;
   - `DEPLOY_USER` — SSH-користувач;
   - `DEPLOY_SSH_KEY` — приватний SSH-ключ (PEM), авторизований на сервері;
   - `DEPLOY_PORT` — SSH-порт (зазвичай `22`);
   - `DEPLOY_PATH` — абсолютний шлях до проєкту на сервері (напр.
     `/var/www/velotor-ride`).
2. На сервері `deploy.sh` (у корені репозиторію) вже виконує: maintenance
   mode → git reset --hard → composer install → підміна `public/build` на
   готову збірку → migrate → optimize → вихід з maintenance mode.
3. Секрети та `.env` (включно з `TELEGRAM_BOT_TOKEN`/`TELEGRAM_CHAT_ID`)
   ніколи не потрапляють у репозиторій чи GitHub Actions — вони живуть лише
   в `.env` на сервері, деплой їх не чіпає.

Workflow: `.github/workflows/deploy.yml`. Скрипт: `deploy.sh`.

### Вручну (без GitHub Actions)

```bash
cd /var/www/velotor-ride
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8. Перевірка після деплою

- Відкрити `https://velotor.example.com/` — головна сторінка повинна
  відкритись без помилок.
- Написати боту `/start` у Telegram-чаті — має відповісти.
- Написати `результат 10 км` — має зарахувати і відповісти з підсумком.
- `php artisan schedule:list` — переконатись, що `week:close` заплановано.
- `/admin/bot-logs` — переконатись, що вхідні повідомлення логуються.
