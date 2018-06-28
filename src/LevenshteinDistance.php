<?php

declare(strict_types=1);

namespace Jfcherng\Utility;

use RuntimeException;

/**
 * Calculate the Levenshtein distance and edit progresses betweewn two strings.
 *
 * @see http://www.cnblogs.com/clam/archive/2012/03/29/2423079.html
 *
 * @author Jack Cherng <jfcherng@gmail.com>
 * @author caojiandong <neu.loner@gmail.com>
 */
class LevenshteinDistance
{
    // operations enum
    const OP_COPY = 0;
    const OP_DELETE = 1;
    const OP_INSERT = 2;
    const OP_REPLACE = 3;

    // operations enum
    const OP_COPY_STR = 'cpy';
    const OP_DELETE_STR = 'del';
    const OP_INSERT_STR = 'ins';
    const OP_REPLACE_STR = 'rep';

    // operations enum
    const OP_INT2STR_MAP = [
        self::OP_COPY => self::OP_COPY_STR,
        self::OP_DELETE => self::OP_DELETE_STR,
        self::OP_INSERT => self::OP_INSERT_STR,
        self::OP_REPLACE => self::OP_REPLACE_STR,
    ];

    // progress options
    const PROGRESS_NO_COPY = 1 << 0;
    const PROGRESS_MERGE_NEIGHBOR = 1 << 1;
    const PROGRESS_OP_AS_STRING = 1 << 2;
    const PROGRESS_PATCH_MODE = 1 << 3;

    /**
     * Prevent from out of memory. A negative number means no limitation.
     *
     * @var float
     */
    protected static $maxSize = 600 ** 2;

    /**
     * Set the maximum size.
     *
     * @param float $size the size
     */
    public static function setMaxSize(float $size): void
    {
        static::$maxSize = $size;
    }

    /**
     * Get the maximum size.
     *
     * @return float the maximum size
     */
    public static function getMaxSize(): float
    {
        return static::$maxSize;
    }

    /**
     * Calculate the Levenshtein distance and edit progresses.
     *
     * @param string $old                 the old string
     * @param string $new                 the new string
     * @param bool   $calculateProgresses calculate the edit progresses
     * @param int    $progressOptions     the progress options
     *
     * @return array the distance and progresses
     */
    public static function calculate(string $old, string $new, bool $calculateProgresses = true, int $progressOptions = 0): array
    {
        return static::calculateWithArray(
            preg_split('//uS', $old, -1, PREG_SPLIT_NO_EMPTY),
            preg_split('//uS', $new, -1, PREG_SPLIT_NO_EMPTY),
            $calculateProgresses,
            $progressOptions
        );
    }

    /**
     * Calculate the Levenshtein distance and edit progresses.
     *
     * $dist[x][y] means the Levenshtein distance betweewn $olds[0:x] and $news[0:y].
     * That is, $dist[oldsCount][oldsCount] is what we are interested in.
     *
     * @phan-suppress PhanTypeInvalidDimOffset
     *
     * @param string[] $olds                the array of old chars
     * @param string[] $news                the array of new chars
     * @param bool     $calculateProgresses calculate the edit progresses
     * @param int      $progressOptions     the progress options
     *
     * @throws RuntimeException
     *
     * @return array the distance and progresses
     */
    public static function calculateWithArray(array $olds, array $news, bool $calculateProgresses = true, int $progressOptions = 0): array
    {
        $m = count($olds);
        $n = count($news);

        // prevent from out of memory
        if (static::$maxSize >= 0 && $n > 0 && $m > static::$maxSize / $n) {
            throw new RuntimeException('Max allowed size is ' . static::$maxSize . " but get {$m} * {$n}.");
        }

        // initial boundary conditions
        $dist = [];
        for ($x = 0; $x <= $m; ++$x) {
            $dist[$x][0] = $x;
        }
        for ($y = 0; $y <= $n; ++$y) {
            $dist[0][$y] = $y;
        }

        // calculate the edit distance
        for ($x = 1; $x <= $m; ++$x) {
            for ($y = 1; $y <= $n; ++$y) {
                $dist[$x][$y] = $olds[$x - 1] === $news[$y - 1]
                    ? $dist[$x - 1][$y - 1] // copy
                    : 1 + min(
                        $dist[$x - 1][$y], // delete
                        $dist[$x][$y - 1], // insert
                        $dist[$x - 1][$y - 1] // replace
                    );
            }
        }

        // calculate edit progresses
        if (!$calculateProgresses) {
            $progresses = null;
        } else {
            // raw edit progresses
            $rawProgresses = static::calculateRawProgresses($dist);

            // resolve raw edit progresses
            $progresses = static::resolveRawProgresses($rawProgresses);

            // merge neighbor progresses
            if ($progressOptions & self::PROGRESS_MERGE_NEIGHBOR) {
                $progresses = static::mergeNeighborProgresses($progresses);
            }

            // merge progresses like patches
            if ($progressOptions & self::PROGRESS_PATCH_MODE) {
                $progresses = static::makeProgressesPatch($olds, $news, $progresses);
            }

            // remove "COPY" operations
            if ($progressOptions & self::PROGRESS_NO_COPY) {
                $progresses = static::removeCopyProgresses($progresses);
            }

            // operation name as string
            if ($progressOptions & self::PROGRESS_OP_AS_STRING) {
                $progresses = static::stringifyOperations($progresses);
            }
        }

        return [
            // (int) Levenshtein distance
            'distance' => $dist[$m][$n],
            // (null|array) edit progresses
            'progresses' => $progresses,
        ];
    }

    /**
     * Calculate the raw progresses.
     *
     * @param array $dist the distance array
     *
     * @return array the raw progresses
     */
    protected static function calculateRawProgresses(array $dist): array
    {
        $m = count($dist) - 1;
        $n = count($dist[0]) - 1;

        $progresses = [];

        for (
            $x = $m, $y = $n;
            $x !== 0 && $y !== 0;
            [$x, $y] = $trace
        ) {
            switch ($dist[$x][$y] - 1) {
                default: // default never happens though
                case $dist[$x - 1][$y]:
                    $trace = [$x - 1, $y, self::OP_DELETE];
                    break;
                case $dist[$x][$y - 1]:
                    $trace = [$x, $y - 1, self::OP_INSERT];
                    break;
                case $dist[$x - 1][$y - 1]:
                    $trace = [$x - 1, $y - 1, self::OP_REPLACE];
                    break;
                case $dist[$x - 1][$y - 1] - 1:
                    $trace = [$x - 1, $y - 1, self::OP_COPY];
                    break;
            }

            $progresses[] = [$x, $y, $trace[2]];
        }

        for (; $x > 0; --$x) {
            $progresses[] = [$x, 0, self::OP_DELETE];
        }

        for (; $y > 0; --$y) {
            $progresses[] = [0, $y, self::OP_INSERT];
        }

        return $progresses;
    }

    /**
     * Resolve the raw progresses.
     *
     * @param array $rawProgresses the raw progresses
     *
     * @return array [operation, old position, new position, length]
     */
    protected static function resolveRawProgresses(array $rawProgresses): array
    {
        static $callbacks;

        $callbacks = $callbacks ?? [
            self::OP_COPY => function (int $x, int $y): array {
                return [self::OP_COPY, $x - 1, $y - 1, 1];
            },
            self::OP_DELETE => function (int $x, int $y): array {
                return [self::OP_DELETE, $x - 1, $y, 1];
            },
            self::OP_INSERT => function (int $x, int $y): array {
                return [self::OP_INSERT, $x, $y - 1, 1];
            },
            self::OP_REPLACE => function (int $x, int $y): array {
                return [self::OP_REPLACE, $x - 1, $y - 1, 1];
            },
        ];

        foreach ($rawProgresses as &$rawProgress) {
            $rawProgress = $callbacks[$rawProgress[2]](
                $rawProgress[0],
                $rawProgress[1]
            );
        }
        unset($rawProgress);

        return $rawProgresses;
    }

    /**
     * Merge neighbor progresses and return the merged result.
     *
     * @param array $progresses the progresses
     *
     * @return array
     */
    protected static function mergeNeighborProgresses(array $progresses): array
    {
        $progressesCount = count($progresses);

        if ($progressesCount === 0) {
            return [];
        }

        $merged = [];
        $last = $progresses[0];

        for ($step = 1; $step < $progressesCount; ++$step) {
            $progress = $progresses[$step];

            if ($last[0] === $progress[0]) {
                $progress[3] = $last[3] + 1;
            } else {
                $merged[] = $last;
            }

            $last = $progress;
        }

        $merged[] = $last;

        return $merged;
    }

    /**
     * Make progresses just like patch.
     *
     * @param array $olds       the old characters
     * @param array $news       the new characters
     * @param array $progresses the progresses
     *
     * @return array
     */
    protected static function makeProgressesPatch(array $olds, array $news, array $progresses): array
    {
        foreach ($progresses as $step => [$operation, $oldPos, $newPos]) {
            $length = $progresses[$step][3] ?? 1;

            switch ($operation) {
                default: // default never happens though
                case self::OP_COPY:
                case self::OP_DELETE:
                    $chars = array_slice($olds, $oldPos, $length);
                    break;
                case self::OP_INSERT:
                case self::OP_REPLACE:
                    $chars = array_slice($news, $newPos, $length);
                    break;
            }

            $progresses[$step][2] = implode('', $chars);
        }

        return $progresses;
    }

    /**
     * Remove "COPY" progresses.
     *
     * @param array $progresses the progresses
     *
     * @return array
     */
    protected static function removeCopyProgresses(array $progresses): array
    {
        $filtered = [];

        foreach ($progresses as $progress) {
            if ($progress[0] !== self::OP_COPY) {
                $filtered[] = $progress;
            }
        }

        return $filtered;
    }

    /**
     * Convert the operation in progresses from int to string.
     *
     * @param array $progresses the progresses
     *
     * @return array
     */
    protected static function stringifyOperations(array $progresses): array
    {
        foreach ($progresses as &$progress) {
            $progress[0] = static::OP_INT2STR_MAP[$progress[0]];
        }
        unset($progress);

        return $progresses;
    }
}
