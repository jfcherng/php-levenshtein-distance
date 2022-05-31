# php-levenshtein-distance

[![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/jfcherng/php-levenshtein-distance/Main/master?style=flat-square)](https://github.com/jfcherng/php-levenshtein-distance/actions)
[![Packagist](https://img.shields.io/packagist/dt/jfcherng/php-levenshtein-distance?style=flat-square)](https://packagist.org/packages/jfcherng/php-levenshtein-distance)
[![Packagist Version](https://img.shields.io/packagist/v/jfcherng/php-levenshtein-distance?style=flat-square)](https://packagist.org/packages/jfcherng/php-levenshtein-distance)
[![Project license](https://img.shields.io/github/license/jfcherng/php-levenshtein-distance?style=flat-square)](https://github.com/jfcherng/php-levenshtein-distance/blob/master/LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/jfcherng/php-levenshtein-distance?style=flat-square&logo=github)](https://github.com/jfcherng/php-levenshtein-distance/stargazers)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-blue.svg?style=flat-square&logo=paypal)](https://www.paypal.me/jfcherng/5usd)

Calculate the Levenshtein distance and edit progresses between two strings.
Note that if you do not need the edit path, PHP has a built-in [levenshtein()](http://php.net/manual/en/function.levenshtein.php) function.


## Features

- UTF-8-ready.
- Full edit progresses information.


## Installation

```bash
$ composer require jfcherng/php-levenshtein-distance
```


## Example

See `demo.php`.

```php
<?php

include __DIR__ . '/vendor/autoload.php';

use Jfcherng\Diff\LevenshteinDistance as LD;

$old = '自訂取代詞語模組';
$new = '自订取代词语模组！';

$calculator = new LD(
    true, // calculate edit progresses?
    // progress options
    LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_PATCH_MODE
);

$results = $calculator->calculate($old, $new);

// this is the same but using an internal singleton
$results = LD::staticCalculate(
    $old, // old string
    $new, // new string
    true, // calculate edit progresses?
    // progress options
    LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_PATCH_MODE
);

// [
//     'distance' => 5,
//     'progresses' => [
//         ['ins', 8, '！', 1],
//         ['rep', 7, '组', 1],
//         ['cpy', 6, '模', 1],
//         ['rep', 5, '语', 1],
//         ['rep', 4, '词', 1],
//         ['cpy', 3, '代', 1],
//         ['cpy', 2, '取', 1],
//         ['rep', 1, '订', 1],
//         ['cpy', 0, '自', 1],
//     ],
// ]
var_dump($results);

$results = LD::staticCalculate(
    $old, // old string
    $new, // new string
    true, // calculate edit progresses?
    // progress options
    LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_PATCH_MODE | LD::PROGRESS_MERGE_NEIGHBOR
);

// [
//     'distance' => 5,
//     'progresses' => [
//         ['ins', 8, '！', 1],
//         ['rep', 7, '组', 1],
//         ['cpy', 6, '模', 1],
//         ['rep', 4, '词语', 2],
//         ['cpy', 2, '取代', 2],
//         ['rep', 1, '订', 1],
//         ['cpy', 0, '自', 1],
//     ],
// ]
var_dump($results);
```


## Progress Options

- `LD::PROGRESS_NO_COPY`: Do not include `COPY` operations in the progresses.
- `LD::PROGRESS_MERGE_NEIGHBOR`: Merge neighbor progresses if possible.
- `LD::PROGRESS_OP_AS_STRING`: Convert the operation in progresses from int to string.
- `LD::PROGRESS_PATCH_MODE`: Replace the new edit position with the corresponding string.


## Returned `progresses`

1. The operation.
1. The edit position for the new string.
1. The edit position for the old string.
   Or the corresponding string if `LD::PROGRESS_PATCH_MODE` is used.
1. The edit length.
