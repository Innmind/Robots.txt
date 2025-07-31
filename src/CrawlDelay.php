<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class CrawlDelay
{
    /**
     * @param int<0, max> $value
     */
    private function __construct(
        private int $value,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param int<0, max> $value
     */
    #[\NoDiscard]
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    #[\NoDiscard]
    public static function maybe(string $value): Maybe
    {
        /** @psalm-suppress ArgumentTypeCoercion It doesn't understand the last filter */
        return Maybe::just($value)
            ->filter(static fn($value) => \is_numeric($value))
            ->map(static fn($value) => (int) $value)
            ->filter(static fn($value) => $value >= 0)
            ->map(self::of(...));
    }

    /**
     * @return int<0, max>
     */
    #[\NoDiscard]
    public function toInt(): int
    {
        return $this->value;
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return 'Crawl-delay: '.$this->value;
    }
}
