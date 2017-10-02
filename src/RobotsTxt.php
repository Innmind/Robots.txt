<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\UrlInterface;
use Innmind\Immutable\StreamInterface;

interface RobotsTxt
{
    public function url(): UrlInterface;

    /**
     * @return StreamInterface<Directives>
     */
    public function directives(): StreamInterface;
    public function disallows(string $userAgent, UrlInterface $url): bool;
    public function __toString(): string;
}
