<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\Directives;

use Innmind\RobotsTxt\{
    Directives as DirectivesInterface,
    Allow,
    Disallow,
    UserAgent,
    CrawlDelay
};
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
    private $string;

    public function __construct(
        UserAgent $userAgent,
        SetInterface $allow,
        SetInterface $disallow,
        CrawlDelay $crawlDelay = null
    ) {
        if ((string) $allow->type() !== Allow::class) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type SetInterface<%s>',
                Allow::class
            ));
        }

        if ((string) $disallow->type() !== Disallow::class) {
            throw new \TypeError(sprintf(
                'Argument 3 must be of type SetInterface<%s>',
                Disallow::class
            ));
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
        if ($this->string !== null) {
            return $this->string;
        }

        $string = (string) $this->userAgent;

        if ($this->allow->size() > 0) {
            $string .= "\n".$this->allow->join("\n");
        }

        if ($this->disallow->size() > 0) {
            $string .= "\n".$this->disallow->join("\n");
        }

        if ($this->hasCrawlDelay()) {
            $string .= "\n".$this->crawlDelay;
        }

        return $this->string = $string;
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
