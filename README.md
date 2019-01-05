# Robots.txt

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Robots.txt/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Robots.txt/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Robots.txt/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Robots.txt/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Robots.txt/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Robots.txt/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Robots.txt/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Robots.txt/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/Robots.txt/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Robots.txt/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/Robots.txt/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Robots.txt/build-status/develop) |

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
