<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Exception\DomainException;

final class CrawlDelay
{
    private int $value;

    private function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException((string) $value);
        }

        $this->value = $value;
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return 'Crawl-delay: '.$this->value;
    }
}
