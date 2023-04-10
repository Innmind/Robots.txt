<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Filesystem\File\Content;
use Innmind\Immutable\{
    Sequence,
    Str,
};

/**
 * @psalm-immutable
 */
final class UserAgent
{
    /** @var Sequence<Str> */
    private Sequence $agents;

    /**
     * @param Sequence<Str> $agents
     */
    private function __construct(Sequence $agents)
    {
        $this->agents = $agents;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $agent): self
    {
        return new self(Sequence::of(Str::of($agent)));
    }

    public function and(string $agent): self
    {
        return new self(($this->agents)(Str::of($agent)));
    }

    public function merge(self $agents): self
    {
        return new self($this->agents->append($agents->agents));
    }

    public function matches(string $userAgent): bool
    {
        $userAgent = Str::of($userAgent)->toLower();

        return $this
            ->agents
            ->find(static fn($agent) => $agent->equals(Str::of('*')) || $userAgent->contains($agent->toLower()->toString()))
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }

    public function asContent(): Content
    {
        return Content\Lines::of(
            $this
                ->agents
                ->map(static fn($agent) => $agent->prepend('User-agent: '))
                ->map(Content\Line::of(...)),
        );
    }
}
