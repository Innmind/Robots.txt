<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Filesystem\File\Content;
use Innmind\Url\Url;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class RobotsTxt
{
    private Url $url;
    /** @var Sequence<Directives> */
    private Sequence $directives;

    /**
     * @param Sequence<Directives> $directives
     */
    public function __construct(Url $url, Sequence $directives)
    {
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
        return $this
            ->directives
            ->filter(static function(Directives $directives) use ($userAgent): bool {
                return $directives->targets($userAgent);
            })
            ->find(static fn($directives) => $directives->disallows($url))
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }

    public function asContent(): Content
    {
        return Content\Lines::of(
            $this
                ->directives
                ->map(static fn($directive) => $directive->asContent())
                ->flatMap(static fn($content) => $content->lines()->add(Content\Line::of(Str::of('')))),
        );
    }
}
