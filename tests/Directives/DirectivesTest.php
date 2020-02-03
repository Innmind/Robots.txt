<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\Directives;

use Innmind\RobotsTxt\{
    Directives\Directives,
    Directives as DirectivesInterface,
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
    public function testInterface()
    {
        $this->assertInstanceOf(
            DirectivesInterface::class,
            new Directives(
                $this->createMock(UserAgent::class),
                Set::of(Allow::class),
                Set::of(Disallow::class)
            )
        );
    }

    public function testTargets()
    {
        $directives = new Directives(
            $userAgent = $this->createMock(UserAgent::class),
            Set::of(Allow::class),
            Set::of(Disallow::class)
        );
        $userAgent
            ->expects($this->at(0))
            ->method('matches')
            ->with('foo')
            ->willReturn(true);
        $userAgent
            ->expects($this->at(1))
            ->method('matches')
            ->with('foo')
            ->willReturn(false);

        $this->assertTrue($directives->targets('foo'));
        $this->assertFalse($directives->targets('foo'));
    }

    public function testHasCrawlDelay()
    {
        $directives = new Directives(
            $this->createMock(UserAgent::class),
            Set::of(Allow::class),
            Set::of(Disallow::class)
        );
        $this->assertFalse($directives->hasCrawlDelay());

        $directives = new Directives(
            $this->createMock(UserAgent::class),
            Set::of(Allow::class),
            Set::of(Disallow::class),
            new CrawlDelay(0)
        );
        $this->assertTrue($directives->hasCrawlDelay());
    }

    public function testCrawlDelay()
    {
        $directives = new Directives(
            $userAgent = $this->createMock(UserAgent::class),
            Set::of(Allow::class),
            Set::of(Disallow::class),
            $delay = new CrawlDelay(0)
        );

        $this->assertSame($delay, $directives->crawlDelay());
    }

    /**
     * @dataProvider cases
     */
    public function testDisallows(bool $expected, string $url, string $allow, string $disallow)
    {
        $directives = new Directives(
            $this->createMock(UserAgent::class),
            Set::of(Allow::class, new Allow(new UrlPattern($allow))),
            Set::of(Disallow::class, new Disallow(new UrlPattern($disallow)))
        );

        $this->assertSame(
            $expected,
            $directives->disallows(Url::of($url))
        );
    }

    public function testThrowWhenInvalidAllowSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set<Innmind\RobotsTxt\Allow>');

        new Directives(
            $this->createMock(UserAgent::class),
            Set::of(UrlPattern::class),
            Set::of(Disallow::class)
        );
    }

    public function testThrowWhenInvalidDisallowSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 3 must be of type Set<Innmind\RobotsTxt\Disallow>');

        new Directives(
            $this->createMock(UserAgent::class),
            Set::of(Allow::class),
            Set::of(UrlPattern::class)
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
                    Allow::class,
                    new Allow(new UrlPattern('/foo')),
                    new Allow(new UrlPattern('/bar'))
                ),
                Set::of(
                    Disallow::class,
                    new Disallow(new UrlPattern('/baz')),
                    new Disallow(new UrlPattern('/'))
                ),
                new CrawlDelay(10)
            ))->toString(),
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
