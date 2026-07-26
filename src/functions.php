<?php

declare(strict_types=1);

namespace NepaliDateLibrary;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;

/**
 * Converts an Anno Domini (AD) date to a Bikram Sambat (BS) date.
 *
 * Mirrors NodeJS/src/Helper/DateConverter.ts's ADtoBS().
 *
 * @param string $adDate The AD date in "YYYY-MM-DD" format.
 * @return string The corresponding BS date in "YYYY-MM-DD" format.
 */
function ADtoBS(string $adDate): string
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $adDate) !== 1) {
        throw new \InvalidArgumentException('Invalid date format. Expected format: YYYY-MM-DD');
    }

    $ad = DateTimeImmutable::createFromFormat('!Y-m-d', $adDate, new DateTimeZone('UTC'));
    if ($ad === false) {
        throw new \InvalidArgumentException("Invalid date input '{$adDate}'");
    }

    try {
        $nepaliDate = new NepaliDate($ad);
        return $nepaliDate->format('YYYY-MM-DD');
    } catch (Throwable $err) {
        throw new RuntimeException('Failed to convert AD to BS', 0, $err);
    }
}

/**
 * Converts a Bikram Sambat (BS) date to an Anno Domini (AD) date.
 *
 * Mirrors NodeJS/src/Helper/DateConverter.ts's BStoAD().
 *
 * @param string $bsDate The BS date in "YYYY-MM-DD" format.
 * @return string The corresponding AD date in "YYYY-MM-DD" format.
 */
function BStoAD(string $bsDate): string
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bsDate) !== 1) {
        throw new \InvalidArgumentException('Invalid date format. Expected format: YYYY-MM-DD');
    }

    try {
        $nepaliDateInstance = $bsDate !== '' ? new NepaliDate($bsDate) : new NepaliDate();
        $date = $nepaliDateInstance->getEnglishDate();

        return $date->format('Y-m-d');
    } catch (Throwable $err) {
        throw new RuntimeException('Failed to convert BS to AD', 0, $err);
    }
}
