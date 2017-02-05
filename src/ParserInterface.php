<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\UrlInterface;

interface ParserInterface
{
    /**
     * @throws FileNotFoundException
     */
    public function __invoke(UrlInterface $url): RobotsTxtInterface;
}
