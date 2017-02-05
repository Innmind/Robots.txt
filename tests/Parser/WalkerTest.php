<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\Parser;

use Innmind\RobotsTxt\{
    Parser\Walker,
    DirectivesInterface
};
use Innmind\Immutable\{
    Str,
    StreamInterface
};

class WalkerTest extends \PHPUnit_Framework_TestCase
{
    public function testExecution()
    {
        $robots = <<<TXT
Sitemap : foo.xml
Host : example.com
Crawl-delay: 10
User-agent : Foo
User-agent : Bar
Allow : /foo
Disallow : /bar

User-agent : *
Disallow :
Crawl-delay : 10
Crawl-delay : 20
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

        $stream = (new Walker)(new Str($robots));

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(DirectivesInterface::class, (string) $stream->type());
        $this->assertCount(2, $stream);
        $this->assertSame(
            $firstDirectives,
            (string) $stream->first()
        );
        $this->assertSame(
            $secondDirectives,
            (string) $stream->last()
        );
    }
}
