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
use Innmind\Immutable\{
    Str,
    Sequence,
    Set,
    Pair,
    Map,
};

/**
 * @internal
 */
final class Walker
{
    private Set $supportedKeys;

    public function __construct()
    {
        $this->supportedKeys = Set::strings(
            'user-agent',
            'allow',
            'disallow',
            'crawl-delay',
        );
    }

    /**
     * @param Sequence<Str> $lines
     *
     * @return Sequence<Directives>
     */
    public function __invoke(Sequence $lines): Sequence
    {
        /** @var Sequence<Directives> */
        return $lines
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
            ->filter(function(Pair $line): bool {
                return $this->supportedKeys->contains($line->key()->toString());
            })
            ->map(function(Pair $directive): object {
                return $this->transformLineToObject($directive);
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
            });
    }

    /**
     * @param Pair<Str, Str> $directive
     *
     * @return UserAgent|Allow|Disallow|CrawlDelay
     */
    private function transformLineToObject(Pair $directive): object
    {
        return match ($directive->key()->toString()) {
            'user-agent' => UserAgent::of($directive->value()->toString()),
            'allow' => Allow::of(
                UrlPattern::of($directive->value()->toString()),
            ),
            'disallow' => Disallow::of(
                UrlPattern::of($directive->value()->toString()),
            ),
            'crawl-delay' => CrawlDelay::of((int) $directive->value()->toString()),
        };
    }
}
