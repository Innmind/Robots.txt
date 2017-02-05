<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser,
    ParserInterface,
    Parser\Walker,
    RobotsTxtInterface
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\Url\UrlInterface;
use Innmind\Http\Message\{
    Request,
    StatusCode,
    ResponseInterface
};
use Innmind\Filesystem\StreamInterface;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ParserInterface::class,
            new Parser(
                $this->createMock(TransportInterface::class),
                new Walker,
                'foo'
            )
        );
    }

    public function testExecution()
    {
        $parse = new Parser(
            $transport = $this->createMock(TransportInterface::class),
            new Walker,
            'InnmindCrawler'
        );
        $url = $this->createMock(UrlInterface::class);
        $transport
            ->expects($this->once())
            ->method('fulfill')
            ->with($this->callback(function(Request $request) use ($url): bool {
                return $request->url() === $url &&
                    (string) $request->method() === 'GET' &&
                    (string) $request->protocolVersion() === '2.0' &&
                    $request->headers()->count() === 1 &&
                    $request->headers()->has('user-agent') &&
                    (string) $request->headers()->get('user-agent') === 'User-Agent : InnmindCrawler' &&
                    (string) $request->body() === '';
            }))
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(200));
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                $stream = $this->createMock(StreamInterface::class)
            );
        $stream
            ->expects($this->once())
            ->method('__toString')
            ->willReturn(<<<TXT
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

        $this->assertInstanceOf(RobotsTxtInterface::class, $robots);
        $this->assertSame($url, $robots->url());
        $this->assertSame($expected, (string) $robots);
    }

    /**
     * @expectedException Innmind\RobotsTxt\Exception\FileNotFoundException
     */
    public function testThrowWhenRequestNotFulfilled()
    {
        $parse = new Parser(
            $transport = $this->createMock(TransportInterface::class),
            new Walker,
            'InnmindCrawler'
        );
        $url = $this->createMock(UrlInterface::class);
        $transport
            ->expects($this->once())
            ->method('fulfill')
            ->willReturn(
                $response = $this->createMock(ResponseInterface::class)
            );
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(new StatusCode(404));
        $response
            ->expects($this->never())
            ->method('body');

        $parse($url);
    }
}
