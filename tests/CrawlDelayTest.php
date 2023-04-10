<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\CrawlDelay;
use PHPUnit\Framework\TestCase;

class CrawlDelayTest extends TestCase
{
    public function testIntCast()
    {
        $delay = CrawlDelay::of(10);

        $this->assertSame(10, $delay->toInt());
    }

    public function testStringCast()
    {
        $this->assertSame(
            'Crawl-delay: 10',
            CrawlDelay::of(10)->toString(),
        );
    }

    public function testReturnNothingWhenNegativeDelay()
    {
        $this->assertNull(CrawlDelay::maybe('-1')->match(
            static fn($delay) => $delay,
            static fn() => null,
        ));
    }
}
