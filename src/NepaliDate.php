<?php

declare(strict_types=1);

namespace NepaliDateLibrary;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use NepaliDateLibrary\Helper\Constants;
use NepaliDateLibrary\Helper\DateFormatter;
use NepaliDateLibrary\Helper\NepaliConstants;
use NepaliDateLibrary\Helper\Utils;

/**
 * Represents a date in the Nepali (Bikram Sambat) calendar.
 *
 * Ported 1:1 from NodeJS/src/NepaliDate.ts (method names, argument order, and
 * validation messages are kept identical wherever PHP allows it).
 *
 * Internally the instant backing this date is stored as Unix epoch
 * milliseconds and all derived getters (weekday, hour, minute, second,
 * millisecond) are computed in Nepal Standard Time (UTC+05:45), applied as
 * an explicit fixed offset so results only ever depend on the instant being
 * represented, never on the host machine's own timezone configuration.
 */
class NepaliDate
{
    /** @var int */
    private $timestampMs;

    /** @var int */
    private $year;

    /** @var int */
    private $month;

    /** @var int */
    private $day;

    /** @var NepaliConstants|null */
    private $rawConstants = null;

    /** @var NepaliConstants|null */
    private $cachedProcessedConstants = null;

    protected function _getConstants(): NepaliConstants
    {
        return Constants::getBundled();
    }

    public function getConstants(): NepaliConstants
    {
        $currentRaw = $this->_getConstants();
        if ($currentRaw === Constants::getBundled()) {
            return $currentRaw;
        }
        if ($this->rawConstants !== $currentRaw || $this->cachedProcessedConstants === null) {
            $this->rawConstants = $currentRaw;
            $this->cachedProcessedConstants = Utils::processConstants($currentRaw);
        }
        return $this->cachedProcessedConstants;
    }

    /**
     * Creates a NepaliDate instance.
     *
     * @param DateTimeInterface|NepaliDate|int|string|null $yearOrDate Nepali
     *        year, a source date (DateTimeInterface, NepaliDate, epoch-ms
     *        int, or parseable string), or null for the current date.
     * @param int|null $month Nepali month (0-11), required together with $day
     *        when $yearOrDate is a Nepali year.
     * @param int|null $day Nepali day (1-32).
     */
    public function __construct($yearOrDate = null, ?int $month = null, ?int $day = null)
    {
        if ($yearOrDate === null) {
            $this->setEnglishDate(self::nowMs());
        } elseif (is_object($yearOrDate)) {
            if ($yearOrDate instanceof self) {
                $this->timestampMs = $yearOrDate->timestampMs;
                $this->year = $yearOrDate->year;
                $this->month = $yearOrDate->month;
                $this->day = $yearOrDate->day;
            } elseif ($yearOrDate instanceof DateTimeInterface) {
                $this->setEnglishDate(self::msFromDateTime($yearOrDate));
            } else {
                throw new InvalidArgumentException('Invalid argument syntax');
            }
        } elseif (is_string($yearOrDate)) {
            [$y, $m, $d] = $this->parseDateString($yearOrDate, $this->getConstants()->dateMap);
            $this->set($y, $m, $d);
        } elseif (is_int($yearOrDate) && $month !== null && $day !== null) {
            $this->set($yearOrDate, $month, $day);
        } elseif (is_int($yearOrDate)) {
            $this->setEnglishDate($yearOrDate);
        } else {
            throw new InvalidArgumentException('Invalid argument syntax');
        }
    }

    private static function nowMs(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    private static function msFromDateTime(DateTimeInterface $date): int
    {
        $micro = (int) $date->format('u');
        return $date->getTimestamp() * 1000 + intdiv($micro, 1000);
    }

    /**
     * Integer floor division (PHP's intdiv() truncates toward zero; this
     * matches JS's Math.floor(a / b) for negative operands).
     */
    private static function floorDiv(int $a, int $b): int
    {
        $q = intdiv($a, $b);
        if ($a % $b !== 0 && ($a < 0) !== ($b < 0)) {
            $q--;
        }
        return $q;
    }

    private static function dateTimeFromMs(int $ms): DateTimeImmutable
    {
        $seconds = self::floorDiv($ms, 1000);
        $micros = $ms - $seconds * 1000;
        $fraction = str_pad((string) ($micros * 1000), 6, '0', STR_PAD_LEFT);

        return new DateTimeImmutable("@{$seconds}.{$fraction}");
    }

    /**
     * Parses a date string into Nepali date components.
     *
     * @return array{0:int,1:int,2:int} [year, month(0-11), day]
     */
    private function parseDateString(string $dateString, ?array $dateMap = null): array
    {
        $parts = preg_split('/[-.\/]/', $dateString, 3);

        $year = self::parseIntLike($parts[0]);
        $month = array_key_exists(1, $parts) ? self::parseIntLike($parts[1]) : 1;
        $day = array_key_exists(2, $parts) ? self::parseIntLike($parts[2]) : 1;

        $map = $dateMap ?? $this->getConstants()->dateMap;

        if ($year < $map[0]['year'] || $year >= $map[0]['year'] + count($map)) {
            throw new InvalidArgumentException('Nepal year out of range');
        }

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid nepali month must be between 1 - 12');
        }

        $daysInMonth = $map[$year - $map[0]['year']]['days'][$month - 1];
        if ($day < 1 || $day > $daysInMonth) {
            throw new InvalidArgumentException(
                "Invalid nepali date must be between 1 - {$daysInMonth} in {$year} {$month}"
            );
        }

        return [$year, $month - 1, $day];
    }

    /**
     * Mimics JS's lenient parseInt(value, 10): parses a leading integer,
     * ignoring surrounding whitespace, and rejects strings with no digits.
     */
    private static function parseIntLike(string $value): int
    {
        if (preg_match('/^\s*[+-]?\d+/', $value, $m) !== 1) {
            throw new InvalidArgumentException('Invalid date');
        }
        return (int) $m[0];
    }

    /**
     * Sets the internal properties based on the provided instant (epoch ms).
     */
    private function setEnglishDate(int $timestampMs): void
    {
        $this->timestampMs = $timestampMs;

        $c = $this->getConstants();
        $map = $c->dateMap;
        $mapCount = count($map);

        $nepalWallClockMs = $timestampMs + $c->utcOffsetMs;
        $daysCount = self::floorDiv($nepalWallClockMs - $c->epoch, 86400000);

        if ($daysCount < 0 || $daysCount >= $map[$mapCount - 1]['daysTillNow']) {
            throw new InvalidArgumentException('Date is out of the supported range');
        }

        $idx = self::floorDiv($daysCount, 366);

        if ($idx < 0) {
            $idx = 0;
        } elseif ($idx >= $mapCount) {
            $idx = $mapCount - 1;
        }

        while ($idx < $mapCount && $daysCount >= $map[$idx]['daysTillNow']) {
            $idx++;
        }

        if ($idx >= $mapCount) {
            throw new InvalidArgumentException('Date is out of the supported range');
        }

        $prevTillNow = $idx - 1 >= 0 ? $map[$idx - 1]['daysTillNow'] : 0;
        $daysCount -= $prevTillNow;

        $tmp = $map[$idx];
        $this->year = $tmp['year'];
        $this->month = 0;

        while ($daysCount >= $tmp['days'][$this->month]) {
            $daysCount -= $tmp['days'][$this->month];
            $this->month++;
        }

        $this->day = $daysCount + 1;
    }

    /**
     * Sets the Nepali date components and updates the internal timestamp.
     *
     * @param int $year Nepali year
     * @param int $month Nepali month (0-11)
     * @param int $date Nepali day (1-32)
     */
    public function set(int $year, int $month, int $date): void
    {
        $c = $this->getConstants();
        $map = $c->dateMap;
        $idx = $year + self::floorDiv($month, 12) - $map[0]['year'];
        if ($idx < 0 || $idx >= count($map)) {
            throw new InvalidArgumentException('Nepal year out of range!');
        }

        $tmp = $map[$idx];
        $d = $tmp['daysTillNow'] - $tmp['totalDays'];

        $m = $month % 12;
        $mm = $m < 0 ? 12 + $m : $m;

        for ($i = 0; $i < $mm; $i++) {
            $d += $tmp['days'][$i];
        }
        $d += $date - 1;

        $utcTimestamp = $c->epoch + $d * 86400000;
        $this->setEnglishDate($utcTimestamp);
    }

    /**
     * Formats the Nepali date according to the specified format string.
     */
    public function format(string $formatStr): string
    {
        return DateFormatter::format($this, $formatStr);
    }

    /**
     * Creates a copy of the current NepaliDate instance.
     */
    public function clone(): self
    {
        return new static($this);
    }

    /**
     * Returns the string representation of the Nepali date (YYYY/M/D).
     */
    public function toString(): string
    {
        return sprintf('%d/%d/%d', $this->year, $this->month + 1, $this->day);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns the equivalent English (Gregorian) date as a UTC DateTimeImmutable.
     */
    public function getEnglishDate(): DateTimeImmutable
    {
        return self::dateTimeFromMs($this->timestampMs);
    }

    /**
     * Parses a date string and updates the current instance.
     */
    public function parse(string $dateString): void
    {
        [$y, $m, $d] = $this->parseDateString($dateString, $this->getConstants()->dateMap);
        $this->set($y, $m, $d);
    }

    public function getYear(): int
    {
        return $this->year;
    }

    /** Nepali month (0-11). */
    public function getMonth(): int
    {
        return $this->month;
    }

    /** Day of week (0-6, 0 = Sunday), computed in Nepal local time. */
    public function getDay(): int
    {
        return (int) gmdate('w', self::floorDiv($this->timestampMs + $this->getConstants()->utcOffsetMs, 1000));
    }

    /** Nepali day of month. */
    public function getDate(): int
    {
        return $this->day;
    }

    /** Hour of day (0-23), computed in Nepal local time. */
    public function getHours(): int
    {
        return (int) gmdate('G', self::floorDiv($this->timestampMs + $this->getConstants()->utcOffsetMs, 1000));
    }

    /** Minutes (0-59), computed in Nepal local time. */
    public function getMinutes(): int
    {
        return (int) gmdate('i', self::floorDiv($this->timestampMs + $this->getConstants()->utcOffsetMs, 1000));
    }

    /** Seconds (0-59), computed in Nepal local time. */
    public function getSeconds(): int
    {
        return (int) gmdate('s', self::floorDiv($this->timestampMs + $this->getConstants()->utcOffsetMs, 1000));
    }

    /** Milliseconds (0-999). */
    public function getMilliseconds(): int
    {
        return (($this->timestampMs % 1000) + 1000) % 1000;
    }

    /** Timestamp in Unix epoch milliseconds. */
    public function getTime(): int
    {
        return $this->timestampMs;
    }

    public function setYear(int $year): void
    {
        $this->set($year, $this->month, $this->day);
    }

    public function setMonth(int $month): void
    {
        $this->set($this->year, $month, $this->day);
    }

    public function setDate(int $day): void
    {
        $this->set($this->year, $this->month, $day);
    }

    /**
     * Adds the specified number of days to the current Nepali date.
     */
    public function addDays(int $days): self
    {
        return new static($this->timestampMs + $days * 86400000);
    }

    /**
     * Adds the specified number of months to the current Nepali date.
     */
    public function addMonths(int $months): self
    {
        $newYear = $this->year;
        $newMonth = $this->month + $months;

        $newYear += self::floorDiv($newMonth, 12);
        $newMonth = $newMonth % 12;

        if ($newMonth < 0) {
            $newMonth += 12;
        }

        $map = $this->getConstants()->dateMap;
        $yearIndex = $newYear - $map[0]['year'];
        if ($yearIndex < 0 || $yearIndex >= count($map)) {
            throw new InvalidArgumentException('Resulting date is out of supported range');
        }

        $daysInNewMonth = $map[$yearIndex]['days'][$newMonth];
        $newDay = min($this->day, $daysInNewMonth);

        return new static($newYear, $newMonth, $newDay);
    }

    /**
     * Adds the specified number of years to the current Nepali date.
     */
    public function addYears(int $years): self
    {
        $newYear = $this->year + $years;

        $map = $this->getConstants()->dateMap;
        if ($newYear < $map[0]['year'] || $newYear >= $map[0]['year'] + count($map)) {
            throw new InvalidArgumentException('Resulting date is out of supported range');
        }

        $yearIndex = $newYear - $map[0]['year'];
        $daysInMonth = $map[$yearIndex]['days'][$this->month];
        $newDay = min($this->day, $daysInMonth);

        return new static($newYear, $this->month, $newDay);
    }

    /**
     * Returns the earliest date supported by NepaliDate.
     */
    public static function minimum(): DateTimeImmutable
    {
        return self::dateTimeFromMs(Constants::$EPOCH);
    }

    /**
     * Returns the latest date supported by NepaliDate.
     */
    public static function maximum(): DateTimeImmutable
    {
        $map = Constants::$NEPALI_DATE_MAP;
        $last = $map[count($map) - 1];
        // daysTillNow is a count (1-indexed); the last valid day sits at
        // offset (daysTillNow - 1).
        return self::dateTimeFromMs(Constants::$EPOCH + ($last['daysTillNow'] - 1) * 86400000);
    }

    /**
     * Returns the number of days in the given (or current) month.
     */
    public function daysInMonth(?int $year = null, ?int $month = null): int
    {
        if ($month !== null && ($month < 0 || $month > 11)) {
            throw new InvalidArgumentException('Invalid month index, must be between 0-11');
        }
        $map = $this->getConstants()->dateMap;
        $targetYear = $year ?? $this->year;
        $targetMonth = $month ?? $this->month;
        $yearIndex = $targetYear - $map[0]['year'];
        if ($yearIndex < 0 || $yearIndex >= count($map)) {
            throw new InvalidArgumentException('Year is out of supported range');
        }
        return $map[$yearIndex]['days'][$targetMonth];
    }

    /**
     * Checks if the current year is a leap year in the Nepali calendar.
     */
    public function isLeapYear(): bool
    {
        $map = $this->getConstants()->dateMap;
        $yearIndex = $this->year - $map[0]['year'];
        return $map[$yearIndex]['totalDays'] >= 366;
    }

    /**
     * Calculates the number of weeks in the current month.
     */
    public function getWeeksInMonth(): int
    {
        $firstDay = new static($this->year, $this->month, 1);
        $firstDayOfWeek = $firstDay->getDay();
        $totalDays = $this->daysInMonth();
        return (int) ceil(($firstDayOfWeek + $totalDays) / 7);
    }

    /**
     * Calculates the difference between two dates in the specified unit.
     *
     * @param 'year'|'month'|'day' $unit
     */
    public function diff(self $date, string $unit): int
    {
        switch ($unit) {
            case 'day':
                return self::floorDiv($this->timestampMs - $date->timestampMs, 86400000);

            case 'month':
                $yearDiff = $this->year - $date->year;
                $monthDiff = $this->month - $date->month;
                return $yearDiff * 12 + $monthDiff;

            case 'year':
                return $this->year - $date->year;

            default:
                throw new InvalidArgumentException('Invalid unit for diff calculation');
        }
    }

    /**
     * Returns a new NepaliDate representing the start of the current day (00:00:00).
     */
    public function startOfDay(): self
    {
        $result = $this->clone();
        $offset = $this->getConstants()->utcOffsetMs;
        $nepalMs = $this->timestampMs + $offset;
        $nepalDayStartMs = self::floorDiv($nepalMs, 86400000) * 86400000;
        $result->timestampMs = $nepalDayStartMs - $offset;
        return $result;
    }

    /**
     * Returns a new NepaliDate representing the end of the current day (23:59:59.999).
     */
    public function endOfDay(): self
    {
        $result = $this->clone();
        $offset = $this->getConstants()->utcOffsetMs;
        $nepalMs = $this->timestampMs + $offset;
        $nepalDayStartMs = self::floorDiv($nepalMs, 86400000) * 86400000;
        $result->timestampMs = $nepalDayStartMs + 86400000 - 1 - $offset;
        return $result;
    }

    /**
     * Returns a new NepaliDate representing the start of the week containing this date.
     */
    public function startOfWeek(int $startOfWeek = 0): self
    {
        if ($startOfWeek < 0 || $startOfWeek > 6) {
            throw new InvalidArgumentException('startOfWeek must be an integer between 0 and 6');
        }

        $currentDay = $this->getDay();
        $daysToSubtract = ($currentDay - $startOfWeek + 7) % 7;

        $result = $this->clone()->startOfDay();

        return $result->addDays(-$daysToSubtract);
    }

    /**
     * Returns a new NepaliDate representing the end of the week containing this date.
     */
    public function endOfWeek(int $startOfWeek = 0): self
    {
        if ($startOfWeek < 0 || $startOfWeek > 6) {
            throw new InvalidArgumentException('startOfWeek must be an integer between 0 and 6');
        }

        $weekStart = $this->startOfWeek($startOfWeek);
        $result = $weekStart->addDays(6);

        return $result->endOfDay();
    }

    /**
     * Returns a new NepaliDate representing the start of the current month.
     */
    public function startOfMonth(): self
    {
        return new static($this->year, $this->month, 1);
    }

    /**
     * Returns a new NepaliDate representing the end of the current month.
     */
    public function endOfMonth(): self
    {
        $daysInMonth = $this->daysInMonth();
        return (new static($this->year, $this->month, $daysInMonth))->endOfDay();
    }

    /**
     * Returns a new NepaliDate representing the start of the current year.
     */
    public function startOfYear(): self
    {
        return new static($this->year, 0, 1);
    }

    /**
     * Returns a new NepaliDate representing the end of the current year.
     */
    public function endOfYear(): self
    {
        $map = $this->getConstants()->dateMap;
        $yearIndex = $this->year - $map[0]['year'];
        $daysInChaitra = $map[$yearIndex]['days'][11];
        return (new static($this->year, 11, $daysInChaitra))->endOfDay();
    }

    /**
     * Returns the name of the specified Nepali month.
     */
    public static function getMonthName(int $month, bool $short = false, bool $nepali = false): string
    {
        if ($month < 0 || $month > 11) {
            throw new InvalidArgumentException('Invalid month index, must be between 0-11');
        }

        if ($nepali) {
            return $short ? Constants::$MONTH_SHORT_NP[$month] : Constants::$MONTH_NP[$month];
        }
        return $short ? Constants::$MONTH_SHORT_EN[$month] : Constants::$MONTH_EN[$month];
    }

    /**
     * Returns the name of the specified day of week.
     */
    public static function getDayName(int $day, bool $short = false, bool $nepali = false): string
    {
        if ($day < 0 || $day > 6) {
            throw new InvalidArgumentException('Invalid day index, must be between 0-6');
        }

        if ($nepali) {
            return $short ? Constants::$WEEK_SHORT_NP[$day] : Constants::$WEEK_NP[$day];
        }
        return $short ? Constants::$WEEK_SHORT_EN[$day] : Constants::$WEEK_EN[$day];
    }

    /**
     * Checks if the specified Nepali date is valid.
     */
    public static function isValid(int $year, int $month, int $day): bool
    {
        try {
            $map = Constants::$NEPALI_DATE_MAP;

            if ($year < $map[0]['year'] || $year >= $map[0]['year'] + count($map)) {
                return false;
            }

            if ($month < 0 || $month > 11) {
                return false;
            }

            $yearIndex = $year - $map[0]['year'];
            $daysInMonth = $map[$yearIndex]['days'][$month];

            return $day >= 1 && $day <= $daysInMonth;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Checks if the current NepaliDate instance contains a valid date.
     *
     * Named differently from the static isValid() because PHP does not allow
     * a static and an instance method to share the same name.
     */
    public function isValidInstance(): bool
    {
        try {
            $map = $this->getConstants()->dateMap;
            if ($this->year < $map[0]['year'] || $this->year >= $map[0]['year'] + count($map)) {
                return false;
            }

            if ($this->month < 0 || $this->month > 11) {
                return false;
            }

            $yearIndex = $this->year - $map[0]['year'];
            $daysInMonth = $map[$yearIndex]['days'][$this->month];

            return $this->day >= 1 && $this->day <= $daysInMonth;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Generates calendar days for a given month, including leading/trailing
     * days from adjacent months to fill a 6-week (42-cell) grid.
     *
     * @return array{
     *     prevRemainingDays:int,
     *     prevMonth: array{year:int, month:int, days:int[]},
     *     currentMonth: array{year:int, month:int, days:int[]},
     *     nextMonth: array{year:int, month:int, days:int[]},
     *     remainingDays:int
     * }
     */
    public static function getCalendarDays(int $year, int $month): array
    {
        if ($month < 0 || $month > 11) {
            throw new InvalidArgumentException('Invalid year or month');
        }

        try {
            $firstDay = new static($year, $month, 1);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Invalid year or month');
        }

        $c = $firstDay->getConstants();
        $map = $c->dateMap;
        $mapStartYear = $map[0]['year'];
        $mapCount = count($map);

        $prevMonthMap = ['year' => 0, 'month' => 0, 'days' => []];
        $currentMonthMap = ['year' => $year, 'month' => $month, 'days' => []];
        $nextMonthMap = ['year' => 0, 'month' => 0, 'days' => []];

        $yearIndex = $year - $mapStartYear;

        $firstDayOfWeek = $firstDay->getDay();

        $daysInMonth = $map[$yearIndex]['days'][$month];

        $prevMonth = $month - 1;
        $prevYear = $year;
        $nextMonth = $month + 1;
        $nextYear = $year;

        if ($prevMonth < 0) {
            $prevMonth = 11;
            $prevYear--;
        }

        if ($prevYear >= $mapStartYear) {
            $prevMonthMap['year'] = $prevYear;
            $prevMonthMap['month'] = $prevMonth;
        }

        if ($prevYear >= $mapStartYear && $firstDayOfWeek > 0) {
            $prevMonthIndex = $prevYear - $mapStartYear;
            $daysInPrevMonth = $map[$prevMonthIndex]['days'][$prevMonth];

            for ($i = 0; $i < $firstDayOfWeek; $i++) {
                $prevMonthMap['days'][] = $daysInPrevMonth - $firstDayOfWeek + $i + 1;
            }
        }

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $currentMonthMap['days'][] = $i;
        }

        if ($nextMonth > 11) {
            $nextMonth = 0;
            $nextYear++;
        }

        if ($nextYear < $mapStartYear + $mapCount) {
            $nextMonthMap['year'] = $nextYear;
            $nextMonthMap['month'] = $nextMonth;
        }

        $remainingDays = 42 - $firstDayOfWeek - count($currentMonthMap['days']);

        if ($nextYear < $mapStartYear + $mapCount && $remainingDays > 0) {
            for ($i = 1; $i <= $remainingDays; $i++) {
                $nextMonthMap['days'][] = $i;
            }
        }

        return [
            'prevRemainingDays' => $firstDayOfWeek,
            'prevMonth' => $prevMonthMap,
            'currentMonth' => $currentMonthMap,
            'nextMonth' => $nextMonthMap,
            'remainingDays' => $remainingDays,
        ];
    }

    /**
     * Checks if this date comes after the specified date.
     */
    public function isAfter(self $date): bool
    {
        return $this->timestampMs > $date->timestampMs;
    }

    /**
     * Checks if this date comes before the specified date.
     */
    public function isBefore(self $date): bool
    {
        return $this->timestampMs < $date->timestampMs;
    }

    /**
     * Checks if this date is equal to the specified date.
     */
    public function isEqual(self $date): bool
    {
        return $this->year === $date->year && $this->month === $date->month && $this->day === $date->day;
    }

    /**
     * Checks if this date is the same as the specified date for the given unit.
     *
     * @param 'year'|'month'|'day' $unit
     */
    public function isSame(self $date, string $unit): bool
    {
        switch ($unit) {
            case 'year':
                return $this->year === $date->year;

            case 'month':
                return $this->year === $date->year && $this->month === $date->month;

            case 'day':
                return $this->isEqual($date);

            default:
                throw new InvalidArgumentException('Invalid unit for comparison');
        }
    }

    /**
     * Returns the start and end dates for a specific quarter in the specified year.
     *
     * @return array{start: self, end: self}
     */
    public static function getQuarter(int $quarter, ?int $year = null): array
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException('Quarter must be an integer between 1 and 4');
        }

        $nepaliYear = $year ?? (new static())->getYear();
        $startMonth = ($quarter - 1) * 3;

        $start = new static($nepaliYear, $startMonth, 1);
        $end = (new static($nepaliYear, $startMonth + 2, 1))->endOfMonth();

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Returns the quarter number (1-4) for the current date.
     */
    public function getCurrentQuarter(): int
    {
        return intdiv($this->month, 3) + 1;
    }

    /**
     * Returns all quarters for a specified year.
     *
     * @return array{Q1: array{start:self,end:self}, Q2: array{start:self,end:self}, Q3: array{start:self,end:self}, Q4: array{start:self,end:self}}
     */
    public static function getQuarters(?int $year = null): array
    {
        $nepaliYear = $year ?? (new static())->getYear();

        return [
            'Q1' => static::getQuarter(1, $nepaliYear),
            'Q2' => static::getQuarter(2, $nepaliYear),
            'Q3' => static::getQuarter(3, $nepaliYear),
            'Q4' => static::getQuarter(4, $nepaliYear),
        ];
    }

    /**
     * Returns the current fiscal year based on the current date.
     * The Nepali fiscal year starts from Shrawan 1st (month index 3).
     */
    public static function getCurrentFiscalYear(): int
    {
        $today = new static();
        $year = $today->getYear();
        $month = $today->getMonth();

        return $month < 3 ? $year - 1 : $year;
    }

    /**
     * Returns the start and end dates for a specific fiscal year quarter.
     *
     * @return array{start: self, end: self}
     */
    public static function getFiscalYearQuarter(int $quarter, ?int $fiscalYear = null): array
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException('Quarter must be an integer between 1 and 4');
        }

        $currentFiscalYear = $fiscalYear ?? static::getCurrentFiscalYear();

        $startYear = $currentFiscalYear;
        $startMonth = ($quarter - 1) * 3 + 3;

        if ($quarter === 4) {
            $startYear = $currentFiscalYear + 1;
            $startMonth = 0;
        }

        if ($startMonth > 11) {
            $startYear++;
            $startMonth -= 12;
        }

        $endMonth = $startMonth + 2;
        $endYear = $startYear;

        if ($endMonth > 11) {
            $endYear++;
            $endMonth -= 12;
        }

        $start = new static($startYear, $startMonth, 1);
        $end = (new static($endYear, $endMonth, 1))->endOfMonth();

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Returns the current fiscal year quarter number (1-4) for the current date.
     */
    public function getCurrentFiscalYearQuarter(): int
    {
        $month = $this->getMonth();

        if ($month >= 3 && $month <= 5) {
            return 1;
        }
        if ($month >= 6 && $month <= 8) {
            return 2;
        }
        if ($month >= 9 && $month <= 11) {
            return 3;
        }

        return 4;
    }

    /**
     * Returns the start and end dates of the current fiscal year quarter.
     *
     * @return array{start: self, end: self}
     */
    public function getCurrentFiscalYearQuarterDates(): array
    {
        $currentQuarter = $this->getCurrentFiscalYearQuarter();
        $currentFiscalYear = static::getCurrentFiscalYear();

        return static::getFiscalYearQuarter($currentQuarter, $currentFiscalYear);
    }

    /**
     * Returns all quarters for a specified fiscal year.
     *
     * @return array{Q1: array{start:self,end:self}, Q2: array{start:self,end:self}, Q3: array{start:self,end:self}, Q4: array{start:self,end:self}}
     */
    public static function getFiscalYearQuarters(?int $fiscalYear = null): array
    {
        $year = $fiscalYear ?? static::getCurrentFiscalYear();

        return [
            'Q1' => static::getFiscalYearQuarter(1, $year),
            'Q2' => static::getFiscalYearQuarter(2, $year),
            'Q3' => static::getFiscalYearQuarter(3, $year),
            'Q4' => static::getFiscalYearQuarter(4, $year),
        ];
    }
}
