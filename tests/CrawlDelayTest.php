<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\CrawlDelay;

class CrawlDelayTest extends \PHPUnit_Framework_TestCase
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
            (string) new CrawlDelay(10)
        );
    }

    /**
     * @expectedException Innmind\RobotsTxt\Exception\InvalidArgumentException
     */
    public function testThrowWhenNegativeDelay()
    {
        new CrawlDelay(-1);
    }
}
