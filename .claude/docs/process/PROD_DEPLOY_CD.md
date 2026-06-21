# Прод-деплой по тегам (CI/CD)

> **Идея:** стенд = разработка + приёмка. **Прод выкатывается ТОЛЬКО по тегу** `vX.Y.Z`
> после полного теста на стенде. Делает это workflow `.github/workflows/deploy-prod.yml`
> на **self-hosted runner**, поднятом на прод-сервере (`mail`).
>
> Деление ролей: **инструкции — здесь, команды на проде вводит владелец.**

---

## Что делает workflow `deploy-prod.yml`

Триггер: push тега `v*` **или** ручной `workflow_dispatch` (с тумблерами миграции/сида).

Шаги (на прод-runner'е, `docker-compose.prod.yml`):
1. **pre-check** (на GitHub-hosted): наличие прод-файлов, команды миграции, сидера RBAC.
2. **Guard**: проект и `.env` на месте; рабочее дерево чистое (иначе прерывает — не затирает локальные прод-правки).
3. **Бэкап БД** `systo` + `baza` → `${PROD_BACKUP_DIR}/*.sql.gz` (ротация, последние 10). **Падает, если бэкап пустой.**
4. **Maintenance ON** (`artisan down` для Backend и Baza).
5. **Pull кода**: `git fetch --tags` + `checkout <тег>`.
6. **composer install** (Backend + Baza).
7. **up -d** контейнеров + ожидание healthy mysql.
8. **Сборка фронта** (старый FrontEnd).
9. **Схемные миграции** `migrate --force` (Backend + Baza).
10. **Data-миграция v2.6.0** `order:migrate-to-guests-format --apply` (с inline-бэкапом, идемпотентна) — тумблер `run_data_migration`.
11. **RBAC Baza** — `db:seed --class=BazaRolePermissionsSeeder` (идемпотентно, **только матрица прав**, без демо) — тумблер `seed_baza_rbac`.
12. storage:link + права + `optimize:clear` + рестарт воркера.
13. **Maintenance OFF** (`artisan up`, всегда — даже при сбое выше).
14. Healthcheck прод-URL.

---

## Разовая настройка прода (владелец вводит на сервере `mail`)

### 1. Self-hosted runner с меткой `prod`

GitHub → репозиторий **Settings → Actions → Runners → New self-hosted runner → Linux**.
GitHub покажет команды с актуальной версией и токеном. Выполнить их на прод-сервере, **добавив метку `prod`**:

```bash
# пример (версию/токен взять из GitHub, метку добавить руками):
mkdir -p ~/actions-runner && cd ~/actions-runner
curl -o runner.tar.gz -L <ссылка-из-GitHub>
tar xzf runner.tar.gz
./config.sh --url https://github.com/ShaevMV/systo --token <ТОКЕН-ИЗ-GITHUB> \
  --labels prod --name prod-mail --unattended
# установить как сервис (автозапуск):
sudo ./svc.sh install
sudo ./svc.sh start
```

> Если runner запускается под `root` (текущий прод — `root@mail`): перед `config.sh`
> выставить `export RUNNER_ALLOW_RUNASROOT=1`. Лучше — отдельный пользователь `deploy`
> в группе `docker` (как на стенде).

**Требования к окружению runner'а** (как на стенде):
- `docker` + `docker compose` доступны пользователю runner'а (группа `docker`).
- Passwordless `sudo` для `git -C <PROD_PROJECT_DIR>` (workflow тянет код через `sudo git`).
  Под root — не нужно. Под `deploy` — добавить в sudoers:
  `deploy ALL=(ALL) NOPASSWD: /usr/bin/git -C /var/www/systo *`
- Репозиторий уже склонирован в `PROD_PROJECT_DIR`, владелец — пользователь runner'а.
  Сейчас прод в `/root/systo` → значит `PROD_PROJECT_DIR=/root/systo` (см. ниже).
- Прод `.env`, `Backend/.env`, `Baza/.env` существуют (реальные секреты). Workflow их **не трогает**.

### 2. Repo Variables (Settings → Secrets and variables → Actions → Variables)

| Variable | Значение | Назначение |
|----------|----------|------------|
| `PROD_PROJECT_DIR` | путь к репо на проде (напр. `/root/systo`) | где лежит проект |
| `PROD_BACKUP_DIR` | каталог бэкапов вне репо (напр. `/var/backups/systo`) | куда писать дампы |

Без них workflow возьмёт дефолты `/var/www/systo` и `/var/backups/systo`.

### 3. Environment-гейт (рекомендуется)

Settings → **Environments → New environment → `production`** → включить **Required reviewers**
(добавить себя). Тогда push тега запустит workflow, но шаг деплоя **встанет на ручное
подтверждение** — защита от случайного выката. Workflow уже привязан к `environment: production`.

---

## Как выкатить релиз (после теста на стенде)

1. Убедиться, что **репетиция миграции на стенде на прод-дампе пройдена** (см. `PROD_RELEASE_v2.6.0.md`).
2. Поставить тег на нужный коммит master и запушить:
   ```bash
   git checkout master && git pull
   git tag -a vX.Y.Z -m "Release vX.Y.Z"
   git push origin vX.Y.Z
   ```
3. Workflow стартует автоматически. Если включён `production`-гейт — **подтвердить** запуск в
   Actions (Review deployments).
4. Следить за логом Actions (бэкап → миграция → up). Healthcheck в конце.

**Ручной запуск без тега** (например на ветку для проверки): Actions → Deploy to Production →
Run workflow → указать `ref`.

---

## Откат

1. Восстановить БД из бэкапа шага 3:
   ```bash
   gunzip -c ${PROD_BACKUP_DIR}/systo_pre_<тег>_<ts>.sql.gz | \
     docker compose -f docker-compose.prod.yml exec -T mysql \
     sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" systo'
   ```
   (для заказов также есть таблица `order_tickets_backup_v2_6_0_<ts>`, созданная data-миграцией).
2. Откатить код на предыдущий тег: повторить выкат с `ref=<предыдущий тег>` (или вручную
   `git checkout <старый тег>` + composer/migrate осторожно).

---

## Важно / ограничения

- **Новые фронты на проде не появятся**: `docker-compose.prod.yml` не содержит сервисов
  `node-admin` (`/admin/`) и `node-baza` (`/baza/` PWA) — это инфра только стенда. Чтобы
  выложить их на прод, нужно отдельно добавить сервисы + nginx-роуты (отдельная задача).
- **Baza Ф2–Ф5 на проде** включается этим выкатом (RBAC/смены): админ через суперроль не
  залочится, матрица прав сеется шагом 11. Перед фестивалём проверить вход на КПП отдельно.
- Первый прогон — **только supervised**: через `workflow_dispatch` или с включённым
  `production`-гейтом, со слежением за логом.

> Связано: `PROD_RELEASE_v2.6.0.md` (детали миграции), `RELEASES.md` (версионирование),
> память `project_v260_prod_migration`.
