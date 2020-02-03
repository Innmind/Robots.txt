<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\Parser;

use Innmind\RobotsTxt\{
    Parser as ParserInterface,
    RobotsTxt,
    Exception\FileNotFound,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;
use Innmind\Http\{
    Message\Request\Request,
    Message\Method,
    Message\StatusCode,
    ProtocolVersion,
    Headers,
    Header,
    Header\Value\Value,
};

final class Parser implements ParserInterface
{
    private Transport $transport;
    private Walker $walker;
    private string $userAgent;

    public function __construct(
        Transport $transport,
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
    public function __invoke(Url $url): RobotsTxt
    {
        $response = ($this->transport)(
            new Request(
                $url,
                Method::get(),
                new ProtocolVersion(2, 0),
                Headers::of(
                    new Header\Header(
                        'User-Agent',
                        new Value($this->userAgent)
                    )
                )
            )
        );

        if ($response->statusCode()->value() !== StatusCode::codes()->get('OK')) {
            throw new FileNotFound;
        }

        $directives = ($this->walker)($response->body()->read());

        return new RobotsTxt\RobotsTxt(
            $url,
            $directives
        );
    }
}
