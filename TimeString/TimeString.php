<?php

/*
 *  Copyright (C) 2020 Universität zu Köln
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

/**
 * Class TimeString
 *
 * A TimeString is a string with MySql datetime format with microseconds. For example, 2010-10-10 18:21:23.912123
 * It does not hold time zone information in itself.
 *
 *
 * @package TimeString
 */

class TimeString
{

    const MYSQL_DATE_FORMAT  = 'Y-m-d H:i:s';
    const END_OF_TIMES = '9999-12-31 23:59:59.999999';
    const TIME_ZERO = '0000-00-00 00:00:00.000000';

    /**
     * Returns a TimeString representing the current time at the given time zone with microsecond
     * precision.
     *
     * If the given time zone string is empty, the current PHP default timezone is used.
     *
     * @param string $timeZone
     * @return string
     */
    public static function now(string $timeZone = '') : string
    {
        return self::fromTimeStamp(microtime(true), $timeZone);
    }

    /**
     * Creates a TimeString with the time at the given time zone from a timestamp value.
     *
     * If the given time zone string is empty, the current PHP default timezone is used.
     *
     * @param float $timeStamp
     * @param string $timeZone
     * @return string
     */
    public static function fromTimeStamp(float $timeStamp, string $timeZone = '') : string
    {
        if ($timeZone !== '') {
            $currentDefaultTimeZone = date_default_timezone_get();
            if ($timeZone !== $currentDefaultTimeZone) {
                date_default_timezone_set($timeZone);
            }
        }
        $intTime =  floor($timeStamp);
        $date=date(self::MYSQL_DATE_FORMAT, $intTime);
        $microSeconds = (int) floor(($timeStamp - $intTime)*1000000);
        if ($timeZone !== '') {
            if ($timeZone !== $currentDefaultTimeZone) {
                date_default_timezone_set($currentDefaultTimeZone);
            }
        }
        return sprintf("%s.%06d", $date, $microSeconds);
    }

    /**
     * Returns a float time stamp from a timeString.
     *
     * If the given time zone string is empty, it is assumed that the TimeString is a time at
     * PHP's default time zone.
     *
     * @param string $timeString
     * @param string $timeZone
     * @return float
     * @throws Exception
     */
    public static function toTimeStamp(string $timeString, string $timeZone = '') : float {
        $dt = self::createDateTime($timeString, $timeZone);
        return intval($dt->format('Uu')) / 1000000.0;
    }

    /**
     * Returns a valid timeString if the variable can be converted to a time.
     *
     * If not, returns an empty string.
     *
     * The given timeZone will be used to generate the TimeString if the input variable
     * is numeric (i.e., a timestamp). It will be ignored if the input variable is
     * a string.
     *
     * @param float|int|string $timeVar
     * @param string $timeZone
     * @return string
     */
    public static function fromVariable(float|int|string $timeVar, string $timeZone = '') : string
    {
        if (is_numeric($timeVar)) {
            return self::fromTimeStamp((float) $timeVar, $timeZone);
        }
        if (is_string($timeVar)) {
            return  self::fromString($timeVar);
        }
        return '';
    }


    /**
     * Returns a TimeString from an input string, which can
     * be a date only, a date and a time without microseconds.
     *
     * Returns an empty string is the input string cannot be converted to
     * a TimeString
     *
     * @param string $str
     * @return string
     */
    public static function fromString(string $str): string
    {
        if (preg_match('/^\d\d\d\d-\d\d-\d\d$/', $str)) {
            $str .= ' 00:00:00.000000';
        } else {
            if (preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $str)){
                $str .= '.000000';
            }
        }
        if (!self::isValid($str)) {
            return '';
        }
        return $str;
    }

    /**
     * Returns true if the given string is a valid timeString
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
     * @param string $timeString
     * @return string
     */
    public static function compactEncode(string $timeString) : string {

        $parts = [];
        preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d):(\d\d)\.(\d\d\d\d\d\d)$/', $timeString, $parts);

        return implode('', array_slice($parts,1));
    }

    /**
     * Decodes a compact representation of a timeString into a normal timeString
     *
     * @param string $compactTimeString
     * @return string
     */
    public static function compactDecode(string $compactTimeString) : string  {
        $parts = [];
        preg_match('/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d\d\d\d\d)$/', $compactTimeString, $parts);

        return $parts[1] . '-' . $parts[2] . '-' . $parts[3] . ' ' . $parts[4] . ':' . $parts[5] . ':' . $parts[6] . '.' . $parts[7];
    }

    /**
     * Creates a DateTime object from a timeString with the given time zone.
     *
     * If the given time zone string is empty, the current PHP default timezone is used.
     *
     * @param string $timeString
     * @param string $timeZone
     * @return DateTime
     * @throws Exception
     */
    public static function createDateTime(string $timeString, string $timeZone = '') : DateTime {
        $dateTimeZone =  $timeZone !== '' ? new DateTimeZone($timeZone) : null;
        return DateTime::createFromFormat("Y-m-d H:i:s.u", $timeString, $dateTimeZone);
    }

    /**
     * Returns a formatted date/time from the given TimeString.
     *
     * The time zone of the TimeString is given with $timeStringTimeZone. If it is an empty string
     * it is assumed that the TimeString represents a time in PHP's default time zone.
     *
     * If a formatTimeZone is given, the returned string will be a time in that timeZone. If it is
     * an empty string, the returned string will have the same time zone as the TimeString.
     *
     * @param string $timeString
     * @param string $format
     * @param string $timeStringTimezone
     * @param string $formatTimeZone
     * @return string
     * @throws Exception
     */
    public static function format(string $timeString, string $format, string $timeStringTimezone = '', string $formatTimeZone = '') : string {
        $formatDateTimeZone = $formatTimeZone !== '' ? new DateTimeZone($formatTimeZone) : new DateTimeZone(date_default_timezone_get());
        try {
            $dateTime = self::createDateTime($timeString, $timeStringTimezone);
        } catch (Exception) {
            return '';
        }
        if ($timeStringTimezone !== $formatTimeZone) {
            $dateTime->setTimezone($formatDateTimeZone);
        }

        return $dateTime->format($format);
    }



}