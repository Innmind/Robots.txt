<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\UrlInterface;

interface Directives
{
    public function targets(string $userAgent): bool;
    public function disallows(UrlInterface $url): bool;

    /**
     * Delay in seconds
     */
    public function crawlDelay(): CrawlDelay;
    public function hasCrawlDelay(): bool;
    public function __toString(): string;
}
