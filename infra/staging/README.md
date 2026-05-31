# Staging-стенд (77.222.32.244)

Инфраструктура для staging-окружения проекта Systo. Сервер используется
для CD-пайплайна GitHub Actions (self-hosted runner + автодеплой через SSH).

## Состав

- `setup-deploy-user.sh` — скрипт первой настройки: создаёт пользователя
  `deploy` для CD, настраивает sudoers, готовит директорию проекта.
- `setup-swap.sh` — опциональный swap-файл (рекомендуется при RAM ≤ 2 ГБ).
- (далее) `setup-runner.sh` — установка GitHub Actions self-hosted runner
- (далее) `docker-compose.staging.yml` — конфигурация контейнеров staging

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

### 5. Проверка

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
