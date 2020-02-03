<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\UserAgent;

use Innmind\RobotsTxt\{
    UserAgent as UserAgentInterface,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

final class UserAgent implements UserAgentInterface
{
    private string $value;
    private string $string;

    public function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new DomainException;
        }

        $this->string = 'User-agent: '.$value;
        $this->value = Str::of($value)->toLower()->toString();
    }

    public function matches(string $userAgent): bool
    {
        if ($this->value === '*') {
            return true;
        }

        return Str::of($userAgent)
            ->toLower()
            ->contains($this->value);
    }

    public function toString(): string
    {
        return $this->string;
    }
}
