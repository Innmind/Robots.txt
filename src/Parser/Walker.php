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
    Exception\LogicException,
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
        /** @var Sequence<Directives> */
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
                    /** @var Sequence<UserAgent|Allow|Disallow|CrawlDelay> $directives */
                    return $this->groupUserAgents($directives, $directive);
                },
            )
            ->reduce(
                Sequence::of(Directives\Directives::class),
                function(Sequence $directives, object $directive): Sequence {
                    /**
                     * @var UserAgent|Allow|Disallow|CrawlDelay $directive
                     * @var Sequence<Directives\Directives> $directives
                     */
                    return $this->groupDirectives($directives, $directive);
                },
            )
            ->mapTo(
                Directives::class, // simply a type change to the sequence here
                static function(Directives\Directives $directives): Directives {
                    /** @var Directives */
                    return $directives;
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

        throw new LogicException("Unknown directive '{$directive->key()->toString()}'");
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

        /** @var UserAgent */
        $last = $last;
        /** @var UserAgent */
        $directive = $directive;

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
     * @param Sequence<Directives\Directives> $directives
     * @param UserAgent|Allow|Disallow|CrawlDelay $directive
     *
     * @return Sequence<Directives\Directives>
     */
    private function groupDirectives(Sequence $directives, object $directive): Sequence
    {
        if ($directive instanceof UserAgent) {
            /** @var UserAgent */
            $directive = $directive;

            return ($directives)(
                new Directives\Directives(
                    $directive,
                    Set::of(Allow::class),
                    Set::of(Disallow::class),
                ),
            );
        }

        // means we don't take into account any directive not specified under a
        // user-agent
        if ($directives->empty()) {
            return $directives;
        }

        $currentDirectives = $directives->last();

        switch (true) {
            case $directive instanceof Allow:
                $currentDirectives = $currentDirectives->withAllow($directive);
                break;

            case $directive instanceof Disallow:
                $currentDirectives = $currentDirectives->withDisallow($directive);
                break;

            case $directive instanceof CrawlDelay:
                $currentDirectives = $currentDirectives->withCrawlDelay($directive);
                break;
        }

        return $directives
            ->dropEnd(1)
            ->add($currentDirectives);
    }
}
