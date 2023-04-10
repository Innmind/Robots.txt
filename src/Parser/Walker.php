<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\Parser;

use Innmind\RobotsTxt\{
    UserAgent,
    Allow,
    Disallow,
    UrlPattern,
    CrawlDelay,
    Directives,
};
use Innmind\Filesystem\File\Content\Line;
use Innmind\Immutable\{
    Str,
    Sequence,
    Pair,
    Map,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Walker
{
    private function __construct()
    {
    }

    /**
     * @param Sequence<Line> $lines
     *
     * @return Sequence<Directives>
     */
    public function __invoke(Sequence $lines): Sequence
    {
        return $lines
            ->map(static fn($line) => $line->str())
            ->map(static function(Str $line): Str {
                return $line
                    ->pregReplace('/ #.*/', '')
                    ->trim();
            })
            ->filter(static fn(Str $line): bool => !$line->empty())
            ->flatMap(static fn($line) =>  $line->split(':')->match(
                static fn($first, $directive) => Sequence::of(new Pair(
                    $first->toLower()->trim(),
                    Str::of(':')
                        ->join($directive->map(static fn($part) => $part->toString()))
                        ->trim(),
                )),
                static fn() => Sequence::of(),
            ))
            ->flatMap(static fn($directive) => match ($directive->key()->toString()) {
                'user-agent' => Sequence::of(UserAgent::of($directive->value()->toString())),
                'allow' => Sequence::of(Allow::of(
                    UrlPattern::of($directive->value()->toString()),
                )),
                'disallow' => Sequence::of(Disallow::of(
                    UrlPattern::of($directive->value()->toString()),
                )),
                'crawl-delay' => CrawlDelay::maybe($directive->value()->toString())->match(
                    static fn($delay) => Sequence::of($delay),
                    static fn() => Sequence::of(),
                ),
                default => Sequence::of(),
            })
            ->aggregate(static function(UserAgent|Allow|Disallow|CrawlDelay $a, $b) {
                if ($a instanceof UserAgent && $b instanceof UserAgent) {
                    return Sequence::of($a->merge($b));
                }

                return Sequence::of($a, $b);
            })
            ->map(static fn($directive) => match (true) {
                $directive instanceof UserAgent => Directives::of($directive),
                default => $directive,
            })
            ->aggregate(static function($a, $b) {
                if (!$a instanceof Directives) {
                    return Sequence::of($b);
                }

                if ($b instanceof Directives) {
                    return Sequence::of($a, $b);
                }

                return Sequence::of(match (true) {
                    $b instanceof Allow => $a->withAllow($b),
                    $b instanceof Disallow => $a->withDisallow($b),
                    $b instanceof CrawlDelay => $a->withCrawlDelay($b),
                });
            })
            ->keep(Instance::of(Directives::class));
    }

    public static function of(): self
    {
        return new self;
    }
}
