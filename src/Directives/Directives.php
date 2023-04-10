<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\Directives;

use Innmind\RobotsTxt\{
    Directives as DirectivesInterface,
    Allow,
    Disallow,
    UserAgent,
    CrawlDelay,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Set,
    Str,
    Maybe,
};

final class Directives implements DirectivesInterface
{
    private UserAgent $userAgent;
    /** @var Set<Allow> */
    private Set $allow;
    /** @var Set<Disallow> */
    private Set $disallow;
    /** @var Maybe<CrawlDelay> */
    private Maybe $crawlDelay;
    private ?string $string = null;

    /**
     * @param Set<Allow> $allow
     * @param Set<Disallow> $disallow
     */
    public function __construct(
        UserAgent $userAgent,
        Set $allow,
        Set $disallow,
        CrawlDelay $crawlDelay = null,
    ) {
        $this->userAgent = $userAgent;
        $this->allow = $allow;
        $this->disallow = $disallow;
        $this->crawlDelay = Maybe::of($crawlDelay);
    }

    public function withAllow(Allow $allow): self
    {
        return new self(
            $this->userAgent,
            ($this->allow)($allow),
            $this->disallow,
            $this->crawlDelay->match(
                static fn($delay) => $delay,
                static fn() => null,
            ),
        );
    }

    public function withDisallow(Disallow $disallow): self
    {
        return new self(
            $this->userAgent,
            $this->allow,
            ($this->disallow)($disallow),
            $this->crawlDelay->match(
                static fn($delay) => $delay,
                static fn() => null,
            ),
        );
    }

    public function withCrawlDelay(CrawlDelay $crawlDelay): self
    {
        return new self(
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

    public function toString(): string
    {
        if ($this->string !== null) {
            return $this->string;
        }

        $string = $this->userAgent->toString();

        if ($this->allow->size() > 0) {
            $allow = $this->allow->map(
                static fn(Allow $allow): string => $allow->toString(),
            );

            $string .= Str::of("\n")->join($allow)->prepend("\n")->toString();
        }

        if ($this->disallow->size() > 0) {
            $disallow = $this->disallow->map(
                static fn(Disallow $disallow): string => $disallow->toString(),
            );

            $string .= Str::of("\n")->join($disallow)->prepend("\n")->toString();
        }

        $string .= $this->crawlDelay->match(
            static fn($delay) => "\n".$delay->toString(),
            static fn() => '',
        );

        return $this->string = $string;
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
