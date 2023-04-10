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
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class DirectivesTest extends TestCase
{
    public function testTargets()
    {
        $directives = new Directives(
            $userAgent = $this->createMock(UserAgent::class),
            Set::of(),
            Set::of(),
        );
        $userAgent
            ->expects($this->exactly(2))
            ->method('matches')
            ->with('foo')
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($directives->targets('foo'));
        $this->assertFalse($directives->targets('foo'));
    }

    public function testCrawlDelay()
    {
        $directives = new Directives(
            $userAgent = $this->createMock(UserAgent::class),
            Set::of(),
            Set::of(),
            $delay = new CrawlDelay(0),
        );

        $this->assertSame($delay, $directives->crawlDelay()->match(
            static fn($delay) => $delay,
            static fn() => null,
        ));

        $directives = new Directives(
            $userAgent = $this->createMock(UserAgent::class),
            Set::of(),
            Set::of(),
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
        $directives = new Directives(
            $this->createMock(UserAgent::class),
            Set::of(new Allow(new UrlPattern($allow))),
            Set::of(new Disallow(new UrlPattern($disallow))),
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
            (new Directives(
                new UserAgent\UserAgent('*'),
                Set::of(
                    new Allow(new UrlPattern('/foo')),
                    new Allow(new UrlPattern('/bar')),
                ),
                Set::of(
                    new Disallow(new UrlPattern('/baz')),
                    new Disallow(new UrlPattern('/')),
                ),
                new CrawlDelay(10),
            ))->toString(),
        );
    }

    public function testWithAllow()
    {
        $directives = new Directives(
            new UserAgent\UserAgent('*'),
            Set::of(),
            Set::of(),
        );
        $directives2 = $directives->withAllow(new Allow(new UrlPattern('/foo')));

        $this->assertInstanceOf(Directives::class, $directives2);
        $this->assertNotSame($directives, $directives2);
        $this->assertSame(
            'User-agent: *',
            $directives->toString(),
        );
        $this->assertSame(
            "User-agent: *\nAllow: /foo",
            $directives2->toString(),
        );
    }

    public function testWithDisallow()
    {
        $directives = new Directives(
            new UserAgent\UserAgent('*'),
            Set::of(),
            Set::of(),
        );
        $directives2 = $directives->withDisallow(new Disallow(new UrlPattern('/foo')));

        $this->assertInstanceOf(Directives::class, $directives2);
        $this->assertNotSame($directives, $directives2);
        $this->assertSame(
            'User-agent: *',
            $directives->toString(),
        );
        $this->assertSame(
            "User-agent: *\nDisallow: /foo",
            $directives2->toString(),
        );
    }

    public function testWithCrawlDelay()
    {
        $directives = new Directives(
            new UserAgent\UserAgent('*'),
            Set::of(),
            Set::of(),
        );
        $directives2 = $directives->withCrawlDelay(new CrawlDelay(42));

        $this->assertInstanceOf(Directives::class, $directives2);
        $this->assertNotSame($directives, $directives2);
        $this->assertSame(
            'User-agent: *',
            $directives->toString(),
        );
        $this->assertSame(
            "User-agent: *\nCrawl-delay: 42",
            $directives2->toString(),
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
