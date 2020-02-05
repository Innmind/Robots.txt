<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\RobotsTxt\Exception\FileNotFound;
use Innmind\Url\Url;

interface Parser
{
    /**
     * @throws FileNotFound
     */
    public function __invoke(Url $url): RobotsTxt;
}
