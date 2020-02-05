<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\Url;

interface Directives
{
    public function targets(string $userAgent): bool;
    public function disallows(Url $url): bool;

    /**
     * Delay in seconds
     */
    public function crawlDelay(): CrawlDelay;
    public function hasCrawlDelay(): bool;
    public function toString(): string;
}
