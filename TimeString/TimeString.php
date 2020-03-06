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
use Exception;

/**
 * Class TimeString
 *
 * Static methods to deal with strings that have a MySql datetime format with microseconds,
 * e.g. 2010-10-10 18:21:23.912123
 *
 * @package TimeString
 */

class TimeString
{

    const MYSQL_DATE_FORMAT  = 'Y-m-d H:i:s';
    const END_OF_TIMES = '9999-12-31 23:59:59.999999';
    const TIME_ZERO = '0000-00-00 00:00:00.000000';

    /**
     * Returns the current time in MySQL format with microsecond precision
     *
     * @return string
     */
    public static function now() : string
    {
        return self::fromTimeStamp(microtime(true));
    }

    /**
     * @param float $timeStamp
     * @return string
     */
    public static function fromTimeStamp(float $timeStamp) : string
    {
        $intTime =  floor($timeStamp);
        $date=date(self::MYSQL_DATE_FORMAT, $intTime);
        $microSeconds = (int) floor(($timeStamp - $intTime)*1000000);
        return sprintf("%s.%06d", $date, $microSeconds);
    }

    /**
     * Returns a valid timeString if the variable can be converted to a time
     * If not, returns an empty string (which will be immediately recognized as
     * invalid by isTimeStringValid
     *
     * @param float|int|string $timeVar
     * @return string
     */
    public static function fromVariable($timeVar) : string
    {
        if (is_numeric($timeVar)) {
            return self::fromTimeStamp((float) $timeVar);
        }
        if (is_string($timeVar)) {
            return  self::fromString($timeVar);
        }
        return '';
    }


    public static function fromString(string $str) {
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
     * Creates a DateTime object from a timeString
     *
     * @param string $timeString
     * @return DateTime
     * @throws Exception
     */
    public static function createDateTime(string $timeString) : DateTime {
        return new DateTime($timeString);
    }

    /**
     *
     * @param string $timeString
     * @param string $format
     * @return string
     */
    public static function format(string $timeString, string $format) : string {

        try {
            $dateTime = self::createDateTime($timeString);
        } catch (Exception $e) {
            return '????';
        }
        return $dateTime->format($format);
    }

}