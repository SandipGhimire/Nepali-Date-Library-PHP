<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Helper;

class Utils
{
    /**
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
                'days' => $entry['days'],
                'totalDays' => $totalDays,
                'daysTillNow' => $totalDaysAccumulator,
            ];
        }

        return $processed;
    }

    public static function processConstants(NepaliConstants $constants): NepaliConstants
    {
        $processedDateMap = self::processDateMap($constants->dateMap);

        return new NepaliConstants(
            $processedDateMap,
            $constants->epoch,
            $constants->utcOffsetMs,
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
    }
}

