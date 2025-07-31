<?php
declare(strict_types = 1);

namespace Tests\Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser,
    RobotsTxt,
};
use Innmind\HttpTransport\{
    Transport,
    ClientError,
    Success,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Http\{
    Request,
    Response,
    Response\StatusCode,
    ProtocolVersion,
};
use Innmind\Immutable\Either;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testExecution()
    {
        $parse = Parser::of(
            $transport = $this->createMock(Transport::class),
            'InnmindCrawler',
        );
        $url = Url::of('http://example.com');
        $response = Response::of(
            StatusCode::ok,
            ProtocolVersion::v11,
            null,
            Content::ofString(<<<TXT
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
            TXT),
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
            ->willReturnCallback(static fn($request) => Either::right(new Success(
                $request,
                $response,
            )));
        $expected = 'User-agent: Foo'."\n";
        $expected .= 'User-agent: Bar'."\n";
        $expected .= 'Allow: /foo'."\n";
        $expected .= 'Disallow: /bar'."\n";
        $expected .= ''."\n";
        $expected .= 'User-agent: *'."\n";
        $expected .= 'Disallow: '."\n";
        $expected .= 'Crawl-delay: 20'."\n";

        $robots = $parse($url)->match(
            static fn($robots) => $robots,
            static fn() => null,
        );

        $this->assertInstanceOf(RobotsTxt::class, $robots);
        $this->assertSame($url, $robots->url());
        $this->assertSame($expected, $robots->asContent()->toString());
    }

    public function testThrowWhenRequestNotFulfilled()
    {
        $parse = Parser::of(
            $transport = $this->createMock(Transport::class),
            'InnmindCrawler',
        );
        $url = Url::of('http://example.com');
        $response = Response::of(
            StatusCode::notFound,
            ProtocolVersion::v11,
        );
        $transport
            ->expects($this->once())
            ->method('__invoke')
            ->willReturnCallback(static fn($request) => Either::left(new ClientError(
                $request,
                $response,
            )));

        $this->assertNull($parse($url)->match(
            static fn($robots) => $robots,
            static fn() => null,
        ));
    }
}
