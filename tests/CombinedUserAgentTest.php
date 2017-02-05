<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    CombinedUserAgent,
    UserAgentInterface
};

class CombinedUserAgentTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            UserAgentInterface::class,
            new CombinedUserAgent(
                $this->createMock(UserAgentInterface::class),
                $this->createMock(UserAgentInterface::class)
            )
        );
    }

    public function testMatchesWithFirstUserAgent()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
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
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
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
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
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
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
        );
        $first
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('foo');
        $second
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('bar');

        $this->assertSame('foo'."\n".'bar', (string) $userAgent);
    }
}
