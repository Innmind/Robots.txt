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
use function Innmind\Immutable\join;

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
        return $lines
            ->map(static function(Str $line): Str {
                return $line
                    ->pregReplace('/ #.*/', '')
                    ->trim();
            })
            ->filter(static fn(Str $line): bool => !$line->empty())
            ->filter(static fn(Str $line): bool => $line->split(':')->size() >= 2)
            ->mapTo(
                Pair::class,
                static function(Str $line): Pair {
                    $parts = $line->split(':');

                    $directive = $parts->drop(1)->mapTo(
                        'string',
                        static fn(Str $part): string => $part->toString(),
                    );

                    return new Pair(
                        $parts->first()->toLower()->trim(),
                        join(':', $directive)->trim(),
                    );
                },
            )
            ->filter(function(Pair $line): bool {
                return $this->supportedKeys->contains($line->key()->toString());
            })
            ->mapTo(
                UserAgent::class.'|'.Allow::class.'|'.Disallow::class.'|'.CrawlDelay::class,
                function(Pair $directive): object {
                    return $this->transformLineToObject($directive);
                },
            )
            ->reduce(
                // it would be nice to find a way not to unwrap the whole content
                // of the sequence here but continue deferring the parsing
                Sequence::of(UserAgent::class.'|'.Allow::class.'|'.Disallow::class.'|'.CrawlDelay::class),
                function(Sequence $directives, object $directive): Sequence {
                    return $this->groupUserAgents($directives, $directive);
                },
            )
            ->reduce(
                Sequence::of(Map::class),
                function(Sequence $directives, object $directive): Sequence {
                    return $this->groupDirectives($directives, $directive);
                },
            )
            ->mapTo(
                Directives::class,
                static function(Map $directive): Directives {
                    return new Directives\Directives(
                        $directive->get('user-agent'),
                        $directive->get('allow'),
                        $directive->get('disallow'),
                        $directive->contains('crawl-delay') ? $directive->get('crawl-delay') : null,
                    );
                },
            );
    }

    /**
     * @param Pair<Str, Str> $directive
     *
     * @return UserAgent|Allow|Disallow|CrawlDelay
     */
    private function transformLineToObject(Pair $directive): object
    {
        switch ($directive->key()->toString()) {
            case 'user-agent':
                return new UserAgent\UserAgent($directive->value()->toString());

            case 'allow':
                return new Allow(
                    new UrlPattern($directive->value()->toString()),
                );

            case 'disallow':
                return new Disallow(
                    new UrlPattern($directive->value()->toString()),
                );

            case 'crawl-delay':
                return new CrawlDelay((int) $directive->value()->toString());
        }
    }

    /**
     * @param Sequence<UserAgent|Allow|Disallow|CrawlDelay> $directives
     * @param UserAgent|Allow|Disallow|CrawlDelay $directive
     *
     * @return Sequence<UserAgent|Allow|Disallow|CrawlDelay>
     */
    private function groupUserAgents(Sequence $directives, object $directive): Sequence
    {
        if ($directives->empty()) {
            return ($directives)($directive);
        }

        $last = $directives->last();

        if (
            !$last instanceof UserAgent ||
            !$directive instanceof UserAgent
        ) {
            return ($directives)($directive);
        }

        return $directives
            ->dropEnd(1)
            ->add(
                new UserAgent\CombinedUserAgent(
                    $last,
                    $directive,
                ),
            );
    }

    /**
     * @param Sequence<Map<string, object>> $directives
     * @param UserAgent|Allow|Disallow|CrawlDelay $directive
     *
     * @return Sequence<Map<string, object>>
     */
    private function groupDirectives(Sequence $directives, object $directive): Sequence {
        if ($directive instanceof UserAgent) {
            return ($directives)(
                Map::of('string', 'object')
                    ('user-agent', $directive)
                    ('allow', Set::of(Allow::class))
                    ('disallow', Set::of(Disallow::class))
            );
        }

        if ($directives->empty()) {
            return $directives;
        }

        $last = $directives->last();

        switch (true) {
            case $directive instanceof Allow:
                $last = ($last)(
                    'allow',
                    $last->get('allow')->add($directive),
                );
                break;

            case $directive instanceof Disallow:
                $last = ($last)(
                    'disallow',
                    $last->get('disallow')->add($directive),
                );
                break;

            case $directive instanceof CrawlDelay:
                $last = ($last)('crawl-delay', $directive);
        }

        return $directives
            ->dropEnd(1)
            ->add($last);
    }
}
