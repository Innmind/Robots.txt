<?php
declare(strict_types = 1);

namespace Innmind\RobotsTxt;

use Innmind\Url\UrlInterface;
use Innmind\Immutable\StreamInterface;

final class RobotsTxt implements RobotsTxtInterface
{
    private $url;
    private $directives;

    public function __construct(
        UrlInterface $url,
        StreamInterface $directives
    ) {
        if ((string) $directives->type() !== DirectivesInterface::class) {
            throw new InvalidArgumentException;
        }

        $this->url = $url;
        $this->directives = $directives;
    }

    public function url(): UrlInterface
    {
        return $this->url;
    }

    public function directives(): StreamInterface
    {
        return $this->directives;
    }

    public function disallows(string $userAgent, UrlInterface $url): bool
    {
        $directives = $this
            ->directives
            ->filter(function(DirectivesInterface $directives) use ($userAgent): bool {
                return $directives->targets($userAgent);
            });

        if ($directives->size() === 0) {
            return false;
        }

        return $directives->reduce(
            false,
            function(bool $carry, DirectivesInterface $directives) use ($url): bool {
                if ($carry === true) {
                    return $carry;
                }

                return $directives->disallows($url);
            }
        );
    }

    public function __toString(): string
    {
        return (string) $this->directives->join("\n\n");
    }
}
