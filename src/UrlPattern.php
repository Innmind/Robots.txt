<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\{
    Str,
    Exception\LogicException,
    Exception\InvalidRegex,
};

/**
 * @psalm-immutable
 */
final class UrlPattern
{
    private function __construct(
        private string $pattern,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(string $pattern): self
    {
        return new self($pattern);
    }

    #[\NoDiscard]
    public function matches(string $url): bool
    {
        if ($this->pattern === '*' || Str::of($this->pattern)->empty()) {
            return true;
        }

        if ($url === $this->pattern) {
            return true;
        }

        try {
            return $this->matchRegex($url);
        } catch (LogicException|InvalidRegex $e) {
            //pass
        }

        return $this->fallUnder($url);
    }

    #[\NoDiscard]
    public function toString(): string
    {
        return $this->pattern;
    }

    private function matchRegex(string $url): bool
    {
        $pattern = Str::of($this->pattern)
            ->pregQuote('#')
            ->replace('\*', '.*')
            ->replace('\^', '^')
            ->replace('\$', '$')
            ->prepend('#')
            ->append('#')
            ->toString();

        return Str::of($url)->matches($pattern);
    }

    private function fallUnder(string $url): bool
    {
        return Str::of($url)->startsWith($this->pattern);
    }
}
