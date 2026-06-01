# Тестовые пользователи (UserSeeder)

Список пользователей, которых создаёт `Backend/database/seeders/UserSeeder.php` на staging/локальной разработке.

**На проде сиды не запускаются** — эти данные есть только в test-средах.

---

## Единый пароль

```
password
```

Используется для **всех** ролей. Это test-environment convention (привет, Laravel), не для прода.

Если нужно сменить — правка одной константы `UserSeeder::PASSWORD`.

---

## Пользователи (по одному на каждую роль)

| Роль | Email | Имя | UUID | `is_admin` | `is_manager` |
|------|-------|-----|------|------------|--------------|
| **admin** | `admin@spaceofjoy.ru` | Admin | `b9df62af-...c259` | ✅ true | ❌ false |
| **guest** | `shaevmv@gmail.com` | Guest User | `b9df62af-...c260` | ❌ false | ❌ false |
| **manager** | `lesystoe@spaceofjoy.ru` | Manager | `b9df62af-...c261` | ❌ false | ✅ true |
| **seller** | `seller@staging.local` | Seller | `b9df62af-...c262` | ❌ false | ❌ false |
| **pusher** | `pusher@staging.local` | Pusher (Friendly) | `b9df62af-...c263` | ❌ false | ❌ false |
| **curator** | `curator@staging.local` | Curator | `b9df62af-...c264` | ❌ false | ❌ false |
| **pusher_curator** | `pushcurator@staging.local` | Pusher + Curator | `b9df62af-...c265` | ❌ false | ❌ false |

Полные UUID'ы — в `UserSeeder::ID_FOR_*_UUID` константах.

---

## Что может каждая роль (краткая сводка)

См. подробности в `.claude/docs/BUSINESS_RULES.md §7` + `.claude/docs/API.md` (раздел «Сводная таблица доступа»).

| Роль | Главное что доступно |
|------|----------------------|
| **admin** | Всё. CRUD типов билетов, опций, локаций, промокодов, пользователей. Просмотр всех заказов. Изменение статусов любых заказов. |
| **guest** | Покупка билетов, заполнение анкеты, просмотр своих заказов, получение invite link |
| **manager** | Анкеты (просмотр/одобрение), заказы-списки (одобрение/отмена) |
| **seller** | Список заказов с фильтром, смена статуса live-билетов (`getList`, `toChangeStatus`) |
| **pusher** | Создание Friendly-заказов, список своих Friendly, смена статуса (`createFriendly`, `getListForFriendly`, `toChangeStatus`) |
| **curator** | Создание заказов-списков, просмотр своих (`createList`, `getCuratorList`) |
| **pusher_curator** | Мульти-роль: pusher + curator одновременно |

---

## Авторизация на staging

```bash
# Endpoint
POST https://api.staging.spaceofjoy.ru/api/login

# Body (JSON)
{
  "email": "admin@spaceofjoy.ru",
  "password": "password"
}

# Response — JWT в `authorisation.token`
```

Из браузера — фронт сам делает запрос с формы https://staging.spaceofjoy.ru.

---

## Idempotency

`UserSeeder` использует `User::updateOrCreate(['id' => UUID])` — **идемпотентен**, безопасно перезапускать сколько угодно раз. Не дублирует, не теряет данные кроме password (он каждый раз перехеширован, но значение то же).

Запуск:
```bash
# Локально:
docker exec -it php-solarSysto php artisan db:seed --class=UserSeeder

# Через workflow staging (полный fresh):
gh workflow run deploy-staging.yml -f seed=fresh
```

---

## Расширение

Чтобы добавить нового тестового юзера:

1. Сгенерируй фиксированный UUID (например, `b9df62af-252a-4890-afd7-73c2a356c2XX`)
2. Добавь `public const ID_FOR_*_UUID = '...'` в `UserSeeder`
3. Добавь `EMAIL_*` константу
4. В методе `run()` — `$this->seedUser([...])` с нужными `role`/`is_admin`/`is_manager`
5. **Обнови эту таблицу выше**
6. Прогон `--class=UserSeeder` идемпотентен — новый юзер просто добавится

---

## История изменений

| Дата | Изменение |
|------|-----------|
| 2026-06-01 | Создан документ. UserSeeder переделан под идемпотентность + по 1 юзеру на каждую из 7 ролей |
