<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\UrlPattern;
use PHPUnit\Framework\TestCase;

class UrlPatternTest extends TestCase
{
    public function testStringCast()
    {
        $this->assertSame(
            '*',
            (new UrlPattern('*'))->toString(),
        );
    }

    /**
     * @dataProvider cases
     */
    public function testMatches(bool $expected, string $pattern, string $url)
    {
        $this->assertSame(
            $expected,
            (new UrlPattern($pattern))->matches($url)
        );
    }

    /**
     * @see https://developers.google.com/webmasters/control-crawl-index/docs/robots_txt#example-path-matches
     */
    public function cases(): array
    {
        return [
            [true, '', '/'],
            [true, '', '/foo'],
            [true, '', '/foo/bar'],
            [true, '', '/foo/bar?some=query'],
            [true, '*', '/'],
            [true, '*', '/foo'],
            [true, '*', '/foo/bar'],
            [true, '*', '/foo/bar?some=query'],
            [true, '/', '/'],
            [true, '/', '/foo'],
            [true, '/', '/foo/bar'],
            [true, '/', '/foo/bar?some=query'],
            [true, '/*', '/'],
            [true, '/*', '/foo'],
            [true, '/*', '/foo/bar'],
            [true, '/*', '/foo/bar?some=query'],
            [true, '/fish', '/fish'],
            [true, '/fish', '/fish.html'],
            [true, '/fish', '/fish/salmon.html'],
            [true, '/fish', '/fishheads'],
            [true, '/fish', '/fishheads/yummy.html'],
            [true, '/fish', '/fish.php?id=anything'],
            [false, '/fish', '/Fish.asp'],
            [false, '/fish', '/catfish'],
            [false, '/fish', '/?id=fish'],
            [true, '/fish*', '/fish'],
            [true, '/fish*', '/fish.html'],
            [true, '/fish*', '/fish/salmon.html'],
            [true, '/fish*', '/fishheads'],
            [true, '/fish*', '/fishheads/yummy.html'],
            [true, '/fish*', '/fish.php?id=anything'],
            [false, '/fish*', '/Fish.asp'],
            [false, '/fish*', '/catfish'],
            [false, '/fish*', '/?id=fish'],
            [true, '/fish/', '/fish/'],
            [true, '/fish/', '/fish/?id=anything'],
            [true, '/fish/', '/fish/salmon.htm'],
            [false, '/fish/', '/fish'],
            [false, '/fish/', '/fish.html'],
            [false, '/fish/', '/Fish/Salmon.asp'],
            [true, '/*.php', '/filename.php'],
            [true, '/*.php', '/folder/filename.php'],
            [true, '/*.php', '/folder/filename.php?parameters'],
            [true, '/*.php', '/folder/any.php.file.html'],
            [true, '/*.php', '/filename.php/'],
            [false, '/*.php', '/'],
            [false, '/*.php', '/windows.PHP'],
            [true, '/*.php$', '/filename.php'],
            [true, '/*.php$', '/folder/filename.php'],
            [false, '/*.php$', '/filename.php?parameters'],
            [false, '/*.php$', '/filename.php/'],
            [false, '/*.php$', '/filename.php5'],
            [false, '/*.php$', '/windows.PHP'],
            [true, '/fish*.php', '/fish.php'],
            [true, '/fish*.php', '/fishheads/catfish.php?parameters'],
            [false, '/fish*.php', '/Fish.PHP'],
        ];
    }
}
