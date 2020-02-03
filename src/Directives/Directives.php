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
use Innmind\Immutable\Set;
use function Innmind\Immutable\{
    assertSet,
    join,
};

final class Directives implements DirectivesInterface
{
    private UserAgent $userAgent;
    private Set $allow;
    private Set $disallow;
    private ?CrawlDelay $crawlDelay = null;
    private ?string $string = null;

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

    public function targets(string $userAgent): bool
    {
        return $this->userAgent->matches($userAgent);
    }

    public function disallows(Url $url): bool
    {
        $url = $this->clean($url)->toString();
        $disallow = $this
            ->disallow
            ->reduce(
                false,
                function(bool $carry, Disallow $disallow) use ($url): bool {
                    if ($carry === true) {
                        return $carry;
                    }

                    return $disallow->matches($url);
                },
            );

        if ($disallow === false) {
            return false;
        }

        return !$this->allows($url);
    }

    /**
     * {@inheritdoc}
     */
    public function crawlDelay(): CrawlDelay
    {
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

        if ($this->hasCrawlDelay()) {
            $string .= "\n".$this->crawlDelay->toString();
        }

        return $this->string = $string;
    }

    private function allows(string $url): bool
    {
        return $this
            ->allow
            ->reduce(
                false,
                function(bool $carry, Allow $allow) use ($url): bool {
                    if ($carry === true) {
                        return $carry;
                    }

                    return $allow->matches($url);
                },
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
