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
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testExecution()
    {
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
        $url = Url::of('http://example.com');
        $parse = Parser::of(
            Transport::via(
                function(Request $request) use ($url, $response): Either {
                    $this->assertSame($url, $request->url());
                    $this->assertSame('GET', $request->method()->toString());
                    $this->assertSame('2.0', $request->protocolVersion()->toString());
                    $this->assertCount(1, $request->headers());
                    $this->assertSame(
                        'User-Agent: InnmindCrawler',
                        $request->headers()->get('user-agent')->match(
                            static fn($header) => $header->toString(),
                            static fn() => null,
                        ),
                    );
                    $this->assertSame('', $request->body()->toString());

                    return Either::right(new Success(
                        $request,
                        $response,
                    ));
                },
            ),
            'InnmindCrawler',
        );
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
        $url = Url::of('http://example.com');
        $response = Response::of(
            StatusCode::notFound,
            ProtocolVersion::v11,
        );
        $parse = Parser::of(
            Transport::via(
                static fn(Request $request) => Either::left(new ClientError(
                    $request,
                    $response,
                )),
            ),
            'InnmindCrawler',
        );

        $this->assertNull($parse($url)->match(
            static fn($robots) => $robots,
            static fn() => null,
        ));
    }
}
