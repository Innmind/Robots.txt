<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Directives,
    DirectivesInterface,
    UserAgentInterface,
    Allow,
    Disallow,
    CrawlDelay,
    UrlPattern,
    UserAgent
};
use Innmind\Url\Url;
use Innmind\Immutable\Set;

class DirectivesTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DirectivesInterface::class,
            new Directives(
                $this->createMock(UserAgentInterface::class),
                new Set(Allow::class),
                new Set(Disallow::class)
            )
        );
    }

    public function testTargets()
    {
        $directives = new Directives(
            $userAgent = $this->createMock(UserAgentInterface::class),
            new Set(Allow::class),
            new Set(Disallow::class)
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
            $this->createMock(UserAgentInterface::class),
            new Set(Allow::class),
            new Set(Disallow::class)
        );
        $this->assertFalse($directives->hasCrawlDelay());

        $directives = new Directives(
            $this->createMock(UserAgentInterface::class),
            new Set(Allow::class),
            new Set(Disallow::class),
            new CrawlDelay(0)
        );
        $this->assertTrue($directives->hasCrawlDelay());
    }

    public function testCrawlDelay()
    {
        $directives = new Directives(
            $userAgent = $this->createMock(UserAgentInterface::class),
            new Set(Allow::class),
            new Set(Disallow::class),
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
            $this->createMock(UserAgentInterface::class),
            (new Set(Allow::class))
                ->add(new Allow(new UrlPattern($allow))),
            (new Set(Disallow::class))
                ->add(new Disallow(new UrlPattern($disallow)))
        );

        $this->assertSame(
            $expected,
            $directives->disallows(Url::fromString($url))
        );
    }

    /**
     * @expectedException Innmind\RobotsTxt\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidAllowSet()
    {
        new Directives(
            $this->createMock(UserAgentInterface::class),
            new Set(UrlPattern::class),
            new Set(Disallow::class)
        );
    }

    /**
     * @expectedException Innmind\RobotsTxt\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidDisallowSet()
    {
        new Directives(
            $this->createMock(UserAgentInterface::class),
            new Set(Allow::class),
            new Set(UrlPattern::class)
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
            (string) new Directives(
                new UserAgent('*'),
                (new Set(Allow::class))
                    ->add(new Allow(new UrlPattern('/foo')))
                    ->add(new Allow(new UrlPattern('/bar'))),
                (new Set(Disallow::class))
                    ->add(new Disallow(new UrlPattern('/baz')))
                    ->add(new Disallow(new UrlPattern('/'))),
                new CrawlDelay(10)
            )
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
