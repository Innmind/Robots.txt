<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Exception\InvalidArgumentException;
use Innmind\Immutable\Str;

final class UserAgent implements UserAgentInterface
{
    private $value;
    private $string;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException;
        }

        $this->string = 'User-agent: '.$value;
        $this->value = (string) (new Str($value))->toLower();
    }

    public function matches(string $userAgent): bool
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
        return $this->string;
    }
}
