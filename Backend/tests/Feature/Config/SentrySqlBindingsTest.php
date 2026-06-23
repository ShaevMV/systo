<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use Tests\TestCase;

/**
 * 152-ФЗ: значения SQL-параметров (ПДн) не должны попадать в Sentry breadcrumbs по умолчанию.
 */
class SentrySqlBindingsTest extends TestCase
{
    public function test_sql_bindings_disabled_by_default(): void
    {
        $this->assertFalse(
            config('sentry.breadcrumbs.sql_bindings'),
            'SENTRY_SQL_BINDINGS по умолчанию должен быть false (ПДн в значениях параметров).',
        );
    }
}
