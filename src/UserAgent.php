<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Exception\InvalidArgumentException;
use Innmind\Immutable\Str;

final class UserAgent implements UserAgentInterface
{
    private $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException;
        }

        $this->value = (string) (new Str($value))->toLower();
    }

    public function match(string $userAgent): bool
    {
        if ($this->value === '*') {
            return true;
        }

        return (new Str($userAgent))
            ->toLower()
            ->contains($this->value);
    }

    public function __toString(): string
    {
        return 'User-agent: '.$this->value;
    }
}
