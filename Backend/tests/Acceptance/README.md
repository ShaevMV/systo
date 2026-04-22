# Acceptance тесты для нового функционала

## Что тестится

Новый функционал добавленный в коммите `ede7de19`:
- Фильтр по типу анкеты на странице анкет
- Исправленные столбцы в таблице анкет
- Загрузка типов анкет из API

## Структура тестов

### 1. QuestionnaireFilterTestCest.php

**Назначение:** Полноценное тестирование фильтрации по типу анкеты

**Тесты:**
| Метод | Что проверяет | Статус |
|-------|--------------|--------|
| `checkQuestionnaireTypeFilterExists` | На странице есть select `#validationDefaultQuestionnaireType` | ⏳ Требует авторизации |
| `checkQuestionnaireTableColumns` | Таблица содержит столбцы "Тип анкеты", "Статус", "В клубе" | ⏳ Требует авторизации |
| `checkQuestionnaireTypesLoadedInFilter` | Select заполнен типами анкет из API | ⏳ Требует авторизации |
| `checkFilterByQuestionnaireTypeWorks` | Фильтрация работает (выбор → отправка → результат) | ⏳ Требует авторизации |

**Проблема:** Страница `/questionnaires/` требует авторизации (`requiresAuth: true`).

**Решение:** Нужно добавить хелпер авторизации или создать тестового пользователя с admin ролью.

### 2. QuestionnaireDomStructureTestCest.php

**Назначение:** Базовая проверка что Codeception + WebDriver работает

**Тесты:**
| Метод | Что проверяет | Статус |
|-------|--------------|--------|
| `checkBuyTicketPageLoads` | Главная страница загружается | ✅ PASSED |
| `checkFrontendAssetsLoaded` | CSS/JS файлы загружаются | ❌ (SPA не имеет статических script тегов) |

### 3. LoginTestCest.php

**Назначение:** Пример простого Acceptance теста

**Тесты:**
| Метод | Что проверяет | Статус |
|-------|--------------|--------|
| `checkLoginPageLoads` | Страница логина загружается | ✅ PASSED |
| `tryLoginWithInvalidCredentials` | Обработка невалидных данных | ✅ PASSED |

## Как запустить тесты

```bash
# Все Acceptance тесты
docker exec -it php-solarSysto php vendor/bin/codecept run acceptance

# Конкретный файл теста
docker exec -it php-solarSysto php vendor/bin/codecept run tests/Acceptance/QuestionnaireFilterTestCest.php

# С пошаговым выводом
docker exec -it php-solarSysto php vendor/bin/codecept run acceptance --steps

# С выводом в консоль + скриншоты
docker exec -it php-solarSysto php vendor/bin/codecept run acceptance -v
```

## Текущий статус

```
✅ Codeception установлен
✅ WebDriver (Selenium Chrome) настроен
✅ ChromeDriver контейнер запущен
✅ Базовые тесты работают (2/2 PASSED)
⏳ Тесты фильтрации требуют авторизации (4/4 требуют login)
```

## Что нужно сделать для полноценного тестирования

### 1. Добавить хелпер авторизации

Создать файл `Backend/tests/Support/Helper/Auth.php`:

```php
<?php

namespace Tests\Support\Helper;

use Codeception\Module;

class Auth extends Module
{
    /**
     * Login as admin user
     */
    public function loginAsAdmin(): void
    {
        $I = new \Tests\Support\AcceptanceTester($this->getModuleSequence());
        
        $I->amOnPage('/');
        $I->wait(2);
        
        // Заполнить форму логина
        // Нужно уточнить селекторы из LoginView.vue
        $I->fillField('input[type="email"]', 'admin@systo.ru'); // Email из UserSeeder
        $I->fillField('input[type="password"]', 'password'); // Пароль из UserSeeder
        $I->click('button[type="submit"]');
        $I->wait(3); // Ждём авторизацию и редирект
    }
    
    /**
     * Logout user
     */
    public function logout(): void
    {
        $I = new \Tests\Support\AcceptanceTester($this->getModuleSequence());
        
        // Кликнуть кнопку выхода
        $I->click('.logout-button');
        $I->wait(2);
    }
}
```

### 2. Обновить QuestionnaireFilterTestCest.php

Добавить `$I->loginAsAdmin()` перед каждым тестом:

```php
public function checkQuestionnaireTypeFilterExists(AcceptanceTester $I): void
{
    $I->loginAsAdmin(); // Авторизоваться
    $I->amOnPage('/questionnaires/');
    // ... остальной тест
}
```

### 3. Уточнить селекторы из LoginView.vue

Посмотреть какие input элементы используются в компоненте логина:
- `FrontEnd/src/views/Auth/LoginView.vue`
- Найти поля email/password и кнопку входа

## DOM селекторы для нового функционала

### Фильтр (QuestionnaireFilter.vue)

| Элемент | Селектор | Описание |
|---------|----------|----------|
| Контейнер фильтра | `#filter` | Обёртка всего фильтра |
| Select типа анкеты | `#validationDefaultQuestionnaireType` | Выбор типа анкеты |
| Опция "Все типы" | `#validationDefaultQuestionnaireType option[value=""]` | Дефолтная опция |
| Кнопка "Отправить" | `button.btn-primary` | Применить фильтр |
| Кнопка "Сбросить" | `button.btn-secondary` | Очистить фильтр |

### Таблица (QuestionnaireList.vue)

| Элемент | Селектор | Описание |
|---------|----------|----------|
| Таблица | `table.table-hover` | Таблица анкет |
| Заголовок "Тип анкеты" | `th:nth-child(7)` | 7-й столбец |
| Заголовок "Статус" | `th:nth-child(8)` | 8-й столбец |
| Заголовок "В клубе" | `th:nth-child(9)` | 9-й столбец |
| Строка таблицы | `table.table-hover tbody tr` | Одна анкета |
| Ячейка типа анкеты | `td:nth-child(7)` | Значение типа анкеты |

## Рекомендации

1. **Для тестов с авторизацией:** Создать отдельный helper модуль или использовать `$I->amLoggedInAs()` паттерн
2. **Для Vue.js реактивности:** Всегда добавлять `$I->wait(2)` после AJAX запросов
3. **Для скриншотов:** Использовать `$I->makeScreenshot('name.png')` для отладки
4. **Для отладки DOM:** Проверять HTML дамп в `tests/_output/*.fail.html`

## Связанные файлы

- `.qwen/docs/BDD_SCENARIOS.md` - 47 BDD сценариев для Auto-Tester Agent
- `Backend/tests/Acceptance.suite.yml` - конфигурация WebDriver
- `Backend/codeception.yml` - главный конфиг Codeception
- `docker-compose.yml` - сервис `chromedriver-solarSysto`
