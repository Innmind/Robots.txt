<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\Url;
use Innmind\Immutable\Maybe;

interface Directives
{
    public function targets(string $userAgent): bool;
    public function disallows(Url $url): bool;

    /**
     * Delay in seconds
     *
     * @return Maybe<CrawlDelay>
     */
    public function crawlDelay(): Maybe;
    public function toString(): string;
}
