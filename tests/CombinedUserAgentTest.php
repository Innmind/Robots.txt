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

    public function testMatchWithFirstUserAgent()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
        );
        $first
            ->expects($this->once())
            ->method('match')
            ->with('foo')
            ->willReturn(true);
        $second
            ->expects($this->never())
            ->method('match');

        $this->assertTrue($userAgent->match('foo'));
    }

    public function testMatchWithSecondUserAgent()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
        );
        $first
            ->expects($this->once())
            ->method('match')
            ->with('foo')
            ->willReturn(false);
        $second
            ->expects($this->once())
            ->method('match')
            ->with('foo')
            ->willReturn(true);

        $this->assertTrue($userAgent->match('foo'));
    }

    public function testDoesnMatch()
    {
        $userAgent = new CombinedUserAgent(
            $first = $this->createMock(UserAgentInterface::class),
            $second = $this->createMock(UserAgentInterface::class)
        );
        $first
            ->expects($this->once())
            ->method('match')
            ->with('foo')
            ->willReturn(false);
        $second
            ->expects($this->once())
            ->method('match')
            ->with('foo')
            ->willReturn(false);

        $this->assertFalse($userAgent->match('foo'));
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
