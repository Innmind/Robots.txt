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
    #[\NoDiscard]
    public static function of(string $agent): self
    {
        return new self(Sequence::of(Str::of($agent)));
    }

    #[\NoDiscard]
    public function and(string $agent): self
    {
        return new self(($this->agents)(Str::of($agent)));
    }

    #[\NoDiscard]
    public function merge(self $agents): self
    {
        return new self($this->agents->append($agents->agents));
    }

    #[\NoDiscard]
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

    #[\NoDiscard]
    public function asContent(): Content
    {
        return Content::ofLines(
            $this
                ->agents
                ->map(static fn($agent) => $agent->prepend('User-agent: '))
                ->map(Content\Line::of(...)),
        );
    }
}
