<?php

declare(strict_types=1);

namespace NepaliDateLibrary\Helper;

use NepaliDateLibrary\NepaliDate;

/**
 * Formats a NepaliDate using a Moment.js-style token format string.
 *
 * Mirrors NodeJS/src/Helper/DateFormatter.ts exactly (same tokens, sizes, and
 * quoting rules for literal text).
 */
final class DateFormatter
{
    /**
     * Pads a number with a leading zero if it's less than 10.
     */
    private static function pad(int $n): string
    {
        return $n < 10 ? "0{$n}" : (string) $n;
    }

    /**
     * Converts a string of ASCII digits into Nepali digits.
     * Example: "123" -> "१२३"
     */
    private static function npDigit(string $str): string
    {
        $res = '';
        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $res .= Constants::$NUMBER_NP[ord($str[$i]) - 48];
        }
        return $res;
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function yearEn(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            $year = (string) $date->getYear();
            if ($size <= 2) {
                return substr($year, 2);
            }
            if ($size === 3) {
                return substr($year, 1);
            }
            return $year;
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function yearNp(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            $year = (string) $date->getYear();
            if ($size <= 2) {
                return self::npDigit(substr($year, 2));
            }
            if ($size === 3) {
                return self::npDigit(substr($year, 1));
            }
            return self::npDigit($year);
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function monthEn(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            if ($size === 1) {
                return (string) ($date->getMonth() + 1);
            }
            if ($size === 2) {
                return self::pad($date->getMonth() + 1);
            }
            if ($size === 3) {
                return Constants::$MONTH_SHORT_EN[$date->getMonth()];
            }
            return Constants::$MONTH_EN[$date->getMonth()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function monthNp(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            if ($size === 1) {
                return self::npDigit((string) ($date->getMonth() + 1));
            }
            if ($size === 2) {
                return self::npDigit(self::pad($date->getMonth() + 1));
            }
            if ($size === 3) {
                return Constants::$MONTH_SHORT_NP[$date->getMonth()];
            }
            return Constants::$MONTH_NP[$date->getMonth()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function dateEn(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            if ($size === 1) {
                return (string) $date->getDate();
            }
            if ($size === 2) {
                return self::pad($date->getDate());
            }
            if ($size === 3) {
                return Constants::$WEEK_SHORT_EN[$date->getDay()];
            }
            return Constants::$WEEK_EN[$date->getDay()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function dateNp(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            if ($size === 1) {
                return self::npDigit((string) $date->getDate());
            }
            if ($size === 2) {
                return self::npDigit(self::pad($date->getDate()));
            }
            if ($size === 3) {
                return Constants::$WEEK_SHORT_NP[$date->getDay()];
            }
            return Constants::$WEEK_NP[$date->getDay()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function pass(string $seq): callable
    {
        return static fn (NepaliDate $date): string => $seq;
    }

    private static function isSpecial(string $ch): bool
    {
        return in_array($ch, ['Y', 'y', 'M', 'm', 'D', 'd'], true);
    }

    /**
     * @param non-empty-string $ch
     * @return callable(NepaliDate):string
     */
    private static function formatterFor(string $ch, int $size): callable
    {
        return match ($ch) {
            'Y' => self::yearEn($size),
            'y' => self::yearNp($size),
            'M' => self::monthEn($size),
            'm' => self::monthNp($size),
            'D' => self::dateEn($size),
            'd' => self::dateNp($size),
        };
    }

    /**
     * Tokenizes a format string into an array of formatter callables.
     *
     * @return array<int, callable(NepaliDate):string>
     */
    private static function tokenize(string $formatStr): array
    {
        $inQuote = false;
        $seq = '';
        $special = '';
        $specialSize = 0;

        $tokens = [];

        $chars = mb_str_split($formatStr, 1, 'UTF-8');

        foreach ($chars as $ch) {
            if ($ch === $special) {
                $specialSize++;
                continue;
            }

            if ($special !== '') {
                $tokens[] = self::formatterFor($special, $specialSize);
                $special = '';
                $specialSize = 0;
            }

            if ($ch === '"') {
                $inQuote = !$inQuote;
                continue;
            }

            if (!self::isSpecial($ch) || $inQuote) {
                $seq .= $ch;
            } else {
                if ($seq !== '') {
                    $tokens[] = self::pass($seq);
                    $seq = '';
                }

                $special = $ch;
                $specialSize = 1;
            }
        }

        if ($seq !== '') {
            $tokens[] = self::pass($seq);
        } elseif ($special !== '') {
            $tokens[] = self::formatterFor($special, $specialSize);
        }

        return $tokens;
    }

    /**
     * Formats a NepaliDate using the specified format string.
     */
    public static function format(NepaliDate $nepaliDate, string $formatStr): string
    {
        $result = '';
        foreach (self::tokenize($formatStr) as $token) {
            $result .= $token($nepaliDate);
        }
        return $result;
    }
}
