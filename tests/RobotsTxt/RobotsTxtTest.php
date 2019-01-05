<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\RobotsTxt;

use Innmind\RobotsTxt\{
    RobotsTxt\RobotsTxt,
    RobotsTxt as RobotsTxtInterface,
    Directives
};
use Innmind\Url\UrlInterface;
use Innmind\Immutable\Stream;
use PHPUnit\Framework\TestCase;

class RobotsTxtTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            RobotsTxtInterface::class,
            new RobotsTxt(
                $this->createMock(UrlInterface::class),
                new Stream(Directives::class)
            )
        );
    }

    public function testUrl()
    {
        $robots = new RobotsTxt(
            $url = $this->createMock(UrlInterface::class),
            new Stream(Directives::class)
        );

        $this->assertSame($url, $robots->url());
    }

    public function testDirectives()
    {
        $robots = new RobotsTxt(
            $this->createMock(UrlInterface::class),
            $directives = new Stream(Directives::class)
        );

        $this->assertSame($directives, $robots->directives());
    }

    public function testDisallows()
    {
        $robots = new RobotsTxt(
            $this->createMock(UrlInterface::class),
            Stream::of(
                Directives::class,
                $mock = $this->createMock(Directives::class)
            )
        );
        $url = $this->createMock(UrlInterface::class);
        $mock
            ->expects($this->at(0))
            ->method('targets')
            ->with('foo')
            ->willReturn(true);
        $mock
            ->expects($this->at(1))
            ->method('disallows')
            ->with($url)
            ->willReturn(true);
        $mock
            ->expects($this->at(2))
            ->method('targets')
            ->with('foo')
            ->willReturn(true);
        $mock
            ->expects($this->at(3))
            ->method('disallows')
            ->with($url)
            ->willReturn(false);
        $mock
            ->expects($this->at(4))
            ->method('targets')
            ->with('foo')
            ->willReturn(false);

        $this->assertTrue($robots->disallows('foo', $url));
        $this->assertFalse($robots->disallows('foo', $url));
        $this->assertFalse($robots->disallows('foo', $url));
    }

    public function testFallbackDirectivesWhenUserAgentMatchesMultipleOnes()
    {
        $robots = new RobotsTxt(
            $this->createMock(UrlInterface::class),
            Stream::of(
                Directives::class,
                $mock1 = $this->createMock(Directives::class),
                $mock2 = $this->createMock(Directives::class)
            )
        );
        $url = $this->createMock(UrlInterface::class);
        $mock1
            ->expects($this->at(0))
            ->method('targets')
            ->with('foo')
            ->willReturn(true);
        $mock2
            ->expects($this->at(0))
            ->method('targets')
            ->with('foo')
            ->willReturn(true);
        $mock1
            ->expects($this->at(1))
            ->method('disallows')
            ->with($url)
            ->willReturn(false);
        $mock2
            ->expects($this->at(1))
            ->method('disallows')
            ->with($url)
            ->willReturn(true);

        $this->assertTrue($robots->disallows('foo', $url));
    }

    public function testStringCast()
    {
        $robots = new RobotsTxt(
            $this->createMock(UrlInterface::class),
            Stream::of(
                Directives::class,
                $mock1 = $this->createMock(Directives::class),
                $mock2 = $this->createMock(Directives::class)
            )
        );
        $mock1
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('foo');
        $mock2
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('bar');

        $this->assertSame('foo'."\n\n".'bar', (string) $robots);
    }
}
