<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Disallow,
    UrlPattern
};
use PHPUnit\Framework\TestCase;

class DisallowTest extends TestCase
{
    public function testMatches()
    {
        $disallow = new Disallow(new UrlPattern('/foo'));

        $this->assertTrue($disallow->matches('/foo/bar'));
        $this->assertFalse($disallow->matches('/bar'));
    }

    public function testAllowWhenEmptyPattern()
    {
        $disallow = new Disallow(new UrlPattern(''));

        $this->assertFalse($disallow->matches('/foo'));
        $this->assertFalse($disallow->matches('/'));
    }

    public function testStringCast()
    {
        $this->assertSame(
            'Disallow: *',
            (string) new Disallow(new UrlPattern('*'))
        );
    }
}
