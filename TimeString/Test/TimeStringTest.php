<?php


namespace ThomasInstitut\TimeString\Test;


use Exception;
use PHPUnit\Framework\TestCase;
use ThomasInstitut\TimeString\Exception\InvalidTimeString;
use ThomasInstitut\TimeString\Exception\InvalidTimeZoneException;
use ThomasInstitut\TimeString\Exception\MalformedStringException;
use ThomasInstitut\TimeString\TimeString;

class TimeStringTest extends TestCase
{

    /**
     * @throws MalformedStringException
     */
    public function testEncode() {

        $timeString1 = new TimeString('2019-12-21 13:45:19.123456');
        $compactTimeString1 = '20191221134519123456';
        $this->assertEquals($compactTimeString1, $timeString1->toCompactString());
        $this->assertTrue(TimeString::equals($timeString1, TimeString::fromCompactString($compactTimeString1)));
        $nIterations = 10;

        for($i = 0; $i < $nIterations; $i++) {
            $now = TimeString::now();
            $compactStringNow = $now->toCompactString();
            $this->assertTrue(TimeString::equals($now, TimeString::fromCompactString($compactStringNow)));
        }
    }

    public function testConstants() {
        $this->assertEquals(TimeString::END_OF_TIMES, (new TimeString(TimeString::END_OF_TIMES))->toString());
        $this->assertEquals(TimeString::TIME_ZERO, (new TimeString(TimeString::TIME_ZERO))->toString());
    }


    /**
     * @throws InvalidTimeString
     * @throws MalformedStringException
     * @throws InvalidTimeZoneException
     */
    public function testFromVariable() {

        $nowTimestamp = time();
        $nowTimeString = TimeString::fromTimeStamp($nowTimestamp);

        $vars = [];
        $vars[] = $nowTimeString->toString();
        $vars[] = $nowTimeString->toTimeStamp();
        $vars[] = intval($nowTimeString->toTimeStamp());

        foreach ($vars as $var) {
            $msg = "Test from variable: $var";
            $this->assertEquals($nowTimeString->toString(), TimeString::fromVariable($var)->toString(), $msg);
        }
    }

    /**
     * @throws MalformedStringException
     */
    public function testDateTime() {
        $timeString1 = TimeString::fromString('2020-03-06');

        $exceptionCaught = false;
        try {
            $dateTime = $timeString1->toDateTime();
        } catch (Exception) {
            $exceptionCaught = true;
        }
        $this->assertFalse($exceptionCaught);
        if (isset($dateTime)) {
            $this->assertEquals('2020', $dateTime->format('Y'));
        }
    }

    public function testFromString() {

        date_default_timezone_set('UTC');
        $testCases = [
            // testString, time zone, valid, expected TimeString
            [ '1971-01-28', '', true, '1971-01-28 00:00:00.000000'],
            [ 'Jan 28, 1971', '', true, '1971-01-28 00:00:00.000000'],
            [ 'Jan 28, 1971 3:00pm', 'America/Costa_Rica', true, '1971-01-28 15:00:00.000000'],
            [ '28 January 1971', '', true, '1971-01-28 00:00:00.000000'],
            [ 'cats and dogs', '',  false, ''],
            [ '28 Yan 1971', '', false, ''],
        ];

        foreach ($testCases as $testCase) {
            [ $testString, , $valid, $expectedTimeString] = $testCase;
            $testMsg = "Testing input string '$testString'";
            $malformedStringExceptionCaught = false;
            try {
                $timeString = TimeString::fromString($testString);
                $this->assertEquals($expectedTimeString, $timeString->toString(), $testMsg);
            } catch (MalformedStringException) {
                $malformedStringExceptionCaught = true;
            }
            $this->assertEquals(!$valid, $malformedStringExceptionCaught, $testMsg);
        }
    }

    /**
     * @throws MalformedStringException
     */
    public function testConvertTimeZones() {

        $testCases = [
            // test TimeString, time zone, converted Time String, new Time zone
            [ '2024-01-22 14:00:00.123456', 'UTC', '2024-01-22 15:00:00.123456',  'Europe/Berlin' ],
            [ '2017-07-28 21:01:58.791319', 'Europe/Berlin', '2017-07-28 19:01:58.791319',  'UTC' ],
            [ '2024-01-22 14:32:04.876209', 'UTC', '2024-01-22 08:32:04.876209',  'America/Costa_Rica' ],
            [ '2024-01-22 16:00:00.664234', 'Europe/Berlin', '2024-01-23 02:00:00.664234',  'Australia/Sydney' ]
            ];

        foreach($testCases as $testCase) {
            [ $testTimeString, $testTimeZone, $expectedConvertedTimeString, $newTimeZone]  = $testCase;
            $testMsg = "Testing $testTimeString @ $testTimeZone to $newTimeZone";
            $exceptionCaught = false;
            $timeString = TimeString::fromString($testTimeString);
            try {
                $convertedTimeString = $timeString->toNewTimeZone($newTimeZone, $testTimeZone);
                $this->assertEquals($expectedConvertedTimeString, $convertedTimeString->toString(), $testMsg);
            } catch (InvalidTimeZoneException) {
                $exceptionCaught = true;
            }
            $this->assertFalse($exceptionCaught, $testMsg);

        }
    }

    /**
     * @throws InvalidTimeZoneException
     * @throws MalformedStringException
     */
    public function testFormat() {
        $timeString1 = TimeString::fromString('2020-03-06');

        $this->assertEquals('2020', $timeString1->format('Y'));
        $this->assertEquals('March', $timeString1->format('F'));
    }

    public function testFromTimestampWithTimezones() {
        $systemTimeZone = date_default_timezone_get();

        $timeZones = [
            'Europe/Berlin',
            'UTC',
            'America/Costa_Rica'
            ];
        $nowTimestamp = time();
        $systemTimeString = TimeString::fromTimeStamp($nowTimestamp);
        foreach($timeZones as $tz) {
            $timeString = TimeString::fromTimeStamp($nowTimestamp, $tz);
            if ($tz === $systemTimeZone) {
                $this->assertEquals($systemTimeString->toString(), $timeString->toString());
            } else {
                $this->assertNotEquals($systemTimeString->toString(), $timeString->toString());
            }
        }
    }

    /**
     * @throws Exception
     */
    public function testToTimeStamp() {
        $timeStamp = microtime(true);
        $testTimeStringTimeZones = [
            'America/Argentina/Buenos_Aires',
            'Asia/Tokyo',
            'UTC',
            'Europe/Berlin'
        ];
        foreach($testTimeStringTimeZones as $tz) {
            $timeString = TimeString::fromTimeStamp($timeStamp, $tz);
            $this->assertEquals($timeStamp, $timeString->toTimeStamp($tz));
        }
    }

    /**
     * @throws Exception
     */
    public function testFormatWithTimeZones() {

        $testTimeStringTimeZones = [
            'America/Argentina/Buenos_Aires',
            'Asia/Tokyo',
            'UTC',
            'Europe/Berlin'
        ];
        foreach($testTimeStringTimeZones as $timeStringTimeZone) {
            $nowTimeString = TimeString::now($timeStringTimeZone);
            $hourUTC = intval($nowTimeString->format( 'H', $timeStringTimeZone, 'UTC'));
            $hourNonUTC = intval($nowTimeString->format('H', $timeStringTimeZone, '-06:00'));
            $hourDiff = $hourUTC > $hourNonUTC ? $hourUTC - $hourNonUTC : $hourUTC - ($hourNonUTC - 24);
            $this->assertNotEquals($hourUTC, $hourNonUTC);
            $this->assertEquals(6, $hourDiff);
        }

    }
}