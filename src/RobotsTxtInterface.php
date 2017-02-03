<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\UrlInterface;

interface RobotsTxtInterface
{
    public function url(): UrlInterface;
    public function directives(): DirectivesInterface;
    public function allows(string $userAgent, UrlInterface $url): bool;
    public function disallows(string $userAgent, UrlInterface $url): bool;
}
