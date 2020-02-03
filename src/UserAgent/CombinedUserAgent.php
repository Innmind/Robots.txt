<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\UserAgent;

use Innmind\RobotsTxt\UserAgent as UserAgentInterface;

final class CombinedUserAgent implements UserAgentInterface
{
    private UserAgentInterface $first;
    private UserAgentInterface $second;

    public function __construct(
        UserAgentInterface $first,
        UserAgentInterface $second
    ) {
        $this->first = $first;
        $this->second = $second;
    }

    public function matches(string $userAgent): bool
    {
        return $this->first->matches($userAgent) || $this->second->matches($userAgent);
    }

    public function __toString(): string
    {
        return $this->first."\n".$this->second;
    }
}
