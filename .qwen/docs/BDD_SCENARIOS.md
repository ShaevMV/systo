# BDD Acceptance сценарии для Codeception + WebDriver

> Этот файл содержит описание всех пользовательских сценариев для Acceptance-тестирования через Codeception Cest.
> Фронтенд: `http://org.tickets.loc/` | Бэкенд API: `http://api.tickets.loc/`

---

## Раздел 1: Аутентификация

### 1.1 checkUserCanRegister

**URL:** `/regGydhf`

**Шаги:**
1. `$I->amOnPage('/regGydhf')`
2. `$I->seeElement('#reg-form')`
3. `$I->seeElement('input[name="email"]')`
4. `$I->fillField('input[name="email"]', 'bdd-test-' . time() . '@example.com')`
5. `$I->fillField('#yourPhone', 'Тест Тестов')`
6. `$I->fillField('input[name="phone"]', '+79001234567')`
7. `$I->fillField('#yourCity', 'Москва')`
8. `$I->fillField('#yourPassword', 'TestPassword123')`
9. `$I->fillField('input[name="password_confirmation"]', 'TestPassword123')`
10. `$I->click('Зарегистрировать аккаунт')`
11. `$I->wait(2)` — AJAX запрос регистрации

**Ожидаемые DOM элементы:**
- После успешной регистрации: редирект на `/` или `/hfjlsd65t4732`
- `$I->seeInCurrentUrl('/hfjlsd65t4732')` или `$I->seeInCurrentUrl('/')`
- В localStorage должен быть токен (проверить через JS):
  `$I->executeJS('return localStorage["user.token"] !== undefined')` → `true`

**Проверка контента:**
- Заголовок страницы: "Регистрация аккаунта"
- Кнопка: "Зарегистрировать аккаунт"

**Негативный сценарий — регистрация с пустыми полями:**
1. `$I->amOnPage('/regGydhf')`
2. `$I->click('Зарегистрировать аккаунт')`
3. `$I->wait(1)`
4. `$I->seeElement('.invalid-feedback')` — ошибки валидации

**Негативный сценарий — несовпадение паролей:**
1. `$I->amOnPage('/regGydhf')`
2. `$I->fillField('#yourEmail', 'test@example.com')`
3. `$I->fillField('#yourPassword', 'Password123')`
4. `$I->fillField('input[name="password_confirmation"]', 'DifferentPassword')`
5. `$I->click('Зарегистрировать аккаунт')`
6. `$I->wait(1)`
7. `$I->seeElement('.invalid-feedback')`

---

### 1.2 checkUserCanLogin

**URL:** `/login`

**Шаги:**
1. `$I->amOnPage('/login')`
2. `$I->seeElement('#contact-form')`
3. `$I->see('Авторизация')`
4. `$I->fillField('#form_email', 'admin@example.com')`
5. `$I->fillField('#form_password', 'correct-password')`
6. `$I->click('Авторизоваться')`
7. `$I->wait(2)` — AJAX + редирект

**Ожидаемые изменения URL:**
- `$I->seeInCurrentUrl('/hfjlsd65t4732')` или `/orders` (для admin)

**Ожидаемые DOM элементы:**
- `$I->seeElement('#main-form')` — главная страница покупки билета
- `$I->seeElement('h1')` — "Форма подтверждения добровольного оргвзноса"

**Проверка контента:**
- На странице покупки билета: "ШАГ 1. Введи свои контактные данные"

**Негативный сценарий — неверный пароль:**
1. `$I->amOnPage('/login')`
2. `$I->fillField('#form_email', 'admin@example.com')`
3. `$I->fillField('#form_password', 'wrong-password')`
4. `$I->click('Авторизоваться')`
5. `$I->wait(1)`
6. `$I->seeElement('.text-muted')` — сообщение об ошибке
7. `$I->seeInCurrentUrl('/login')` — остаёмся на странице логина

---

### 1.3 checkUserCanRecoverPassword

**URL:** `/forgotPassword`

**Шаги:**
1. `$I->amOnPage('/forgotPassword')`
2. `$I->see('Забыли пароль?')`
3. `$I->fillField('#form_email', 'admin@example.com')`
4. `$I->click('Напомнить пароль')`
5. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.messager')` — сообщение об отправке

**Проверка контента:**
- `$I->see('На указанный е-мейл отправлена ссылка для восстановления пароля')` или аналогичное сообщение

---

### 1.4 checkLoginRedirectsToPreviousPage

**URL:** `/login?nextUrl=/orders`

**Шаги:**
1. `$I->amOnPage('/login?nextUrl=/orders')`
2. `$I->fillField('#form_email', 'admin@example.com')`
3. `$I->fillField('#form_password', 'correct-password')`
4. `$I->click('Авторизоваться')`
5. `$I->wait(2)`

**Ожидаемые изменения URL:**
- `$I->seeInCurrentUrl('/orders')`

---

## Раздел 2: Покупка билета

### 2.1 checkGuestCanSeeBuyTicketForm

**URL:** `/hfjlsd65t4732`

**Шаги:**
1. `$I->amOnPage('/hfjlsd65t4732')`
2. `$I->wait(2)` — загрузка данных фестиваля

**Ожидаемые DOM элементы:**
- `$I->seeElement('#main-form')`
- `$I->seeElement('h1')` — "Форма подтверждения добровольного оргвзноса"
- `$I->seeElement('#form_email')`
- `$I->seeElement('#form_phone')`
- `$I->seeElement('#enter-guests')`
- `$I->seeElement('#org-type')`
- `$I->seeElement('#form_promo_cod')`
- `$I->seeElement('#idBuy')`
- `$I->seeElement('#check-check')`
- `$I->seeElement('.reg-btn')` — "Зарегистрировать оргвзнос"

**Проверка контента:**
- `$I->see('ШАГ 1.')`
- `$I->see('ШАГ 2.')`
- `$I->see('ШАГ 3.')`
- `$I->see('Тип оргвзноса')`
- `$I->seeElement('.ticket-choice')` — типы билетов загружены

---

### 2.2 checkPromoCodeValidation

**URL:** `/hfjlsd65t4732`

**Шаги:**
1. `$I->amOnPage('/hfjlsd65t4732')`
2. `$I->wait(2)` — загрузка типов билетов
3. `$I->fillField('#form_promo_cod', 'INVALID_PROMO_CODE')`
4. `$I->click('#basic-addon1')` — клик по кнопке проверки промокода
5. `$I->wait(2)` — AJAX запрос

**Ожидаемые DOM элементы:**
- `$I->seeElement('.id-info')` — сообщение о промокоде

**Проверка контента (негативный):**
- `$I->see('Промокод не найден')` или аналогичное сообщение об ошибке

**Позитивный сценарий (валидный промокод):**
1. `$I->fillField('#form_promo_cod', 'VALID_PROMO')`
2. `$I->click('#basic-addon1')`
3. `$I->wait(2)`
4. `$I->seeElement('.id-info')`
5. `$I->see('Скидка')` или аналогичное сообщение об успехе
6. `$I->seeElement('.itog-row')` — итоговая строка с новой ценой

---

### 2.3 checkGuestCanBeAdded

**URL:** `/hfjlsd65t4732`

**Шаги:**
1. `$I->amOnPage('/hfjlsd65t4732')`
2. `$I->wait(2)`
3. `$I->fillField('#newGuest', 'Иван Иванов')`
4. `$I->fillField('#newEmailGuest', 'ivan@example.com')`
5. `$I->click('#basic-addon1')` — кнопка "Добавить"
6. `$I->wait(1)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#adding-guests')` — список добавленных гостей
- `$I->seeElement('[readonly]')` — readonly поля с данными гостя
- `$I->see('Иван Иванов')` — имя гостя в списке

**Проверка контента:**
- `$I->seeElement('#count-label')` — "Общее количество гостей"
- `$I->see('2')` — счётчик гостей (основной + 1)

---

### 2.4 checkTicketTypeSelection

**URL:** `/hfjlsd65t4732`

**Шаги:**
1. `$I->amOnPage('/hfjlsd65t4732')`
2. `$I->wait(2)`
3. `$I->seeElement('.ticket-choice')` — типы билетов загружены
4. `$I->click('.ticket-choice input[type="radio"]')` — выбор первого типа
5. `$I->wait(1)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.ticket-choice input[type="radio"]:checked')` — тип выбран
- `$I->seeElement('#enter-guests')` — блок гостей активен

---

### 2.5 checkOrderCreation

**URL:** `/hfjlsd65t4732`

**Шаги:**
1. `$I->amOnPage('/hfjlsd65t4732')`
2. `$I->wait(2)`
3. `$I->fillField('#form_email', 'test-' . time() . '@example.com')`
4. `$I->fillField('#form_phone', '+79001234567')`
5. `$I->fillField('input[name="city"]', 'Москва')`
6. `$I->click('.ticket-choice input[type="radio"]')` — выбор типа билета
7. `$I->wait(1)`
8. `$I->fillField('#newGuest', 'Тест Гость')`
9. `$I->fillField('#newEmailGuest', 'guest@example.com')`
10. `$I->click('#basic-addon1')` — добавить гостя
11. `$I->wait(1)`
12. `$I->click('.payment-choice input[type="radio"]')` — выбор способа оплаты
13. `$I->fillField('#idBuy', '1234')` — идентификатор платежа
14. `$I->fillField('input[name="date"]', '11 апреля в 12.00')`
15. `$I->fillField('.order-text', 'Тестовый комментарий')`
16. `$I->click('#defaultCheck1')` — чекбокс согласия
17. `$I->wait(1)`
18. `$I->click('.reg-btn')` — кнопка "Зарегистрировать оргвзнос"
19. `$I->wait(3)` — AJAX запрос

**Ожидаемые DOM элементы:**
- `$I->seeElement('.modal-body')` — модальное окно успеха
- `$I->see('Мы удачно зарегистрировали ваш заказ')` или аналогичное сообщение

**Ожидаемые изменения URL:**
- Возможна переадресация на `/myOrders`

---

## Раздел 3: Заказы

### 3.1 checkAdminCanSeeOrderList

**URL:** `/orders`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/orders')`
2. `$I->wait(2)` — загрузка списка заказов

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — блок фильтрации
- `$I->seeElement('#filter-results')` — блок результатов
- `$I->seeElement('table.table-hover')` — таблица заказов
- `$I->seeElement('th')` — заголовки колонок: "№ заказа", "Email", "Гости", "Тип оргвзноса" и т.д.
- `$I->seeElement('select.form-select')` — фильтры

**Проверка контента:**
- `$I->see('Заказы пользователей')`
- `$I->see('Фильтр')`

---

### 3.2 checkOrderFiltering

**URL:** `/orders`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/orders')`
2. `$I->wait(2)`
3. `$I->fillField('#validationDefaultUsername', 'admin@example.com')` — email в фильтре
4. `$I->selectOption('select.form-select', 'paid')` — статус заказа (если есть option)
5. `$I->click('Отправить')`
6. `$I->wait(2)` — AJAX запрос

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter-results')` — результаты обновлены
- `$I->seeElement('table.table-hover')` — таблица с отфильтрованными результатами

**Проверка контента:**
- В таблице должны быть заказы с email "admin@example.com"

---

### 3.3 checkAdminCanViewOrderDetails

**URL:** `/order/{id}`

**Предусловие:** Пользователь авторизован, существует заказ с известным ID

**Шаги:**
1. `$I->amOnPage('/order/' . $orderId)`
2. `$I->wait(2)` — загрузка деталей заказа

**Ожидаемые DOM элементы:**
- `$I->seeElement('.card-body')`
- `$I->seeElement('table.table-hover')` — таблица деталей
- `$I->seeElement('.download-title')` — "Скачать электронные билеты с qr-кодом"
- `$I->seeElement('.downloader')` — кнопки скачивания билетов

**Проверка контента:**
- `$I->see('Заказ #')`
- `$I->seeElement('td')` — с данными: "Гости", "Тип оплаты", "Стоимость", "Статус"

---

### 3.4 checkAdminCanDownloadTicketPdf

**URL:** `/order/{id}`

**Предусловие:** Заказ в статусе `paid`

**Шаги:**
1. `$I->amOnPage('/order/' . $orderId)`
2. `$I->wait(2)`
3. `$I->seeElement('.downloader')` — кнопки скачивания
4. `$I->click('.downloader')` — клик по кнопке "Скачать билет"
5. `$I->wait(2)`

**Ожидаемые изменения:**
- Открывается новая вкладка с PDF (проверить через JS):
  `$I->executeJS('return window.open')` — вызывается

**Ожидаемые DOM элементы:**
- `$I->seeElement('.download-title')` — заголовок "Скачать электронные билеты"
- `$I->seeElement('.qr-text')` — текст о доступности билетов

---

### 3.5 checkAdminCanChangeOrderStatus

**URL:** `/orders`

**Предусловие:** Пользователь авторизован с ролью admin, есть заказ в статусе `new`

**Шаги:**
1. `$I->amOnPage('/orders')`
2. `$I->wait(2)`
3. `$I->click('.btn-danger.dropdown-toggle')` — открыть dropdown смены статуса
4. `$I->seeElement('.dropdown-menu')`
5. `$I->click('.dropdown-item.btn-link')` — выбрать статус (например "Оплатить")
6. `$I->wait(2)` — AJAX запрос

**Ожидаемые DOM элементы:**
- `$I->seeElement('.table-hover')` — таблица обновлена
- Цвет строки изменился (проверить через style):
  `$I->seeElement('td[style*="color: #1e871c"]')` — статус `paid` зелёный

---

### 3.6 checkAdminCanChangeStatusWithComment

**URL:** `/orders`

**Предусловие:** Заказ в статусе `new`

**Шаги:**
1. `$I->amOnPage('/orders')`
2. `$I->wait(2)`
3. `$I->click('.btn-danger.dropdown-toggle')`
4. `$I->click('.dropdown-item')` — статус "Возникли трудности"
5. `$I->wait(1)`
6. `$I->seeElement('#exampleModal')` — модальное окно
7. `$I->seeElement('.modal-body')`
8. `$I->fillField('.modal-body textarea', 'Тестовый комментарий')`
9. `$I->click('Сменить статус')`
10. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->dontSeeElement('#exampleModal')` — модальное окно закрыто (или класс `hide`)
- Таблица обновлена с новым статусом

---

### 3.7 checkAdminCanChangeStatusWithLiveTicket

**URL:** `/orders`

**Предусловие:** Заказ в статусе `new`, тип билета — живой

**Шаги:**
1. `$I->amOnPage('/orders')`
2. `$I->wait(2)`
3. `$I->click('.btn-danger.dropdown-toggle')`
4. `$I->click('.dropdown-item')` — статус "Выдать живые билеты"
5. `$I->wait(1)`
6. `$I->seeElement('#exampleModalLive')` — модальное окно живых билетов
7. `$I->seeElement('.modal-body table')` — таблица гостей
8. `$I->fillField('.modal-body input[type="text"]', '12345')` — номер билета
9. `$I->click('Сменить статус')`
10. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->dontSeeElement('#exampleModalLive')` — модальное окно закрыто

---

## Раздел 4: Анкеты

### 4.1 checkAdminCanSeeQuestionnaireList

**URL:** `/questionnaires/`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/questionnaires/')`
2. `$I->wait(2)` — загрузка списка анкет

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — блок фильтрации
- `$I->seeElement('table.table-hover')` — таблица анкет
- `$I->seeElement('th')` — заголовки: "№", "Email", "Телефон", "Telegram", "VK", "Статус"

**Проверка контента:**
- `$I->see('Анкеты пользователей')`

---

### 4.2 checkQuestionnaireFilterHasQuestionnaireTypeSelect

**URL:** `/questionnaires/`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/questionnaires/')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#validationDefaultQuestionnaireType')` — select с типами анкет
- `$I->seeElement('#validationDefaultQuestionnaireType option')` — опции в select

**Проверка контента:**
- `$I->see('Тип анкеты')` — label
- `$I->see('Все типы')` — первая опция

---

### 4.3 checkQuestionnaireFilterByType

**URL:** `/questionnaires/`

**Предусловие:** Пользователь авторизован, существуют анкеты разных типов

**Шаги:**
1. `$I->amOnPage('/questionnaires/')`
2. `$I->wait(2)`
3. `$I->selectOption('#validationDefaultQuestionnaireType', $questionnaireTypeId)`
4. `$I->click('Отправить')`
5. `$I->wait(2)` — AJAX запрос

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter-results')` — результаты обновлены
- `$I->seeElement('table.table-hover tbody tr')` — строки с анкетами

**Проверка контента:**
- Все анкеты в таблице должны иметь выбранный тип (проверить через колонку "Тип анкеты")

---

### 4.4 checkAdminCanApproveQuestionnaire

**URL:** `/questionnaires/`

**Предусловие:** Существует анкета в статусе `NEW`

**Шаги:**
1. `$I->amOnPage('/questionnaires/')`
2. `$I->wait(2)`
3. `$I->click('.btn-danger.dropdown-toggle')` — dropdown у анкеты
4. `$I->seeElement('.dropdown-menu')`
5. `$I->click('.dropdown-item')` — "Подтвердить"
6. `$I->wait(1)`
7. `$I->seeInPopup('Анкета одобрена')` — alert с подтверждением
8. `$I->acceptPopup()`
9. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('table.table-hover')` — таблица обновлена
- Статус анкеты изменился на "Одобрена"

---

### 4.5 checkAdminCanFillGuestQuestionnaire

**URL:** `/questionnaire/guest/{orderId}/{ticketId}`

**Предусловие:** Существует заказ с билетами

**Шаги:**
1. `$I->amOnPage('/questionnaire/guest/' . $orderId . '/' . $ticketId)`
2. `$I->wait(2)` — загрузка типа анкеты
3. `$I->seeElement('#main-quest')`
4. `$I->seeElement('#check-check')` — чекбокс согласия
5. Заполнить динамические поля (зависит от questionnaire_type):
   - `$I->fillField('input[name="phone"]', '+79001234567')`
   - `$I->fillField('input[name="telegram"]', 'test_user')`
   - `$I->fillField('input[name="agy"]', '25')`
6. `$I->click('#defaultCheck1')` — чекбокс согласия
7. `$I->click('.reg-btn')` — "Зарегистрировать анкету"
8. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#exampleModal')` — модальное окно успеха
- `$I->see('Спасибо большое, ваши анкетные данные зарегистрированы')`

---

### 4.6 checkNewUserQuestionnaire

**URL:** `/questionnaire/newUser`

**Шаги:**
1. `$I->amOnPage('/questionnaire/newUser')`
2. `$I->wait(2)` — загрузка типа анкеты `new_user`
3. `$I->seeElement('#main-quest')`
4. Заполнить поля:
   - `$I->fillField('input[name="telegram"]', 'new_user_test')`
   - `$I->fillField('input[name="agy"]', '30')`
5. `$I->click('#defaultCheck1')`
6. `$I->click('.reg-btn')`
7. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#exampleModal')` — модальное окно успеха

---

## Раздел 5: Админка типов билетов

### 5.1 checkAdminCanSeeTicketTypeList

**URL:** `/ticketType/list`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/ticketType/list')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — фильтр
- `$I->seeElement('#filter-results')` — результаты
- `$I->seeElement('table.table-hover')` — таблица типов билетов
- `$I->seeElement('th')` — заголовки: "Имя", "Стоимость", "Лимит на кол-во", "Сорт", "Фестиваль"

**Проверка контента:**
- `$I->see('Типы оргвзносов')`

---

### 5.2 checkAdminCanCreateTicketType

**URL:** `/ticketType/`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/ticketType/')`
2. `$I->wait(2)`
3. `$I->seeElement('#company')` — поле названия (первое input)
4. `$I->fillField('input[name="company"]', 'Тестовый тип билета')`
5. `$I->fillField('input[name="price"]', '5000')`
6. `$I->fillField('input[name="groupLimit"]', '10')`
7. `$I->selectOption('select.form-select', 'false')` — для живых билетов
8. `$I->selectOption('select.form-select', 'true')` — активность
9. `$I->click('Сохранить изменения')`
10. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.row.messager')` — сообщение об успехе
- `$I->see('Тип билета создан')` или аналогичное сообщение

---

### 5.3 checkAdminCanEditTicketType

**URL:** `/ticketType/{id}`

**Предусловие:** Существует тип билета с известным ID

**Шаги:**
1. `$I->amOnPage('/ticketType/' . $ticketTypeId)`
2. `$I->wait(2)`
3. `$I->seeElement('#company')`
4. `$I->seeInField('input[name="company"]', 'Существующее название')`
5. `$I->fillField('input[name="company"]', 'Обновлённый тип билета')`
6. `$I->click('Сохранить изменения')`
7. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.row.messager')` — сообщение об успехе

---

### 5.4 checkAdminCanDeleteTicketType

**URL:** `/ticketType/list`

**Предусловие:** Существует тип билета который можно удалить

**Шаги:**
1. `$I->amOnPage('/ticketType/list')`
2. `$I->wait(2)`
3. `$I->click('td span[style*="cursor: pointer"]')` — иконка удаления 🗑️
4. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- Строка с типом билета исчезла из таблицы

---

## Раздел 6: Админка пользователей

### 6.1 checkAdminCanSeeAccountList

**URL:** `/account/list`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/account/list')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — фильтр
- `$I->seeElement('#filter-results')` — результаты
- `$I->seeElement('table.table-hover')` — таблица пользователей
- `$I->seeElement('th')` — заголовки: "email", "Телефон", "город", "роль"

**Проверка контента:**
- `$I->see('НАШИ ЛЮБИМЫе ПОЛЬЗОВАТЕЛИ')`

---

### 6.2 checkAdminCanFilterAccountsByEmail

**URL:** `/account/list`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/account/list')`
2. `$I->wait(2)`
3. `$I->fillField('#validationDefaultUsername', 'admin@example.com')`
4. `$I->click('Отправить')`
5. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter-results')` — результаты обновлены
- В таблице только пользователи с email "admin@example.com"

---

### 6.3 checkAdminCanChangeUserRole

**URL:** `/account/list`

**Предусловие:** Существует пользователь с ролью `guest`

**Шаги:**
1. `$I->amOnPage('/account/list')`
2. `$I->wait(2)`
3. `$I->click('.btn-danger.dropdown-toggle')` — dropdown смены роли
4. `$I->seeElement('.dropdown-menu')`
5. `$I->seeElement('.dropdown-item')` — опции: "Админ", "Продовец живых билетов" и т.д.
6. `$I->click('.dropdown-item')` — выбрать роль (например "seller")
7. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('table.table-hover')` — таблица обновлена
- В колонке "роль" отображается новая роль

**Проверка контента:**
- `$I->see('Продовец живых билетов')` или аналогичное название роли

---

### 6.4 checkAdminCanDeleteAccount

**URL:** `/account/list`

**Шаги:**
1. `$I->amOnPage('/account/list')`
2. `$I->wait(2)`
3. `$I->click('td span[style*="cursor: pointer"]')` — иконка удаления 🗑️
4. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- Строка пользователя исчезла из таблицы

---

## Раздел 7: Промокоды (админка)

### 7.1 checkAdminCanSeePromoCodeList

**URL:** `/promo-codes`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/promo-codes')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — фильтр (если есть)
- `$I->seeElement('table.table-hover')` — таблица промокодов
- `$I->seeElement('th')` — заголовки: "Имя", "Скидка", "Тип", "Лимит"

---

### 7.2 checkAdminCanCreatePromoCode

**URL:** `/promoCode/`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/promoCode/')`
2. `$I->wait(2)`
3. `$I->seeElement('input[name="name"]')` — поле имени
4. `$I->fillField('input[name="name"]', 'TEST_PROMO')`
5. `$I->fillField('input[name="discount"]', '10')`
6. `$I->selectOption('select[name="is_percent"]', 'true')` — процентная скидка
7. `$I->selectOption('select[name="active"]', 'true')` — активен
8. `$I->click('Сохранить')`
9. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.row.messager')` — сообщение об успехе
- `$I->seeInCurrentUrl('/promo-codes')` — редирект на список

---

## Раздел 8: Профиль пользователя

### 8.1 checkUserCanViewProfile

**URL:** `/profile`

**Предусловие:** Пользователь авторизован

**Шаги:**
1. `$I->amOnPage('/profile')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#profile-form')` или аналогичный контейнер
- `$I->seeElement('input[name="email"]')` — email
- `$I->seeElement('input[name="phone"]')` — телефон
- `$I->seeElement('input[name="city"]')` — город

---

### 8.2 checkUserCanEditProfile

**URL:** `/profile`

**Шаги:**
1. `$I->amOnPage('/profile')`
2. `$I->wait(2)`
3. `$I->fillField('input[name="name"]', 'Новое Имя')`
4. `$I->fillField('input[name="phone"]', '+79009876543')`
5. `$I->click('Сохранить')`
6. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.messager')` — сообщение об успехе
- `$I->see('Данные пользователя изменены')`

---

### 8.3 checkUserCanChangePassword

**URL:** `/profile`

**Шаги:**
1. `$I->amOnPage('/profile')`
2. `$I->wait(2)`
3. `$I->fillField('input[name="old_password"]', 'current-password')`
4. `$I->fillField('input[name="password"]', 'NewPassword123')`
5. `$I->fillField('input[name="password_confirmation"]', 'NewPassword123')`
6. `$I->click('Сменить пароль')`
7. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.messager')` — сообщение об успехе
- `$I->see('Пароль сменён')`

---

## Раздел 9: Приглашения (Invite Links)

### 9.1 checkUserCanGetInviteLink

**URL:** `/invite`

**Предусловие:** Пользователь авторизован, анкета одобрена

**Шаги:**
1. `$I->amOnPage('/invite')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.invite-link')` или аналогичный элемент со ссылкой
- `$I->seeElement('a[href*="/invite/"]')` — ссылка-приглашение

---

### 9.2 checkInviteLinkValidation

**URL:** `/invite/newUser/{userId}`

**Шаги:**
1. `$I->amOnPage('/invite/newUser/' . $userId)`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#main-form')` — форма покупки билета
- `$I->seeInCurrentUrl('/hfjlsd65t4732')` или остаёмся на invite

---

## Раздел 10: Ошибки и граничные случаи

### 10.1 check404Page

**URL:** `/non-existent-page`

**Шаги:**
1. `$I->amOnPage('/non-existent-page')`
2. `$I->wait(1)`

**Ожидаемые DOM элементы:**
- `$I->see('404')` или "Страница не найдена"
- `$I->seeElement('.error-page')` или аналогичный контейнер

---

### 10.2 checkGuestCannotAccessAdminPages

**URL:** `/orders`

**Шаги:**
1. `$I->amOnPage('/orders')`
2. `$I->wait(1)`

**Ожидаемые изменения URL:**
- `$I->seeInCurrentUrl('/login')` — редирект на страницу входа

---

### 10.3 checkLoggedInUserCannotAccessLogin

**URL:** `/login`

**Предусловие:** Пользователь уже авторизован

**Шаги:**
1. `$I->amOnPage('/login')`
2. `$I->wait(1)`

**Ожидаемые изменения URL:**
- `$I->seeInCurrentUrl('/')` — редирект на главную

---

## Раздел 11: Friendly заказы (pusher)

### 11.1 checkPusherCanSeeFriendlyOrderList

**URL:** `/ordersFriendly`

**Предусловие:** Пользователь авторизован с ролью `pusher`

**Шаги:**
1. `$I->amOnPage('/ordersFriendly')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — фильтр
- `$I->seeElement('#filter-results')` — результаты
- `$I->seeElement('table.table-hover')` — таблица friendly заказов

---

### 11.2 checkPusherCanCreateFriendlyOrder

**URL:** `/frendlyOrder`

**Предусловие:** Пользователь авторизован с ролью `pusher`

**Шаги:**
1. `$I->amOnPage('/frendlyOrder')`
2. `$I->wait(2)`
3. Заполнить форму заказа (аналогично обычной покупке)
4. `$I->click('.reg-btn')`
5. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.modal-body')` — модальное окно успеха

---

## Раздел 12: Живые билеты

### 12.1 checkLiveTicketNumberDecryption

**URL:** `/ticket/live/{cash}`

**Шаги:**
1. `$I->amOnPage('/ticket/live/12345')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.live-ticket-result')` или аналогичный контейнер
- `$I->see('success')` или аналогичное сообщение об успехе

---

## Раздел 13: Типы анкет (админка)

### 13.1 checkAdminCanSeeQuestionnaireTypeList

**URL:** `/questionnaireType/list`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/questionnaireType/list')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — фильтр
- `$I->seeElement('#filter-results')` — результаты
- `$I->seeElement('table.table-hover')` — таблица типов анкет

---

### 13.2 checkAdminCanCreateQuestionnaireType

**URL:** `/questionnaireType/`

**Шаги:**
1. `$I->amOnPage('/questionnaireType/')`
2. `$I->wait(2)`
3. `$I->seeElement('input[name="name"]')`
4. `$I->fillField('input[name="name"]', 'Тестовый тип анкеты')`
5. `$I->fillField('input[name="code"]', 'test_questionnaire')`
6. `$I->click('Сохранить')`
7. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.row.messager')` — сообщение об успехе

---

## Раздел 14: Способы оплаты (админка)

### 14.1 checkAdminCanSeeTypesOfPaymentList

**URL:** `/typesOfPayment/list`

**Предусловие:** Пользователь авторизован с ролью admin

**Шаги:**
1. `$I->amOnPage('/typesOfPayment/list')`
2. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('#filter')` — фильтр
- `$I->seeElement('#filter-results')` — результаты
- `$I->seeElement('table.table-hover')` — таблица способов оплаты

---

### 14.2 checkAdminCanCreateTypeOfPayment

**URL:** `/typesOfPayment/`

**Шаги:**
1. `$I->amOnPage('/typesOfPayment/')`
2. `$I->wait(2)`
3. `$I->seeElement('input[name="name"]')`
4. `$I->fillField('input[name="name"]', 'Тестовый способ оплаты')`
5. `$I->click('Сохранить')`
6. `$I->wait(2)`

**Ожидаемые DOM элементы:**
- `$I->seeElement('.row.messager')` — сообщение об успехе

---

## Сводная таблица сценариев

| # | Имя теста | Раздел | URL | Позитивный | Негативный |
|---|-----------|--------|-----|-----------|------------|
| 1 | `checkUserCanRegister` | Аутентификация | `/regGydhf` | ✅ | ✅ |
| 2 | `checkUserCanLogin` | Аутентификация | `/login` | ✅ | ✅ |
| 3 | `checkUserCanRecoverPassword` | Аутентификация | `/forgotPassword` | ✅ | |
| 4 | `checkLoginRedirectsToPreviousPage` | Аутентификация | `/login?nextUrl=/orders` | ✅ | |
| 5 | `checkGuestCanSeeBuyTicketForm` | Покупка билета | `/hfjlsd65t4732` | ✅ | |
| 6 | `checkPromoCodeValidation` | Покупка билета | `/hfjlsd65t4732` | ✅ | ✅ |
| 7 | `checkGuestCanBeAdded` | Покупка билета | `/hfjlsd65t4732` | ✅ | |
| 8 | `checkTicketTypeSelection` | Покупка билета | `/hfjlsd65t4732` | ✅ | |
| 9 | `checkOrderCreation` | Покупка билета | `/hfjlsd65t4732` | ✅ | |
| 10 | `checkAdminCanSeeOrderList` | Заказы | `/orders` | ✅ | |
| 11 | `checkOrderFiltering` | Заказы | `/orders` | ✅ | |
| 12 | `checkAdminCanViewOrderDetails` | Заказы | `/order/{id}` | ✅ | |
| 13 | `checkAdminCanDownloadTicketPdf` | Заказы | `/order/{id}` | ✅ | |
| 14 | `checkAdminCanChangeOrderStatus` | Заказы | `/orders` | ✅ | |
| 15 | `checkAdminCanChangeStatusWithComment` | Заказы | `/orders` | ✅ | |
| 16 | `checkAdminCanChangeStatusWithLiveTicket` | Заказы | `/orders` | ✅ | |
| 17 | `checkAdminCanSeeQuestionnaireList` | Анкеты | `/questionnaires/` | ✅ | |
| 18 | `checkQuestionnaireFilterHasQuestionnaireTypeSelect` | Анкеты | `/questionnaires/` | ✅ | |
| 19 | `checkQuestionnaireFilterByType` | Анкеты | `/questionnaires/` | ✅ | |
| 20 | `checkAdminCanApproveQuestionnaire` | Анкеты | `/questionnaires/` | ✅ | |
| 21 | `checkAdminCanFillGuestQuestionnaire` | Анкеты | `/questionnaire/guest/{id}/{id}` | ✅ | |
| 22 | `checkNewUserQuestionnaire` | Анкеты | `/questionnaire/newUser` | ✅ | |
| 23 | `checkAdminCanSeeTicketTypeList` | Типы билетов | `/ticketType/list` | ✅ | |
| 24 | `checkAdminCanCreateTicketType` | Типы билетов | `/ticketType/` | ✅ | |
| 25 | `checkAdminCanEditTicketType` | Типы билетов | `/ticketType/{id}` | ✅ | |
| 26 | `checkAdminCanDeleteTicketType` | Типы билетов | `/ticketType/list` | ✅ | |
| 27 | `checkAdminCanSeeAccountList` | Пользователи | `/account/list` | ✅ | |
| 28 | `checkAdminCanFilterAccountsByEmail` | Пользователи | `/account/list` | ✅ | |
| 29 | `checkAdminCanChangeUserRole` | Пользователи | `/account/list` | ✅ | |
| 30 | `checkAdminCanDeleteAccount` | Пользователи | `/account/list` | ✅ | |
| 31 | `checkAdminCanSeePromoCodeList` | Промокоды | `/promo-codes` | ✅ | |
| 32 | `checkAdminCanCreatePromoCode` | Промокоды | `/promoCode/` | ✅ | |
| 33 | `checkUserCanViewProfile` | Профиль | `/profile` | ✅ | |
| 34 | `checkUserCanEditProfile` | Профиль | `/profile` | ✅ | |
| 35 | `checkUserCanChangePassword` | Профиль | `/profile` | ✅ | |
| 36 | `checkUserCanGetInviteLink` | Приглашения | `/invite` | ✅ | |
| 37 | `checkInviteLinkValidation` | Приглашения | `/invite/newUser/{id}` | ✅ | |
| 38 | `check404Page` | Ошибки | `/non-existent-page` | | ✅ |
| 39 | `checkGuestCannotAccessAdminPages` | Ошибки | `/orders` | | ✅ |
| 40 | `checkLoggedInUserCannotAccessLogin` | Ошибки | `/login` | | ✅ |
| 41 | `checkPusherCanSeeFriendlyOrderList` | Friendly | `/ordersFriendly` | ✅ | |
| 42 | `checkPusherCanCreateFriendlyOrder` | Friendly | `/frendlyOrder` | ✅ | |
| 43 | `checkLiveTicketNumberDecryption` | Живые билеты | `/ticket/live/{cash}` | ✅ | |
| 44 | `checkAdminCanSeeQuestionnaireTypeList` | Типы анкет | `/questionnaireType/list` | ✅ | |
| 45 | `checkAdminCanCreateQuestionnaireType` | Типы анкет | `/questionnaireType/` | ✅ | |
| 46 | `checkAdminCanSeeTypesOfPaymentList` | Способы оплаты | `/typesOfPayment/list` | ✅ | |
| 47 | `checkAdminCanCreateTypeOfPayment` | Способы оплаты | `/typesOfPayment/` | ✅ | |

---

## Рекомендации для Auto-Tester Agent

### 1. Структура Cest файла

```php
// Backend/tests/Acceptance/UserAuthCest.php
class UserAuthCest
{
    public function checkUserCanLogin(AcceptanceTester $I)
    {
        // тест
    }

    public function checkUserCanLoginWithWrongPassword(AcceptanceTester $I)
    {
        // тест
    }
}
```

### 2. Хелперы для авторизации

```php
// В AcceptanceTester.php или отдельном хелпере
public function loginAsAdmin($email = 'admin@example.com', $password = 'password')
{
    $I = $this;
    $I->amOnPage('/login');
    $I->fillField('#form_email', $email);
    $I->fillField('#form_password', $password);
    $I->click('Авторизоваться');
    $I->wait(2);
}

public function loginAsPusher($email = 'pusher@example.com', $password = 'password')
{
    // аналогично
}
```

### 3. Данные для тестов

Использовать seeders для создания тестовых данных:
- Admin пользователь: `admin@example.com` / `password`
- Pusher пользователь: `pusher@example.com` / `password`
- Тестовый заказ с билетами
- Тестовые анкеты разных типов

### 4. WebDriverWait

После каждого AJAX запроса — `$I->wait(2)` минимум. Для загрузки данных фестиваля — `$I->wait(3)`.

### 5. Селекторы

Все селекторы должны быть максимально стабильными:
- Использовать `id` если есть (`#form_email`, `#company`)
- Использовать CSS классы компонентов (`.ticket-choice`, `.payment-choice`)
- Избегать хрупких селекторов вроде `div:nth-child(3) > span`

### 6. Модальные окна Bootstrap

Для работы с модальными окнами:
```php
$I->seeElement('#exampleModal');
$I->fillField('.modal-body textarea', 'comment');
$I->click('Сменить статус');
// Модальное окно закрывается — проверяем что его нет или скрыто
```

### 7. Vue.js реактивность

После изменения данных в Vue компонентах — ждать перерендеринга:
```php
$I->wait(1); // Vue обновляет DOM асинхронно
```
