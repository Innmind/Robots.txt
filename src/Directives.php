<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Str,
    Maybe,
    Sequence,
};

/**
 * @psalm-immutable
 */
final class Directives
{
    private UserAgent $userAgent;
    /** @var Sequence<Allow> */
    private Sequence $allow;
    /** @var Sequence<Disallow> */
    private Sequence $disallow;
    /** @var Maybe<CrawlDelay> */
    private Maybe $crawlDelay;

    /**
     * @param Sequence<Allow> $allow
     * @param Sequence<Disallow> $disallow
     * @param Maybe<CrawlDelay> $crawlDelay
     */
    private function __construct(
        UserAgent $userAgent,
        Sequence $allow,
        Sequence $disallow,
        Maybe $crawlDelay,
    ) {
        $this->userAgent = $userAgent;
        $this->allow = $allow;
        $this->disallow = $disallow;
        $this->crawlDelay = $crawlDelay;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Allow> $allow
     * @param Sequence<Disallow> $disallow
     */
    public static function of(
        UserAgent $userAgent,
        Sequence $allow = null,
        Sequence $disallow = null,
        CrawlDelay $crawlDelay = null,
    ): self {
        return new self(
            $userAgent,
            $allow ?? Sequence::of(),
            $disallow ?? Sequence::of(),
            Maybe::of($crawlDelay),
        );
    }

    public function withAllow(Allow $allow): self
    {
        return new self(
            $this->userAgent,
            ($this->allow)($allow),
            $this->disallow,
            $this->crawlDelay,
        );
    }

    public function withDisallow(Disallow $disallow): self
    {
        return new self(
            $this->userAgent,
            $this->allow,
            ($this->disallow)($disallow),
            $this->crawlDelay,
        );
    }

    public function withCrawlDelay(CrawlDelay $crawlDelay): self
    {
        return self::of(
            $this->userAgent,
            $this->allow,
            $this->disallow,
            $crawlDelay,
        );
    }

    public function targets(string $userAgent): bool
    {
        return $this->userAgent->matches($userAgent);
    }

    public function disallows(Url $url): bool
    {
        $url = $this->clean($url)->toString();

        return $this
            ->disallow
            ->find(static fn(Disallow $disallow): bool => $disallow->matches($url))
            // if a disallow directive is found and the url is not explicitly
            // allowed then the url is considered disallowed
            ->filter(fn() => !$this->allows($url))
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }

    /**
     * @return Maybe<CrawlDelay>
     */
    public function crawlDelay(): Maybe
    {
        return $this->crawlDelay;
    }

    public function asContent(): Content
    {
        $lines = $this
            ->userAgent
            ->asContent()
            ->lines()
            ->append(
                $this
                    ->allow
                    ->map(static fn($allow) => $allow->toString())
                    ->map(Str::of(...))
                    ->map(Content\Line::of(...)),
            )
            ->append(
                $this
                    ->disallow
                    ->map(static fn($disallow) => $disallow->toString())
                    ->map(Str::of(...))
                    ->map(Content\Line::of(...)),
            )
            ->append(
                $this
                    ->crawlDelay
                    ->map(static fn($delay) => $delay->toString())
                    ->map(Str::of(...))
                    ->map(Content\Line::of(...))
                    ->match(
                        static fn($line) => Sequence::of($line),
                        static fn() => Sequence::of(),
                    ),
            );

        return Content::ofLines($lines);
    }

    private function allows(string $url): bool
    {
        return $this
            ->allow
            ->find(static fn(Allow $allow): bool => $allow->matches($url))
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }

    private function clean(Url $url): Url
    {
        return $url
            ->withoutScheme()
            ->withoutAuthority()
            ->withoutFragment();
    }
}
