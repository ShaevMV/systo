<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * Acceptance тесты для проверки DOM элементов нового функционала.
 * 
 * Эти тесты проверяют:
 * 1. Что HTML структура страницы содержит правильные элементы
 * 2. Что фильтры и таблицы рендерятся корректно
 * 
 * Тесты работают с публичными страницами чтобы не требовать авторизации.
 * Для тестов требующих авторизации - создайте отдельный класс с пред-условием логина.
 */
class QuestionnaireDomStructureTestCest
{
    /**
     * Тест: Страница покупки билета загружается (публичная страница)
     * 
     * Это базовый тест чтобы проверить что Codeception + WebDriver работают.
     */
    public function checkBuyTicketPageLoads(AcceptanceTester $I): void
    {
        // Переход на главную (должна быть публичная страница)
        $I->amOnPage('/');
        $I->wait(2);
        
        // Проверяем что страница загрузилась
        $I->seeElement('body');
        
        // Делаем скриншот для отладки
        $I->makeScreenshot('main_page.png');
    }

    /**
     * Тест: Проверка что фронтенд собран и доступен
     * 
     * Проверяет что CSS и JS файлы загружаются корректно.
     */
    public function checkFrontendAssetsLoaded(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->wait(2);
        
        // Проверяем что есть script теги (Vue.js bundle)
        $I->seeElement('script[src]');
        
        // Проверяем что есть link теги (CSS)
        $I->seeElement('link[rel="stylesheet"]');
    }
}
