<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Tests;

use InvalidArgumentException;
use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\Helper\NepaliConstants;
use NepaliDateLibrary\NepaliDate;
use PHPUnit\Framework\TestCase;

class ExtendedNepaliDate extends NepaliDate
{
    /** @var NepaliConstants|null */
    public static $customCalendar = null;

    protected function _getConstants(): NepaliConstants
    {
        return self::$customCalendar ?? Constants::getBundled();
    }
}

final class PluggableConstantsTest extends TestCase
{
    private static function getCustomCalendar(): NepaliConstants
    {
        return new NepaliConstants(
            [
                ['year' => 3000, 'days' => [30, 31, 30, 31, 30, 31, 30, 31, 30, 31, 30, 31]], // 366 (leap)
                ['year' => 3001, 'days' => [30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30]], // 360
                ['year' => 3002, 'days' => [31, 31, 31, 31, 31, 31, 31, 31, 31, 31, 31, 31]], // 372
            ],
            Constants::$EPOCH,
            Constants::$NEPAL_UTC_OFFSET_MS,
            Constants::$WEEK_EN,
            Constants::$WEEK_SHORT_EN,
            Constants::$WEEK_NP,
            Constants::$WEEK_SHORT_NP,
            [
                'Custom1', 'Custom2', 'Custom3', 'Custom4', 'Custom5', 'Custom6',
                'Custom7', 'Custom8', 'Custom9', 'Custom10', 'Custom11', 'Custom12'
            ],
            Constants::$MONTH_SHORT_EN,
            Constants::$MONTH_NP,
            Constants::$MONTH_SHORT_NP,
            Constants::$NUMBER_NP
        );
    }

    protected function setUp(): void
    {
        ExtendedNepaliDate::$customCalendar = self::getCustomCalendar();
    }

    protected function tearDown(): void
    {
        ExtendedNepaliDate::$customCalendar = null;
    }

    public function testConstructsFromStringInCustomCalendarRange(): void
    {
        $d = new ExtendedNepaliDate('3000-02-15');
        $this->assertSame(3000, $d->getYear());
        $this->assertSame(1, $d->getMonth()); // 0-indexed
        $this->assertSame(15, $d->getDate());
        $this->assertSame('3000-02-15', $d->format('YYYY-MM-DD'));
    }

    public function testConstructsFromYearMonthDayInCustomRange(): void
    {
        $d = new ExtendedNepaliDate(3001, 5, 20);
        $this->assertSame(3001, $d->getYear());
        $this->assertSame(5, $d->getMonth());
        $this->assertSame(20, $d->getDate());
        $this->assertSame('3001-06-20', $d->format('YYYY-MM-DD'));
    }

    public function testThrowsWhenStringIsOutOfCustomCalendarRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nepal year out of range');
        new ExtendedNepaliDate('2080-01-01');
    }

    public function testThrowsWhenYearIntegerIsOutOfCustomCalendarRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nepal year out of range!');
        new ExtendedNepaliDate(2080, 0, 1);
    }

    public function testThrowsWhenDayExceedsCustomMonthDayLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nepali date');
        new ExtendedNepaliDate('3001-01-31');
    }

    public function testComputesDaysInMonthCorrectlyFromCustomData(): void
    {
        $d = new ExtendedNepaliDate('3000-01-01');
        $this->assertSame(30, $d->daysInMonth());
        $this->assertSame(31, $d->daysInMonth(3000, 1));
        $this->assertSame(30, $d->daysInMonth(3001, 0));
        $this->assertSame(31, $d->daysInMonth(3002, 0));
    }

    public function testEvaluatesIsLeapYearBasedOnCustomTotalDays(): void
    {
        $leap = new ExtendedNepaliDate('3000-01-01');
        $this->assertTrue($leap->isLeapYear()); // 366 days

        $nonLeap = new ExtendedNepaliDate('3001-01-01');
        $this->assertFalse($nonLeap->isLeapYear()); // 360 days
    }

    public function testCorrectlyValidatesDatesWithIsValidInstance(): void
    {
        $valid = new ExtendedNepaliDate('3000-01-10');
        $this->assertTrue($valid->isValidInstance());

        $invalidDay = new ExtendedNepaliDate('3000-01-30');
        $reflector = new \ReflectionProperty(NepaliDate::class, 'day');
        $reflector->setValue($invalidDay, 32);
        $this->assertFalse($invalidDay->isValidInstance());
    }

    public function testAddsMonthsAndPreservesSubclass(): void
    {
        $d = new ExtendedNepaliDate('3000-01-15');
        $nextMonth = $d->addMonths(2);

        $this->assertInstanceOf(ExtendedNepaliDate::class, $nextMonth);
        $this->assertSame('3000-03-15', $nextMonth->format('YYYY-MM-DD'));
    }

    public function testAddsYearsAndPreservesSubclass(): void
    {
        $d = new ExtendedNepaliDate('3000-05-10');
        $nextYear = $d->addYears(1);

        $this->assertInstanceOf(ExtendedNepaliDate::class, $nextYear);
        $this->assertSame('3001-05-10', $nextYear->format('YYYY-MM-DD'));
    }

    public function testAddsDaysAndPreservesSubclass(): void
    {
        $d = new ExtendedNepaliDate('3000-01-01');
        $nextDay = $d->addDays(5);

        $this->assertInstanceOf(ExtendedNepaliDate::class, $nextDay);
        $this->assertSame('3000-01-06', $nextDay->format('YYYY-MM-DD'));
    }

    public function testClampsDayWhenAddingMonthsToMonthWithFewerDays(): void
    {
        $d = new ExtendedNepaliDate('3000-02-31');
        $nextMonth = $d->addMonths(1);

        $this->assertSame('3000-03-30', $nextMonth->format('YYYY-MM-DD'));
    }

    public function testComputesEndOfYearAccordingToCustomLastMonthDays(): void
    {
        $d = new ExtendedNepaliDate('3000-01-01');
        $end = $d->endOfYear();

        $this->assertInstanceOf(ExtendedNepaliDate::class, $end);
        $this->assertSame('3000-12-31', $end->format('YYYY-MM-DD'));
    }

    public function testFormatsWithCustomMonthNames(): void
    {
        $d = new ExtendedNepaliDate('3000-01-01');
        $this->assertSame('Custom1 3000', $d->format('MMMM YYYY'));

        $d2 = new ExtendedNepaliDate('3000-05-01');
        $this->assertSame('Custom5', $d2->format('MMMM'));
    }

    public function testDetectsWhenCustomConstantsReferenceUpdates(): void
    {
        $d = new ExtendedNepaliDate('3000-01-01');
        $this->assertSame(30, $d->daysInMonth());

        // Update custom calendar reference
        ExtendedNepaliDate::$customCalendar = new NepaliConstants(
            [
                ['year' => 3000, 'days' => [32, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30]],
                ['year' => 3001, 'days' => [30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30]],
            ],
            Constants::$EPOCH,
            Constants::$NEPAL_UTC_OFFSET_MS,
            Constants::$WEEK_EN,
            Constants::$WEEK_SHORT_EN,
            Constants::$WEEK_NP,
            Constants::$WEEK_SHORT_NP,
            Constants::$MONTH_EN,
            Constants::$MONTH_SHORT_EN,
            Constants::$MONTH_NP,
            Constants::$MONTH_SHORT_NP,
            Constants::$NUMBER_NP
        );

        $this->assertSame(32, $d->daysInMonth());
    }
}
