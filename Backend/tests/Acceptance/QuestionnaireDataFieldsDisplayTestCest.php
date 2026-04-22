<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Acceptance тест для проверки отображения полей из JSON-колонки `data` в админке анкет.
 *
 * Проверяет:
 * - Авторизация как администратор работает
 * - Поля phone, telegram, vk, email, is_have_in_club из JSON `data`
 *   правильно отображаются в таблице анкет (не "—")
 * - Данные из `data` имеют приоритет над корневыми колонками
 *
 * ПРЕДВАРИТЕЛЬНО: запустить QuestionnaireTestDataSeeder
 */
class QuestionnaireDataFieldsDisplayTestCest
{
    // Данные для входа из UserSeeder
    private const ADMIN_EMAIL = 'admin@spaceofjoy.ru';
    private const ADMIN_PASSWORD = 'osenosen';

    /**
     * Хелпер: Авторизация как администратор через UI
     */
    private function loginAsAdmin(AcceptanceTester $I): void
    {
        // Переход на главную страницу
        $I->amOnPage('/');
        $I->wait(2);

        // Проверяем, не авторизованы ли мы уже (ищем элементы админки)
        try {
            $I->seeElement('#filter'); // Элемент фильтра анкет
            codecept_debug('Уже авторизован — пропускаем логин');
            return;
        } catch (\Exception $e) {
            // Не авторизован — продолжаем логин
        }

        // Ищем форму логина
        $I->seeElement('input[type="email"]');
        $I->seeElement('input[type="password"]');

        // Вводим credentials
        $I->fillField('input[type="email"]', self::ADMIN_EMAIL);
        $I->fillField('input[type="password"]', self::ADMIN_PASSWORD);

        // Нажимаем кнопку входа
        $I->click('button[type="submit"]');
        $I->wait(3);

        // Проверяем что авторизовались — видим элементы админки
        $I->seeElement('#filter', 'После логина должны видеть фильтр анкет');
    }

    /**
     * Тест: Поля из JSON data отображаются в таблице анкет
     *
     * Сценарий:
     * 1. Логинимся как админ
     * 2. Переходим на страницу анкет
     * 3. Проверяем что в таблице есть данные (не "—") для phone, telegram, vk
     */
    public function checkDataFieldsFromJsonDisplayInTable(AcceptanceTester $I): void
    {
        // 1. Авторизация
        $this->loginAsAdmin($I);

        // 2. Переход на страницу анкет
        $I->amOnPage('/questionnaires/');
        $I->wait(3);

        // Делаем скриншот для отладки
        $I->makeScreenshot('questionnaire_list_page.png');

        // 3. Проверяем что таблица загрузилась
        $I->seeElement('table.table-hover');
        $I->seeElement('table.table-hover tbody tr');

        // 4. Проверяем заголовки колонок
        $I->see('Email', 'table');
        $I->see('Телефон', 'table');
        $I->see('Telegram', 'table');
        $I->see('VK', 'table');

        // 5. КЛЮЧЕВАЯ ПРОВЕРКА: данные из JSON data отображаются (не "—")
        // Проверяем что в таблице есть конкретные значения из сидера
        $I->see('+79991234567', 'table'); // phone из data
        $I->see('testuser1', 'table'); // telegram из data
        $I->see('https://vk.com/testuser1', 'table'); // vk из data
        $I->see('test1@example.com', 'table'); // email из data

        // 6. Проверяем вторую анкету
        $I->see('+79997654321', 'table');
        $I->see('approved_user', 'table');

        // 7. Проверяем что is_have_in_club отображается корректно
        // "Да" для первой анкеты (true), "Нет" для второй (false)
        // Это зависит от реализации — может быть галочка или текст
        $I->see('Да', 'table');
        $I->see('Нет', 'table');
    }

    /**
     * Тест: Пустые поля отображаются как "—"
     *
     * Проверяет что если поле в JSON data отсутствует,
     * то в таблице отображается "—" (дефолтное поведение фронтенда)
     */
    public function checkEmptyFieldsShowAsDash(AcceptanceTester $I): void
    {
        $this->loginAsAdmin($I);

        $I->amOnPage('/questionnaires/');
        $I->wait(3);

        // Третья анкета имеет только email и phone в data
        // telegram и vk должны быть "—"
        // Это сложно проверить точно без знания позиции строки,
        // но мы можем проверить что "—" вообще присутствует на странице
        $I->see('—');
    }

    /**
     * Тест: Статус анкеты отображается корректно
     *
     * Проверяет что статусы NEW и APPROVE правильно маппятся
     * на русские названия ("Новая", "Одобрена")
     */
    public function checkQuestionnaireStatusDisplay(AcceptanceTester $I): void
    {
        $this->loginAsAdmin($I);

        $I->amOnPage('/questionnaires/');
        $I->wait(3);

        // Проверяем что статусы отображаются
        $I->see('Новая', 'table');
        $I->see('Одобрена', 'table');
    }
}
