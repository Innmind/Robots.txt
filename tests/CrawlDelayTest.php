<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    CrawlDelay,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class CrawlDelayTest extends TestCase
{
    public function testIntCast()
    {
        $delay = new CrawlDelay(10);

        $this->assertSame(10, $delay->toInt());
    }

    public function testStringCast()
    {
        $this->assertSame(
            'Crawl-delay: 10',
            (new CrawlDelay(10))->toString(),
        );
    }

    public function testThrowWhenNegativeDelay()
    {
        $this->expectException(DomainException::class);

        new CrawlDelay(-1);
    }
}
