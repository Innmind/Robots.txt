<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Allow,
    UrlPattern
};
use PHPUnit\Framework\TestCase;

class AllowTest extends TestCase
{
    public function testMatches()
    {
        $allow = new Allow(new UrlPattern('/foo'));

        $this->assertTrue($allow->matches('/foo/bar'));
        $this->assertFalse($allow->matches('/bar'));
    }

    public function testStringCast()
    {
        $this->assertSame(
            'Allow: *',
            (string) new Allow(new UrlPattern('*'))
        );
    }
}
