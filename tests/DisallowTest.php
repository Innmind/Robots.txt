<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Disallow,
    UrlPattern,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class DisallowTest extends TestCase
{
    public function testMatches()
    {
        $disallow = Disallow::of(UrlPattern::of('/foo'));

        $this->assertTrue($disallow->matches('/foo/bar'));
        $this->assertFalse($disallow->matches('/bar'));
    }

    public function testAllowWhenEmptyPattern()
    {
        $disallow = Disallow::of(UrlPattern::of(''));

        $this->assertFalse($disallow->matches('/foo'));
        $this->assertFalse($disallow->matches('/'));
    }

    public function testStringCast()
    {
        $this->assertSame(
            'Disallow: *',
            Disallow::of(UrlPattern::of('*'))->toString(),
        );
    }
}
