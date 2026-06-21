# Ссылки на стенд (staging.spaceofjoy.ru)

Единый источник правды по публичным URL'ам staging-стенда и тому, что на каждом из них можно проверить.

**Сервер:** `77.222.32.244`
**Деплой:** auto-deploy при push в ветку `staging` (см. `.github/workflows/deploy-staging.yml`)
**Подробности инфраструктуры:** `infra/staging/README.md`

---

## Публичные URL'ы

| URL | Что это | Где проверять |
|-----|---------|---------------|
| **https://staging.spaceofjoy.ru** | Фронт SPA (Vue 3, старый `FrontEnd/`, Vue CLI/webpack) | Покупка билетов, формы заказа, личный кабинет, админ-UI (после логина) |
| **https://staging.spaceofjoy.ru/admin/** | Новая админка org (`AdminFront/`, Vite + PrimeVue Sakai) | Новая admin-only система. Разделы: **Управление** (дашборд, анкеты), **Заказы** (qr-заказы, оргвзносы, дружеские, заказы-списки), **Справочники** (типы билетов, опции, типы анкет, типы оплат, локации, промокоды), **Письма** (шаблоны, привязки, доставка). Сборка `--base=/admin/`, dist раздаётся nginx-staging (location `/admin/` + 301 с голого `/admin`). Перехватывает `/admin/*` у старого фронта (мини-cutover) |
| **https://api.staging.spaceofjoy.ru** | Backend API (Laravel) | Endpoint'ы `/api/v1/*` — через `curl`/Postman/DevTools браузера. Healthcheck: `GET /` → 200 |
| **https://vhod.staging.spaceofjoy.ru** | Baza (аутентификация) | Регистрация, логин, восстановление пароля |
| **https://vhod.staging.spaceofjoy.ru/baza/** | BazaFront — offline-first PWA входа на КПП (Ф5, Vite + Vue 3 + PrimeVue) | Сканер/впуск/поиск гостя без QR, офлайн-режим (service worker + IndexedDB-очередь намерений впуска). Сборка `--base=/baza/`, dist раздаётся nginx-staging в server-блоке `vhod.*` (location `/baza/` + 301 с голого `/baza`). Старый Blade Baza (`/scan`, `/search`, `/enter` на `/`) остаётся боевым fallback (Strangler). BazaFront висит на поддомене `vhod.*` (Baza), а не на `staging.*` (где `/admin/`) |
| **https://pma.staging.spaceofjoy.ru** | phpMyAdmin → MySQL | Просмотр/правка БД `systo`, `baza`. **Basic auth + MySQL логин** (см. §«Креды») |
| **https://mail.staging.spaceofjoy.ru** | Mailpit (email-catcher) | Письма от Backend на тестовый адрес — ничего не уходит наружу. **Basic auth** |
| **https://staging.spaceofjoy.ru/rabbitmq/** | RabbitMQ Management UI | Брокер сообщений (транспорт для интеграции qr→org). Очереди, обмены, сообщения. На под-пути основного домена (без отдельного сабдомена). **Basic auth + логин RabbitMQ** (см. §«Креды»). AMQP (5672) наружу не торчит — только внутри docker-сети staging |

---

## По типам задач — куда смотреть

| Что меняли | Куда смотреть в первую очередь |
|---|---|
| Новый/изменённый эндпоинт API | `https://api.staging.spaceofjoy.ru/api/v1/...` |
| UI заказа / форма покупки билетов | `https://staging.spaceofjoy.ru` → переход на покупку |
| Админ-UI (CRUD типов билетов / опций / промокодов / локаций) | `https://staging.spaceofjoy.ru` → логин админа → нужный раздел |
| Новая админка (AdminFront, Vite + Sakai) / экран QR-заказов | `https://staging.spaceofjoy.ru/admin/` |
| BazaFront PWA / вход на КПП / сканер / поиск гостя без QR / офлайн-режим | `https://vhod.staging.spaceofjoy.ru/baza/` |
| Регистрация / логин / пароль / роли (старый Blade-вход Baza на корне vhod.*) | `https://vhod.staging.spaceofjoy.ru` |
| Миграция БД, новая таблица, поле | `https://pma.staging.spaceofjoy.ru` → таблица → SHOW COLUMNS |
| Письма (оплата заказа / отмена / анкета / Friendly / список) | `https://mail.staging.spaceofjoy.ru` |
| Очереди/сообщения RabbitMQ (интеграция qr→org) | `https://staging.spaceofjoy.ru/rabbitmq/` |
| Расчёт цены, скидка промокодом | `https://staging.spaceofjoy.ru` → форма покупки → сравнить с ожиданием |
| Логи Backend (ошибки) | `ssh deploy@77.222.32.244 'docker logs --tail=200 php-staging'` |

---

## Креды

**В этом файле паролей НЕТ** — только инструкция как достать.

### Basic auth (pma + mail + rabbit)

Логин задавался вручную через `infra/staging/setup-basic-auth.sh` (хеш bcrypt в `/etc/nginx/auth/staging-tools.htpasswd`). Один и тот же htpasswd защищает phpMyAdmin, Mailpit и RabbitMQ UI.

Сбросить пароль / добавить нового юзера:
```bash
ssh root@77.222.32.244 'bash /var/www/systo/infra/staging/setup-basic-auth.sh <логин>'
```

### MySQL (внутри phpMyAdmin)

Пароли генерируются автоматически при первом деплое (`openssl rand -hex 16`) и лежат в `/var/www/systo/.env.staging` на сервере. Достать:
```bash
ssh deploy@77.222.32.244 'grep -E "^(MYSQL_ROOT_PASSWORD|MYSQL_PASSWORD)" /var/www/systo/.env.staging'
```

База: **`systo`** (основное приложение) или **`baza`** (аутентификация).

### RabbitMQ (логин в Management UI)

Пользователь/пароль генерируются автоматически при деплое (`openssl rand -hex 16`) и лежат в `/var/www/systo/.env.staging`. Достать:
```bash
ssh deploy@77.222.32.244 'grep -E "^RABBITMQ_DEFAULT_(USER|PASS|VHOST)" /var/www/systo/.env.staging'
```
Сначала basic auth nginx (как pma/mail), затем — этот логин в самой UI.

### Подробности — `.claude/docs/process/RELEASES.md §9`

---

## Состояние БД (для тестов)

```bash
ssh deploy@77.222.32.244 'docker exec mysql-staging sh -c '"'"'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT \"festivals\" AS tab, COUNT(*) FROM systo.festivals UNION ALL SELECT \"ticket_type\", COUNT(*) FROM systo.ticket_type UNION ALL SELECT \"order_tickets\", COUNT(*) FROM systo.order_tickets;" 2>/dev/null'"'"''
```

Если БД пустая (`festivals=0`) — на следующем push в staging auto-mode workflow прокатит сидеры. Force-режим: `gh workflow run deploy-staging.yml -f seed=fresh` (см. `.github/workflows/deploy-staging.yml`).

---

## Новая админка `/admin/` (AdminFront)

- **Сервис:** `node-admin-staging` (build `Docker/node`, bind `./AdminFront:/var/www/admin`, env `VITE_API_URL=https://api.staging.spaceofjoy.ru/`).
- **Сборка:** шаг `Build admin frontend (Vite, /admin/)` в `deploy-staging.yml` — под root: `run --rm node-admin-staging sh -c "rm -rf dist && npm ci --include=dev && npm run build -- --base=/admin/"` (свежий bind-каталог, дефолтный юзер контейнера не может писать в чужой UID).
- **Раздача:** nginx-staging, location `/admin/` (`Docker/nginx/default.staging.conf`), `try_files … /admin/index.html` (SPA-fallback) + `location = /admin` → 301 на `/admin/`.
- **Мини-cutover:** `/admin/*` теперь обслуживает новая админка (раньше было `/new-admin/`). Старый фронт (`https://staging.spaceofjoy.ru`) остаётся главным на `/` и собирается отдельным шагом (`node-staging`) — полный cutover по Strangler позже.

---

## BazaFront `/baza/` (offline-first PWA входа на КПП, Ф5)

- **Сервис:** `node-baza-staging` (build `Docker/node`, bind `./BazaFront:/var/www/baza`, env `VITE_API_URL=https://vhod.staging.spaceofjoy.ru/`).
- **Сборка:** шаг `Build baza frontend (Vite PWA, /baza/)` в `deploy-staging.yml` — под root: `run --rm --no-deps -u root node-baza-staging sh -c "rm -rf dist && npm ci --include=dev && npm run build -- --base=/baza/"`. CI собирает заранее job-ом `build-baza-front` в `ci.yml` (закрывает TD-32).
- **Раздача:** nginx-staging, location `/baza/` (`alias /var/www/baza/dist/`, SPA-fallback `/baza/index.html`) в server-блоке `vhod.staging.spaceofjoy.ru` + `location = /baza` → 301 на `/baza/`.
- **PWA:** service worker (Workbox, `registerType: prompt`) + precache app-shell + manifest install + IndexedDB append-only очередь намерений впуска + `device_id`.
- **Strangler:** старый Blade Baza (`/scan`, `/search`, `/enter` на корне `vhod.*`) остаётся боевым fallback ≥1 фестиваль. Полный cutover на PWA позже.

---

## Использование агентами

Этот файл — справочник для:
- **project-manager** — обязан включать релевантные ссылки в каждый отчёт пользователю
- **scrum-master** — добавляет ссылки в release notes
- **tester** — использует как чек-лист для мануальной проверки
- **devops-engineer** — проверяет healthcheck endpoints

Перед использованием ссылок — **актуализируй**: проверь что страница отвечает 200 (или 401 для защищённых), что не упала.

---

## История изменений документа

| Дата | Изменение |
|------|-----------|
| 2026-06-01 | Создан документ — единый источник ссылок на стенд для агентов |
| 2026-06-14 | Добавлена новая админка `/new-admin/` (AdminFront, Vite + PrimeVue Sakai): URL, инфра-сервис `node-admin-staging`, nginx-location, шаг Vite-сборки в workflow |
| 2026-06-18 | Новая админка переехала `/new-admin/` → **`/admin/`** (мини-cutover превью): nginx `location /admin/` + 301 с голого `/admin`, Vite `--base=/admin/`. Релиз `v2.7.0-alpha.3` |
| 2026-06-20 | Добавлен BazaFront — offline-first PWA входа на КПП (Ф5) под `https://vhod.staging.spaceofjoy.ru/baza/`: сервис `node-baza-staging`, nginx `location /baza/` в server-блоке vhod.*, Vite `--base=/baza/`, CI-job `build-baza-front`. PR-1 (#120/#121 каркас + Docker + nginx + CI), PR-2 (#122 service worker Workbox + manifest + IndexedDB-очередь намерений + device_id). Спека `.claude/specs/baza-f5-pwa.md` |
