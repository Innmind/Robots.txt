<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\{
    Parser\Walker,
    Exception\FileNotFoundException
};
use Innmind\HttpTransport\TransportInterface;
use Innmind\Url\UrlInterface;
use Innmind\Http\{
    Message\Request,
    Message\Method,
    Message\StatusCode,
    ProtocolVersion,
    Headers,
    Header\HeaderInterface,
    Header\HeaderValueInterface,
    Header\Header,
    Header\HeaderValue
};
use Innmind\Filesystem\Stream\NullStream;
use Innmind\Immutable\{
    Map,
    Str,
    Set
};

final class Parser implements ParserInterface
{
    private $transport;
    private $walker;
    private $userAgent;

    public function __construct(
        TransportInterface $transport,
        Walker $walker,
        string $userAgent
    ) {
        $this->transport = $transport;
        $this->walker = $walker;
        $this->userAgent = $userAgent;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(UrlInterface $url): RobotsTxtInterface
    {
        $response = $this->transport->fulfill(
            new Request(
                $url,
                new Method(Method::GET),
                new ProtocolVersion(2, 0),
                new Headers(
                    (new Map('string', HeaderInterface::class))
                        ->put(
                            'User-Agent',
                            new Header(
                                'User-Agent',
                                (new Set(HeaderValueInterface::class))
                                    ->add(new HeaderValue($this->userAgent))
                            )
                        )
                ),
                new NullStream
            )
        );

        if ($response->statusCode()->value() !== StatusCode::codes()->get('OK')) {
            throw new FileNotFoundException;
        }

        $directives = ($this->walker)(new Str((string) $response->body()));

        return new RobotsTxt(
            $url,
            $directives
        );
    }
}
