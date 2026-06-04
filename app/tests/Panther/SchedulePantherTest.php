<?php

namespace App\Tests\Panther;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Panther\PantherTestCase;

#[Group('panther')]
class SchedulePantherTest extends PantherTestCase
{
    public function testSchedulePageRendersInBrowser(): void
    {
        if (!filter_var($_SERVER['RUN_PANTHER'] ?? $_ENV['RUN_PANTHER'] ?? false, FILTER_VALIDATE_BOOL)) {
            self::markTestSkipped('Set RUN_PANTHER=1 to run browser tests.');
        }

        $client = static::createPantherClient();
        $client->request('GET', '/schedule');

        self::assertPageTitleContains('Planning');
        self::assertSelectorTextContains('h1', 'Planning');
        self::assertSelectorExists('.schedule-filters');
    }
}
