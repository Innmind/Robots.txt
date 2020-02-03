<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\Url;
use Innmind\Immutable\Sequence;

interface RobotsTxt
{
    public function url(): Url;

    /**
     * @return Sequence<Directives>
     */
    public function directives(): Sequence;
    public function disallows(string $userAgent, Url $url): bool;
    public function toString(): string;
}
