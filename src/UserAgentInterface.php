<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

interface UserAgentInterface
{
    public function matches(string $userAgent): bool;
    public function __toString(): string;
}
