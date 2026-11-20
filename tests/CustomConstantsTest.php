<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\Helper\NepaliConstants;
use NepaliDateLibrary\Helper\Utils;
use NepaliDateLibrary\NepaliDate;
use PHPUnit\Framework\TestCase;

use function NepaliDateLibrary\ADtoBS;
use function NepaliDateLibrary\BStoAD;

/**
 * Base for the test calendars below: each subclass redeclares $calendar, so each gets its own storage.
 */
abstract class ConfigurableDate extends NepaliDate
{
    public static $calendar = null;

    protected static function constants(): NepaliConstants
    {
        return static::$calendar ?? parent::constants();
    }
}

class CalDate extends ConfigurableDate
{
    public static $calendar = null;
}

class PlainChildDate extends CalDate
{
}

class OverridingChildDate extends CalDate
{
    public static $calendar = null;
}

class SmallDate extends ConfigurableDate
{
    public static $calendar = null;
}

class LongDate extends ConfigurableDate
{
    public static $calendar = null;
}

class ExtendedDate extends ConfigurableDate
{
    public static $calendar = null;
}

class PatchedDate extends ConfigurableDate
{
    public static $calendar = null;
}

class ShiftedDate extends ConfigurableDate
{
    public static $calendar = null;
}

class UtcDate extends ConfigurableDate
{
    public static $calendar = null;
}

class Plus5Date extends ConfigurableDate
{
    public static $calendar = null;
}

class WestDate extends ConfigurableDate
{
    public static $calendar = null;
}

class PartialDate extends ConfigurableDate
{
    public static $calendar = null;
}

class SwapDate extends ConfigurableDate
{
    public static $calendar = null;
}

class BrokenDate extends NepaliDate
{
    protected static function constants(): NepaliConstants
    {
        return new NepaliConstants(
            [['year' => 3000, 'days' => [30, 30]]],
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
    }
}

/** Returns a brand-new (equal) constants object on every call, which is the slow-but-correct path. */
class FreshDate extends NepaliDate
{
    protected static function constants(): NepaliConstants
    {
        return NepaliConstants::define(['dateMap' => [['year' => 7, 'days' => array_fill(0, 12, 30)]]]);
    }
}

final class CustomConstantsTest extends TestCase
{
    private const DAY_MS = 86400000;

    public static function setUpBeforeClass(): void
    {
        CalDate::$calendar = NepaliConstants::define([
            'dateMap' => [
                ['year' => 3000, 'days' => [30, 31, 30, 31, 30, 31, 30, 31, 30, 31, 30, 31]], // 366
                ['year' => 3001, 'days' => array_fill(0, 12, 30)], // 360
                ['year' => 3002, 'days' => array_fill(0, 12, 31)], // 372
            ],
            'weekEn' => self::names(7, 'Day'),
            'weekShortEn' => self::names(7, 'D'),
            'weekNp' => self::names(7, 'NpDay'),
            'weekShortNp' => self::names(7, 'ND'),
            'monthEn' => self::names(12, 'Month', 1),
            'monthShortEn' => self::names(12, 'M', 1),
            'monthNp' => self::names(12, 'NpMonth', 1),
            'monthShortNp' => self::names(12, 'NM', 1),
            'numberNp' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'],
        ]);

        SmallDate::$calendar = NepaliConstants::define(['dateMap' => [['year' => 10, 'days' => array_fill(0, 12, 29)]]]);

        $long = [];
        for ($y = 3000; $y <= 3004; $y++) {
            $long[] = ['year' => $y, 'days' => array_fill(0, 12, 31)];
        }
        LongDate::$calendar = NepaliConstants::define(['dateMap' => $long]);

        ExtendedDate::$calendar = NepaliConstants::define([
            'dateMap' => array_merge(Constants::$NEPALI_DATE_MAP, [self::extraYear()]),
        ]);

        $patched = [];
        foreach (Constants::$NEPALI_DATE_MAP as $entry) {
            $days = $entry['days'];
            if ($entry['year'] === 2080) {
                $days[0] = 32;
            }
            $patched[] = ['year' => $entry['year'], 'days' => $days];
        }
        PatchedDate::$calendar = NepaliConstants::define(['dateMap' => $patched]);

        ShiftedDate::$calendar = NepaliConstants::define([
            'epoch' => gmmktime(0, 0, 0, 4, 14, 2023) * 1000,
            'dateMap' => [['year' => 2080, 'days' => [30, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31]]],
        ]);

        $twoYears = [
            ['year' => 1, 'days' => array_fill(0, 12, 30)],
            ['year' => 2, 'days' => array_fill(0, 12, 30)],
        ];
        UtcDate::$calendar = NepaliConstants::define(['epoch' => self::customEpoch(), 'utcOffsetMs' => 0, 'dateMap' => $twoYears]);
        Plus5Date::$calendar = NepaliConstants::define([
            'epoch' => self::customEpoch(),
            'utcOffsetMs' => 5 * 3600000,
            'dateMap' => [['year' => 1, 'days' => array_fill(0, 12, 30)]],
        ]);
        WestDate::$calendar = NepaliConstants::define([
            'epoch' => self::customEpoch(),
            'utcOffsetMs' => -3 * 3600000,
            'dateMap' => [['year' => 1, 'days' => array_fill(0, 12, 30)]],
        ]);

        PartialDate::$calendar = NepaliConstants::define(['monthEn' => self::names(12, 'Only')]);
    }

    /**
     * @return string[]
     */
    private static function names(int $count, string $prefix, int $start = 0): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = $prefix . ($i + $start);
        }

        return $out;
    }

    private static function extraYear(): array
    {
        return ['year' => 2101, 'days' => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31]];
    }

    private static function customEpoch(): int
    {
        return gmmktime(0, 0, 0, 1, 1, 2000) * 1000;
    }

    private static function epoch(): int
    {
        return Constants::$EPOCH;
    }

    /** AD (UTC) calendar date that is $n days after the epoch. */
    private static function adOf(int $n, ?int $epoch = null): string
    {
        $epoch = $epoch ?? self::epoch();

        return gmdate('Y-m-d', intdiv($epoch, 1000) + $n * 86400);
    }

    /**
     * @param callable $fn
     */
    private function assertFails(callable $fn, string $needle, string $class = InvalidArgumentException::class): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e, 'wrong exception type: ' . get_class($e) . ': ' . $e->getMessage());
            $this->assertStringContainsString($needle, $e->getMessage());

            return;
        }
        $this->fail("Expected an exception containing \"{$needle}\" but nothing was thrown");
    }

    private function ymd(NepaliDate $d): string
    {
        return $d->format('YYYY-MM-DD');
    }

    /**
     * Builds a NepaliConstants through the public constructor with one field replaced.
     *
     * @param array<string, mixed> $override
     */
    private function rawWith(array $override): NepaliConstants
    {
        $base = [
            'dateMap' => [['year' => 3000, 'days' => array_fill(0, 12, 30)]],
            'epoch' => Constants::$EPOCH,
            'utcOffsetMs' => Constants::$NEPAL_UTC_OFFSET_MS,
            'weekEn' => Constants::$WEEK_EN,
            'weekShortEn' => Constants::$WEEK_SHORT_EN,
            'weekNp' => Constants::$WEEK_NP,
            'weekShortNp' => Constants::$WEEK_SHORT_NP,
            'monthEn' => Constants::$MONTH_EN,
            'monthShortEn' => Constants::$MONTH_SHORT_EN,
            'monthNp' => Constants::$MONTH_NP,
            'monthShortNp' => Constants::$MONTH_SHORT_NP,
            'numberNp' => Constants::$NUMBER_NP,
        ];
        $v = array_merge($base, $override);

        return new NepaliConstants(
            $v['dateMap'],
            $v['epoch'],
            $v['utcOffsetMs'],
            $v['weekEn'],
            $v['weekShortEn'],
            $v['weekNp'],
            $v['weekShortNp'],
            $v['monthEn'],
            $v['monthShortEn'],
            $v['monthNp'],
            $v['monthShortNp'],
            $v['numberNp']
        );
    }

    // ------------------------------------------------------------------------------------------
    // NepaliConstants::define()
    // ------------------------------------------------------------------------------------------

    public function testDefineWithNoOverridesCopiesTheBundledData(): void
    {
        $c = NepaliConstants::define();

        $this->assertSame(Constants::$EPOCH, $c->epoch);
        $this->assertSame(Constants::$NEPAL_UTC_OFFSET_MS, $c->utcOffsetMs);
        $this->assertSame(Constants::$MONTH_EN, $c->monthEn);
        $this->assertSame(Constants::$NUMBER_NP, $c->numberNp);
        $this->assertCount(count(Constants::$NEPALI_DATE_MAP), $c->dateMap);
        $this->assertSame(['year' => 1976, 'days' => Constants::$NEPALI_DATE_MAP[0]['days']], $c->dateMap[0]);
    }

    public function testDefineFallsBackToBundledValuesForWhatIsNotOverridden(): void
    {
        $c = NepaliConstants::define(['monthEn' => self::names(12, 'X')]);

        $this->assertSame('X0', $c->monthEn[0]);
        $this->assertSame(Constants::$WEEK_EN, $c->weekEn);
        $this->assertCount(count(Constants::$NEPALI_DATE_MAP), $c->dateMap);
    }

    public function testDefineIgnoresNullOverrides(): void
    {
        $c = NepaliConstants::define(['monthEn' => null, 'epoch' => null]);

        $this->assertSame(Constants::$MONTH_EN, $c->monthEn);
        $this->assertSame(Constants::$EPOCH, $c->epoch);
    }

    public function testDefineStripsProcessedOnlyFieldsAndAcceptsExtraYears(): void
    {
        $c = NepaliConstants::define(['dateMap' => array_merge(Constants::$NEPALI_DATE_MAP, [self::extraYear()])]);

        $this->assertCount(count(Constants::$NEPALI_DATE_MAP) + 1, $c->dateMap);
        $this->assertSame(2101, $c->dateMap[count($c->dateMap) - 1]['year']);
        $keys = array_keys($c->dateMap[0]);
        sort($keys);
        $this->assertSame(['days', 'year'], $keys);
    }

    public function testDefineAcceptsTheNepaliDateMapAlias(): void
    {
        $c = NepaliConstants::define(['nepaliDateMap' => [['year' => 5, 'days' => array_fill(0, 12, 30)]]]);
        $this->assertSame(5, $c->dateMap[0]['year']);
    }

    public function testFromArrayBehavesLikeDefine(): void
    {
        $c = NepaliConstants::fromArray(['epoch' => self::customEpoch(), 'dateMap' => [['year' => 5, 'days' => array_fill(0, 12, 30)]]]);
        $this->assertSame(self::customEpoch(), $c->epoch);
        $this->assertFails(function () {
            NepaliConstants::fromArray(['nope' => 1]);
        }, 'unknown key "nope"');
    }

    public function testDefineAcceptsAnIntegralFloatEpoch(): void
    {
        $c = NepaliConstants::define(['epoch' => (float) self::customEpoch()]);
        $this->assertSame(self::customEpoch(), $c->epoch);
    }

    public function testDefineRejectsUnknownKeys(): void
    {
        $this->assertFails(function () {
            NepaliConstants::define(['monthEN' => []]);
        }, 'unknown key "monthEN"');
    }

    public function testDefineRejectsBadScalarTypesInsteadOfCasting(): void
    {
        $this->assertFails(function () {
            NepaliConstants::define(['epoch' => 'abc']);
        }, 'epoch must be an integer');
        $this->assertFails(function () {
            NepaliConstants::define(['epoch' => 1.5]);
        }, 'epoch must be an integer');
        $this->assertFails(function () {
            NepaliConstants::define(['utcOffsetMs' => '5']);
        }, 'utcOffsetMs must be an integer');
        $this->assertFails(function () {
            NepaliConstants::define(['monthEn' => 'January']);
        }, 'monthEn must be an array');
        $this->assertFails(function () {
            NepaliConstants::define(['dateMap' => 'nope']);
        }, 'dateMap must be a non-empty list');
    }

    public function testDefineValidatesTheMergedResult(): void
    {
        $this->assertFails(function () {
            NepaliConstants::define(['monthEn' => ['only', 'two']]);
        }, 'monthEn must be an array of exactly 12');
    }

    // ------------------------------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------------------------------

    public function testBundledConstantsValidate(): void
    {
        Utils::validateConstants(Constants::getBundled());
        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    private function invalidCases(): array
    {
        $year = static function (int $y): array {
            return ['year' => $y, 'days' => array_fill(0, 12, 30)];
        };
        $thirty = array_fill(0, 11, 30);

        return [
            'empty dateMap' => [['dateMap' => []], 'dateMap must be a non-empty list'],
            'non-list dateMap' => [['dateMap' => [5 => $year(3000)]], 'dateMap must be a non-empty list'],
            'non-integer year' => [['dateMap' => [['year' => 'x', 'days' => array_fill(0, 12, 30)]]], 'year must be an integer'],
            'entry not an array' => [['dateMap' => [42]], 'year must be an integer'],
            'missing year' => [['dateMap' => [['days' => array_fill(0, 12, 30)]]], 'year must be an integer'],
            'gap between years' => [['dateMap' => [$year(3000), $year(3002)]], 'expected 3001 at index 1 but got 3002'],
            'years out of order' => [['dateMap' => [$year(3001), $year(3000)]], 'consecutive'],
            'too few months' => [['dateMap' => [['year' => 3000, 'days' => [30, 30]]]], 'exactly 12 month lengths'],
            'too many months' => [['dateMap' => [['year' => 3000, 'days' => array_fill(0, 13, 30)]]], 'exactly 12 month lengths'],
            'days not an array' => [['dateMap' => [['year' => 3000, 'days' => '30']]], 'exactly 12 month lengths'],
            'missing days' => [['dateMap' => [['year' => 3000]]], 'exactly 12 month lengths'],
            'zero-day month' => [['dateMap' => [['year' => 3000, 'days' => array_merge([0], $thirty)]]], 'month 1 of year 3000'],
            '33-day month' => [['dateMap' => [['year' => 3000, 'days' => array_merge($thirty, [33])]]], 'month 12 of year 3000'],
            'fractional days' => [['dateMap' => [['year' => 3000, 'days' => array_merge([30.5], $thirty)]]], 'month 1'],
            'string days' => [['dateMap' => [['year' => 3000, 'days' => array_merge(['30'], $thirty)]]], 'month 1'],
            'epoch not on UTC midnight' => [['epoch' => Constants::$EPOCH + 1], 'UTC midnight'],
            'weekEn too short' => [['weekEn' => ['a']], 'weekEn must be an array of exactly 7'],
            'weekShortEn too long' => [['weekShortEn' => self::names(8, 'x')], 'weekShortEn must be an array of exactly 7'],
            'weekNp wrong' => [['weekNp' => []], 'weekNp must be an array of exactly 7'],
            'weekShortNp wrong' => [['weekShortNp' => self::names(6, 'x')], 'weekShortNp must be an array of exactly 7'],
            'monthEn wrong' => [['monthEn' => self::names(11, 'x')], 'monthEn must be an array of exactly 12'],
            'monthShortEn wrong' => [['monthShortEn' => self::names(13, 'x')], 'monthShortEn must be an array of exactly 12'],
            'monthNp wrong' => [['monthNp' => []], 'monthNp must be an array of exactly 12'],
            'monthShortNp wrong' => [['monthShortNp' => self::names(1, 'x')], 'monthShortNp must be an array of exactly 12'],
            'numberNp wrong' => [['numberNp' => self::names(9, 'x')], 'numberNp must be an array of exactly 10'],
            'numeric name entry' => [['monthEn' => array_merge([1], self::names(11, 'x'))], 'monthEn must only contain strings'],
            'associative name table' => [['weekEn' => ['a' => 'x', 'b' => 'x', 'c' => 'x', 'd' => 'x', 'e' => 'x', 'f' => 'x', 'g' => 'x']], 'weekEn must be an array of exactly 7'],
        ];
    }

    public function testEveryInvalidShapeIsRejectedWithAReadableMessage(): void
    {
        foreach ($this->invalidCases() as $name => $case) {
            list($override, $needle) = $case;
            $raw = $this->rawWith($override);
            $this->assertFails(function () use ($raw) {
                Utils::validateConstants($raw);
            }, $needle);
            $this->assertFails(function () use ($raw) {
                $raw->process();
            }, 'Invalid Nepali constants: ');
        }
    }

    public function testValidationAcceptsThirtyTwoDayMonthsAndOtherOffsets(): void
    {
        Utils::validateConstants($this->rawWith([
            'dateMap' => [['year' => 1, 'days' => array_fill(0, 12, 32)]],
            'utcOffsetMs' => -3600000,
        ]));
        $this->addToAssertionCount(1);
    }

    // ------------------------------------------------------------------------------------------
    // processDateMap / findYearIndex
    // ------------------------------------------------------------------------------------------

    public function testProcessConstantsComputesCumulativeDays(): void
    {
        $p = Utils::processConstants(CalDate::$calendar);

        $this->assertSame([366, 360, 372], array_column($p->dateMap, 'totalDays'));
        $this->assertSame([366, 726, 1098], array_column($p->dateMap, 'daysTillNow'));
    }

    public function testFindYearIndexOnBoundaries(): void
    {
        $map = CalDate::getConstants()->dateMap;

        $this->assertSame(0, Utils::findYearIndex($map, 0));
        $this->assertSame(0, Utils::findYearIndex($map, 365));
        $this->assertSame(1, Utils::findYearIndex($map, 366));
        $this->assertSame(1, Utils::findYearIndex($map, 725));
        $this->assertSame(2, Utils::findYearIndex($map, 726));
        $this->assertSame(2, Utils::findYearIndex($map, 1097));
    }

    public function testFindYearIndexAgreesWithALinearScanOverTheBundledCalendar(): void
    {
        $map = Constants::getBundled()->dateMap;
        $last = $map[count($map) - 1]['daysTillNow'];

        for ($d = 0; $d < $last; $d++) {
            $expected = 0;
            while ($d >= $map[$expected]['daysTillNow']) {
                $expected++;
            }
            if (Utils::findYearIndex($map, $d) !== $expected) {
                $this->fail("findYearIndex mismatch at day {$d}");
            }
        }
        $this->addToAssertionCount(1);
    }

    public function testFindYearIndexWithASingleYear(): void
    {
        $map = SmallDate::getConstants()->dateMap;
        $this->assertSame(0, Utils::findYearIndex($map, 0));
        $this->assertSame(0, Utils::findYearIndex($map, 347));
    }

    // ------------------------------------------------------------------------------------------
    // Resolution, caching, isolation
    // ------------------------------------------------------------------------------------------

    public function testBundledClassKeepsUsingTheBundledObject(): void
    {
        $this->assertSame(Constants::getBundled(), NepaliDate::getConstants());
        $this->assertSame(Constants::getBundled(), (new NepaliDate(2080, 0, 1))->getConstants());
        $this->assertSame('2080-01-01', $this->ymd(new NepaliDate('2080-01-01')));
    }

    public function testTheSameSnapshotIsReusedByTheClassAndItsInstances(): void
    {
        $first = CalDate::getConstants();

        $this->assertSame($first, CalDate::getConstants());
        $this->assertSame($first, (new CalDate(3000, 0, 1))->getConstants());
        $this->assertSame($first, (new CalDate(3001, 0, 1))->getConstants());
        $this->assertSame($first, $first->process());
    }

    public function testClassesReturningTheSameRawObjectShareOneSnapshot(): void
    {
        $this->assertSame(CalDate::getConstants(), PlainChildDate::getConstants());
    }

    public function testCalendarsAreIsolatedFromEachOtherAndFromTheDefault(): void
    {
        $this->assertNotSame(SmallDate::getConstants(), CalDate::getConstants());
        $this->assertSame(29, (new SmallDate(10, 0, 1))->daysInMonth());
        $this->assertSame(30, (new CalDate(3000, 0, 1))->daysInMonth());
        $this->assertSame(31, (new NepaliDate(2080, 0, 1))->daysInMonth());
    }

    public function testConstantsAreSnapshottedOnFirstUse(): void
    {
        $raw = NepaliConstants::define(['dateMap' => [['year' => 1, 'days' => array_fill(0, 12, 30)]]]);
        SwapDate::$calendar = $raw;

        $before = SwapDate::getConstants();
        $raw->dateMap[0]['days'][0] = 32;
        $raw->monthEn[0] = 'Hacked';

        $this->assertSame($before, SwapDate::getConstants());
        $this->assertSame(30, (new SwapDate(1, 0, 1))->daysInMonth());
        $this->assertSame('Baisakh', (new SwapDate(1, 0, 1))->format('MMMM'));
    }

    public function testReplacingTheConstantsObjectIsPickedUp(): void
    {
        SwapDate::$calendar = NepaliConstants::define(['dateMap' => [['year' => 1, 'days' => array_fill(0, 12, 30)]]]);
        $d = new SwapDate(1, 0, 1);
        $this->assertSame(30, $d->daysInMonth());

        SwapDate::$calendar = NepaliConstants::define(['dateMap' => [['year' => 1, 'days' => array_fill(0, 12, 31)]]]);
        $this->assertSame(31, $d->daysInMonth());
    }

    public function testAFreshObjectOnEveryCallStillWorks(): void
    {
        $d = new FreshDate(7, 2, 3);
        $this->assertSame('7-03-03', $this->ymd($d));
        $this->assertSame(30, $d->daysInMonth());
    }

    public function testChildClassesCanKeepOrReplaceTheParentCalendar(): void
    {
        $this->assertSame('3002-04-04', $this->ymd(new PlainChildDate(3002, 3, 4)));

        OverridingChildDate::$calendar = NepaliConstants::define(['dateMap' => [['year' => 77, 'days' => array_fill(0, 12, 30)]]]);
        $this->assertSame(77, (new OverridingChildDate(77, 0, 1))->getYear());
        $this->assertFails(function () {
            new OverridingChildDate(3000, 0, 1);
        }, 'out of range');
        $this->assertSame(3000, (new CalDate(3000, 0, 1))->getYear());
    }

    public function testInvalidConstantsFailFastWithAReadableMessage(): void
    {
        $this->assertFails(function () {
            new BrokenDate(3000, 0, 1);
        }, 'exactly 12 month lengths');
        $this->assertFails(function () {
            new BrokenDate('3000-01-01');
        }, 'Invalid Nepali constants');
        $this->assertFails(function () {
            new BrokenDate();
        }, 'Invalid Nepali constants');
        $this->assertFails(function () {
            BrokenDate::getConstants();
        }, 'Invalid Nepali constants');
        $this->assertFails(function () {
            BrokenDate::getMonthName(0);
        }, 'Invalid Nepali constants');
        $this->assertFails(function () {
            BrokenDate::isValid(3000, 0, 1);
        }, 'Invalid Nepali constants');
        $this->assertFails(function () {
            BrokenDate::getCalendarDays(3000, 0);
        }, 'Invalid Nepali constants');
    }

    public function testBrokenConstantsDoNotPoisonOtherClasses(): void
    {
        $this->assertFails(function () {
            new BrokenDate(3000, 0, 1);
        }, 'Invalid Nepali constants');
        $this->assertSame('2080-01-01', $this->ymd(new NepaliDate(2080, 0, 1)));
        $this->assertSame('3000-01-01', $this->ymd(new CalDate(3000, 0, 1)));
    }

    // ------------------------------------------------------------------------------------------
    // Construction and parsing
    // ------------------------------------------------------------------------------------------

    public function testBuildsFromYearMonthDay(): void
    {
        $d = new CalDate(3001, 5, 20);
        $this->assertSame([3001, 5, 20], [$d->getYear(), $d->getMonth(), $d->getDate()]);
        $this->assertSame('3001-06-20', $this->ymd($d));
    }

    public function testParsesEverySeparator(): void
    {
        foreach (['3000-02-15', '3000/02/15', '3000.02.15', '3000-2-15'] as $s) {
            $d = new CalDate($s);
            $this->assertSame([3000, 1, 15], [$d->getYear(), $d->getMonth(), $d->getDate()], $s);
        }
    }

    public function testPartialStringsDefaultToFirstMonthAndDay(): void
    {
        $this->assertSame('3001-01-01', $this->ymd(new CalDate('3001')));
        $this->assertSame('3001-04-01', $this->ymd(new CalDate('3001-04')));
    }

    public function testRejectsYearsOutsideTheCustomRange(): void
    {
        foreach (['2080-01-01', '2999-12-30', '3003-01-01'] as $s) {
            $this->assertFails(function () use ($s) {
                new CalDate($s);
            }, 'Nepal year out of range');
        }
        $this->assertFails(function () {
            new CalDate(2080, 0, 1);
        }, 'Nepal year out of range!');
        $this->assertFails(function () {
            new CalDate(3003, 0, 1);
        }, 'Nepal year out of range!');
    }

    public function testUsesCustomMonthLengthsWhenValidating(): void
    {
        $this->assertFails(function () {
            new CalDate('3001-01-31');
        }, 'Invalid nepali date');
        $this->assertSame(31, (new CalDate('3002-01-31'))->getDate());
        $this->assertFails(function () {
            new CalDate('3000-13-01');
        }, 'Invalid nepali month');
        $this->assertFails(function () {
            new CalDate('3000-00-01');
        }, 'Invalid nepali month');
        $this->assertFails(function () {
            new CalDate('3000-01-00');
        }, 'Invalid nepali date');
        $this->assertFails(function () {
            new CalDate('garbage');
        }, 'Invalid date');
    }

    public function testParseUpdatesAnInstance(): void
    {
        $d = new CalDate(3000, 0, 1);
        $d->parse('3001/03/04');
        $this->assertSame('3001-03-04', $this->ymd($d));
        $this->assertFails(function () use ($d) {
            $d->parse('2080-01-01');
        }, 'Nepal year out of range');
    }

    public function testSetRollsMonthsOverAndHandlesNegativeMonths(): void
    {
        $d = new CalDate(3000, 0, 1);
        $d->set(3000, 12, 1);
        $this->assertSame('3001-01-01', $this->ymd($d));
        $d->set(3001, -1, 1);
        $this->assertSame('3000-12-01', $this->ymd($d));
    }

    public function testSettersGoThroughTheCustomCalendar(): void
    {
        $d = new CalDate(3000, 1, 31);
        $d->setYear(3002);
        $this->assertSame('3002-02-31', $this->ymd($d));
        $d->setMonth(5);
        $this->assertSame('3002-06-31', $this->ymd($d));
        $d->setDate(1);
        $this->assertSame('3002-06-01', $this->ymd($d));
    }

    public function testTheEpochDayIsTheFirstCustomDay(): void
    {
        $this->assertSame('3000-01-01', $this->ymd(new CalDate(new DateTimeImmutable('@' . intdiv(self::epoch(), 1000)))));
    }

    public function testInstantsOutsideTheRangeAreRejected(): void
    {
        $e = self::epoch();
        $this->assertFails(function () use ($e) {
            new CalDate($e - self::DAY_MS);
        }, 'out of the supported range');
        $this->assertFails(function () use ($e) {
            new CalDate($e + 1098 * self::DAY_MS);
        }, 'out of the supported range');
        $this->assertSame('3002-12-31', $this->ymd(new CalDate($e + 1097 * self::DAY_MS)));
    }

    public function testAnIntegerAloneIsAUnixTimestampInMilliseconds(): void
    {
        $this->assertSame('3000-01-06', $this->ymd(new CalDate(self::epoch() + 5 * self::DAY_MS)));
    }

    public function testRejectsInvalidArgumentTypes(): void
    {
        $this->assertFails(function () {
            new CalDate(new \stdClass());
        }, 'Invalid argument syntax');
        $this->assertFails(function () {
            new CalDate(true);
        }, 'Invalid argument syntax');
    }

    // ------------------------------------------------------------------------------------------
    // Long years regression
    // ------------------------------------------------------------------------------------------

    public function testEveryDayOfA372DayYearCalendarRoundTrips(): void
    {
        $count = 0;
        for ($y = 3000; $y <= 3004; $y++) {
            for ($m = 0; $m < 12; $m++) {
                for ($d = 1; $d <= 31; $d++) {
                    $date = new LongDate($y, $m, $d);
                    if ([$date->getYear(), $date->getMonth(), $date->getDate()] !== [$y, $m, $d]) {
                        $this->fail("round trip failed for {$y}-{$m}-{$d}");
                    }
                    $count++;
                }
            }
        }
        $this->assertSame(5 * 372, $count);
    }

    public function testEveryDayOfTheMixedCalendarRoundTripsThroughTimestamps(): void
    {
        for ($n = 0; $n < 1098; $n++) {
            $ts = self::epoch() + $n * self::DAY_MS;
            $a = new CalDate($ts);
            $b = new CalDate($a->getYear(), $a->getMonth(), $a->getDate());
            if ($b->getTime() !== $ts) {
                $this->fail("round trip failed for day {$n}");
            }
        }
        $this->addToAssertionCount(1);
    }

    public function testWalkingForwardOneDayAtATimeHasNoGapsOrRepeats(): void
    {
        $d = new CalDate(3000, 0, 1);
        $seen = [$d->toString() => true];
        for ($n = 1; $n < 1098; $n++) {
            $d = $d->addDays(1);
            $this->assertArrayNotHasKey($d->toString(), $seen);
            $seen[$d->toString()] = true;
        }
        $this->assertSame('3002-12-31', $this->ymd($d));
        $this->assertCount(1098, $seen);
    }

    public function testTheBundledCalendarStillConvertsCorrectly(): void
    {
        $this->assertSame('2080-01-01', $this->ymd(new NepaliDate(gmmktime(0, 0, 0, 4, 14, 2023) * 1000)));
        $this->assertSame('1976-01-01', $this->ymd(new NepaliDate(self::epoch())));
        $max = NepaliDate::maximum();
        $this->assertSame('2100-12-31', $this->ymd(new NepaliDate($max)));
    }

    // ------------------------------------------------------------------------------------------
    // Static methods
    // ------------------------------------------------------------------------------------------

    public function testGetMonthNameUsesTheCustomTables(): void
    {
        $this->assertSame('Month1', CalDate::getMonthName(0));
        $this->assertSame('Month12', CalDate::getMonthName(11));
        $this->assertSame('M1', CalDate::getMonthName(0, true));
        $this->assertSame('NpMonth1', CalDate::getMonthName(0, false, true));
        $this->assertSame('NM1', CalDate::getMonthName(0, true, true));
        $this->assertSame('Baisakh', NepaliDate::getMonthName(0));
    }

    public function testGetDayNameUsesTheCustomTables(): void
    {
        $this->assertSame('Day0', CalDate::getDayName(0));
        $this->assertSame('D6', CalDate::getDayName(6, true));
        $this->assertSame('NpDay1', CalDate::getDayName(1, false, true));
        $this->assertSame('ND1', CalDate::getDayName(1, true, true));
        $this->assertSame('Sunday', NepaliDate::getDayName(0));
    }

    public function testNameLookupsStillValidateTheirIndex(): void
    {
        $this->assertFails(function () {
            CalDate::getMonthName(12);
        }, 'Invalid month index');
        $this->assertFails(function () {
            CalDate::getMonthName(-1);
        }, 'Invalid month index');
        $this->assertFails(function () {
            CalDate::getDayName(7);
        }, 'Invalid day index');
    }

    public function testIsValidUsesTheCustomCalendar(): void
    {
        $this->assertTrue(CalDate::isValid(3000, 0, 1));
        $this->assertTrue(CalDate::isValid(3002, 5, 31));
        $this->assertFalse(CalDate::isValid(3001, 0, 31));
        $this->assertFalse(CalDate::isValid(3000, 0, 0));
        $this->assertFalse(CalDate::isValid(3000, 12, 1));
        $this->assertFalse(CalDate::isValid(3000, -1, 1));
        $this->assertFalse(CalDate::isValid(2999, 0, 1));
        $this->assertFalse(CalDate::isValid(3003, 0, 1));
        $this->assertFalse(NepaliDate::isValid(3000, 0, 1));
        $this->assertTrue(NepaliDate::isValid(2080, 0, 31));
    }

    public function testMinimumAndMaximumFollowTheCustomCalendar(): void
    {
        $this->assertSame(self::epoch(), CalDate::minimum()->getTimestamp() * 1000);
        $this->assertSame(self::epoch() + 1097 * self::DAY_MS, CalDate::maximum()->getTimestamp() * 1000);
        $this->assertSame(self::adOf(1097), CalDate::maximum()->format('Y-m-d'));
    }

    public function testMinimumAndMaximumFollowACustomEpoch(): void
    {
        $this->assertSame(self::customEpoch(), UtcDate::minimum()->getTimestamp() * 1000);
        $this->assertSame(self::customEpoch() + 719 * self::DAY_MS, UtcDate::maximum()->getTimestamp() * 1000);
    }

    public function testGetCalendarDaysBuildsTheGridFromTheCustomCalendar(): void
    {
        $grid = CalDate::getCalendarDays(3001, 0);

        $this->assertSame(2, $grid['prevRemainingDays']);
        $this->assertSame(['year' => 3000, 'month' => 11, 'days' => [30, 31]], $grid['prevMonth']);
        $this->assertSame(3001, $grid['currentMonth']['year']);
        $this->assertCount(30, $grid['currentMonth']['days']);
        $this->assertSame(42 - 2 - 30, $grid['remainingDays']);
        $this->assertSame(3001, $grid['nextMonth']['year']);
        $this->assertSame(1, $grid['nextMonth']['month']);
        $this->assertCount(10, $grid['nextMonth']['days']);
    }

    public function testGetCalendarDaysAtTheEdgesLeavesTheMissingNeighbourEmpty(): void
    {
        $first = CalDate::getCalendarDays(3000, 0);
        $this->assertSame(0, $first['prevRemainingDays']);
        $this->assertSame(['year' => 0, 'month' => 0, 'days' => []], $first['prevMonth']);

        $last = CalDate::getCalendarDays(3002, 11);
        $this->assertSame(['year' => 0, 'month' => 0, 'days' => []], $last['nextMonth']);
    }

    public function testGetCalendarDaysRejectsMonthsTheCalendarDoesNotHave(): void
    {
        foreach ([[2080, 0], [3003, 0], [3000, 12], [3000, -1]] as $case) {
            $this->assertFails(function () use ($case) {
                CalDate::getCalendarDays($case[0], $case[1]);
            }, 'Invalid year or month');
        }
        $this->assertFails(function () {
            NepaliDate::getCalendarDays(3000, 0);
        }, 'Invalid year or month');
    }

    public function testQuartersReturnSubclassInstancesForAnExplicitYear(): void
    {
        $q1 = CalDate::getQuarter(1, 3001);
        $this->assertInstanceOf(CalDate::class, $q1['start']);
        $this->assertSame('3001-01-01', $this->ymd($q1['start']));
        $this->assertSame('3001-03-30', $this->ymd($q1['end']));

        $all = CalDate::getQuarters(3002);
        $this->assertSame(['Q1', 'Q2', 'Q3', 'Q4'], array_keys($all));
        $this->assertSame('3002-10-01', $this->ymd($all['Q4']['start']));
        $this->assertSame('3002-12-31', $this->ymd($all['Q4']['end']));
        $this->assertInstanceOf(CalDate::class, $all['Q4']['end']);
    }

    public function testFiscalQuartersCrossIntoTheNextCustomYear(): void
    {
        $q1 = CalDate::getFiscalYearQuarter(1, 3000);
        $this->assertSame('3000-04-01', $this->ymd($q1['start']));
        $this->assertSame('3000-06-31', $this->ymd($q1['end']));

        $q4 = CalDate::getFiscalYearQuarter(4, 3000);
        $this->assertSame('3001-01-01', $this->ymd($q4['start']));
        $this->assertSame('3001-03-30', $this->ymd($q4['end']));

        $all = CalDate::getFiscalYearQuarters(3000);
        $this->assertSame('3000-10-01', $this->ymd($all['Q3']['start']));
        $this->assertInstanceOf(CalDate::class, $all['Q3']['start']);
    }

    public function testQuartersThatLeaveTheRangeOrAreInvalidThrow(): void
    {
        $this->assertFails(function () {
            CalDate::getFiscalYearQuarter(4, 3002);
        }, 'Nepal year out of range!');
        $this->assertFails(function () {
            CalDate::getQuarter(1, 2080);
        }, 'Nepal year out of range!');
        $this->assertFails(function () {
            CalDate::getQuarter(5, 3000);
        }, 'Quarter must be an integer between 1 and 4');
        $this->assertFails(function () {
            CalDate::getFiscalYearQuarter(0, 3000);
        }, 'Quarter must be an integer between 1 and 4');
    }

    public function testCurrentDateHelpersThrowClearlyWhenTodayIsOutsideTheCustomRange(): void
    {
        $this->assertFails(function () {
            CalDate::getCurrentFiscalYear();
        }, 'out of the supported range');
        $this->assertFails(function () {
            CalDate::getQuarter(1);
        }, 'out of the supported range');
    }

    // ------------------------------------------------------------------------------------------
    // Instance behaviour
    // ------------------------------------------------------------------------------------------

    public function testDaysInMonthLeapYearAndValidity(): void
    {
        $d = new CalDate(3000, 0, 1);
        $this->assertSame(30, $d->daysInMonth());
        $this->assertSame(31, $d->daysInMonth(3000, 1));
        $this->assertSame(30, $d->daysInMonth(3001, 0));
        $this->assertSame(31, $d->daysInMonth(3002, 5));
        $this->assertTrue($d->isLeapYear());
        $this->assertFalse((new CalDate(3001, 0, 1))->isLeapYear());
        $this->assertTrue((new CalDate(3002, 0, 1))->isLeapYear());
        $this->assertTrue($d->isValidInstance());
    }

    public function testDaysInMonthRejectsYearsOutsideTheCalendar(): void
    {
        $d = new CalDate(3000, 0, 1);
        $this->assertFails(function () use ($d) {
            $d->daysInMonth(3003);
        }, 'Year is out of supported range');
        $this->assertFails(function () use ($d) {
            $d->daysInMonth(2080);
        }, 'Year is out of supported range');
        $this->assertFails(function () use ($d) {
            $d->daysInMonth(3000, 12);
        }, 'Invalid month index');
    }

    public function testGetDayFollowsTheEpochWeekday(): void
    {
        $this->assertSame(0, (new CalDate(3000, 0, 1))->getDay());
        $this->assertSame(1, (new CalDate(3000, 0, 2))->getDay());
        $this->assertSame(6, (new CalDate(3000, 1, 5))->getDay());
        $this->assertSame(2, (new CalDate(3001, 0, 1))->getDay());
    }

    public function testGetWeeksInMonth(): void
    {
        $this->assertSame(5, (new CalDate(3001, 0, 10))->getWeeksInMonth());
        $this->assertSame(5, (new CalDate(3000, 0, 1))->getWeeksInMonth());
    }

    public function testAddDaysKeepsTheSubclassAndCrossesYears(): void
    {
        $d = (new CalDate(3000, 11, 31))->addDays(1);
        $this->assertInstanceOf(CalDate::class, $d);
        $this->assertSame('3001-01-01', $this->ymd($d));
        $this->assertSame('3000-12-31', $this->ymd((new CalDate(3001, 0, 1))->addDays(-1)));
        $this->assertFails(function () {
            (new CalDate(3002, 11, 31))->addDays(1);
        }, 'out of the supported range');
        $this->assertFails(function () {
            (new CalDate(3000, 0, 1))->addDays(-1);
        }, 'out of the supported range');
    }

    public function testAddMonthsClampsToTheTargetMonthLength(): void
    {
        $next = (new CalDate(3000, 11, 31))->addMonths(1);
        $this->assertInstanceOf(CalDate::class, $next);
        $this->assertSame('3001-01-30', $this->ymd($next));
        $this->assertSame('3000-03-30', $this->ymd((new CalDate(3000, 1, 31))->addMonths(1)));
        $this->assertSame('3000-12-15', $this->ymd((new CalDate(3001, 5, 15))->addMonths(-6)));
        $this->assertSame('3002-01-01', $this->ymd((new CalDate(3000, 0, 1))->addMonths(24)));
        $this->assertFails(function () {
            (new CalDate(3000, 0, 1))->addMonths(-1);
        }, 'Resulting date is out of supported range');
        $this->assertFails(function () {
            (new CalDate(3002, 11, 1))->addMonths(1);
        }, 'Resulting date is out of supported range');
    }

    public function testAddYearsClampsAndKeepsTheSubclass(): void
    {
        $up = (new CalDate(3000, 1, 31))->addYears(1);
        $this->assertInstanceOf(CalDate::class, $up);
        $this->assertSame('3001-02-30', $this->ymd($up));
        $this->assertSame('3002-02-31', $this->ymd((new CalDate(3000, 1, 31))->addYears(2)));
        $this->assertSame('3000-02-31', $this->ymd((new CalDate(3002, 1, 31))->addYears(-2)));
        $this->assertFails(function () {
            (new CalDate(3000, 0, 1))->addYears(3);
        }, 'Resulting date is out of supported range');
        $this->assertFails(function () {
            (new CalDate(3000, 0, 1))->addYears(-1);
        }, 'Resulting date is out of supported range');
    }

    public function testCloneKeepsTheSubclassAndTheDate(): void
    {
        $original = new CalDate(3001, 4, 9);
        $copy = $original->clone();

        $this->assertInstanceOf(CalDate::class, $copy);
        $this->assertNotSame($original, $copy);
        $this->assertSame('3001-05-09', $this->ymd($copy));
        $this->assertSame($original->getTime(), $copy->getTime());
    }

    public function testStartAndEndHelpersKeepTheSubclass(): void
    {
        $d = new CalDate(3001, 4, 9);
        $this->assertInstanceOf(CalDate::class, $d->startOfMonth());
        $this->assertSame('3001-05-01', $this->ymd($d->startOfMonth()));
        $this->assertSame('3001-05-30', $this->ymd($d->endOfMonth()));
        $this->assertSame('3001-01-01', $this->ymd($d->startOfYear()));
        $this->assertSame('3001-12-30', $this->ymd($d->endOfYear()));
        $this->assertSame('3002-12-31', $this->ymd((new CalDate(3002, 0, 1))->endOfYear()));
        $this->assertInstanceOf(CalDate::class, $d->endOfYear());
    }

    public function testStartAndEndOfWeekUseTheCustomWeekday(): void
    {
        $tuesday = new CalDate(3001, 0, 1);
        $this->assertSame('3000-12-30', $this->ymd($tuesday->startOfWeek()));
        $this->assertSame('3001-01-05', $this->ymd($tuesday->endOfWeek()));
        $this->assertSame('3000-12-31', $this->ymd($tuesday->startOfWeek(1)));
        $this->assertInstanceOf(CalDate::class, $tuesday->startOfWeek());
    }

    public function testStartAndEndOfDayStayOnTheSameCustomDay(): void
    {
        $d = new CalDate(3001, 4, 9);
        $start = $d->startOfDay();
        $end = $d->endOfDay();

        $this->assertSame('3001-05-09', $this->ymd($start));
        $this->assertSame('3001-05-09', $this->ymd($end));
        $this->assertSame(self::DAY_MS - 1, $end->getTime() - $start->getTime());
        $this->assertSame(0, $start->getHours());
        $this->assertSame(23, $end->getHours());
    }

    public function testComparisonsAndDifferences(): void
    {
        $a = new CalDate(3000, 0, 1);
        $b = new CalDate(3001, 2, 1);

        $this->assertSame(1, $b->diff($a, 'year'));
        $this->assertSame(14, $b->diff($a, 'month'));
        $this->assertSame(366 + 60, $b->diff($a, 'day'));
        $this->assertTrue($a->isBefore($b));
        $this->assertTrue($b->isAfter($a));
        $this->assertTrue($a->isEqual($a->clone()));
        $this->assertTrue($a->isSame(new CalDate(3000, 5, 5), 'year'));
        $this->assertFalse($a->isSame(new CalDate(3000, 5, 5), 'month'));
    }

    public function testToStringAndGetEnglishDate(): void
    {
        $d = new CalDate(3000, 0, 6);
        $this->assertSame('3000/1/6', $d->toString());
        $this->assertSame(self::adOf(5), $d->getEnglishDate()->format('Y-m-d'));
    }

    // ------------------------------------------------------------------------------------------
    // Formatting
    // ------------------------------------------------------------------------------------------

    public function testFormattingWithCustomLabelsAndDigits(): void
    {
        $d = new CalDate(3000, 1, 5); // Saturday, month index 1

        $this->assertSame('Month2', $d->format('MMMM'));
        $this->assertSame('M2', $d->format('MMM'));
        $this->assertSame('Day6', $d->format('DDDD'));
        $this->assertSame('D6', $d->format('DDD'));

        $this->assertSame('NpMonth2', $d->format('mmmm'));
        $this->assertSame('NM2', $d->format('mmm'));
        $this->assertSame('NpDay6', $d->format('dddd'));
        $this->assertSame('ND6', $d->format('ddd'));

        // 3000 -> d a a a, month 2 -> c, day 5 -> f
        $this->assertSame('daaa', $d->format('yyyy'));
        $this->assertSame('aa', $d->format('yy'));
        $this->assertSame('aaa', $d->format('yyy'));
        $this->assertSame('c', $d->format('m'));
        $this->assertSame('ac', $d->format('mm'));
        $this->assertSame('f', $d->format('d'));
        $this->assertSame('af', $d->format('dd'));
        $this->assertSame('daaa-ac-af', $d->format('yyyy-mm-dd'));

        // English digits are untouched
        $this->assertSame('3000-02-05', $d->format('YYYY-MM-DD'));
        $this->assertSame('2-5', $d->format('M-D'));
    }

    public function testDefaultClassKeepsItsOwnLabels(): void
    {
        $this->assertSame('Baisakh Friday', (new NepaliDate(2080, 0, 1))->format('MMMM DDDD'));
        $this->assertSame('बैशाख ०१', (new NepaliDate(2080, 0, 1))->format('mmmm dd'));
    }

    public function testPartialOverrideOnlyChangesWhatItNames(): void
    {
        $p = new PartialDate(2080, 0, 1);
        $this->assertSame('Only0', $p->format('MMMM'));
        $this->assertSame('Friday', $p->format('DDDD'));
        $this->assertSame('बैशाख', $p->format('mmmm'));
    }

    // ------------------------------------------------------------------------------------------
    // Extending the real calendar
    // ------------------------------------------------------------------------------------------

    public function testAnExtraYearIsSupportedOnlyByTheSubclass(): void
    {
        $this->assertSame('2101-01-01', $this->ymd(new ExtendedDate(2101, 0, 1)));
        $this->assertFails(function () {
            new NepaliDate(2101, 0, 1);
        }, 'Nepal year out of range!');
        $this->assertTrue(ExtendedDate::isValid(2101, 11, 31));
        $this->assertFalse(NepaliDate::isValid(2101, 11, 31));
    }

    public function testExtendedCalendarAgreesWithTheDefaultOnSharedDates(): void
    {
        foreach ([[1976, 0, 1], [2000, 5, 15], [2080, 0, 1], [2081, 6, 30], [2100, 11, 31]] as $case) {
            $base = new NepaliDate($case[0], $case[1], $case[2]);
            $ext = new ExtendedDate($case[0], $case[1], $case[2]);
            $this->assertSame($base->getTime(), $ext->getTime());
            $this->assertSame($base->format('YYYY-MM-DD dddd'), $ext->format('YYYY-MM-DD dddd'));
        }
    }

    public function testMaximumGrowsByExactlyTheAddedYear(): void
    {
        $added = array_sum(self::extraYear()['days']);
        $this->assertSame(
            $added * self::DAY_MS,
            ExtendedDate::maximum()->getTimestamp() * 1000 - NepaliDate::maximum()->getTimestamp() * 1000
        );
        $this->assertSame(NepaliDate::minimum()->getTimestamp(), ExtendedDate::minimum()->getTimestamp());
    }

    public function testTheDayAfterTheOldMaximumConvertsOnlyInTheSubclass(): void
    {
        $afterMs = NepaliDate::maximum()->getTimestamp() * 1000 + self::DAY_MS;
        $this->assertFails(function () use ($afterMs) {
            new NepaliDate($afterMs);
        }, 'out of the supported range');
        $this->assertSame('2101-01-01', $this->ymd(new ExtendedDate($afterMs)));
    }

    public function testArithmeticAcrossTheOldBoundary(): void
    {
        $this->assertSame('2101-06-10', $this->ymd((new ExtendedDate(2100, 5, 10))->addYears(1)));
        $this->assertSame('2101-12-31', $this->ymd((new ExtendedDate(2101, 0, 1))->endOfYear()));
    }

    public function testACorrectedMonthLengthOnlyAffectsTheSubclass(): void
    {
        $this->assertSame(31, (new NepaliDate(2080, 0, 1))->daysInMonth());
        $this->assertSame(32, (new PatchedDate(2080, 0, 1))->daysInMonth());
        $this->assertTrue(PatchedDate::isValid(2080, 0, 32));
        $this->assertFalse(NepaliDate::isValid(2080, 0, 32));
        $this->assertSame(
            (new NepaliDate(2080, 1, 1))->getTime() + self::DAY_MS,
            (new PatchedDate(2080, 1, 1))->getTime()
        );
    }

    // ------------------------------------------------------------------------------------------
    // Custom epoch and offset
    // ------------------------------------------------------------------------------------------

    public function testACustomEpochMapsToTheFirstDay(): void
    {
        $e = self::customEpoch();
        $this->assertSame('1-01-01', $this->ymd(new UtcDate($e)));
        $this->assertSame($e, (new UtcDate(1, 0, 1))->getTime());
        $this->assertSame($e + 30 * self::DAY_MS, (new UtcDate(1, 1, 1))->getTime());
        $this->assertSame($e + 719 * self::DAY_MS, (new UtcDate(2, 11, 30))->getTime());
    }

    public function testTimeOfDayGettersUseTheCustomOffset(): void
    {
        $instant = gmmktime(13, 30, 15, 1, 31, 2000) * 1000 + 250;
        $u = new UtcDate($instant);
        $this->assertSame([1, 1, 1], [$u->getYear(), $u->getMonth(), $u->getDate()]);
        $this->assertSame(13, $u->getHours());
        $this->assertSame(30, $u->getMinutes());
        $this->assertSame(15, $u->getSeconds());
        $this->assertSame(250, $u->getMilliseconds());

        $p = new Plus5Date($instant);
        $this->assertSame(18, $p->getHours());
        $this->assertSame(30, $p->getMinutes());
    }

    public function testTheOffsetCanPushAnInstantOntoTheNextLocalDay(): void
    {
        $late = gmmktime(20, 0, 0, 1, 1, 2000) * 1000;
        $this->assertSame(1, (new UtcDate($late))->getDate());
        $this->assertSame(2, (new Plus5Date($late))->getDate());
    }

    public function testStartAndEndOfDayRespectTheOffset(): void
    {
        $p = new Plus5Date(gmmktime(20, 0, 0, 1, 1, 2000) * 1000);
        $this->assertSame(0, $p->startOfDay()->getHours());
        $this->assertSame(23, $p->endOfDay()->getHours());
        $this->assertSame(2, $p->startOfDay()->getDate());
    }

    public function testInstantsOutsideACustomEpochRangeAreRejected(): void
    {
        $e = self::customEpoch();
        $this->assertFails(function () use ($e) {
            new UtcDate($e - 1);
        }, 'out of the supported range');
        $this->assertFails(function () use ($e) {
            new UtcDate($e + 720 * self::DAY_MS);
        }, 'out of the supported range');
    }

    public function testANegativeOffset(): void
    {
        $w = new WestDate(gmmktime(1, 0, 0, 1, 2, 2000) * 1000);
        $this->assertSame(1, $w->getDate());
        $this->assertSame(22, $w->getHours());
    }

    // ------------------------------------------------------------------------------------------
    // Copy constructor
    // ------------------------------------------------------------------------------------------

    public function testCopyWithinTheSameCalendarKeepsTheFields(): void
    {
        $original = new CalDate(3001, 4, 9);
        $copy = new CalDate($original);
        $this->assertSame([3001, 4, 9], [$copy->getYear(), $copy->getMonth(), $copy->getDate()]);
        $this->assertSame($original->getTime(), $copy->getTime());
    }

    public function testCopyBetweenTheDefaultClassAndASubclassSharingItsCalendar(): void
    {
        $copy = new ChildOfDefault(new NepaliDate(2080, 5, 6));
        $this->assertSame('2080-06-06', $this->ymd($copy));
        $this->assertSame('2080-06-06', $this->ymd(new NepaliDate($copy)));
    }

    public function testCopyAcrossDifferentCalendarsRecomputesFromTheInstant(): void
    {
        // bundled Baisakh 2080 has 31 days, so day 31 there is day 1 of month 2 in the shifted calendar
        $converted = new ShiftedDate(new NepaliDate(2080, 0, 31));
        $this->assertSame([2080, 1, 1], [$converted->getYear(), $converted->getMonth(), $converted->getDate()]);
        $this->assertTrue($converted->isValidInstance());
        $this->assertSame((new NepaliDate(2080, 0, 31))->getTime(), $converted->getTime());
    }

    public function testCopyAcrossCalendarsDoesNotKeepForeignFields(): void
    {
        $this->assertFails(function () {
            new CalDate(new NepaliDate(2080, 0, 1));
        }, 'out of the supported range');
        // both calendars share the default epoch, so the same instant is 1976-01-01 in the bundled one
        $this->assertSame('1976-01-01', $this->ymd(new NepaliDate(new CalDate(3000, 0, 1))));
    }

    // ------------------------------------------------------------------------------------------
    // ADtoBS / BStoAD with a custom class
    // ------------------------------------------------------------------------------------------

    public function testConvertersUseTheSubclassCalendar(): void
    {
        $this->assertSame('3000-01-01', ADtoBS('1919-04-13', CalDate::class));
        $this->assertSame('1919-04-13', BStoAD('3000-01-01', CalDate::class));
        $this->assertSame(self::adOf(366), BStoAD('3001-01-01', CalDate::class));
        $this->assertSame(self::adOf(1097), BStoAD('3002-12-31', CalDate::class));
        $this->assertSame('3002-01-01', ADtoBS(self::adOf(726), CalDate::class));
    }

    public function testConvertersRoundTripEveryCustomDaySampled(): void
    {
        for ($n = 0; $n < 1098; $n += 17) {
            $this->assertSame(self::adOf($n), BStoAD(ADtoBS(self::adOf($n), CalDate::class), CalDate::class));
        }
    }

    public function testConvertersReportFailuresOutsideTheCustomRange(): void
    {
        $this->assertFails(function () {
            ADtoBS('2023-04-14', CalDate::class);
        }, 'Failed to convert AD to BS', \RuntimeException::class);
        $this->assertFails(function () {
            BStoAD('2080-01-01', CalDate::class);
        }, 'Failed to convert BS to AD', \RuntimeException::class);
        $this->assertFails(function () {
            BStoAD('3001-01-31', CalDate::class);
        }, 'Failed to convert BS to AD', \RuntimeException::class);
    }

    public function testConvertersKeepValidatingTheInputShape(): void
    {
        $this->assertFails(function () {
            ADtoBS('1919/04/13', CalDate::class);
        }, 'Invalid date format');
        $this->assertFails(function () {
            BStoAD('3000-1-1', CalDate::class);
        }, 'Invalid date format');
    }

    public function testConvertersRejectClassesThatAreNotNepaliDates(): void
    {
        $this->assertFails(function () {
            ADtoBS('2023-04-14', \stdClass::class);
        }, 'must be');
        $this->assertFails(function () {
            BStoAD('2080-01-01', 'No\\Such\\Class');
        }, 'must be');
    }

    public function testConvertersDefaultToTheBundledCalendar(): void
    {
        $this->assertSame('2080-01-01', ADtoBS('2023-04-14'));
        $this->assertSame('2023-04-14', BStoAD('2080-01-01'));
        $this->assertFails(function () {
            BStoAD('3000-01-01');
        }, 'Failed to convert BS to AD', \RuntimeException::class);
    }
}

class ChildOfDefault extends NepaliDate
{
}
