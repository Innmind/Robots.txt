<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Exception\DomainException;

final class CrawlDelay
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new DomainException;
        }

        $this->value = $value;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return 'Crawl-delay: '.$this->value;
    }
}
