-- Создание дополнительной БД `baza` для приложения Baza (аутентификация).
-- MySQL-контейнер автоматически создаёт только одну БД через MYSQL_DATABASE,
-- остальные БД должны быть созданы вручную или через init-скрипты.
--
-- Этот скрипт выполняется только при ПЕРВОМ запуске контейнера
-- (когда /var/lib/mysql пуст). Для уже инициализированных volume'ов
-- БД нужно создавать вручную через mysql exec.

CREATE DATABASE IF NOT EXISTS baza
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Выдать те же права на baza, что и для основной БД systo.
-- MYSQL_USER создаётся entrypoint'ом, ему уже выданы права на MYSQL_DATABASE (=systo).
-- Дополнительно выдаём на baza.
GRANT ALL PRIVILEGES ON baza.* TO 'systo'@'%';
FLUSH PRIVILEGES;
