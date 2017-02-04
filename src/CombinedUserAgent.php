<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

final class CombinedUserAgent implements UserAgentInterface
{
    private $first;
    private $second;

    public function __construct(
        UserAgentInterface $first,
        UserAgentInterface $second
    ) {
        $this->first = $first;
        $this->second = $second;
    }

    public function match(string $userAgent): bool
    {
        return $this->first->match($userAgent) || $this->second->match($userAgent);
    }

    public function __toString(): string
    {
        return $this->first."\n".$this->second;
    }
}
