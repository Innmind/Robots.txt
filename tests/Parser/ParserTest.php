<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt\Parser;

use Innmind\RobotsTxt\{
    Parser\Parser,
    Parser as ParserInterface,
    Parser\Walker,
    RobotsTxt,
    Exception\FileNotFound,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;
use Innmind\Http\Message\{
    Request\Request,
    StatusCode,
    Response,
};
use Innmind\Stream\Readable;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new Parser(
                $this->createMock(Transport::class),
                'foo'
            )
        );
    }

    public function testExecution()
    {
        $parse = new Parser(
            $transport = $this->createMock(Transport::class),
            'InnmindCrawler'
        );
        $url = Url::of('http://example.com');
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request) use ($url): bool {
                return $request->url() === $url &&
                    $request->method()->toString() === 'GET' &&
                    $request->protocolVersion()->toString() === '2.0' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->contains('user-agent') &&
                    $request->headers()->get('user-agent')->toString() === 'User-Agent: InnmindCrawler' &&
                    $request->body()->toString() === '';
            }))
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                Readable\Stream::ofContent(<<<TXT
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
TXT
                ),
            );
        $expected = 'User-agent: Foo'."\n";
        $expected .= 'User-agent: Bar'."\n";
        $expected .= 'Allow: /foo'."\n";
        $expected .= 'Disallow: /bar'."\n";
        $expected .= ''."\n";
        $expected .= 'User-agent: *'."\n";
        $expected .= 'Disallow: '."\n";
        $expected .= 'Crawl-delay: 20';

        $robots = $parse($url);

        $this->assertInstanceOf(RobotsTxt::class, $robots);
        $this->assertSame($url, $robots->url());
        $this->assertSame($expected, $robots->toString());
    }

    public function testThrowWhenRequestNotFulfilled()
    {
        $parse = new Parser(
            $transport = $this->createMock(Transport::class),
            'InnmindCrawler'
        );
        $url = Url::of('http://example.com');
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(
                $response = $this->createMock(Response::class)
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(404));
        $response
            ->expects($this->never())
            ->method('body');

        $this->expectException(FileNotFound::class);

        $parse($url);
    }
}
