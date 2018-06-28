<?php

include __DIR__ . '/vendor/autoload.php';

use Jfcherng\Utility\LevenshteinDistance as LD;

$old = '自訂取代詞語模組';
$new = '自订取代词语模组！';

$results = LD::calculate(
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

$results = LD::calculate(
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
