<?php

declare(strict_types=1);

namespace Tests\Unit\EmailDelivery;

use Tests\TestCase;
use Tickets\EmailDelivery\Domain\EmailEvent;

/**
 * Каталог событий писем содержит новое событие «анкета одобрена» (questionnaire_approved)
 * с дефолтным slug questionnaireApproved и меткой «Анкета одобрена».
 */
class EmailEventCatalogTest extends TestCase
{
    public function test_questionnaire_approved_is_registered(): void
    {
        $this->assertSame('questionnaire_approved', EmailEvent::QUESTIONNAIRE_APPROVED);
        $this->assertTrue(EmailEvent::isValid(EmailEvent::QUESTIONNAIRE_APPROVED));
        $this->assertSame('questionnaireApproved', EmailEvent::defaultSlug(EmailEvent::QUESTIONNAIRE_APPROVED));
        $this->assertContains('questionnaire_approved', EmailEvent::all());
    }

    public function test_catalog_contains_questionnaire_approved_label(): void
    {
        $entry = array_values(array_filter(
            EmailEvent::catalog(),
            static fn (array $i): bool => $i['value'] === 'questionnaire_approved',
        ));

        $this->assertNotEmpty($entry);
        $this->assertSame('Анкета одобрена', $entry[0]['label']);
    }
}
