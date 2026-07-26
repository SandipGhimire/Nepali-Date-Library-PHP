<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function NepaliDateLibrary\ADtoBS;
use function NepaliDateLibrary\BStoAD;

final class DateConverterTest extends TestCase
{
    public function testADtoBSKnownDate(): void
    {
        self::assertSame('2080-01-01', ADtoBS('2023-04-14'));
    }

    public function testBStoADKnownDate(): void
    {
        self::assertSame('2023-04-14', BStoAD('2080-01-01'));
    }

    public function testRoundTrip(): void
    {
        $ad = '2023-04-28';
        $bs = ADtoBS($ad);
        self::assertSame($ad, BStoAD($bs));
    }

    public function testADtoBSRejectsMalformedInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ADtoBS('2023/04/14');
    }

    public function testBStoADRejectsMalformedInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        BStoAD('2080/01/01');
    }

    public function testADtoBSRejectsOutOfRangeDate(): void
    {
        $this->expectException(RuntimeException::class);
        ADtoBS('1800-01-01');
    }

    public function testBStoADRejectsOutOfRangeYear(): void
    {
        $this->expectException(RuntimeException::class);
        BStoAD('1800-01-01');
    }
}
