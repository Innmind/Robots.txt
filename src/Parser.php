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
    Header\Value,
};
use Innmind\Immutable\Maybe;

final class Parser
{
    private function __construct(
        private Transport $fulfill,
        private Walker $walker,
        private string $userAgent,
    ) {
    }

    /**
     * @return Maybe<RobotsTxt>
     */
    #[\NoDiscard]
    public function __invoke(Url $url): Maybe
    {
        return ($this->fulfill)(
            Request::of(
                $url,
                Method::get,
                ProtocolVersion::v20,
                Headers::of(
                    Header::of(
                        'User-Agent',
                        Value::of($this->userAgent),
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
        return new self($fulfill, Walker::of(), $userAgent);
    }
}
