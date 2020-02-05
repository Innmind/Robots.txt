# Robots.txt

[![codecov](https://codecov.io/gh/Innmind/Robots.txt/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Robots.txt)
[![Build Status](https://github.com/Innmind/Robots.txt/workflows/CI/badge.svg)](https://github.com/Innmind/Robots.txt/actions?query=workflow%3ACI)
[![Type Coverage](https://shepherd.dev/github/Innmind/Robots.txt/coverage.svg)](https://shepherd.dev/github/Innmind/Robots.txt)

Robots.txt parser

## Installation

```sh
composer require innmind/robots-txt
```

## Usage

```php
use Innmind\RobotsTxt\{
    Parser,
    Parser\Walker,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;

$parse = new Parser(
    /* an instance of Transport */,
    new Walker,
    'My user agent'
);
$robots = $parse(Url::fromString('https://github.com/robots.txt'));
$robots->disallows('My user agent', Url::fromString('/humans.txt')); //false
$robots->disallows('My user agent', Url::fromString('/any/other/url')); //true
```

**Note**: Here only the path `/humans.txt` is allowed because by default github disallows any user agent to crawl there website except for this file.
