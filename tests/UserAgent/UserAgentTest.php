<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\UserAgent;

use Innmind\RobotsTxt\{
    UserAgent\UserAgent,
    UserAgent as UserAgentInterface
};
use PHPUnit\Framework\TestCase;

class UserAgentTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            UserAgentInterface::class,
            new UserAgent('*')
        );
    }

    /**
     * @expectedException Innmind\RobotsTxt\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyUserAgent()
    {
        new UserAgent('');
    }

    /**
     * @dataProvider cases
     */
    public function testMatches(string $pattern, string $userAgent, bool $expected)
    {
        $this->assertSame(
            $expected,
            (new UserAgent($pattern))->matches($userAgent)
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            'User-agent: GoogleBot',
            (string) new UserAgent('GoogleBot')
        );
    }

    public function cases(): array
    {
        return [
            ['*', 'GoogleBot', true],
            ['*', 'InnmindCrawler', true],
            ['GoogleBot', 'InnmindCrawler', false],
            ['GoogleBot', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', true],
        ];
    }
}
