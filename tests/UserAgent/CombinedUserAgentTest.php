<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\UserAgent;

use Innmind\RobotsTxt\{
    UserAgent\CombinedUserAgent,
    UserAgent,
};
use PHPUnit\Framework\TestCase;

class CombinedUserAgentTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            UserAgent::class,
            new CombinedUserAgent(
                $this->createMock(UserAgent::class),
                $this->createMock(UserAgent::class)
            )
        );
    }

    public function testMatchesWithFirstUserAgent()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgent::class),
            $second = $this->createMock(UserAgent::class)
        );
        $first
            ->expects($this->once())
            ->method('matches')
            ->with('foo')
            ->willReturn(true);
        $second
            ->expects($this->never())
            ->method('matches');

        $this->assertTrue($userAgent->matches('foo'));
    }

    public function testMatchesWithSecondUserAgent()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgent::class),
            $second = $this->createMock(UserAgent::class)
        );
        $first
            ->expects($this->once())
            ->method('matches')
            ->with('foo')
            ->willReturn(false);
        $second
            ->expects($this->once())
            ->method('matches')
            ->with('foo')
            ->willReturn(true);

        $this->assertTrue($userAgent->matches('foo'));
    }

    public function testDoesnMatch()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgent::class),
            $second = $this->createMock(UserAgent::class)
        );
        $first
            ->expects($this->once())
            ->method('matches')
            ->with('foo')
            ->willReturn(false);
        $second
            ->expects($this->once())
            ->method('matches')
            ->with('foo')
            ->willReturn(false);

        $this->assertFalse($userAgent->matches('foo'));
    }

    public function testStringCast()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgent::class),
            $second = $this->createMock(UserAgent::class)
        );
        $first
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');
        $second
            ->expects($this->once())
            ->method('toString')
            ->willReturn('bar');

        $this->assertSame('foo'."\n".'bar', $userAgent->toString());
    }
}
