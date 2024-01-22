<?php


namespace ThomasInstitut\TimeString;


use Exception;
use PHPUnit\Framework\TestCase;

class TimeStringTest extends TestCase
{

    public function testEncode() {

        $timeString1 = '2019-12-21 13:45:19.123456';
        $compactTimeString1 = '20191221134519123456';
        $this->assertEquals($compactTimeString1, TimeString::compactEncode($timeString1));
        $this->assertEquals($timeString1, TimeString::compactDecode($compactTimeString1));

        $nIterations = 10;

        for($i = 0; $i < $nIterations; $i++) {
            $now = TimeString::now();
            $compactNow = TimeString::compactEncode($now);
            $this->assertEquals($now, TimeString::compactDecode($compactNow));
        }
    }

    /**
     * @throws MalformedStringException
     * @throws InvalidTimeZoneException
     */
    public function testDateTime() {
        $timeString1 = TimeString::fromString('2020-03-06');

        $exceptionCaught = false;
        try {
            $dateTime = TimeString::toDateTime($timeString1);
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
            [ $testString, $timeZone, $valid, $expectedTimeString] = $testCase;
            $testMsg = "Testing TimeString '$testString'";
            $invalidTimeZoneExceptionCaught = false;
            $malformedStringExceptionCaught = false;
            try {
                $timeString = TimeString::fromString($testString);
            } catch (InvalidTimeZoneException) {
                $invalidTimeZoneExceptionCaught = true;
            } catch (MalformedStringException) {
                $malformedStringExceptionCaught = true;
            }
            $this->assertFalse($invalidTimeZoneExceptionCaught, $testMsg);
            $this->assertEquals(!$valid, $malformedStringExceptionCaught, $testMsg);
            if ($valid) {
                $this->assertTrue(isset($timeString), $testMsg);
                $this->assertEquals($expectedTimeString, $timeString, $testMsg);
            }
        }
    }

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
            try {
                $convertedTimeString = TimeString::toNewTimeZone($testTimeString, $newTimeZone, $testTimeZone);
            } catch (InvalidTimeString|InvalidTimeZoneException) {
                $exceptionCaught = true;
            }
            $this->assertFalse($exceptionCaught, $testMsg);
            if (isset($convertedTimeString)) {
                $this->assertEquals($expectedConvertedTimeString, $convertedTimeString, $testMsg);
            }
        }
    }

    /**
     * @throws InvalidTimeZoneException
     * @throws MalformedStringException
     * @throws InvalidTimeString
     */
    public function testFormat() {
        $timeString1 = TimeString::fromString('2020-03-06');

        $this->assertEquals('2020', TimeString::format($timeString1, 'Y'));
        $this->assertEquals('March', TimeString::format($timeString1, 'F'));
    }

    /**
     * @throws InvalidTimeZoneException
     */
    public function testFromTimestampWithTimezones() {
        $systemTimeZone = date_default_timezone_get();

        $timeZones = [
            'Europe/Berlin',
            'UTC',
            'America/Costa_Rica'
            ];
        $now = time();
        $systemTimeString = TimeString::fromTimeStamp($now);
        foreach($timeZones as $tz) {

            $timeString = TimeString::fromTimeStamp($now, $tz);

            if ($tz === $systemTimeZone) {
                $this->assertEquals($systemTimeString, $timeString);
            } else {
                $this->assertNotEquals($systemTimeString, $timeString);
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
            $this->assertEquals($timeStamp, TimeString::toTimeStamp($timeString, $tz));
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
            $hourUTC = intval(TimeString::format($nowTimeString, 'H', $timeStringTimeZone, 'UTC'));
            $hourNonUTC = intval(TimeString::format($nowTimeString, 'H', $timeStringTimeZone, '-06:00'));
            $hourDiff = $hourUTC > $hourNonUTC ? $hourUTC - $hourNonUTC : $hourUTC - ($hourNonUTC - 24);
            $this->assertNotEquals($hourUTC, $hourNonUTC);
            $this->assertEquals(6, $hourDiff);
        }

    }
}