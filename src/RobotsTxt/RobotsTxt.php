<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\RobotsTxt;

use Innmind\RobotsTxt\{
    RobotsTxt as RobotsTxtInterface,
    Directives,
};
use Innmind\Url\Url;
use Innmind\Immutable\Sequence;
use function Innmind\Immutable\join;

final class RobotsTxt implements RobotsTxtInterface
{
    private Url $url;
    private Sequence $directives;

    public function __construct(
        Url $url,
        Sequence $directives
    ) {
        if ((string) $directives->type() !== Directives::class) {
            throw new \TypeError(sprintf(
                'Argument 2 must be of type Sequence<%s>',
                Directives::class
            ));
        }

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

        if ($directives->size() === 0) {
            return false;
        }

        return $directives->reduce(
            false,
            static function(bool $carry, Directives $directives) use ($url): bool {
                if ($carry === true) {
                    return $carry;
                }

                return $directives->disallows($url);
            }
        );
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
