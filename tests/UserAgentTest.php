<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    UserAgent,
    UserAgentInterface
};

class UserAgentTest extends \PHPUnit_Framework_TestCase
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
    public function testMatch(string $pattern, string $userAgent, bool $expected)
    {
        $this->assertSame(
            $expected,
            (new UserAgent($pattern))->match($userAgent)
        );
    }

    public function testStringCast()
    {
        $this->assertSame(
            'User-agent: *',
            (string) new UserAgent('*')
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
