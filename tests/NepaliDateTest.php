<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\NepaliDate;
use PHPUnit\Framework\TestCase;

final class NepaliDateTest extends TestCase
{
    // -------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------

    public function testConstructWithYearMonthDay(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame(2080, $d->getYear());
        self::assertSame(0, $d->getMonth());
        self::assertSame(15, $d->getDate());
        self::assertSame('2080-01-15', $d->format('YYYY-MM-DD'));
    }

    public function testConstructFromDateTime(): void
    {
        $ad = DateTimeImmutable::createFromFormat('!Y-m-d', '2023-04-28', new DateTimeZone('UTC'));
        $d = new NepaliDate($ad);
        self::assertSame('2080-01-15', $d->format('YYYY-MM-DD'));
    }

    public function testConstructFromNepaliDateCopiesState(): void
    {
        $original = new NepaliDate(2080, 5, 10);
        $copy = new NepaliDate($original);

        self::assertSame($original->getYear(), $copy->getYear());
        self::assertSame($original->getMonth(), $copy->getMonth());
        self::assertSame($original->getDate(), $copy->getDate());
        self::assertSame($original->getTime(), $copy->getTime());
    }

    /**
     * @dataProvider stringFormatsProvider
     */
    public function testConstructFromString(string $input): void
    {
        $d = new NepaliDate($input);
        self::assertSame('2080-01-01', $d->format('YYYY-MM-DD'));
    }

    /**
     * @return array<string, array{0:string}>
     */
    public static function stringFormatsProvider(): array
    {
        return [
            'dashes' => ['2080-01-01'],
            'slashes' => ['2080/1/1'],
            'dots' => ['2080.1.1'],
            'year only defaults month/day to 1' => ['2080'],
        ];
    }

    public function testConstructFromEpochMillisecondTimestamp(): void
    {
        $d = new NepaliDate(Constants::$EPOCH);
        self::assertSame('1976-01-01', $d->format('YYYY-MM-DD'));
    }

    public function testConstructWithNoArgumentsGivesAValidCurrentDate(): void
    {
        $d = new NepaliDate();
        self::assertTrue($d->isValidInstance());
    }

    public function testConstructThrowsOnInvalidArgumentType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument syntax');
        new NepaliDate(3.14); // @phpstan-ignore-line intentional invalid type
    }

    public function testConstructThrowsOnInvalidDateString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NepaliDate('not-a-date');
    }

    public function testConstructThrowsOnYearOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nepal year out of range');
        new NepaliDate('1975-01-01');
    }

    public function testConstructThrowsOnInvalidMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid nepali month must be between 1 - 12');
        new NepaliDate('2080-13-01');
    }

    public function testConstructThrowsOnInvalidDay(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NepaliDate('2080-01-32');
    }

    public function testSetThrowsWithExclamationMessageOnYearOutOfRange(): void
    {
        $d = new NepaliDate(2080, 0, 1);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nepal year out of range!');
        $d->set(1800, 0, 1);
    }

    // -------------------------------------------------------------------
    // Formatting
    // -------------------------------------------------------------------

    public function testFormatTokens(): void
    {
        $d = new NepaliDate(2080, 0, 15);

        self::assertSame('2080', $d->format('YYYY'));
        self::assertSame('080', $d->format('YYY'));
        self::assertSame('80', $d->format('YY'));

        self::assertSame('1', $d->format('M'));
        self::assertSame('01', $d->format('MM'));
        self::assertSame('Bai', $d->format('MMM'));
        self::assertSame('Baisakh', $d->format('MMMM'));

        self::assertSame('15', $d->format('D'));
        self::assertSame('15', $d->format('DD'));
        self::assertSame('Fri', $d->format('DDD'));
        self::assertSame('Friday', $d->format('DDDD'));
    }

    public function testFormatNepaliTokens(): void
    {
        $d = new NepaliDate(2080, 0, 15);

        self::assertSame('२०८०', $d->format('yyyy'));
        self::assertSame('१', $d->format('m'));
        self::assertSame('०१', $d->format('mm'));
        self::assertSame('बै', $d->format('mmm'));
        self::assertSame('बैशाख', $d->format('mmmm'));
        self::assertSame('१५', $d->format('dd'));
        self::assertSame('शुक्र', $d->format('ddd'));
        self::assertSame('शुक्रबार', $d->format('dddd'));
    }

    public function testFormatWithQuotedLiterals(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame('M01', $d->format('"M"MM'));
    }

    public function testToStringMatchesFormatOfYMD(): void
    {
        $d = new NepaliDate(2080, 0, 5);
        self::assertSame('2080/1/5', $d->toString());
        self::assertSame('2080/1/5', (string) $d);
    }

    // -------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------

    public function testAddDays(): void
    {
        $d = new NepaliDate(2080, 0, 31); // Baisakh 2080 has 31 days
        self::assertSame('2080-02-01', $d->addDays(1)->format('YYYY-MM-DD'));
    }

    public function testAddMonthsWithinYear(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        $next = $d->addMonths(1);
        self::assertSame(2080, $next->getYear());
        self::assertSame(1, $next->getMonth());
    }

    /**
     * Regression test: addMonths() used to double-decrement the year when
     * crossing a year boundary going backwards (e.g. -1 month from Baisakh
     * landed a full year too early). See NodeJS/src/NepaliDate.ts history.
     */
    public function testAddMonthsDoesNotDoubleCountYearOnNegativeRollover(): void
    {
        $start = new NepaliDate(2080, 0, 15);

        $back1 = $start->addMonths(-1);
        self::assertSame(2079, $back1->getYear());
        self::assertSame(11, $back1->getMonth());

        $back13 = $start->addMonths(-13);
        self::assertSame(2078, $back13->getYear());
        self::assertSame(11, $back13->getMonth());

        $back12 = $start->addMonths(-12);
        self::assertSame(2079, $back12->getYear());
        self::assertSame(0, $back12->getMonth());
    }

    public function testAddYearsCapsDayToTargetMonthLength(): void
    {
        $d = new NepaliDate(2084, 10, 30); // Falgun 30, 2084 (2084's Falgun has 30 days)
        $next = $d->addYears(1);

        self::assertSame('2085-11-29', $next->format('YYYY-MM-DD')); // 2085's Falgun only has 29 days
    }

    public function testSetYearMonthDate(): void
    {
        $d = new NepaliDate(2080, 0, 15);

        $d->setYear(2081);
        self::assertSame(2081, $d->getYear());

        $d->setMonth(5);
        self::assertSame(5, $d->getMonth());

        $d->setDate(20);
        self::assertSame(20, $d->getDate());
    }

    // -------------------------------------------------------------------
    // Calendar metadata
    // -------------------------------------------------------------------

    public function testDaysInMonthAndLeapYear(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame(31, $d->daysInMonth());
        self::assertFalse($d->isLeapYear());
    }

    public function testGetWeeksInMonth(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame(6, $d->getWeeksInMonth());
    }

    public function testMinimumAndMaximum(): void
    {
        self::assertSame('1919-04-13', NepaliDate::minimum()->format('Y-m-d'));

        $last = Constants::$NEPALI_DATE_MAP[count(Constants::$NEPALI_DATE_MAP) - 1];
        $expectedMaxAd = (new NepaliDate($last['year'], 11, $last['days'][11]))->getEnglishDate()->format('Y-m-d');
        self::assertSame($expectedMaxAd, NepaliDate::maximum()->format('Y-m-d'));
    }

    /**
     * Regression test: maximum() used to return a date one day past the
     * actual last supported day (daysTillNow is a 1-indexed count), which
     * meant round-tripping it back into a NepaliDate would throw.
     */
    public function testMaximumRoundTripsBackIntoAValidNepaliDate(): void
    {
        $max = new NepaliDate(NepaliDate::maximum());
        $last = Constants::$NEPALI_DATE_MAP[count(Constants::$NEPALI_DATE_MAP) - 1];

        self::assertSame($last['year'], $max->getYear());
        self::assertSame(11, $max->getMonth());
        self::assertSame($last['days'][11], $max->getDate());
        self::assertTrue($max->isValidInstance());
    }

    // -------------------------------------------------------------------
    // Start/end of day/week/month/year
    // -------------------------------------------------------------------

    public function testTimeGettersReturnNepalLocalTime(): void
    {
        $ad = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s.u', '2023-04-28 13:45:30.250000', new DateTimeZone('UTC'));
        $d = new NepaliDate($ad);

        self::assertSame(19, $d->getHours());
        self::assertSame(30, $d->getMinutes());
        self::assertSame(30, $d->getSeconds());
        self::assertSame(250, $d->getMilliseconds());
    }

    public function testStartAndEndOfDay(): void
    {
        $d = new NepaliDate(2080, 0, 15);

        self::assertSame('2023-04-27T18:15:00+00:00', $d->startOfDay()->getEnglishDate()->format('c'));
        self::assertSame(0, $d->startOfDay()->getHours());
        self::assertSame('2023-04-28T18:14:59+00:00', $d->endOfDay()->getEnglishDate()->format('c'));
        self::assertSame(23, $d->endOfDay()->getHours());
        self::assertSame(999, $d->endOfDay()->getMilliseconds());
    }

    public function testStartAndEndOfWeek(): void
    {
        $d = new NepaliDate(2080, 0, 15); // Friday

        self::assertSame('2080-01-10', $d->startOfWeek()->format('YYYY-MM-DD'));
        self::assertSame(0, $d->startOfWeek()->getDay());
        self::assertSame('2080-01-16', $d->endOfWeek()->format('YYYY-MM-DD'));
    }

    public function testStartOfWeekRejectsOutOfRangeArgument(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        $this->expectException(InvalidArgumentException::class);
        $d->startOfWeek(7);
    }

    public function testStartAndEndOfMonth(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame('2080-01-01', $d->startOfMonth()->format('YYYY-MM-DD'));
        self::assertSame('2080-01-31', $d->endOfMonth()->format('YYYY-MM-DD'));
    }

    public function testStartAndEndOfYear(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame('2080-01-01', $d->startOfYear()->format('YYYY-MM-DD'));
        self::assertSame('2080-12-30', $d->endOfYear()->format('YYYY-MM-DD'));
    }

    // -------------------------------------------------------------------
    // Comparisons and diff
    // -------------------------------------------------------------------

    public function testComparisons(): void
    {
        $a = new NepaliDate(2080, 0, 1);
        $b = new NepaliDate(2080, 0, 15);
        $c = new NepaliDate(2080, 0, 1);

        self::assertTrue($b->isAfter($a));
        self::assertTrue($a->isBefore($b));
        self::assertTrue($a->isEqual($c));
        self::assertFalse($a->isEqual($b));
    }

    public function testIsSame(): void
    {
        $a = new NepaliDate(2080, 5, 10);
        $b = new NepaliDate(2080, 5, 20);
        $c = new NepaliDate(2081, 5, 10);

        self::assertTrue($a->isSame($b, 'year'));
        self::assertTrue($a->isSame($b, 'month'));
        self::assertFalse($a->isSame($b, 'day'));
        self::assertFalse($a->isSame($c, 'year'));
    }

    public function testDiff(): void
    {
        $a = new NepaliDate(2080, 5, 10);
        $b = new NepaliDate(2080, 0, 10);

        self::assertSame(5, $a->diff($b, 'month'));
        self::assertSame(0, $a->diff($b, 'year'));
        self::assertGreaterThan(0, $a->diff($b, 'day'));
    }

    public function testDiffThrowsOnInvalidUnit(): void
    {
        $a = new NepaliDate(2080, 5, 10);
        $b = new NepaliDate(2080, 0, 10);

        $this->expectException(InvalidArgumentException::class);
        $a->diff($b, 'week');
    }

    // -------------------------------------------------------------------
    // Quarters and fiscal years
    // -------------------------------------------------------------------

    public function testGetQuarter(): void
    {
        $q1 = NepaliDate::getQuarter(1, 2080);
        self::assertSame('2080-01-01', $q1['start']->format('YYYY-MM-DD'));
        self::assertSame('2080-03-31', $q1['end']->format('YYYY-MM-DD'));

        $q4 = NepaliDate::getQuarter(4, 2080);
        self::assertSame('2080-10-01', $q4['start']->format('YYYY-MM-DD'));
        self::assertSame('2080-12-30', $q4['end']->format('YYYY-MM-DD'));
    }

    public function testGetQuarterRejectsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NepaliDate::getQuarter(5, 2080);
    }

    public function testGetQuarters(): void
    {
        $quarters = NepaliDate::getQuarters(2080);
        self::assertSame(['Q1', 'Q2', 'Q3', 'Q4'], array_keys($quarters));
        self::assertSame('2080-01-01', $quarters['Q1']['start']->format('YYYY-MM-DD'));
    }

    public function testGetCurrentQuarter(): void
    {
        $d = new NepaliDate(2080, 0, 15);
        self::assertSame(1, $d->getCurrentQuarter());
    }

    public function testGetFiscalYearQuarter(): void
    {
        $fq1 = NepaliDate::getFiscalYearQuarter(1, 2080);
        self::assertSame('2080-04-01', $fq1['start']->format('YYYY-MM-DD'));
        self::assertSame('2080-06-30', $fq1['end']->format('YYYY-MM-DD'));

        $fq4 = NepaliDate::getFiscalYearQuarter(4, 2080);
        self::assertSame('2081-01-01', $fq4['start']->format('YYYY-MM-DD'));
        self::assertSame('2081-03-31', $fq4['end']->format('YYYY-MM-DD'));
    }

    public function testGetCurrentFiscalYearQuarter(): void
    {
        $d = new NepaliDate(2080, 0, 15); // Baisakh -> fiscal Q4
        self::assertSame(4, $d->getCurrentFiscalYearQuarter());

        $d2 = new NepaliDate(2080, 3, 15); // Shrawan -> fiscal Q1
        self::assertSame(1, $d2->getCurrentFiscalYearQuarter());
    }

    public function testGetFiscalYearQuarters(): void
    {
        $quarters = NepaliDate::getFiscalYearQuarters(2080);
        self::assertSame(['Q1', 'Q2', 'Q3', 'Q4'], array_keys($quarters));
    }

    // -------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------

    public function testGetMonthName(): void
    {
        self::assertSame('Baisakh', NepaliDate::getMonthName(0));
        self::assertSame('Bai', NepaliDate::getMonthName(0, true));
        self::assertSame('बैशाख', NepaliDate::getMonthName(0, false, true));
        self::assertSame('बै', NepaliDate::getMonthName(0, true, true));
    }

    public function testGetMonthNameRejectsOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NepaliDate::getMonthName(12);
    }

    public function testGetDayName(): void
    {
        self::assertSame('Sunday', NepaliDate::getDayName(0));
        self::assertSame('Sun', NepaliDate::getDayName(0, true));
        self::assertSame('आइतबार', NepaliDate::getDayName(0, false, true));
        self::assertSame('आइत', NepaliDate::getDayName(0, true, true));
    }

    public function testIsValidStaticAndInstance(): void
    {
        self::assertTrue(NepaliDate::isValid(2080, 0, 31));
        self::assertFalse(NepaliDate::isValid(2080, 0, 32));
        self::assertFalse(NepaliDate::isValid(1800, 0, 1));

        $d = new NepaliDate(2080, 0, 15);
        self::assertTrue($d->isValidInstance());
    }

    public function testGetCalendarDaysStructure(): void
    {
        $cal = NepaliDate::getCalendarDays(2080, 0);

        self::assertSame(5, $cal['prevRemainingDays']);
        self::assertSame(6, $cal['remainingDays']);
        self::assertSame(['year' => 2079, 'month' => 11, 'days' => [26, 27, 28, 29, 30]], $cal['prevMonth']);
        self::assertSame(['year' => 2080, 'month' => 1, 'days' => [1, 2, 3, 4, 5, 6]], $cal['nextMonth']);
        self::assertCount(31, $cal['currentMonth']['days']);
    }

    public function testGetCalendarDaysRejectsInvalidMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NepaliDate::getCalendarDays(2080, 12);
    }
}
