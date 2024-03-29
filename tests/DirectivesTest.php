<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Directives,
    UserAgent,
    Allow,
    Disallow,
    CrawlDelay,
    UrlPattern,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class DirectivesTest extends TestCase
{
    public function testTargets()
    {
        $directives = Directives::of(
            UserAgent::of('foo'),
        );

        $this->assertTrue($directives->targets('foo'));
        $this->assertFalse($directives->targets('bar'));
    }

    public function testCrawlDelay()
    {
        $directives = Directives::of(
            UserAgent::of('*'),
            null,
            null,
            $delay = CrawlDelay::of(0),
        );

        $this->assertSame($delay, $directives->crawlDelay()->match(
            static fn($delay) => $delay,
            static fn() => null,
        ));

        $directives = Directives::of(
            UserAgent::of('*'),
        );

        $this->assertNull($directives->crawlDelay()->match(
            static fn($delay) => $delay,
            static fn() => null,
        ));
    }

    /**
     * @dataProvider cases
     */
    public function testDisallows(bool $expected, string $url, string $allow, string $disallow)
    {
        $directives = Directives::of(
            UserAgent::of('*'),
            Sequence::of(Allow::of(UrlPattern::of($allow))),
            Sequence::of(Disallow::of(UrlPattern::of($disallow))),
        );

        $this->assertSame(
            $expected,
            $directives->disallows(Url::of($url)),
        );
    }

    public function testStringCast()
    {
        $expected = 'User-agent: *'."\n";
        $expected .= 'Allow: /foo'."\n";
        $expected .= 'Allow: /bar'."\n";
        $expected .= 'Disallow: /baz'."\n";
        $expected .= 'Disallow: /'."\n";
        $expected .= 'Crawl-delay: 10';

        $this->assertSame(
            $expected,
            (Directives::of(
                UserAgent::of('*'),
                Sequence::of(
                    Allow::of(UrlPattern::of('/foo')),
                    Allow::of(UrlPattern::of('/bar')),
                ),
                Sequence::of(
                    Disallow::of(UrlPattern::of('/baz')),
                    Disallow::of(UrlPattern::of('/')),
                ),
                CrawlDelay::of(10),
            ))->asContent()->toString(),
        );
    }

    public function testWithAllow()
    {
        $directives = Directives::of(
            UserAgent::of('*'),
        );
        $directives2 = $directives->withAllow(Allow::of(UrlPattern::of('/foo')));

        $this->assertInstanceOf(Directives::class, $directives2);
        $this->assertNotSame($directives, $directives2);
        $this->assertSame(
            'User-agent: *',
            $directives->asContent()->toString(),
        );
        $this->assertSame(
            "User-agent: *\nAllow: /foo",
            $directives2->asContent()->toString(),
        );
    }

    public function testWithDisallow()
    {
        $directives = Directives::of(
            UserAgent::of('*'),
        );
        $directives2 = $directives->withDisallow(Disallow::of(UrlPattern::of('/foo')));

        $this->assertInstanceOf(Directives::class, $directives2);
        $this->assertNotSame($directives, $directives2);
        $this->assertSame(
            'User-agent: *',
            $directives->asContent()->toString(),
        );
        $this->assertSame(
            "User-agent: *\nDisallow: /foo",
            $directives2->asContent()->toString(),
        );
    }

    public function testWithCrawlDelay()
    {
        $directives = Directives::of(
            UserAgent::of('*'),
        );
        $directives2 = $directives->withCrawlDelay(CrawlDelay::of(42));

        $this->assertInstanceOf(Directives::class, $directives2);
        $this->assertNotSame($directives, $directives2);
        $this->assertSame(
            'User-agent: *',
            $directives->asContent()->toString(),
        );
        $this->assertSame(
            "User-agent: *\nCrawl-delay: 42",
            $directives2->asContent()->toString(),
        );
    }

    /**
     * @see https://developers.google.com/webmasters/control-crawl-index/docs/robots_txt#order-of-precedence-for-group-member-records
     */
    public function cases(): array
    {
        return [
            [false, 'http://example.com/page', '/p', '/'],
            [false, 'http://example.com/folder/page', '/folder/', '/folder'],
            [false, 'http://example.com/page.htm', '/page', '/*.htm'],
            [false, 'http://example.com/', '/$', '/'],
            [true, 'http://example.com/page.htm', '/$', '/'],
        ];
    }
}
