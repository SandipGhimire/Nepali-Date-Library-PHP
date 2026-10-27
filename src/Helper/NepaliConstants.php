<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Helper;

class NepaliConstants
{
    /** @var array<int, array{year:int, days:int[]}> */
    public $dateMap;

    /** @var int */
    public $epoch;

    /** @var int */
    public $utcOffsetMs;

    /** @var string[] */
    public $weekEn;

    /** @var string[] */
    public $weekShortEn;

    /** @var string[] */
    public $weekNp;

    /** @var string[] */
    public $weekShortNp;

    /** @var string[] */
    public $monthEn;

    /** @var string[] */
    public $monthShortEn;

    /** @var string[] */
    public $monthNp;

    /** @var string[] */
    public $monthShortNp;

    /** @var string[] */
    public $numberNp;

    public function __construct(
        array $dateMap,
        int $epoch,
        int $utcOffsetMs,
        array $weekEn,
        array $weekShortEn,
        array $weekNp,
        array $weekShortNp,
        array $monthEn,
        array $monthShortEn,
        array $monthNp,
        array $monthShortNp,
        array $numberNp
    ) {
        $this->dateMap = $dateMap;
        $this->epoch = $epoch;
        $this->utcOffsetMs = $utcOffsetMs;
        $this->weekEn = $weekEn;
        $this->weekShortEn = $weekShortEn;
        $this->weekNp = $weekNp;
        $this->weekShortNp = $weekShortNp;
        $this->monthEn = $monthEn;
        $this->monthShortEn = $monthShortEn;
        $this->monthNp = $monthNp;
        $this->monthShortNp = $monthShortNp;
        $this->numberNp = $numberNp;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        Constants::init();

        return new self(
            $data['dateMap'] ?? $data['nepaliDateMap'] ?? Constants::$NEPALI_DATE_MAP,
            isset($data['epoch']) ? (int) $data['epoch'] : Constants::$EPOCH,
            isset($data['utcOffsetMs']) ? (int) $data['utcOffsetMs'] : Constants::$NEPAL_UTC_OFFSET_MS,
            $data['weekEn'] ?? Constants::$WEEK_EN,
            $data['weekShortEn'] ?? Constants::$WEEK_SHORT_EN,
            $data['weekNp'] ?? Constants::$WEEK_NP,
            $data['weekShortNp'] ?? Constants::$WEEK_SHORT_NP,
            $data['monthEn'] ?? Constants::$MONTH_EN,
            $data['monthShortEn'] ?? Constants::$MONTH_SHORT_EN,
            $data['monthNp'] ?? Constants::$MONTH_NP,
            $data['monthShortNp'] ?? Constants::$MONTH_SHORT_NP,
            $data['numberNp'] ?? Constants::$NUMBER_NP
        );
    }
}
