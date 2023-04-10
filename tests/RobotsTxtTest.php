<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    RobotsTxt,
    Directives,
    Disallow,
    UrlPattern,
    UserAgent,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class RobotsTxtTest extends TestCase
{
    public function testUrl()
    {
        $robots = RobotsTxt::of(
            $url = Url::of('http://example.com/robots.txt'),
            Sequence::of(),
        );

        $this->assertSame($url, $robots->url());
    }

    public function testDirectives()
    {
        $robots = RobotsTxt::of(
            Url::of('http://example.com/robots.txt'),
            $directives = Sequence::of(),
        );

        $this->assertSame($directives, $robots->directives());
    }

    public function testDisallows()
    {
        $robots = RobotsTxt::of(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                Directives::of(
                    UserAgent::of('Innmind'),
                    null,
                    Sequence::of(Disallow::of(UrlPattern::of('/some-file'))),
                ),
            ),
        );

        $this->assertTrue($robots->disallows('Innmind', Url::of('http://example.com/some-file')));
        $this->assertFalse($robots->disallows('Innmind', Url::of('http://example.com/some-unknown-file')));
        $this->assertFalse($robots->disallows('Unknown', Url::of('http://example.com/some-file')));
    }

    public function testFallbackDirectivesWhenUserAgentMatchesMultipleOnes()
    {
        $robots = RobotsTxt::of(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                Directives::of(
                    UserAgent::of('foo'),
                ),
                Directives::of(
                    UserAgent::of('foo'),
                    null,
                    Sequence::of(Disallow::of(UrlPattern::of('/robots.txt'))),
                ),
            ),
        );

        $this->assertTrue($robots->disallows('foo', Url::of('http://example.com/robots.txt')));
    }

    public function testStringCast()
    {
        $robots = RobotsTxt::of(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                Directives::of(
                    UserAgent::of('foo'),
                ),
                Directives::of(
                    UserAgent::of('bar'),
                ),
            ),
        );

        $this->assertSame(
            'User-agent: foo'."\n\n".'User-agent: bar'."\n",
            $robots->asContent()->toString(),
        );
    }
}
