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

    // the detail level of the returned edit progresses
    const PROGRESS_NONE = 0;
    const PROGRESS_SIMPLE = 1;
    const PROGRESS_FULL = 2;

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
     * @param string $oldStr       the old string
     * @param string $newStr       the new string
     * @param int    $progressType the detail level of the returned edit progresses
     *
     * @return array the distance and progresses
     */
    public static function calculate(string $oldStr, string $newStr, int $progressType = self::PROGRESS_FULL): array
    {
        return static::calculateWithArray(
            preg_split('//uS', $oldStr, -1, PREG_SPLIT_NO_EMPTY),
            preg_split('//uS', $newStr, -1, PREG_SPLIT_NO_EMPTY),
            $progressType
        );
    }

    /**
     * Calculate the Levenshtein distance and edit progresses.
     *
     * $dist[x][y] means the Levenshtein distance betweewn $oldChars[0:x] and $newChars[0:y].
     * That is, $dist[oldCharsCount][oldCharsCount] will be the final Levenshtein distance.
     *
     * $trace[x][y] is the corresponding backtracking information for $dist[x][y].
     *
     * @param string[] $oldChars     the array of old chars
     * @param string[] $newChars     the array of new chars
     * @param int      $progressType the detail level of the returned edit progresses
     *
     * @throws RuntimeException
     *
     * @return array the distance and progresses
     */
    public static function calculateWithArray(array $oldChars, array $newChars, int $progressType = self::PROGRESS_FULL): array
    {
        $m = count($oldChars);
        $n = count($newChars);

        // prevent from out of memory
        if (static::$maxSize >= 0 && $n > 0 && $m > static::$maxSize / $n) {
            throw new RuntimeException('Max allowed size is ' . static::$maxSize . " but get {$m} * {$n}.");
        }

        // initialization
        $dist = $trace = [];
        for ($x = 0; $x <= $m; ++$x) {
            $dist[$x] = $trace[$x] = [];
        }

        // fill in boundary conditions
        for ($x = 0; $x <= $m; ++$x) {
            $dist[$x][0] = $x;
        }
        for ($y = 0; $y <= $n; ++$y) {
            $dist[0][$y] = $y;
        }

        // calculate the edit distance and tracing information
        for ($x = 1; $x <= $m; ++$x) {
            for ($y = 1; $y <= $n; ++$y) {
                if ($oldChars[$x - 1] === $newChars[$y - 1]) {
                    $dist[$x][$y] = $dist[$x - 1][$y - 1];
                    $trace[$x][$y] = [$x - 1, $y - 1, self::OP_COPY];
                } else {
                    $dist[$x][$y] = $dist[$x - 1][$y] + 1;
                    $trace[$x][$y] = [$x - 1, $y, self::OP_DELETE];
                    if ($dist[$x][$y] > $dist[$x][$y - 1] + 1) {
                        $dist[$x][$y] = $dist[$x][$y - 1] + 1;
                        $trace[$x][$y] = [$x, $y - 1, self::OP_INSERT];
                    }
                    if ($dist[$x][$y] > $dist[$x - 1][$y - 1] + 1) {
                        $dist[$x][$y] = $dist[$x - 1][$y - 1] + 1;
                        $trace[$x][$y] = [$x - 1, $y - 1, self::OP_REPLACE];
                    }
                }
            }
        }

        // resolve edit progresses
        if ($progressType === self::PROGRESS_NONE) {
            $progresses = null;
        } else {
            $progresses = [];

            for (
                $traceX = $m, $traceY = $n;
                $traceX !== 0 && $traceY !== 0;
                [$traceX, $traceY] = $trace[$traceX][$traceY]
            ) {
                // current trace type
                $traceType = $trace[$traceX][$traceY][2];

                $progresses[] = $progressType === self::PROGRESS_SIMPLE
                    ? $traceType
                    : static::resolveFullProgress($traceType, $traceX, $traceY);
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
     * Resolve the full progress.
     *
     * @param int $traceType the trace type
     * @param int $x         the x index
     * @param int $y         the y index
     *
     * @return array the progress
     */
    protected static function resolveFullProgress(int $traceType, int $x, int $y): array
    {
        static $callbacks;

        $callbacks = $callbacks ?? [
            self::OP_COPY => function (int $x, int $y): array {
                return [self::OP_COPY_STR, $x - 1, $y - 1];
            },
            self::OP_DELETE => function (int $x, int $y): array {
                return [self::OP_DELETE_STR, $x - 1];
            },
            self::OP_INSERT => function (int $x, int $y): array {
                return [self::OP_INSERT_STR, $x - 1];
            },
            self::OP_REPLACE => function (int $x, int $y): array {
                return [self::OP_REPLACE_STR, $x - 1, $y - 1];
            },
        ];

        assert(isset($callbacks[$traceType]));

        return $callbacks[$traceType]($x, $y);
    }
}
