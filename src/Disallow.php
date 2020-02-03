<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\Str;

final class Disallow
{
    private UrlPattern $pattern;

    public function __construct(UrlPattern $pattern)
    {
        $this->pattern = $pattern;
    }

    public function matches(string $url): bool
    {
        if (Str::of((string) $this->pattern)->empty()) {
            return false;
        }

        return $this->pattern->matches($url);
    }

    public function __toString(): string
    {
        return 'Disallow: '.$this->pattern;
    }
}
