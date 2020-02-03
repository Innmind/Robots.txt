<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\Url;

interface Parser
{
    /**
     * @throws FileNotFoundException
     */
    public function __invoke(Url $url): RobotsTxt;
}
