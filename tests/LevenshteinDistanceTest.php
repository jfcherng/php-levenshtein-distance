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
     * Data provider for LevenshteinDistance::calculate.
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
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_NO_COPY,
                [
                    'distance' => 9,
                    'progresses' => [
                        ['ins', 14, 's'],
                        ['ins', 9, 'e'],
                        ['rep', 8, 'm'],
                        ['rep', 7, 'o'],
                        ['del', 5, 'i'],
                        ['rep', 2, 'a'],
                        ['ins', 1, ' '],
                        ['ins', 1, 'e'],
                        ['rep', 0, 'h'],
                    ],
                ],
            ],
            [
                'this is a book',
                'he has some books',
                true,
                LD::PROGRESS_OP_AS_STRING | LD::PROGRESS_NO_COPY | LD::PROGRESS_MERGE_NEIGHBOR,
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
                LD::PROGRESS_OP_AS_STRING,
                [
                    'distance' => 5,
                    'progresses' => [
                        ['ins', 8, '！'],
                        ['rep', 7, '组'],
                        ['cpy', 6, '模'],
                        ['rep', 5, '语'],
                        ['rep', 4, '词'],
                        ['cpy', 3, '代'],
                        ['cpy', 2, '取'],
                        ['rep', 1, '订'],
                        ['cpy', 0, '自'],
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
     * Test the LevenshteinDistance::calculate.
     *
     * @covers       \Jfcherng\Utility\LevenshteinDistance::calculate
     * @covers       \Jfcherng\Utility\LevenshteinDistance::calculateWithArray
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
            LD::calculate($old, $new, $calculateProgresses, $progressOptions)
        );
    }
}
