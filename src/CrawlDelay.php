<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class CrawlDelay
{
    /** @var 0|positive-int */
    private int $value;

    /**
     * @param 0|positive-int $value
     */
    private function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param 0|positive-int $value
     */
    public static function of(int $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
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
     * @return 0|positive-int
     */
    public function toInt(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return 'Crawl-delay: '.$this->value;
    }
}
