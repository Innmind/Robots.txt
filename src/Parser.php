<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Parser\Walker;
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;
use Innmind\Http\{
    Request,
    Method,
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

    private function __construct(
        Transport $fulfill,
        string $userAgent,
    ) {
        $this->fulfill = $fulfill;
        $this->walker = Walker::of();
        $this->userAgent = $userAgent;
    }

    /**
     * @return Maybe<RobotsTxt>
     */
    public function __invoke(Url $url): Maybe
    {
        return ($this->fulfill)(
            Request::of(
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
            ->map($this->walker)
            ->map(static fn($directives) => RobotsTxt::of($url, $directives));
    }

    public static function of(Transport $fulfill, string $userAgent): self
    {
        return new self($fulfill, $userAgent);
    }
}
