<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\Parser;

use Innmind\RobotsTxt\{
    UserAgent,
    Allow,
    Disallow,
    UrlPattern,
    CrawlDelay,
    Directives
};
use Innmind\Immutable\{
    Str,
    StreamInterface,
    Stream,
    Set,
    Pair,
    Map
};

final class Walker
{
    private $supportedKeys;

    public function __construct()
    {
        $this->supportedKeys = (new Set('string'))
            ->add('user-agent')
            ->add('allow')
            ->add('disallow')
            ->add('crawl-delay');
    }

    /**
     * @return StreamInterface<Directives>
     */
    public function __invoke(Str $robots): StreamInterface
    {
        return $robots
            ->split("\n")
            ->map(function(Str $line): Str {
                return $line
                    ->pregReplace('/ #.*/', '')
                    ->trim();
            })
            ->filter(function(Str $line): bool {
                return $line->length() > 0;
            })
            ->filter(function(Str $line): bool {
                return $line->split(':')->size() >= 2;
            })
            ->reduce(
                new Stream(Pair::class),
                function(Stream $carry, Str $line): Stream {
                    $parts = $line->split(':');

                    return $carry->add(
                        new Pair(
                            $parts->first()->toLower()->trim(),
                            $parts->drop(1)->join(':')->trim()
                        )
                    );
                }
            )
            ->filter(function(Pair $line): bool {
                return $this->supportedKeys->contains((string) $line->key());
            })
            ->reduce(
                new Stream('object'),
                function(Stream $carry, Pair $line): Stream {
                    return $this->transformLineToObject($carry, $line);
                }
            )
            ->reduce(
                new Stream('object'),
                function(Stream $carry, $object): Stream {
                    return $this->groupUserAgents($carry, $object);
                }
            )
            ->reduce(
                new Stream(Map::class),
                function(Stream $carry, $object): Stream {
                    return $this->groupDirectives($carry, $object);
                }
            )
            ->reduce(
                new Stream(Directives::class),
                function(Stream $carry, Map $map): Stream {
                    return $carry->add(
                        new Directives\Directives(
                            $map->get('user-agent'),
                            $map->get('allow'),
                            $map->get('disallow'),
                            $map['crawl-delay'] ?? null
                        )
                    );
                }
            );
    }

    /**
     * @param Stream<object> $carry
     * @param Pair<Str, Str> $line
     * @return Stream<object>
     */
    private function transformLineToObject(Stream $carry, Pair $line): Stream {
        switch ((string) $line->key()) {
            case 'user-agent':
                return $carry->add(
                    new UserAgent\UserAgent((string) $line->value())
                );

            case 'allow':
                return $carry->add(
                    new Allow(
                        new UrlPattern((string) $line->value())
                    )
                );

            case 'disallow':
                return $carry->add(
                    new Disallow(
                        new UrlPattern((string) $line->value())
                    )
                );

            case 'crawl-delay':
                return $carry->add(
                    new CrawlDelay((int) (string) $line->value())
                );
        }
    }

    /**
     * @param Stream<object> $carry
     * @param object $object
     *
     * @return Stream<object>
     */
    private function groupUserAgents(Stream $carry, $object): Stream {
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
     * @param Stream<Map<string, object>> $carry
     * @param object $object
     *
     * @return Stream<Map<string, string>>
     */
    private function groupDirectives(Stream $carry, $object): Stream {
        if ($object instanceof UserAgent) {
            return $carry->add(
                (new Map('string', 'object'))
                    ->put('user-agent', $object)
                    ->put('allow', new Set(Allow::class))
                    ->put('disallow', new Set(Disallow::class))
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
