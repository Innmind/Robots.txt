<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Disallow
{
    private UrlPattern $pattern;

    private function __construct(UrlPattern $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * @psalm-pure
     */
    public static function of(UrlPattern $pattern): self
    {
        return new self($pattern);
    }

    public function matches(string $url): bool
    {
        if (Str::of($this->pattern->toString())->empty()) {
            return false;
        }

        return $this->pattern->matches($url);
    }

    public function toString(): string
    {
        return 'Disallow: '.$this->pattern->toString();
    }
}
