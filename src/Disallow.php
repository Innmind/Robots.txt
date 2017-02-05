<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

final class Disallow
{
    private $pattern;

    public function __construct(UrlPattern $pattern)
    {
        $this->pattern = $pattern;
    }

    public function matches(string $url): bool
    {
        if (empty((string) $this->pattern)) {
            return false;
        }

        return $this->pattern->matches($url);
    }

    public function __toString(): string
    {
        return 'Disallow: '.$this->pattern;
    }
}
