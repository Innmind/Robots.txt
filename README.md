# Robots.txt

[![CI](https://github.com/Innmind/Robots.txt/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/Innmind/Robots.txt/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Innmind/Robots.txt/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Robots.txt)
[![Type Coverage](https://shepherd.dev/github/Innmind/Robots.txt/coverage.svg)](https://shepherd.dev/github/Innmind/Robots.txt)

Robots.txt parser

## Installation

```sh
composer require innmind/robots-txt
```

## Usage

```php
use Innmind\RobotsTxt\Parser;
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;

$os = Factory::build();
$parse = Parser::of(
    $os->remote()->http(),
    'My user agent',
);
$robots = $parse(Url::of('https://github.com/robots.txt'))->match(
    static fn($robots) => $robots,
    static fn() => throw new \RuntimeException('robots.txt not found'),
);
$robots->disallows('My user agent', Url::of('/humans.txt')); //false
$robots->disallows('My user agent', Url::of('/any/other/url')); //true
```

> [!NOTE]
> Here only the path `/humans.txt` is allowed because by default github disallows any user agent to crawl there website except for this file.
