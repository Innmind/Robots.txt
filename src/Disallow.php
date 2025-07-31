<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Disallow
{
    private function __construct(
        private UrlPattern $pattern,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(UrlPattern $pattern): self
    {
        return new self($pattern);
    }

    #[\NoDiscard]
    public function matches(string $url): bool
    {
        if (Str::of($this->pattern->toString())->empty()) {
            return false;
        }

        return $this->pattern->matches($url);
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return 'Disallow: '.$this->pattern->toString();
    }
}
