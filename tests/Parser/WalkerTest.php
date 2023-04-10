<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\Parser;

use Innmind\RobotsTxt\{
    Parser\Walker,
    Directives,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Set,
};
use PHPUnit\Framework\TestCase;

class WalkerTest extends TestCase
{
    public function testExecution()
    {
        $robots = <<<TXT
Sitemap : foo.xml
Host : example.com
Crawl-delay: 10
User-agent : Foo # some comment here
User-agent : Bar
Allow : /foo # and there
Disallow : /bar

User-agent : *
Disallow : #and me too
Crawl-delay : 10
Crawl-delay : 20 #
TXT;
        $firstDirectives = <<<TXT
User-agent: Foo
User-agent: Bar
Allow: /foo
Disallow: /bar
TXT;
        $secondDirectives = 'User-agent: *'."\n";
        $secondDirectives .= 'Disallow: '."\n";
        $secondDirectives .= 'Crawl-delay: 20';

        $stream = (new Walker)(Str::of($robots)->split("\n"));

        $this->assertInstanceOf(Sequence::class, $stream);
        $this->assertCount(2, $stream);
        $this->assertSame(
            $firstDirectives,
            $stream->first()->match(
                static fn($directive) => $directive->toString(),
                static fn() => null,
            ),
        );
        $this->assertSame(
            $secondDirectives,
            $stream->last()->match(
                static fn($directive) => $directive->toString(),
                static fn() => null,
            ),
        );
    }
}
