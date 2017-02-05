<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

final class Allow
{
    private $pattern;

    public function __construct(UrlPattern $pattern)
    {
        $this->pattern = $pattern;
    }

    public function matches(string $url): bool
    {
        return $this->pattern->matches($url);
    }

    public function __toString(): string
    {
        return 'Allow: '.$this->pattern;
    }
}
