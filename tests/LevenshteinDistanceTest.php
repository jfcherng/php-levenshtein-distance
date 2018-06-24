<?php

declare(strict_types=1);

namespace Jfcherng\Utility\Test;

use Jfcherng\Utility\LevenshteinDistance;
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
                'This is a book.',
                'There are some books.',
                [
                    'distance' => 11,
                    'progresses' => [
                        ['cpy', 14, 20],
                        ['ins', 13],
                        ['cpy', 13, 18],
                        ['cpy', 12, 17],
                        ['cpy', 11, 16],
                        ['cpy', 10, 15],
                        ['cpy', 9, 14],
                        ['ins', 8],
                        ['ins', 8],
                        ['ins', 8],
                        ['rep', 8, 10],
                        ['cpy', 7, 9],
                        ['ins', 6],
                        ['rep', 6, 7],
                        ['rep', 5, 6],
                        ['cpy', 4, 5],
                        ['ins', 3],
                        ['rep', 3, 3],
                        ['rep', 2, 2],
                        ['cpy', 1, 1],
                        ['cpy', 0, 0],
                    ],
                ],
            ],
            [
                '自訂取代詞語模組',
                '自订取代词语模组！',
                [
                    'distance' => 5,
                    'progresses' => [
                        ['ins', 7],
                        ['rep', 7, 7],
                        ['cpy', 6, 6],
                        ['rep', 5, 5],
                        ['rep', 4, 4],
                        ['cpy', 3, 3],
                        ['cpy', 2, 2],
                        ['rep', 1, 1],
                        ['cpy', 0, 0],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the LevenshteinDistance::calculate with PROGRESS_FULL.
     *
     * @covers \Jfcherng\Utility\LevenshteinDistance::calculate
     * @covers \Jfcherng\Utility\LevenshteinDistance::calculateWithArray
     * @dataProvider calculateDataProvider
     *
     * @param string $old the old
     * @param string $new the new
     */
    public function testCalculate(string $old, string $new, array $expected): void
    {
        $this->assertSame(
            $expected,
            LevenshteinDistance::calculate($old, $new, LevenshteinDistance::PROGRESS_FULL)
        );
    }
}
