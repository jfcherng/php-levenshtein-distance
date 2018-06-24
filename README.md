# php-levenshtein-distance [![Build Status](https://travis-ci.org/jfcherng/php-levenshtein-distance.svg?branch=master)](https://travis-ci.org/jfcherng/php-levenshtein-distance)

Calculate the Levenshtein distance and edit progresses between two strings.


# Features

- UTF-8-ready.
- Full edit progresses information.


# Installation

```
$ composer require jfcherng/php-levenshtein-distance
```


# Example

See `demo.php`.

```php
<?php

include __DIR__ . '/vendor/autoload.php';

use Jfcherng\Utility\LevenshteinDistance;

$results = LevenshteinDistance::calculate(
    // old string
    '自訂取代詞語模組',
    // new string
    '自订取代词语模组！',
    // progress type
    LevenshteinDistance::PROGRESS_FULL
);

// [
//     'distance' => 5,
//     'progresses' => [
//         ['ins', 7],
//         ['rep', 7, 7],
//         ['cpy', 6, 6],
//         ['rep', 5, 5],
//         ['rep', 4, 4],
//         ['cpy', 3, 3],
//         ['cpy', 2, 2],
//         ['rep', 1, 1],
//         ['cpy', 0, 0],
//     ],
// ]
var_dump($results);
```


# Options


## Progress Types

- `LevenshteinDistance::PROGRESS_NONE`: Won't resolve the detailed edit progresses and directly return `null`.
- `LevenshteinDistance::PROGRESS_SIMPLE`: Only resolve types (no position information) of detailed edit progresses.
- `LevenshteinDistance::PROGRESS_FULL` *(default)*: Resolve entire detailed edit progresses.


Supporters <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ATXYY9Y78EQ3Y" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" /></a>
==========

Thank you guys for sending me some cups of coffee.
