<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Exception\InvalidArgumentException;
use Innmind\Url\{
    UrlInterface,
    Url,
    NullScheme,
    Authority,
    Authority\UserInformation,
    Authority\UserInformation\NullUser,
    Authority\UserInformation\NullPassword,
    Authority\NullHost,
    Authority\NullPort,
    NullFragment
};
use Innmind\Immutable\SetInterface;

final class Directives implements DirectivesInterface
{
    private $userAgent;
    private $allow;
    private $disallow;
    private $crawlDelay;

    public function __construct(
        UserAgentInterface $userAgent,
        SetInterface $allow,
        SetInterface $disallow,
        CrawlDelay $crawlDelay = null
    ) {
        if (
            (string) $allow->type() !== Allow::class ||
            (string) $disallow->type() !== Disallow::class
        ) {
            throw new InvalidArgumentException;
        }

        $this->userAgent = $userAgent;
        $this->allow = $allow;
        $this->disallow = $disallow;
        $this->crawlDelay = $crawlDelay;
    }

    public function targets(string $userAgent): bool
    {
        return $this->userAgent->matches($userAgent);
    }

    public function disallows(UrlInterface $url): bool
    {
        $url = (string) $this->clean($url);
        $disallow = $this
            ->disallow
            ->reduce(
                false,
                function(bool $carry, Disallow $disallow) use ($url): bool {
                    if ($carry === true) {
                        return $carry;
                    }

                    return $disallow->matches($url);
                }
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

    public function __toString(): string
    {
        $string = (string) $this->userAgent;
        $string .= "\n";
        $string = $this
            ->allow
            ->reduce(
                $string,
                function(string $carry, Allow $allow): string {
                    return $carry.$allow."\n";
                }
            );
        $string = $this
            ->disallow
            ->reduce(
                $string,
                function(string $carry, Disallow $disallow): string {
                    return $carry.$disallow."\n";
                }
            );

        if ($this->hasCrawlDelay()) {
            $string .= $this->crawlDelay;
        }

        return $string;
    }

    private function allows(string $url): bool
    {
        return $this
            ->allow
            ->reduce(
                false,
                function(string $carry, Allow $allow) use ($url): bool {
                    if ($carry === true) {
                        return $carry;
                    }

                    return $allow->matches($url);
                }
            );
    }

    private function clean(UrlInterface $url): UrlInterface
    {
        return new Url(
            new NullScheme,
            new Authority(
                new UserInformation(
                    new NullUser,
                    new NullPassword
                ),
                new NullHost,
                new NullPort
            ),
            $url->path(),
            $url->query(),
            new NullFragment
        );
    }
}