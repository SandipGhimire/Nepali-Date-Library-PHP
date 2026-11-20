<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Helper;

use InvalidArgumentException;

class Utils
{
    private const MS_PER_DAY = 86400000;
    private const MAX_DAYS_IN_MONTH = 32;

    /** Required length of every name table. */
    private const NAME_TABLE_SIZES = [
        'weekEn' => 7,
        'weekShortEn' => 7,
        'weekNp' => 7,
        'weekShortNp' => 7,
        'monthEn' => 12,
        'monthShortEn' => 12,
        'monthNp' => 12,
        'monthShortNp' => 12,
        'numberNp' => 10,
    ];

    /**
     * Always throws. (Declared void for PHP 7.1 compatibility.)
     *
     * @throws InvalidArgumentException
     */
    private static function fail(string $message): void
    {
        throw new InvalidArgumentException('Invalid Nepali constants: ' . $message);
    }

    /**
     * @param mixed $value
     */
    private static function isList($value): bool
    {
        return is_array($value) && $value === array_values($value);
    }

    /**
     * Validates a (raw) constants object and throws on the first problem found.
     *
     * Checks that:
     * - dateMap is a non-empty list of consecutive years, each with 12 integer month lengths (1-32)
     * - epoch is an integer number of milliseconds falling exactly on a UTC midnight
     * - utcOffsetMs is a finite number
     * - every name table (weeks, months, digits) has the right size and only holds strings
     *
     * @throws InvalidArgumentException when the constants are not usable
     */
    public static function validateConstants(NepaliConstants $constants): void
    {
        $dateMap = $constants->dateMap;

        if (!self::isList($dateMap) || count($dateMap) === 0) {
            self::fail('dateMap must be a non-empty list');
        }

        $firstYear = null;
        foreach ($dateMap as $i => $entry) {
            if (!is_array($entry) || !isset($entry['year']) || !is_int($entry['year'])) {
                self::fail("dateMap[{$i}].year must be an integer");
            }
            if ($firstYear === null) {
                $firstYear = $entry['year'];
            }
            if ($entry['year'] !== $firstYear + $i) {
                $expected = $firstYear + $i;
                self::fail("dateMap years must be consecutive, expected {$expected} at index {$i} but got {$entry['year']}");
            }
            if (!isset($entry['days']) || !self::isList($entry['days']) || count($entry['days']) !== 12) {
                self::fail("dateMap[{$i}] (year {$entry['year']}) must have exactly 12 month lengths");
            }
            foreach ($entry['days'] as $m => $days) {
                if (!is_int($days) || $days < 1 || $days > self::MAX_DAYS_IN_MONTH) {
                    $month = $m + 1;
                    self::fail("month {$month} of year {$entry['year']} must be an integer between 1 and " . self::MAX_DAYS_IN_MONTH);
                }
            }
        }

        $epoch = $constants->epoch;
        if (!is_int($epoch) || $epoch % self::MS_PER_DAY !== 0) {
            self::fail('epoch must be an integer number of milliseconds on a UTC midnight boundary');
        }

        $offset = $constants->utcOffsetMs;
        if (!is_int($offset) && !(is_float($offset) && is_finite($offset))) {
            self::fail('utcOffsetMs must be a finite number');
        }

        foreach (self::NAME_TABLE_SIZES as $key => $size) {
            $table = $constants->{$key};
            if (!self::isList($table) || count($table) !== $size) {
                self::fail("{$key} must be an array of exactly {$size} strings");
            }
            foreach ($table as $item) {
                if (!is_string($item)) {
                    self::fail("{$key} must only contain strings");
                }
            }
        }
    }

    /**
     * Builds the processed (cumulative) calendar table from a raw one.
     *
     * @param array<int, array{year:int, days:int[]}> $dateMap
     * @return array<int, array{year:int, days:int[], totalDays:int, daysTillNow:int}>
     */
    public static function processDateMap(array $dateMap): array
    {
        $totalDaysAccumulator = 0;
        $processed = [];

        foreach ($dateMap as $entry) {
            $totalDays = array_sum($entry['days']);
            $totalDaysAccumulator += $totalDays;

            $processed[] = [
                'year' => (int) $entry['year'],
                'days' => array_values($entry['days']),
                'totalDays' => $totalDays,
                'daysTillNow' => $totalDaysAccumulator,
            ];
        }

        return $processed;
    }

    /**
     * Validates the constants and returns a processed snapshot. The snapshot never
     * shares state with the input (PHP arrays are copied on write), so later changes
     * to the input do not affect it.
     *
     * @throws InvalidArgumentException when the constants fail validation
     */
    public static function processConstants(NepaliConstants $constants): NepaliConstants
    {
        self::validateConstants($constants);

        $processed = new NepaliConstants(
            self::processDateMap($constants->dateMap),
            $constants->epoch,
            (int) $constants->utcOffsetMs,
            $constants->weekEn,
            $constants->weekShortEn,
            $constants->weekNp,
            $constants->weekShortNp,
            $constants->monthEn,
            $constants->monthShortEn,
            $constants->monthNp,
            $constants->monthShortNp,
            $constants->numberNp
        );

        return $processed->markProcessed();
    }

    /**
     * Finds the index of the year block containing the given zero-based day offset
     * (days since the epoch). Binary search, so it works for any year length.
     *
     * @param array<int, array{daysTillNow:int}> $dateMap Processed year entries
     * @param int $daysCount Days since the epoch, must be inside the calendar range
     */
    public static function findYearIndex(array $dateMap, int $daysCount): int
    {
        $lo = 0;
        $hi = count($dateMap) - 1;

        while ($lo < $hi) {
            $mid = ($lo + $hi) >> 1;
            if ($daysCount < $dateMap[$mid]['daysTillNow']) {
                $hi = $mid;
            } else {
                $lo = $mid + 1;
            }
        }

        return $lo;
    }
}
