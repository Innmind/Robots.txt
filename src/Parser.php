<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Parser\Walker;
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
use Innmind\Immutable\Maybe;

final class Parser
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

    /**
     * @return Maybe<RobotsTxt>
     */
    public function __invoke(Url $url): Maybe
    {
        return ($this->fulfill)(
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
        )
            ->maybe()
            ->map(static fn($success) => $success->response()->body()->lines())
            ->map(static fn($lines) => $lines->map(static fn($line) => $line->str()))
            ->map($this->walker)
            ->map(static fn($directives) => new RobotsTxt($url, $directives));
    }
}
