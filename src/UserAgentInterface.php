<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

interface UserAgentInterface
{
    public function match(string $userAgent): bool;
    public function __toString(): string;
}
