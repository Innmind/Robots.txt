<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser,
    Parser\Walker,
    RobotsTxt,
    Exception\FileNotFound,
};
use Innmind\HttpTransport\{
    Transport,
    ClientError,
    Success,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Http\Message\{
    Request,
    StatusCode,
    Response,
};
use Innmind\Immutable\Either;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testExecution()
    {
        $parse = new Parser(
            $transport = $this->createMock(Transport::class),
            'InnmindCrawler',
        );
        $url = Url::of('http://example.com');
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(StatusCode::ok);
        $response
            ->expects($this->once())
            ->method('body')
            ->willReturn(
                Content\Lines::ofContent(<<<TXT
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
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(static function(Request $request) use ($url): bool {
                return $request->url() === $url &&
                    $request->method()->toString() === 'GET' &&
                    $request->protocolVersion()->toString() === '2.0' &&
                    $request->headers()->count() === 1 &&
                    'User-Agent: InnmindCrawler' === $request->headers()->get('user-agent')->match(
                        static fn($header) => $header->toString(),
                        static fn() => null,
                    ) &&
                    $request->body()->toString() === '';
            }))
            ->willReturn(Either::right(new Success(
                $this->createMock(Request::class),
                $response,
            )));
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
            'InnmindCrawler',
        );
        $url = Url::of('http://example.com');
        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('statusCode')
            ->willReturn(StatusCode::notFound);
        $response
            ->expects($this->never())
            ->method('body');
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(Either::left(new ClientError(
                $this->createMock(Request::class),
                $response,
            )));

        $this->expectException(FileNotFound::class);

        $parse($url);
    }
}
