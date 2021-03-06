<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\RobotsTxt;

use Innmind\RobotsTxt\{
    RobotsTxt as RobotsTxtInterface,
    Directives,
};
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\{
    assertSequence,
    join,
};

final class RobotsTxt implements RobotsTxtInterface
{
    private Url $url;
    /** @var Sequence<Directives> */
    private Sequence $directives;

    /**
     * @param Sequence<Directives> $directives
     */
    public function __construct(Url $url, Sequence $directives)
    {
        assertSequence(Directives::class, $directives, 2);

        $this->url = $url;
        $this->directives = $directives;
    }

    public function url(): Url
    {
        return $this->url;
    }

    public function directives(): Sequence
    {
        return $this->directives;
    }

    public function disallows(string $userAgent, Url $url): bool
    {
        $directives = $this
            ->directives
            ->filter(static function(Directives $directives) use ($userAgent): bool {
                return $directives->targets($userAgent);
            });

        if ($directives->empty()) {
            return false;
        }

        try {
            $directives->find(
                static fn(Directives $directives): bool => $directives->disallows($url),
            );

            return true;
        } catch (NoElementMatchingPredicateFound $e) {
            return false;
        }
    }

    public function toString(): string
    {
        $directives = $this->directives->mapTo(
            'string',
            static fn(Directives $directives): string => $directives->toString(),
        );

        return join("\n\n", $directives)->toString();
    }
}
