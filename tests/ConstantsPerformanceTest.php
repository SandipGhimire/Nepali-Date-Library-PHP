<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Tests;

use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\Helper\NepaliConstants;
use NepaliDateLibrary\NepaliDate;
use PHPUnit\Framework\TestCase;

class ConstantsPerformanceTest extends TestCase
{
    public function testDefaultConstantsPerformance(): void
    {
        $date = new NepaliDate();

        $N = 1000000;
        $start = microtime(true);
        for ($i = 0; $i < $N; $i++) {
            $date->getConstants();
        }
        $elapsed = microtime(true) - $start;
        $microPerOp = ($elapsed * 1000000) / $N;

        echo "\n[PHP Benchmark] Default getConstants(): " . sprintf('%.4f', $microPerOp) . " µs/op\n";
        $this->assertLessThan(1.0, $microPerOp);
    }

    public function testCustomConstantsProcessingAndCaching(): void
    {
        $customConstants = new NepaliConstants(
            [
                ['year' => 2080, 'days' => [31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 29, 31]],
                ['year' => 2081, 'days' => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31]],
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

        $customDate = new class($customConstants) extends NepaliDate {
            private $custom;
            public function __construct(NepaliConstants $custom) {
                $this->custom = $custom;
                parent::__construct(2080, 0, 1);
            }
            protected function _getConstants(): NepaliConstants {
                return $this->custom;
            }
        };

        // First process
        $start = microtime(true);
        /** @var NepaliConstants $first */
        $first = $customDate->getConstants();
        $firstRunMicro = (microtime(true) - $start) * 1000000;

        $this->assertEquals(364, $first->dateMap[0]['totalDays']);
        $this->assertEquals(364, $first->dateMap[0]['daysTillNow']);
        $this->assertEquals(730, $first->dateMap[1]['daysTillNow']);

        // Subsequent cached runs
        $N = 1000000;
        $start = microtime(true);
        for ($i = 0; $i < $N; $i++) {
            $customDate->getConstants();
        }
        $cachedMicro = ((microtime(true) - $start) * 1000000) / $N;

        echo "[PHP Benchmark] Custom first-run process: " . sprintf('%.2f', $firstRunMicro) . " µs | Cached calls: " . sprintf('%.4f', $cachedMicro) . " µs/op\n";
        $this->assertLessThan(1.0, $cachedMicro);
    }
}

