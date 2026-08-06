<?php declare(strict_types=1);

namespace NCache\Tests\Units\Core\TtlManager;

use NCache\Core\Clock\Duration;
use NCache\Tests\TestsUnit\TestsUnit;

final class DurationTest extends TestsUnit
{
    public function testSecond(): void
    {
        self::assertSame(60, Duration::second(60));
    }

    public function testMinutes(): void
    {
        self::assertSame(60, Duration::minutes(1));
        self::assertSame(300, Duration::minutes(5));
    }

    public function testHours(): void
    {
        self::assertSame(3600, Duration::hours(1));
        self::assertSame(10_800, Duration::hours(3));
    }

    public function testDays(): void
    {
        self::assertSame(86_400, Duration::days(1));
        self::assertSame(604_800, Duration::days(7));
    }

    public function testWeek(): void
    {
        self::assertSame(604_800, Duration::week(1));
        self::assertSame(1_209_600, Duration::week(2));
    }

    public function testMonth(): void
    {
        self::assertSame(2_592_000, Duration::month(1));
        self::assertSame(5_184_000, Duration::month(2));
    }

    public function testMakeCombinesEveryDurationPart(): void
    {
        $duration = Duration::make(
            days: 1,
            hours: 2,
            minutes: 30,
            second: 15
        );

        self::assertSame(
            86_400 + 7_200 + 1_800 + 15,
            $duration
        );
    }

    public function testMakeWithNoArgumentsReturnsZero(): void
    {
        self::assertSame(0, Duration::make());
    }

    public function testMakeCanUseOnlySeconds(): void
    {
        self::assertSame(
            45,
            Duration::make(second: 45)
        );
    }

    public function testZeroValuesReturnZero(): void
    {
        self::assertSame(0, Duration::minutes(0));
        self::assertSame(0, Duration::hours(0));
        self::assertSame(0, Duration::days(0));
        self::assertSame(0, Duration::week(0));
        self::assertSame(0, Duration::month(0));
    }

    public function testNegativeValuesKeepCurrentMathematicalBehavior(): void
    {
        self::assertSame(-60, Duration::minutes(-1));
        self::assertSame(-3_600, Duration::hours(-1));
        self::assertSame(-86_400, Duration::days(-1));
    }
}
