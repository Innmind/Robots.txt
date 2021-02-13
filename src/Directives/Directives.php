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
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\{
    assertSet,
    join,
};

final class Directives implements DirectivesInterface
{
    private UserAgent $userAgent;
    /** @var Set<Allow> */
    private Set $allow;
    /** @var Set<Disallow> */
    private Set $disallow;
    private ?CrawlDelay $crawlDelay = null;
    private ?string $string = null;

    /**
     * @param Set<Allow> $allow
     * @param Set<Disallow> $disallow
     */
    public function __construct(
        UserAgent $userAgent,
        Set $allow,
        Set $disallow,
        CrawlDelay $crawlDelay = null
    ) {
        assertSet(Allow::class, $allow, 2);
        assertSet(Disallow::class, $disallow, 3);

        $this->userAgent = $userAgent;
        $this->allow = $allow;
        $this->disallow = $disallow;
        $this->crawlDelay = $crawlDelay;
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

        try {
            $this->disallow->find(
                static fn(Disallow $disallow): bool => $disallow->matches($url),
            );

            // if a disallow directive is found and the url is not explicitly
            // allowed then the url is considered disallowed
            return !$this->allows($url);
        } catch (NoElementMatchingPredicateFound $e) {
            return false;
        }
    }

    /**
     * @psalm-suppress InvalidNullableReturnType
     */
    public function crawlDelay(): CrawlDelay
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->crawlDelay;
    }

    public function hasCrawlDelay(): bool
    {
        return $this->crawlDelay instanceof CrawlDelay;
    }

    public function toString(): string
    {
        if ($this->string !== null) {
            return $this->string;
        }

        $string = $this->userAgent->toString();

        if ($this->allow->size() > 0) {
            $allow = $this->allow->mapTo(
                'string',
                static fn(Allow $allow): string => $allow->toString(),
            );

            $string .= join("\n", $allow)->prepend("\n")->toString();
        }

        if ($this->disallow->size() > 0) {
            $disallow = $this->disallow->mapTo(
                'string',
                static fn(Disallow $disallow): string => $disallow->toString(),
            );

            $string .= join("\n", $disallow)->prepend("\n")->toString();
        }

        if ($this->crawlDelay) {
            $string .= "\n".$this->crawlDelay->toString();
        }

        return $this->string = $string;
    }

    private function allows(string $url): bool
    {
        try {
            $this->allow->find(
                static fn(Allow $allow): bool => $allow->matches($url),
            );

            return true;
        } catch (NoElementMatchingPredicateFound $e) {
            return false;
        }
    }

    private function clean(Url $url): Url
    {
        return $url
            ->withoutScheme()
            ->withoutAuthority()
            ->withoutFragment();
    }
}
