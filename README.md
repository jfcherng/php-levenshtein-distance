[![Build Status](https://travis-ci.org/jfcherng/php-levenshtein-distance.svg?branch=master)](https://travis-ci.org/jfcherng/php-levenshtein-distance)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/2e8fc5053c9c47e59b25ba5e56890576)](https://app.codacy.com/app/jfcherng/php-levenshtein-distance?utm_source=github.com&utm_medium=referral&utm_content=jfcherng/php-levenshtein-distance&utm_campaign=Badge_Grade_Settings)

# php-levenshtein-distance 

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

use Jfcherng\Utility\LevenshteinDistance as LD;

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


# Progress Options

- `LD::PROGRESS_NO_COPY`: Do not include `COPY` operations in the progresses.
- `LD::PROGRESS_MERGE_NEIGHBOR`: Merge neighbor progresses if possible.
- `LD::PROGRESS_OP_AS_STRING`: Convert the operation in progresses from int to string.
- `LD::PROGRESS_PATCH_MODE`: Replace the new edit position with the corresponding string.


# Return value

1. The operation.
1. The edit position for the new string.
1. The edit position for the old string.
   Or the corresponding string if `LD::PROGRESS_PATCH_MODE` is used.
1. The edit length.


Supporters <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ATXYY9Y78EQ3Y" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" /></a>
==========

Thank you guys for sending me some cups of coffee.
