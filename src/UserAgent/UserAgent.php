<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt\UserAgent;

use Innmind\RobotsTxt\{
    UserAgent as UserAgentInterface,
    Exception\DomainException
};
use Innmind\Immutable\Str;

final class UserAgent implements UserAgentInterface
{
    private $value;
    private $string;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new DomainException;
        }

        $this->string = 'User-agent: '.$value;
        $this->value = (string) Str::of($value)->toLower();
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

    public function __toString(): string
    {
        return $this->string;
    }
}
