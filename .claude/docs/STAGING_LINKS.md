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
| **https://staging.spaceofjoy.ru/new-admin/** | Новая админка org (`AdminFront/`, Vite + PrimeVue Sakai) — PoC | Превью новой admin-only системы. Пока один экран: QR-заказы (read-only). Сборка `--base=/new-admin/`, dist раздаётся nginx-staging (location `/new-admin/`) |
| **https://api.staging.spaceofjoy.ru** | Backend API (Laravel) | Endpoint'ы `/api/v1/*` — через `curl`/Postman/DevTools браузера. Healthcheck: `GET /` → 200 |
| **https://vhod.staging.spaceofjoy.ru** | Baza (аутентификация) | Регистрация, логин, восстановление пароля |
| **https://pma.staging.spaceofjoy.ru** | phpMyAdmin → MySQL | Просмотр/правка БД `systo`, `baza`. **Basic auth + MySQL логин** (см. §«Креды») |
| **https://mail.staging.spaceofjoy.ru** | Mailpit (email-catcher) | Письма от Backend на тестовый адрес — ничего не уходит наружу. **Basic auth** |

---

## По типам задач — куда смотреть

| Что меняли | Куда смотреть в первую очередь |
|---|---|
| Новый/изменённый эндпоинт API | `https://api.staging.spaceofjoy.ru/api/v1/...` |
| UI заказа / форма покупки билетов | `https://staging.spaceofjoy.ru` → переход на покупку |
| Админ-UI (CRUD типов билетов / опций / промокодов / локаций) | `https://staging.spaceofjoy.ru` → логин админа → нужный раздел |
| Новая админка (AdminFront, Vite + Sakai) / экран QR-заказов | `https://staging.spaceofjoy.ru/new-admin/` |
| Регистрация / логин / пароль / роли | `https://vhod.staging.spaceofjoy.ru` |
| Миграция БД, новая таблица, поле | `https://pma.staging.spaceofjoy.ru` → таблица → SHOW COLUMNS |
| Письма (оплата заказа / отмена / анкета / Friendly / список) | `https://mail.staging.spaceofjoy.ru` |
| Расчёт цены, скидка промокодом | `https://staging.spaceofjoy.ru` → форма покупки → сравнить с ожиданием |
| Логи Backend (ошибки) | `ssh deploy@77.222.32.244 'docker logs --tail=200 php-staging'` |

---

## Креды

**В этом файле паролей НЕТ** — только инструкция как достать.

### Basic auth (pma + mail)

Логин задавался вручную через `infra/staging/setup-basic-auth.sh` (хеш bcrypt в `/etc/nginx/auth/staging-tools.htpasswd`).

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

### Подробности — `.claude/docs/process/RELEASES.md §9`

---

## Состояние БД (для тестов)

```bash
ssh deploy@77.222.32.244 'docker exec mysql-staging sh -c '"'"'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT \"festivals\" AS tab, COUNT(*) FROM systo.festivals UNION ALL SELECT \"ticket_type\", COUNT(*) FROM systo.ticket_type UNION ALL SELECT \"order_tickets\", COUNT(*) FROM systo.order_tickets;" 2>/dev/null'"'"''
```

Если БД пустая (`festivals=0`) — на следующем push в staging auto-mode workflow прокатит сидеры. Force-режим: `gh workflow run deploy-staging.yml -f seed=fresh` (см. `.github/workflows/deploy-staging.yml`).

---

## Новая админка `/new-admin/` (AdminFront)

- **Сервис:** `node-admin-staging` (build `Docker/node`, bind `./AdminFront:/var/www/admin`, env `VITE_API_URL=https://api.staging.spaceofjoy.ru/`).
- **Сборка:** шаг `Build admin frontend (Vite, /new-admin/)` в `deploy-staging.yml` — под root: `run --rm node-admin-staging sh -c "rm -rf dist && npm ci --include=dev && npm run build -- --base=/new-admin/"` (свежий bind-каталог, дефолтный юзер контейнера не может писать в чужой UID).
- **Раздача:** nginx-staging, location `/new-admin/` (`Docker/nginx/default.staging.conf`), `try_files … /new-admin/index.html` (SPA-fallback).
- Старый фронт (`https://staging.spaceofjoy.ru`) собирается отдельным шагом (`node-staging`) и продолжает работать — переезд по Strangler.

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
