<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Immutable\{
    Str,
    Exception\RegexException,
    Exception\SubstringException,
};

final class UrlPattern
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

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
        } catch (RegexException $e) {
            //pass
        }

        try {
            return $this->fallUnder($url);
        } catch (SubstringException $e) {
            return false;
        }
    }

    public function toString(): string
    {
        return $this->pattern;
    }

    /**
     * @throws RegexException if the pattern is not a regex
     */
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
        return Str::of($url)->position($this->pattern) === 0;
    }
}
