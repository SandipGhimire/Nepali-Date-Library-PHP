<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Helper;

use InvalidArgumentException;

/**
 * A complete set of calendar data (year table, epoch, UTC offset and name tables).
 *
 * Treat instances as immutable: build one with NepaliConstants::define() and return it from a
 * NepaliDate subclass. It is validated and snapshotted the first time a date uses it.
 */
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
     * Processed snapshot of this object (itself when it is already processed).
     *
     * @var NepaliConstants|null
     */
    private $snapshot = null;

    /**
     * Returns the validated, ready-to-use form of these constants.
     *
     * The first call validates the data and builds a snapshot that is cached on this object,
     * so later changes to the public properties are NOT picked up. To change the calendar,
     * hand the date class a new NepaliConstants object instead of mutating this one.
     *
     * @throws InvalidArgumentException when the constants are invalid
     */
    public function process(): self
    {
        if ($this->snapshot === null) {
            $this->snapshot = Utils::processConstants($this);
        }

        return $this->snapshot;
    }

    /**
     * Marks this object as already processed (cumulative day counts present).
     *
     * @internal Used by the library for the bundled data and for snapshots.
     */
    public function markProcessed(): self
    {
        $this->snapshot = $this;

        return $this;
    }

    /**
     * Builds a validated constants object by layering $overrides over the bundled data.
     * Fields that are not overridden fall back to the bundled values, so a partial array is fine.
     *
     * Accepted keys: dateMap (alias nepaliDateMap), epoch, utcOffsetMs, weekEn, weekShortEn,
     * weekNp, weekShortNp, monthEn, monthShortEn, monthNp, monthShortNp, numberNp.
     *
     * @param array<string, mixed> $overrides
     * @throws InvalidArgumentException on unknown keys or invalid values
     */
    public static function define(array $overrides = []): self
    {
        Constants::init();

        $allowed = [
            'dateMap', 'nepaliDateMap', 'epoch', 'utcOffsetMs', 'weekEn', 'weekShortEn', 'weekNp',
            'weekShortNp', 'monthEn', 'monthShortEn', 'monthNp', 'monthShortNp', 'numberNp',
        ];
        foreach (array_keys($overrides) as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('Invalid Nepali constants: unknown key "' . $key . '"');
            }
        }

        $pick = static function (string $key, $default) use ($overrides) {
            if (!isset($overrides[$key])) {
                return $default;
            }
            if (!is_array($overrides[$key])) {
                throw new InvalidArgumentException('Invalid Nepali constants: ' . $key . ' must be an array');
            }

            return $overrides[$key];
        };

        $integer = static function (string $key, int $default) use ($overrides): int {
            if (!isset($overrides[$key])) {
                return $default;
            }
            $value = $overrides[$key];
            if (is_float($value) && is_finite($value) && floor($value) === $value) {
                $value = (int) $value;
            }
            if (!is_int($value)) {
                throw new InvalidArgumentException('Invalid Nepali constants: ' . $key . ' must be an integer');
            }

            return $value;
        };

        $dateMap = $overrides['dateMap'] ?? $overrides['nepaliDateMap'] ?? Constants::$NEPALI_DATE_MAP;
        if (!is_array($dateMap)) {
            throw new InvalidArgumentException('Invalid Nepali constants: dateMap must be a non-empty list');
        }
        // Keep only year/days so processed-only fields from the bundled map do not leak in.
        $normalized = [];
        foreach ($dateMap as $entry) {
            $normalized[] = [
                'year' => is_array($entry) && array_key_exists('year', $entry) ? $entry['year'] : null,
                'days' => is_array($entry) && isset($entry['days']) && is_array($entry['days']) ? $entry['days'] : null,
            ];
        }

        $constants = new self(
            $normalized,
            $integer('epoch', Constants::$EPOCH),
            $integer('utcOffsetMs', Constants::$NEPAL_UTC_OFFSET_MS),
            $pick('weekEn', Constants::$WEEK_EN),
            $pick('weekShortEn', Constants::$WEEK_SHORT_EN),
            $pick('weekNp', Constants::$WEEK_NP),
            $pick('weekShortNp', Constants::$WEEK_SHORT_NP),
            $pick('monthEn', Constants::$MONTH_EN),
            $pick('monthShortEn', Constants::$MONTH_SHORT_EN),
            $pick('monthNp', Constants::$MONTH_NP),
            $pick('monthShortNp', Constants::$MONTH_SHORT_NP),
            $pick('numberNp', Constants::$NUMBER_NP)
        );

        Utils::validateConstants($constants);

        return $constants;
    }

    /**
     * Alias of define(), kept for readability when building from decoded JSON / config arrays.
     *
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException on unknown keys or invalid values
     */
    public static function fromArray(array $data): self
    {
        return self::define($data);
    }
}
