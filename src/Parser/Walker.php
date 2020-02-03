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
            'crawl-delay'
        );
    }

    /**
     * @return Sequence<Directives>
     */
    public function __invoke(Str $robots): Sequence
    {
        return $robots
            ->split("\n")
            ->map(static function(Str $line): Str {
                return $line
                    ->pregReplace('/ #.*/', '')
                    ->trim();
            })
            ->filter(static function(Str $line): bool {
                return $line->length() > 0;
            })
            ->filter(static function(Str $line): bool {
                return $line->split(':')->size() >= 2;
            })
            ->reduce(
                Sequence::of(Pair::class),
                static function(Sequence $carry, Str $line): Sequence {
                    $parts = $line->split(':');

                    $directive = $parts->drop(1)->mapTo(
                        'string',
                        static fn(Str $part): string => $part->toString(),
                    );

                    return $carry->add(
                        new Pair(
                            $parts->first()->toLower()->trim(),
                            join(':', $directive)->trim()
                        )
                    );
                }
            )
            ->filter(function(Pair $line): bool {
                return $this->supportedKeys->contains($line->key()->toString());
            })
            ->reduce(
                Sequence::objects(),
                function(Sequence $carry, Pair $line): Sequence {
                    return $this->transformLineToObject($carry, $line);
                }
            )
            ->reduce(
                Sequence::objects(),
                function(Sequence $carry, $object): Sequence {
                    return $this->groupUserAgents($carry, $object);
                }
            )
            ->reduce(
                Sequence::of(Map::class),
                function(Sequence $carry, $object): Sequence {
                    return $this->groupDirectives($carry, $object);
                }
            )
            ->reduce(
                Sequence::of(Directives::class),
                static function(Sequence $carry, Map $map): Sequence {
                    return $carry->add(
                        new Directives\Directives(
                            $map->get('user-agent'),
                            $map->get('allow'),
                            $map->get('disallow'),
                            $map->contains('crawl-delay') ? $map->get('crawl-delay') : null
                        )
                    );
                }
            );
    }

    /**
     * @param Sequence<object> $carry
     * @param Pair<Str, Str> $line
     * @return Sequence<object>
     */
    private function transformLineToObject(Sequence $carry, Pair $line): Sequence {
        switch ($line->key()->toString()) {
            case 'user-agent':
                return $carry->add(
                    new UserAgent\UserAgent($line->value()->toString())
                );

            case 'allow':
                return $carry->add(
                    new Allow(
                        new UrlPattern($line->value()->toString())
                    )
                );

            case 'disallow':
                return $carry->add(
                    new Disallow(
                        new UrlPattern($line->value()->toString())
                    )
                );

            case 'crawl-delay':
                return $carry->add(
                    new CrawlDelay((int) $line->value()->toString())
                );
        }
    }

    /**
     * @param Sequence<object> $carry
     * @param object $object
     *
     * @return Sequence<object>
     */
    private function groupUserAgents(Sequence $carry, $object): Sequence {
        if ($carry->size() === 0) {
            return $carry->add($object);
        }

        $last = $carry->last();

        if (
            !$last instanceof UserAgent ||
            !$object instanceof UserAgent
        ) {
            return $carry->add($object);
        }

        return $carry
            ->dropEnd(1)
            ->add(
                new UserAgent\CombinedUserAgent(
                    $last,
                    $object
                )
            );
    }

    /**
     * @param Sequence<Map<string, object>> $carry
     * @param object $object
     *
     * @return Sequence<Map<string, string>>
     */
    private function groupDirectives(Sequence $carry, $object): Sequence {
        if ($object instanceof UserAgent) {
            return $carry->add(
                Map::of('string', 'object')
                    ('user-agent', $object)
                    ('allow', Set::of(Allow::class))
                    ('disallow', Set::of(Disallow::class))
            );
        }

        if ($carry->size() === 0) {
            return $carry;
        }

        $last = $carry->last();

        switch (true) {
            case $object instanceof Allow:
                $last = $last->put(
                    'allow',
                    $last->get('allow')->add($object)
                );
                break;

            case $object instanceof Disallow:
                $last = $last->put(
                    'disallow',
                    $last->get('disallow')->add($object)
                );
                break;

            case $object instanceof CrawlDelay:
                $last = $last->put('crawl-delay', $object);
        }

        return $carry
            ->dropEnd(1)
            ->add($last);
    }
}
