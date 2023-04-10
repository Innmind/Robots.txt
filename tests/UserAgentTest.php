<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    UserAgent,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class UserAgentTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testMatches(string $pattern, string $userAgent, bool $expected)
    {
        $this->assertSame(
            $expected,
            UserAgent::of($pattern)->matches($userAgent),
        );
    }

    public function testMatchesWithMultipleAgents()
    {
        $userAgent = UserAgent::of('Innmind')->and('GoogleBot');

        $this->assertTrue($userAgent->matches('Innmind'));
        $this->assertTrue($userAgent->matches('GoogleBot'));
        $this->assertFalse($userAgent->matches('Unknown'));
    }

    public function testStringCast()
    {
        $this->assertSame(
            'User-agent: GoogleBot',
            UserAgent::of('GoogleBot')->asContent()->toString(),
        );
        $this->assertSame(
            'User-agent: Innmind'."\n".'User-agent: GoogleBot',
            UserAgent::of('Innmind')->and('GoogleBot')->asContent()->toString(),
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
