<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

interface UserAgent
{
    public function matches(string $userAgent): bool;
    public function toString(): string;
}
