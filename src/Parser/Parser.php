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
    ProtocolVersion,
    Headers,
    Header,
    Header\Value\Value,
};

final class Parser implements ParserInterface
{
    private Transport $fulfill;
    private Walker $walker;
    private string $userAgent;

    public function __construct(
        Transport $fulfill,
        string $userAgent,
    ) {
        $this->fulfill = $fulfill;
        $this->walker = new Walker;
        $this->userAgent = $userAgent;
    }

    public function __invoke(Url $url): RobotsTxt
    {
        $response = ($this->fulfill)(
            new Request(
                $url,
                Method::get,
                ProtocolVersion::v20,
                Headers::of(
                    new Header\Header(
                        'User-Agent',
                        new Value($this->userAgent),
                    ),
                ),
            ),
        )->match(
            static fn($success) => $success->response(),
            static fn() => throw new FileNotFound($url->toString()),
        );

        $directives = ($this->walker)(
            $response
                ->body()
                ->lines()
                ->map(static fn($line) => $line->str())
        );

        return new RobotsTxt\RobotsTxt(
            $url,
            $directives,
        );
    }
}
