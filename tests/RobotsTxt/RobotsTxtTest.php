<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\RobotsTxt;

use Innmind\RobotsTxt\{
    RobotsTxt\RobotsTxt,
    RobotsTxt as RobotsTxtInterface,
    Directives,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class RobotsTxtTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RobotsTxtInterface::class,
            new RobotsTxt(
                Url::of('http://example.com/robots.txt'),
                Sequence::of(),
            ),
        );
    }

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
                $mock = $this->createMock(Directives::class),
            ),
        );
        $url = Url::of('http://example.com/robots.txt');
        $mock
            ->expects($this->exactly(3))
            ->method('targets')
            ->with('foo')
            ->will($this->onConsecutiveCalls(true, true, false));
        $mock
            ->expects($this->exactly(2))
            ->method('disallows')
            ->with($url)
            ->will($this->onConsecutiveCalls(true, false));

        $this->assertTrue($robots->disallows('foo', $url));
        $this->assertFalse($robots->disallows('foo', $url));
        $this->assertFalse($robots->disallows('foo', $url));
    }

    public function testFallbackDirectivesWhenUserAgentMatchesMultipleOnes()
    {
        $robots = new RobotsTxt(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                $mock1 = $this->createMock(Directives::class),
                $mock2 = $this->createMock(Directives::class),
            ),
        );
        $url = Url::of('http://example.com/robots.txt');
        $mock1
            ->expects($this->once())
            ->method('targets')
            ->with('foo')
            ->willReturn(true);
        $mock2
            ->expects($this->once())
            ->method('targets')
            ->with('foo')
            ->willReturn(true);
        $mock1
            ->expects($this->once())
            ->method('disallows')
            ->with($url)
            ->willReturn(false);
        $mock2
            ->expects($this->once())
            ->method('disallows')
            ->with($url)
            ->willReturn(true);

        $this->assertTrue($robots->disallows('foo', $url));
    }

    public function testStringCast()
    {
        $robots = new RobotsTxt(
            Url::of('http://example.com/robots.txt'),
            Sequence::of(
                $mock1 = $this->createMock(Directives::class),
                $mock2 = $this->createMock(Directives::class),
            ),
        );
        $mock1
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');
        $mock2
            ->expects($this->once())
            ->method('toString')
            ->willReturn('bar');

        $this->assertSame('foo'."\n\n".'bar', $robots->toString());
    }
}
