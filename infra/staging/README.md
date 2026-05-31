# Staging-стенд (77.222.32.244)

Инфраструктура для staging-окружения проекта Systo. Сервер используется
для CD-пайплайна GitHub Actions (self-hosted runner + автодеплой через SSH).

## Состав

- `setup-deploy-user.sh` — создаёт пользователя `deploy` для CD,
  настраивает sudoers, готовит директорию проекта.
- `setup-swap.sh` — опциональный swap-файл (рекомендуется при RAM ≤ 2 ГБ).
- `setup-runner.sh` — устанавливает self-hosted GitHub Actions runner
  как systemd service.
- `setup-host-nginx.sh` — настраивает host nginx как reverse proxy
  для 5 поддоменов.
- `setup-basic-auth.sh` — создаёт `.htpasswd` для phpMyAdmin и Mailpit.
- `setup-ssl.sh` — Let's Encrypt SSL через certbot для всех 5 поддоменов.
- `nginx/sites-available/*.conf` — шаблоны nginx server-блоков.
- (далее) `docker-compose.staging.yml` — конфигурация контейнеров staging

## Архитектура поддоменов

```
[Internet:80/443]
       │
  [HOST NGINX] (Let's Encrypt SSL)
       │
       ├─ staging.spaceofjoy.ru          → 127.0.0.1:8080  Frontend (Vue SPA)
       ├─ api.staging.spaceofjoy.ru      → 127.0.0.1:8081  Backend (Laravel)
       ├─ vhod.staging.spaceofjoy.ru     → 127.0.0.1:8082  Baza (Laravel)
       ├─ pma.staging.spaceofjoy.ru      → 127.0.0.1:8083  phpMyAdmin   + basic auth
       └─ mail.staging.spaceofjoy.ru     → 127.0.0.1:8084  Mailpit      + basic auth
```

## Workflow

- `.github/workflows/deploy-staging.yml` — auto-deploy при push в ветку `staging`

## Текущий staging-сервер

| Параметр | Значение |
|----------|----------|
| Адрес | `77.222.32.244` |
| OS | Ubuntu 24.04.4 LTS |
| CPU / RAM | 2 ядра / 1.9 Gi |
| Диск | 30 GB (24 свободно) |
| Docker | 29.5.2 (предустановлен) |

> ⚠️ Память ограничена — обязательно запусти `setup-swap.sh` перед
> установкой runner и контейнеров, иначе при npm build / docker compose up
> получишь OOM-kill.

## Первая настройка (один раз)

### 1. Подготовка SSH-ключа (на ЛОКАЛЬНОЙ машине)

Если ещё не создан — сгенерируй deploy-ключ для GitHub Actions:

```bash
ssh-keygen -t ed25519 -C "github-actions-systo" -f ~/.ssh/gha_deploy -N ""
```

### 2. Загрузка ключа в GitHub Secrets

```bash
gh secret set DEPLOY_SSH_KEY -R ShaevMV/systo < ~/.ssh/gha_deploy
gh secret set DEPLOY_HOST    -R ShaevMV/systo --body "77.222.32.244"
gh secret set DEPLOY_USER    -R ShaevMV/systo --body "deploy"
```

### 3. Swap (опционально — для серверов с RAM ≤ 2 ГБ)

```bash
scp infra/staging/setup-swap.sh root@77.222.32.244:/tmp/
ssh root@77.222.32.244 'bash /tmp/setup-swap.sh 4'
```

### 4. Настройка пользователя deploy

Скопируй скрипт на сервер:

```bash
scp infra/staging/setup-deploy-user.sh root@77.222.32.244:/tmp/
```

Запусти с **локальной** машины одной строкой (ключ читается локально, без копирования на сервер):

```bash
ssh root@77.222.32.244 "bash /tmp/setup-deploy-user.sh '$(cat ~/.ssh/gha_deploy.pub)'"
```

> Важно: запускать именно с локальной — там лежит `~/.ssh/gha_deploy.pub`.
> Если запустить на сервере (`ssh ... && bash ...`), он не найдёт файл.

> ⚠️ Скрипт идемпотентен — можно запускать повторно без вреда. Каждое уже
> сделанное действие будет пропущено.

### 5. Установка self-hosted GitHub Actions runner

Runner подхватывает job-ы с лейблом `staging` из workflow `deploy-staging.yml`.

С локальной машины:

```bash
# Получить одноразовый registration-token (валиден 1 час)
TOKEN=$(gh api -X POST repos/ShaevMV/systo/actions/runners/registration-token --jq .token)

# Скопировать скрипт
scp infra/staging/setup-runner.sh root@77.222.32.244:/tmp/

# Запустить (от root — нужен для systemd unit, внутри сам делает sudo -u deploy)
ssh root@77.222.32.244 "bash /tmp/setup-runner.sh https://github.com/ShaevMV/systo '$TOKEN'"
```

После запуска:
- В `Settings → Actions → Runners` появится `staging-systo` со статусом `Idle`
- Логи: `ssh root@... 'sudo journalctl -u actions.runner.*.service -f'`

### 6. Bootstrap проекта (один раз, **до первого деплоя**)

Runner работает в `/home/deploy/actions-runner/_work`, но проект разворачиваем
в `/var/www/systo` (созданном `setup-deploy-user.sh`). Первый клон делается
вручную как `deploy`:

```bash
ssh -i ~/.ssh/gha_deploy deploy@77.222.32.244
cd /var/www/systo
git clone https://github.com/ShaevMV/systo.git .
git checkout staging  # ветка должна существовать в репо
exit
```

После — workflow сам делает `sudo git pull` при каждом push в staging.

### 7. Host nginx как reverse proxy (5 поддоменов)

**Перед запуском убедись что DNS распространился:**

```bash
for sub in '' api. vhod. pma. mail.; do
  echo -n "${sub}staging.spaceofjoy.ru: "
  dig +short ${sub}staging.spaceofjoy.ru @8.8.8.8
done
# Должно вернуть 77.222.32.244 пять раз
```

Запуск:

```bash
# Локально — нужна вся директория infra/staging (включая nginx/sites-available/)
scp -r infra/staging root@77.222.32.244:/tmp/
ssh root@77.222.32.244 'bash /tmp/staging/setup-host-nginx.sh'
```

После — все 5 поддоменов на 80 порту, но возвращают 502 (контейнеры ещё нет).

### 8. Basic auth для phpMyAdmin и Mailpit

```bash
ssh root@77.222.32.244 'bash /tmp/staging/setup-basic-auth.sh admin'
# Введёшь пароль (без эха). Запиши его — пригодится для входа в pma/mail.
```

### 9. SSL через Let's Encrypt

```bash
ssh root@77.222.32.244 'bash /tmp/staging/setup-ssl.sh твой-email@example.com'
```

Скрипт сам:
- Проверит распространение DNS
- Проверит доступность `/.well-known/acme-challenge/`
- Запросит сертификаты для всех 5 поддоменов
- Настроит HTTP → HTTPS редирект
- Включит auto-renew через `certbot.timer`

Сертификаты обновляются автоматически за 30 дней до истечения.

### 10. Проверка

С локальной машины:

```bash
ssh -i ~/.ssh/gha_deploy deploy@77.222.32.244 whoami
# должно вывести: deploy

ssh -i ~/.ssh/gha_deploy deploy@77.222.32.244 'sudo -n docker ps'
# должно НЕ запросить пароль (т.к. docker в белом списке sudoers)
```

## Архитектура CD-пайплайна (план)

```
git push в ветку staging
        │
        ▼
GitHub Actions (.github/workflows/deploy-staging.yml)
        │
        ├─ Сборка артефактов на GitHub-hosted runner
        │  (PHPUnit, lint, npm build)
        │
        └─ Self-hosted runner на 77.222.32.244
              │
              ▼
          deploy job:
              sudo git -C /var/www/systo pull
              sudo docker compose -f docker-compose.staging.yml up -d --build
              healthcheck
```

## Безопасность

- Пользователь `deploy` не имеет пароля (только SSH-ключ)
- `sudo` разрешён ТОЛЬКО для конкретных команд (docker, systemctl restart, git -C /var/www/systo)
- Нет `ALL ALL` — невозможно получить root shell через `sudo -i`
- Sudoers-файл проверяется через `visudo -c` перед установкой
- root-доступ остаётся у владельца (для аварийных случаев)

См. правило CI/CD в memory: `feedback_tests_required.md` §4 (никаких ручных
правок на сервере — всё через пайплайн).
