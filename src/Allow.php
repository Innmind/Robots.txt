<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

final class Allow
{
    private UrlPattern $pattern;

    private function __construct(UrlPattern $pattern)
    {
        $this->pattern = $pattern;
    }

    public static function of(UrlPattern $pattern): self
    {
        return new self($pattern);
    }

    public function matches(string $url): bool
    {
        return $this->pattern->matches($url);
    }

    public function toString(): string
    {
        return 'Allow: '.$this->pattern->toString();
    }
}
