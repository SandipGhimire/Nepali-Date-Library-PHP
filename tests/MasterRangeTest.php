<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Tests;

use DateTimeImmutable;
use DateTimeZone;
use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\NepaliDate;
use PHPUnit\Framework\TestCase;

use function NepaliDateLibrary\ADtoBS;
use function NepaliDateLibrary\BStoAD;

/**
 * Full-range verification (BS 1976 - 2100), mirroring
 * NodeJS/test/master.test.ts and Python's tests/test_nepali_date.py.
 *
 * For every single supported day, checks that BS -> AD -> BS round-trips
 * losslessly and that reconstructing a NepaliDate from the converted AD
 * date yields the identical BS date.
 */
final class MasterRangeTest extends TestCase
{
    public function testFullRangeRoundTrip(): void
    {
        foreach (Constants::$NEPALI_DATE_MAP as $yearData) {
            $year = $yearData['year'];

            foreach ($yearData['days'] as $monthIndex => $daysInMonth) {
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $nepaliDate = new NepaliDate($year, $monthIndex, $day);
                    $expected = $nepaliDate->format('YYYY-MM-DD');

                    $bsToAdDate = BStoAD($expected);

                    $adDateTime = DateTimeImmutable::createFromFormat(
                        '!Y-m-d',
                        $bsToAdDate,
                        new DateTimeZone('UTC')
                    );
                    $nepaliDate2 = new NepaliDate($adDateTime);
                    $adToBsDate = ADtoBS($bsToAdDate);

                    self::assertSame(
                        $expected,
                        $nepaliDate2->format('YYYY-MM-DD'),
                        "Round-trip via AD mismatch for {$expected} (AD {$bsToAdDate})"
                    );
                    self::assertSame(
                        $expected,
                        $adToBsDate,
                        "ADtoBS mismatch for {$expected} (AD {$bsToAdDate})"
                    );
                }
            }
        }
    }
}
