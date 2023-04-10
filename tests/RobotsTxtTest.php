<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    RobotsTxt,
    Directives,
    Disallow,
    UrlPattern,
    UserAgent\UserAgent,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Set,
};
use PHPUnit\Framework\TestCase;

class RobotsTxtTest extends TestCase
{
    public function testUrl()
    {
        $robots = new RobotsTxt(
            $url = Url::of('http://example.com/robots.txt'),
            Sequence::of(),
        );

        $this->assertSame($url, $robots->url());
    }

    public function testDirectives()
    {
        $robots = new RobotsTxt(
            Url::of('http://example.com/robots.txt'),
            $directives = Sequence::of(),
        );

        $this->assertSame($directives, $robots->directives());
    }

    public function testDisallows()
    {
        $robots = new RobotsTxt(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                Directives::of(
                    new UserAgent('Innmind'),
                    Set::of(),
                    Set::of(Disallow::of(UrlPattern::of('/some-file'))),
                ),
            ),
        );

        $this->assertTrue($robots->disallows('Innmind', Url::of('http://example.com/some-file')));
        $this->assertFalse($robots->disallows('Innmind', Url::of('http://example.com/some-unknown-file')));
        $this->assertFalse($robots->disallows('Unknown', Url::of('http://example.com/some-file')));
    }

    public function testFallbackDirectivesWhenUserAgentMatchesMultipleOnes()
    {
        $robots = new RobotsTxt(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                Directives::of(
                    new UserAgent('foo'),
                    Set::of(),
                    Set::of(),
                ),
                Directives::of(
                    new UserAgent('foo'),
                    Set::of(),
                    Set::of(Disallow::of(UrlPattern::of('/robots.txt'))),
                ),
            ),
        );

        $this->assertTrue($robots->disallows('foo', Url::of('http://example.com/robots.txt')));
    }

    public function testStringCast()
    {
        $robots = new RobotsTxt(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                Directives::of(
                    new UserAgent('foo'),
                    Set::of(),
                    Set::of(),
                ),
                Directives::of(
                    new UserAgent('bar'),
                    Set::of(),
                    Set::of(),
                ),
            ),
        );

        $this->assertSame('User-agent: foo'."\n\n".'User-agent: bar', $robots->toString());
    }
}
