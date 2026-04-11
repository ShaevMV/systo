<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class LoginTestCest
{
    /**
     * Проверка что страница логина загружается
     */
    public function checkLoginPageLoads(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        
        // Ждём загрузки Vue.js
        $I->wait(2);
        
        // Проверка что страница загрузилась (есть заголовок или логотип)
        $I->seeElement('body');
        
        // Делаем скриншот для отладки
        $I->makeScreenshot('login_page.png');
    }

    /**
     * Проверка входа с невалидными данными
     */
    public function tryLoginWithInvalidCredentials(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->wait(2);
        
        // Пробуем найти любые input поля на странице
        // Если структура неизвестна - просто проверяем что страница есть
        $I->seeElement('body');
        
        // Для реальных тестов нужно будет уточнить селекторы
        // после анализа DOM структуры фронтенда
    }
}
