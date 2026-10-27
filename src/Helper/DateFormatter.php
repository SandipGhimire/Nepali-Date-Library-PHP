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
    private static function npDigit(string $str, ?array $digits = null): string
    {
        $chars = $digits ?? Constants::$NUMBER_NP;
        $res = '';
        $length = strlen($str);
        for ($i = 0; $i < $length; $i++) {
            $res .= $chars[ord($str[$i]) - 48];
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
            $digits = $date->getConstants()->numberNp;
            if ($size <= 2) {
                return self::npDigit(substr($year, 2), $digits);
            }
            if ($size === 3) {
                return self::npDigit(substr($year, 1), $digits);
            }
            return self::npDigit($year, $digits);
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
            $c = $date->getConstants();
            if ($size === 3) {
                return $c->monthShortEn[$date->getMonth()];
            }
            return $c->monthEn[$date->getMonth()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function monthNp(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            $c = $date->getConstants();
            if ($size === 1) {
                return self::npDigit((string) ($date->getMonth() + 1), $c->numberNp);
            }
            if ($size === 2) {
                return self::npDigit(self::pad($date->getMonth() + 1), $c->numberNp);
            }
            if ($size === 3) {
                return $c->monthShortNp[$date->getMonth()];
            }
            return $c->monthNp[$date->getMonth()];
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
            $c = $date->getConstants();
            if ($size === 3) {
                return $c->weekShortEn[$date->getDay()];
            }
            return $c->weekEn[$date->getDay()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function dateNp(int $size): callable
    {
        return static function (NepaliDate $date) use ($size): string {
            $c = $date->getConstants();
            if ($size === 1) {
                return self::npDigit((string) $date->getDate(), $c->numberNp);
            }
            if ($size === 2) {
                return self::npDigit(self::pad($date->getDate()), $c->numberNp);
            }
            if ($size === 3) {
                return $c->weekShortNp[$date->getDay()];
            }
            return $c->weekNp[$date->getDay()];
        };
    }

    /**
     * @return callable(NepaliDate):string
     */
    private static function pass(string $seq): callable
    {
        return static function (NepaliDate $date) use ($seq): string {
            return $seq;
        };
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
        switch ($ch) {
            case 'Y':
                return self::yearEn($size);
            case 'y':
                return self::yearNp($size);
            case 'M':
                return self::monthEn($size);
            case 'm':
                return self::monthNp($size);
            case 'D':
                return self::dateEn($size);
            case 'd':
                return self::dateNp($size);
        }

        throw new \UnexpectedValueException("Unhandled format character '{$ch}'");
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

        $chars = preg_split('//u', $formatStr, -1, PREG_SPLIT_NO_EMPTY);

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
