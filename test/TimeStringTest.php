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

    public function testDateTime() {
        $timeString1 = TimeString::fromString('2020-03-06');

        $exceptionCaught = false;
        try {
            $dateTime = TimeString::createDateTime($timeString1);
        } catch (Exception) {
            $exceptionCaught = true;
        }
        $this->assertFalse($exceptionCaught);

        $this->assertEquals('2020', $dateTime->format('Y'));

    }

    public function testFormat() {
        $timeString1 = TimeString::fromString('2020-03-06');

        $this->assertEquals('2020', TimeString::format($timeString1, 'Y'));
        $this->assertEquals('March', TimeString::format($timeString1, 'F'));
    }

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
            $this->assertEquals(floor($timeStamp * 1000), floor(TimeString::toTimeStamp($timeString, $tz) * 1000));
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