<?php

/*
 *  Copyright (C) 2020-2025 Universität zu Köln
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace ThomasInstitut\TimeString;

use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;
use ThomasInstitut\TimeString\Exception\InvalidTimeString;
use ThomasInstitut\TimeString\Exception\InvalidTimeZoneException;
use ThomasInstitut\TimeString\Exception\MalformedStringException;

/**
 * Class TimeString
 *
 * A TimeString holds a string with MySql datetime format with microseconds representing a point in
 * time in an undetermined time zone. For example, 2010-10-10 18:21:23.912123.
 *
 *
 * @package TimeString
 */

class TimeString
{

    const Version = '2.0';

    private string $theActualTimeString;
    const MYSQL_DATE_FORMAT  = 'Y-m-d H:i:s';

    const TIME_STRING_FORMAT = 'Y-m-d H:i:s.u';
    const END_OF_TIMES = '9999-12-31 23:59:59.999999';
    const TIME_ZERO = '0000-00-00 00:00:00.000000';


    /**
     * Constructs a TimeString object from a string
     *
     * The string must be a valid MySql datetime value with microseconds, e.g., `2010-10-10 18:21:23.912123`
     *
     * @throws InvalidTimeString
     */
    public function __construct(string $someString)
    {
        if (self::isValid($someString)) {
            $this->theActualTimeString = $someString;
        } else {
            throw new InvalidTimeString("String '$someString' is not a valid time string");
        }
    }

    public function toString() : string {
        return $this->theActualTimeString;
    }

    public function __toString(): string
    {
        return $this->toString();
    }





    /**
     * Returns a string representing the current time at the given time zone with microsecond
     * precision.
     *
     * If the given time zone string is empty, the current PHP default timezone is used.
     *
     * @param string $timeZone
     * @return TimeString
     */
    public static function now(string $timeZone = '') : TimeString
    {
        return self::fromTimeStamp(microtime(true), $timeZone);
    }

    /**
     * Creates a TimeString with the time at the given time zone from a timestamp value.
     *
     * If the given time zone string not given, an empty string or invalid, the current PHP timezone will be used
     *
     * @param float $timeStamp
     * @param string $timeZone
     * @return TimeString
     */
    public static function fromTimeStamp(float $timeStamp, string $timeZone = '') : TimeString
    {
        $intTime =  floor($timeStamp);
        $dt =new DateTime();
        $dt->setTimestamp($intTime);
        if ($timeZone !== '') {
            try {
                $tz = self::getTimeZoneFromString($timeZone);
            } catch (InvalidTimeZoneException) {
                $tz = null;
            }
            if ($tz !== null) {
                $dt->setTimezone($tz);
            }
        }
        $date = $dt->format(self::MYSQL_DATE_FORMAT);
        $microSeconds = (int) round(($timeStamp - $intTime)*1000000);
        try {
            return new TimeString(sprintf("%s.%06d", $date, $microSeconds));
        } catch (InvalidTimeString) {
            // should never happen
            throw new RuntimeException("Invalid time string exception when creating from timestamp");
        }
    }

    /**
     * Creates a TimeString from DateTime object
     *
     * @param DateTime $dt
     * @return TimeString
     */
    public static function fromDateTime(DateTime $dt): TimeString
    {
        try {
            return new TimeString($dt->format(self::TIME_STRING_FORMAT));
        } catch (InvalidTimeString) {
            // should never happen
            throw new RuntimeException("Invalid time string exception when creating from DateTime");
        }
    }

    /**
     * Returns a float time stamp from a TimeString, assuming that the TimeString represents
     * a time at the given time zone.
     *
     * If the time zone is empty or is not given, the current PHP default timezone is used.
     *
     *
     * @param string $timeZone
     * @return float
     * @throws InvalidTimeZoneException
     */
    public function toTimeStamp(string $timeZone = '') : float {
        $dateTime = substr($this->theActualTimeString, 0, 19);
        $microSeconds = substr($this->theActualTimeString, 20);
        $dt = (new TimeString("$dateTime.000000"))->toDateTime($timeZone);
        return floatval($dt->format('U') . ".$microSeconds");
    }

    /**
     * Returns a valid timeString if the variable can be converted to a time.
     *
     * The given timeZone will be used to generate the TimeString if the input variable
     * is numeric (i.e., a timestamp). It will be ignored if the input variable is
     * a string.
     *
     * @param float|int|string|DateTime $timeVar
     * @param string $timeZone
     * @return TimeString
     * @throws InvalidTimeString
     * @throws MalformedStringException
     */
    public static function fromVariable(float|int|string|DateTime $timeVar, string $timeZone = '') : TimeString
    {
        if (is_numeric($timeVar)) {
            return self::fromTimeStamp((float) $timeVar, $timeZone);
        }
        if (is_string($timeVar)) {
            return  self::fromString($timeVar);
        }
        if (is_a($timeVar, DateTime::class)) {
            return self::fromDateTime($timeVar);
        }
        throw new InvalidTimeString("TimeString cannot be created from '$timeVar'");
    }


    /**
     * Returns a TimeString from an input string, which can be any valid MySql datetime value with
     * or without microseconds or any string that can be parsed into a DateTime object
     *
     * Documentation on valid formats can be found at https://www.php.net/manual/en/datetime.formats.php
     *
     * @param string $str
     * @return TimeString
     * @throws MalformedStringException
     */
    public static function fromString(string $str): TimeString
    {
        // first, add missing time and microseconds if the string is only a date
        if (preg_match('/^\d\d\d\d-\d\d-\d\d$/', $str)) {
            $str .= ' 00:00:00.000000';
        } else {
            if (preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $str)){
                $str .= '.000000';
            }
        }
        if (self::isValid($str)) {
            // the string is already a valid time string, so just use it as is
            try {
                return new TimeString($str);
            } catch (InvalidTimeString) {
                // should never happen
                throw new RuntimeException("Invalid time string exception when creating from from string");
            }
        }
        try {
            $dt = new DateTime($str);
        } catch (Exception) { // We can't use DateTimeMalformed string exception until PHP 8.3
            throw new MalformedStringException("String $str cannot be parsed to a DateTime");
        }
        return self::fromDateTime($dt);
    }

    /**
     * @throws InvalidTimeZoneException
     */
    private static function getTimeZoneFromString(string $timeZone) : ?DateTimeZone {
        if ($timeZone !== '') {
            $tz = timezone_open($timeZone);
            if ($tz === false) {
                throw new InvalidTimeZoneException();
            }
        } else {
            $tz = null;
        }
        return $tz;
    }

    /**
     * Returns true if the given string can be used to create a TimeString object
     *
     * @param string $str
     * @return bool
     */
    public static function isValid(string $str) : bool {
        if ($str === '') {
            return false;
        }
        $matches = [];
        if (preg_match('/^\d\d\d\d-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)\.\d\d\d\d\d\d$/', $str, $matches) !== 1) {
            return false;
        }
        if (intval($matches[1]) > 12) {
            return false;
        }
        if (intval($matches[2]) > 31) {
            return false;
        }
        if (intval($matches[3]) > 23) {
            return false;
        }
        if (intval($matches[4]) > 59) {
            return false;
        }
        if (intval($matches[5]) > 59) {
            return false;
        }
        return true;
    }

    /**
     * Encodes a timeString into a compact representation containing only numbers
     *
     * @return string
     */
    public function toCompactString() : string {

        $parts = [];
        preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)\.(\d\d\d\d\d\d)$/', $this->theActualTimeString, $parts);

        return implode('', array_slice($parts,1));
    }

    /**
     * Decodes a compact representation of a timeString into a string
     *
     * @param string $compactTimeString
     * @return TimeString
     * @throws MalformedStringException
     */
    public static function fromCompactString(string $compactTimeString) : TimeString  {
        $parts = [];
        preg_match('/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d\d\d\d\d)$/', $compactTimeString, $parts);

        $theString = implode('', [
            $parts[1] ?? 'XX',
            '-',
            $parts[2] ?? 'XX',
            '-',
            $parts[3] ?? 'XX',
            ' ',
            $parts[4] ?? 'XX',
            ':',
            $parts[5] ?? 'XX',
            ':',
            $parts[6] ?? 'XX',
            '.',
            $parts[7] ?? 'YYY',
            ]);
        return self::fromString($theString);
    }

    /**
     * Creates a DateTime object from a timeString with the given time zone.
     *
     * If the given time zone string is empty, the current PHP default timezone is used.
     *
     * @param string $timeZone
     * @return DateTime
     * @throws InvalidTimeZoneException
     */
    public function toDateTime(string $timeZone = '') : DateTime {
        $dateTimeZone = self::getTimeZoneFromString($timeZone);
        $dt = DateTime::createFromFormat("Y-m-d H:i:s.u", $this->theActualTimeString, $dateTimeZone);
        if ($dt === false) {
            throw new RuntimeException("Invalid time string exception when creating DateTime object");
        }
        return $dt;
    }

    /**
     * Returns a formatted date/time from the given TimeString using the formats defined
     * in PHP's DateTime interface (https://www.php.net/manual/en/datetime.format.php)
     *
     * The time zone of the TimeString is given with $timeStringTimeZone. If it is an empty string
     * it is assumed that the TimeString represents a time in PHP's default time zone.
     *
     * If a formatTimeZone is given, the returned string will be a time in that timeZone. If it is
     * an empty string, the returned string will have the same time zone as the TimeString.
     *
     * @param string $format
     * @param string $timeStringTimezone
     * @param string $formatTimeZone
     * @return string
     * @throws InvalidTimeZoneException
     */
    public function format(string $format, string $timeStringTimezone = '', string $formatTimeZone = '') : string {
        try {
            $formatDateTimeZone = self::getTimeZoneFromString($formatTimeZone) ?? new DateTimeZone(date_default_timezone_get());
        } catch (Exception) {
            // This can only happen if date_default_timezone_get returns an invalid time zone
            // which means that PHP went crazy
            throw new InvalidTimeZoneException("Invalid default time zone");
        }
        $dateTime = $this->toDateTime($timeStringTimezone);
        if ($timeStringTimezone !== $formatTimeZone) {
            $dateTime->setTimezone($formatDateTimeZone);
        }
        return $dateTime->format($format);
    }

    /**
     * Returns a new TimeString in a different time zone.
     *
     * @param string $newTimeZone the new time zone
     * @param string $timeStringTimezone if empty, the time string is assumed to be in PHP's default timezone
     * @return TimeString
     * @throws InvalidTimeZoneException
     */
    public function toNewTimeZone(string $newTimeZone, string $timeStringTimezone = '') : TimeString
    {
        try {
            return new TimeString($this->format(self::TIME_STRING_FORMAT, $timeStringTimezone, $newTimeZone));
        } catch (InvalidTimeString) {
            // should never happen
            throw new RuntimeException("Invalid time string exception when creating from from string");
        }
    }

    public static function cmp(TimeString $timeString1, TimeString $timeString2) : int {
        return strcmp($timeString1, $timeString2);
    }
    public static function equals(TimeString $timeString1, TimeString $timeString2) : bool
    {
        return self::cmp($timeString1, $timeString2) === 0;
    }

}