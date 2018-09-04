<?php

declare(strict_types=1);

namespace Jfcherng\Utility\Test;

use Jfcherng\Utility\LevenshteinDistance as LD;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class LevenshteinDistanceTest extends TestCase
{
    /**
     * Data provider for calculate* function in LevenshteinDistance.
     *
     * @return array the data provider
     */
    public function calculateDataProvider(): array
    {
        return [
            [
                'this is a book',
                'he has some books',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_NO_COPY | LD::PROGRESS_PATCH_MODE,
                [
                    'distance' => 9,
                    'progresses' => [
                        ['ins', 14, 's', 1],
                        ['ins', 9, 'e', 1],
                        ['rep', 8, 'm', 1],
                        ['rep', 7, 'o', 1],
                        ['del', 5, 'i', 1],
                        ['rep', 2, 'a', 1],
                        ['ins', 1, ' ', 1],
                        ['ins', 1, 'e', 1],
                        ['rep', 0, 'h', 1],
                    ],
                ],
            ],
            [
                'this is a book',
                'he has some books',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_NO_COPY | LD::PROGRESS_MERGE_NEIGHBOR | LD::PROGRESS_PATCH_MODE,
                [
                    'distance' => 9,
                    'progresses' => [
                        ['ins', 14, 's', 1],
                        ['ins', 9, 'e', 1],
                        ['rep', 7, 'om', 2],
                        ['del', 5, 'i', 1],
                        ['rep', 2, 'a', 1],
                        ['ins', 1, 'e ', 2],
                        ['rep', 0, 'h', 1],
                    ],
                ],
            ],
            [
                '自訂取代詞語模組',
                '自订取代词语模组！',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_PATCH_MODE,
                [
                    'distance' => 5,
                    'progresses' => [
                        ['ins', 8, '！', 1],
                        ['rep', 7, '组', 1],
                        ['cpy', 6, '模', 1],
                        ['rep', 5, '语', 1],
                        ['rep', 4, '词', 1],
                        ['cpy', 3, '代', 1],
                        ['cpy', 2, '取', 1],
                        ['rep', 1, '订', 1],
                        ['cpy', 0, '自', 1],
                    ],
                ],
            ],
            [
                '自訂取代詞語模組',
                '自订取代词语模组！',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_MERGE_NEIGHBOR,
                [
                    'distance' => 5,
                    'progresses' => [
                        ['ins', 8, 8, 1],
                        ['rep', 7, 7, 1],
                        ['cpy', 6, 6, 1],
                        ['rep', 4, 4, 2],
                        ['cpy', 2, 2, 2],
                        ['rep', 1, 1, 1],
                        ['cpy', 0, 0, 1],
                    ],
                ],
            ],
            [
                '自訂取代詞語模組',
                '自订取代词语模组！',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_MERGE_NEIGHBOR | LD::PROGRESS_PATCH_MODE,
                [
                    'distance' => 5,
                    'progresses' => [
                        ['ins', 8, '！', 1],
                        ['rep', 7, '组', 1],
                        ['cpy', 6, '模', 1],
                        ['rep', 4, '词语', 2],
                        ['cpy', 2, '取代', 2],
                        ['rep', 1, '订', 1],
                        ['cpy', 0, '自', 1],
                    ],
                ],
            ],
        ];
    }

    /**
     * Data provider for testing cost map.
     *
     * @return array the data provider
     */
    public function costMapDataProvider(): array
    {
        return [
            [
                'Hi man!',
                'Heh man!',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_MERGE_NEIGHBOR | LD::PROGRESS_PATCH_MODE,
                [LD::OP_REPLACE => \INF], // disallow "REPLACE" operation
                [
                    'distance' => 3,
                    'progresses' => [
                        ['cpy', 2, ' man!', 5],
                        ['del', 1, 'i', 1],
                        ['ins', 1, 'eh', 2],
                        ['cpy', 0, 'H', 1],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the LevenshteinDistance::staticCalculate.
     *
     * @covers       \Jfcherng\Utility\LevenshteinDistance::staticCalculate
     * @dataProvider calculateDataProvider
     *
     * @param string $old                 the old
     * @param string $new                 the new
     * @param bool   $calculateProgresses calculate the edit progresses
     * @param int    $progressOptions     the progress options
     * @param array  $expected            the expected
     */
    public function testStaticCalculate(string $old, string $new, bool $calculateProgresses, int $progressOptions, array $expected): void
    {
        $this->assertSame(
            $expected,
            LD::staticCalculate($old, $new, $calculateProgresses, $progressOptions)
        );
    }

    /**
     * Test the LevenshteinDistance::calculate.
     *
     * @covers       \Jfcherng\Utility\LevenshteinDistance::calculate
     * @dataProvider calculateDataProvider
     *
     * @param string $old                 the old
     * @param string $new                 the new
     * @param bool   $calculateProgresses calculate the edit progresses
     * @param int    $progressOptions     the progress options
     * @param array  $expected            the expected
     */
    public function testCalculate(string $old, string $new, bool $calculateProgresses, int $progressOptions, array $expected): void
    {
        $this->assertSame(
            $expected,
            (new LD())
                ->setCalculateProgresses($calculateProgresses)
                ->setProgressOptions($progressOptions)
                ->calculate($old, $new)
        );
    }

    /**
     * Test the cost map is working as intended.
     *
     * @dataProvider costMapDataProvider
     *
     * @param string $old                 the old
     * @param string $new                 the new
     * @param bool   $calculateProgresses calculate the edit progresses
     * @param int    $progressOptions     the progress options
     * @param int[]  $costMap             the cost map
     * @param array  $expected            the expected
     */
    public function testCostMap(string $old, string $new, bool $calculateProgresses, int $progressOptions, array $costMap, array $expected): void
    {
        $this->assertSame(
            $expected,
            (new LD())
                ->setCalculateProgresses($calculateProgresses)
                ->setProgressOptions($progressOptions)
                ->setCostMap($costMap)
                ->calculate($old, $new)
        );
    }
}
