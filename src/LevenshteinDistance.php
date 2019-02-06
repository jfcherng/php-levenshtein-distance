<?php

declare(strict_types=1);

namespace Jfcherng\Diff;

/**
 * Calculate the Levenshtein distance and edit progresses betweewn two strings.
 *
 * @see http://www.cnblogs.com/clam/archive/2012/03/29/2423079.html
 *
 * @author Jack Cherng <jfcherng@gmail.com>
 * @author caojiandong <neu.loner@gmail.com>
 */
final class LevenshteinDistance
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

    // the cost of operations
    const COST_MAP_DEFAULT = [
        self::OP_COPY => 0,
        self::OP_DELETE => 1,
        self::OP_INSERT => 1,
        self::OP_REPLACE => 1,
    ];

    // progress options
    const PROGRESS_NO_COPY = 1 << 0;
    const PROGRESS_MERGE_NEIGHBOR = 1 << 1;
    const PROGRESS_OP_AS_STRING = 1 << 2;
    const PROGRESS_PATCH_MODE = 1 << 3;

    /**
     * Calculate the edit progresses.
     *
     * @var bool
     */
    protected $calculateProgresses = false;

    /**
     * The progresses options.
     *
     * @var int the progress options
     */
    protected $progressOptions = 0;

    /**
     * The cost of the "REPLACE" operation.
     *
     * @var int[]
     */
    protected $costMap = self::COST_MAP_DEFAULT;

    /**
     * Prevent from out of memory. A negative number means no limitation.
     *
     * @var float
     */
    protected $maxSize = 600 ** 2;

    /**
     * The constructor.
     *
     * @param bool $calculateProgresses calculate the edit progresses
     * @param int  $progressOptions     the progress options
     */
    public function __construct(bool $calculateProgresses = false, int $progressOptions = 0, float $maxSize = 600 ** 2)
    {
        $this
            ->setCalculateProgresses($calculateProgresses)
            ->setProgressOptions($progressOptions)
            ->setMaxSize($maxSize);
    }

    /**
     * Set the calculate progresses.
     *
     * @param bool $calculateProgresses calculate the edit progresses
     *
     * @return self
     */
    public function setCalculateProgresses(bool $calculateProgresses): self
    {
        $this->calculateProgresses = $calculateProgresses;

        return $this;
    }

    /**
     * Set the progress options.
     *
     * @param int $progressOptions the progress options
     *
     * @return self
     */
    public function setProgressOptions(int $progressOptions): self
    {
        $this->progressOptions = $progressOptions;

        return $this;
    }

    /**
     * Set the cost map.
     *
     * @param int[] $costMap the cost map
     *
     * @return self
     */
    public function setCostMap(array $costMap): self
    {
        $this->costMap = $costMap + self::COST_MAP_DEFAULT;

        return $this;
    }

    /**
     * Set the maximum size.
     *
     * @param float $size the size
     *
     * @return self
     */
    public function setMaxSize(float $size): self
    {
        $this->maxSize = $size;

        return $this;
    }

    /**
     * Get the calculate progresses.
     *
     * @return bool
     */
    public function getCalculateProgresses(): bool
    {
        return $this->calculateProgresses;
    }

    /**
     * Get the progress options.
     *
     * @return int
     */
    public function getProgressOptions(): int
    {
        return $this->progressOptions;
    }

    /**
     * Get the replace map.
     *
     * @return int[]
     */
    public function getCostMap(): array
    {
        return $this->costMap;
    }

    /**
     * Get the maximum size.
     *
     * @return float the maximum size
     */
    public function getMaxSize(): float
    {
        return $this->maxSize;
    }

    /**
     * Get the singleton.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        static $singleton;

        if (!isset($singleton)) {
            $singleton = new static();
        }

        return $singleton;
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
    public static function staticCalculate(string $old, string $new, bool $calculateProgresses = false, int $progressOptions = 0): array
    {
        return static::getInstance()
            ->setCalculateProgresses($calculateProgresses)
            ->setProgressOptions($progressOptions)
            ->calculate($old, $new);
    }

    /**
     * Calculate the Levenshtein distance and edit progresses.
     *
     * @param string $old the old string
     * @param string $new the new string
     *
     * @return array the distance and progresses
     */
    public function calculate(string $old, string $new): array
    {
        $olds = \preg_split('//uS', $old, -1, \PREG_SPLIT_NO_EMPTY);
        $news = \preg_split('//uS', $new, -1, \PREG_SPLIT_NO_EMPTY);

        // calculate edit distance matrix
        $dist = $this->calculateDistance($olds, $news);

        // calculate edit progresses
        $progresses = $this->calculateProgresses($olds, $news, $dist);

        return [
            // (int) Levenshtein distance
            'distance' => $dist[\count($olds)][\count($news)],
            // (null|array) edit progresses
            'progresses' => $progresses,
        ];
    }

    /**
     * Calculate the edit distance matrix.
     *
     * $dist[x][y] means the Levenshtein distance betweewn $olds[0:x] and $news[0:y].
     * That is, $dist[oldsCount][oldsCount] is what we are interested in.
     *
     * @phan-suppress PhanTypeInvalidDimOffset
     *
     * @param array $olds the olds
     * @param array $news the news
     *
     * @throws \RuntimeException
     *
     * @return array the edit distance matrix
     */
    protected function calculateDistance(array $olds, array $news): array
    {
        $m = \count($olds);
        $n = \count($news);

        // prevent from out of memory
        if ($this->maxSize >= 0 && $n > 0 && $m > $this->maxSize / $n) {
            throw new \RuntimeException('Max allowed size is ' . $this->maxSize . " but get {$m} * {$n}.");
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
                    ? $dist[$x - 1][$y - 1] + $this->costMap[self::OP_COPY] // copy
                    : \min(
                        $dist[$x - 1][$y] + $this->costMap[self::OP_DELETE], // delete
                        $dist[$x][$y - 1] + $this->costMap[self::OP_INSERT], // insert
                        $dist[$x - 1][$y - 1] + $this->costMap[self::OP_REPLACE] // replace
                    );
            }
        }

        return $dist;
    }

    /**
     * Calculate the edit distance matrix.
     *
     * @param array $olds the olds
     * @param array $news the news
     * @param array $dist the edit distance matrix
     *
     * @return null|array the edit progresses
     */
    protected function calculateProgresses(array $olds, array $news, array $dist): ?array
    {
        if (!$this->calculateProgresses) {
            return null;
        }

        // raw edit progresses
        $rawProgresses = $this->calculateRawProgresses($dist);

        // resolve raw edit progresses
        $progresses = $this->resolveRawProgresses($rawProgresses);

        // merge neighbor progresses
        if ($this->progressOptions & self::PROGRESS_MERGE_NEIGHBOR) {
            $progresses = $this->mergeNeighborProgresses($progresses);
        }

        // merge progresses like patches
        if ($this->progressOptions & self::PROGRESS_PATCH_MODE) {
            $progresses = $this->makeProgressesPatch($olds, $news, $progresses);
        }

        // remove "COPY" operations
        if ($this->progressOptions & self::PROGRESS_NO_COPY) {
            $progresses = $this->removeCopyProgresses($progresses);
        }

        // operation name as string
        if ($this->progressOptions & self::PROGRESS_OP_AS_STRING) {
            $progresses = $this->stringifyOperations($progresses);
        }

        return $progresses;
    }

    /**
     * Calculate the raw progresses.
     *
     * @param array $dist the distance array
     *
     * @return array the raw progresses
     */
    protected function calculateRawProgresses(array $dist): array
    {
        $m = \count($dist) - 1;
        $n = \count($dist[0]) - 1;

        $progresses = [];

        for (
            $x = $m, $y = $n;
            $x !== 0 && $y !== 0;
            [$x, $y] = $trace
        ) {
            switch ($dist[$x][$y]) {
                case $dist[$x - 1][$y] + $this->costMap[self::OP_DELETE]:
                    $trace = [$x - 1, $y, self::OP_DELETE];
                    break;
                case $dist[$x][$y - 1] + $this->costMap[self::OP_INSERT]:
                    $trace = [$x, $y - 1, self::OP_INSERT];
                    break;
                case $dist[$x - 1][$y - 1] + $this->costMap[self::OP_REPLACE]:
                    $trace = [$x - 1, $y - 1, self::OP_REPLACE];
                    break;
                default:
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
    protected function resolveRawProgresses(array $rawProgresses): array
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
    protected function mergeNeighborProgresses(array $progresses): array
    {
        $progressesCount = \count($progresses);

        if ($progressesCount === 0) {
            return [];
        }

        $merged = [];
        $last = $progresses[0];

        for ($step = 1; $step < $progressesCount; ++$step) {
            $progress = $progresses[$step];

            if ($last[0] === $progress[0]) {
                $progress[3] += $last[3];
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
    protected function makeProgressesPatch(array $olds, array $news, array $progresses): array
    {
        foreach ($progresses as $step => [$operation, $oldPos, $newPos, $length]) {
            switch ($operation) {
                default: // default never happens though
                case self::OP_COPY:
                case self::OP_DELETE:
                    $chars = \array_slice($olds, $oldPos, $length);
                    break;
                case self::OP_INSERT:
                case self::OP_REPLACE:
                    $chars = \array_slice($news, $newPos, $length);
                    break;
            }

            $progresses[$step][2] = \implode('', $chars);
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
    protected function removeCopyProgresses(array $progresses): array
    {
        foreach ($progresses as $step => $progress) {
            if ($progress[0] === self::OP_COPY) {
                unset($progresses[$step]);
            }
        }

        // resort keys
        return \array_values($progresses);
    }

    /**
     * Convert the operation in progresses from int to string.
     *
     * @param array $progresses the progresses
     *
     * @return array
     */
    protected function stringifyOperations(array $progresses): array
    {
        foreach ($progresses as &$progress) {
            $progress[0] = static::OP_INT2STR_MAP[$progress[0]];
        }
        unset($progress);

        return $progresses;
    }
}
