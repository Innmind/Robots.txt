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
    /**
     * @param Sequence<Directives> $directives
     */
    private function __construct(
        private Url $url,
        private Sequence $directives,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Directives> $directives
     */
    #[\NoDiscard]
    public static function of(Url $url, Sequence $directives): self
    {
        return new self($url, $directives);
    }

    #[\NoDiscard]
    public function url(): Url
    {
        return $this->url;
    }

    #[\NoDiscard]
    public function directives(): Sequence
    {
        return $this->directives;
    }

    #[\NoDiscard]
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

    #[\NoDiscard]
    public function asContent(): Content
    {
        return Content::ofLines(
            $this
                ->directives
                ->map(static fn($directive) => $directive->asContent())
                ->flatMap(static fn($content) => $content->lines()->add(Content\Line::of(Str::of('')))),
        );
    }
}
