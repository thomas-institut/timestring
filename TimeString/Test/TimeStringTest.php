<?php


namespace ThomasInstitut\TimeString\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use ThomasInstitut\TimeString\TimeString;

class TimeStringTest extends TestCase
{


    public function testEncode()
    {

        $timeString1 = new TimeString('2019-12-21 13:45:19.123456');
        $compactTimeString1 = '20191221134519123456';
        $this->assertEquals($compactTimeString1, $timeString1->toCompactString());
        $this->assertTrue(TimeString::equals($timeString1, TimeString::fromCompactString($compactTimeString1)));
        $nIterations = 10;

        for ($i = 0; $i < $nIterations; $i++) {
            $now = TimeString::now();
            $compactStringNow = $now->toCompactString();
            $this->assertTrue(TimeString::equals($now, TimeString::fromCompactString($compactStringNow)));
        }
    }

    public function testCloning()
    {
        $timeString1 = new TimeString('2019-12-21 13:45:19.123456');
        $timeString2 = clone $timeString1;
        $this->assertTrue(TimeString::equals($timeString1, $timeString2));
    }

    public function testConstants()
    {
        $this->assertEquals(
            TimeString::endOfTimes()->toString(),
            (new TimeString(TimeString::END_OF_TIMES))->toString()
        );
        $this->assertEquals(
            TimeString::zero()->toString(),
            (new TimeString(TimeString::TIME_ZERO))->toString()
        );
    }


    public function testFromVariable()
    {

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

    public function testDateTime()
    {
        $timeString1 = TimeString::fromString('2020-03-06');
        $dateTime = $timeString1->toDateTime();
        $this->assertEquals('2020', $dateTime->format('Y'));
    }

    public function testBadTimezone()
    {

        $testCases = [
            // timezone, valid
            [ 'Europe/London', true ],
            [ 'Bad/Timezone', false]
        ];
        $now = time();
        foreach ($testCases as $testCase) {
            [ $tz, $valid] = $testCase;
            $expectedExceptionCaught = !$valid;
            $exceptionCaught = false;
            try {
                TimeString::fromTimeStamp($now, $tz);
            } catch (Exception) {
                $exceptionCaught = true;
            }
            $this->assertEquals($expectedExceptionCaught, $exceptionCaught);
        }
    }

    public function testConstructor()
    {
        $testCases = [
            // testString, valid, expected
            [ '',  false, ''],
            [ '1971-01-28', true, '1971-01-28 00:00:00.000000'],
            [ '1971-01-28 00:00:00', true, '1971-01-28 00:00:00.000000'],
            [ 'Jan 28, 1971', true, '1971-01-28 00:00:00.000000'],
            [ 'Jan 28, 1971 3:00pm', true, '1971-01-28 15:00:00.000000'],
            [ '28 January 1971', true, '1971-01-28 00:00:00.000000'],
            [ 'cats and dogs', false, ''],
            [ '28 Yan 1971', false, ''],
        ];

        foreach ($testCases as $testCase) {
            [ $testString, $valid, $expected ] = $testCase;
            $testMsg = "Testing input string '$testString'";
            $exceptionCaught = false;
            try {
                $timeString = new TimeString($testString);
                if ($valid) {
                    $this->assertEquals($expected, $timeString->toString(), $testMsg);
                }
            } catch (Exception) {
                $exceptionCaught = true;
            }
            if (!$valid) {
                $this->assertTrue($exceptionCaught, $testMsg);
            }
        }
    }

    public function testFromString()
    {

        date_default_timezone_set('UTC');
        $testCases = [
            // testString, time zone, valid, expected TimeString
            [ '', '', false, ''],
            [ '1971-01-28', '', true, '1971-01-28 00:00:00.000000'],
            [ '1971-01-28 00:00:00', '', true, '1971-01-28 00:00:00.000000'],
            [ 'Jan 28, 1971', '', true, '1971-01-28 00:00:00.000000'],
            [ 'Jan 28, 1971 3:00pm', 'America/Costa_Rica', true, '1971-01-28 15:00:00.000000'],
            [ '28 January 1971', '', true, '1971-01-28 00:00:00.000000'],
            [ 'cats and dogs', '',  false, ''],
            [ '28 Yan 1971', '', false, ''],
        ];

        foreach ($testCases as $testCase) {
            [ $testString, , $valid, $expectedTimeString] = $testCase;
            $testMsg = "Testing input string '$testString'";
            $exceptionCaught = null;
            $exceptionMsg = '';
            try {
                $timeString = TimeString::fromString($testString);
                $this->assertEquals($expectedTimeString, $timeString->toString(), $testMsg);
            } catch (Exception $e) {
                $exceptionCaught = get_class($e);
                $exceptionMsg = $e->getMessage();
            }
            if ($valid) {
                $this->assertNull($exceptionCaught, "Test String '$testString': exception msg '$exceptionMsg'");
            } else {
                $this->assertNotNull($exceptionCaught);
            }
        }
    }

    public function testConvertTimeZones()
    {

        $testCases = [
            // test TimeString, time zone, converted Time String, new Time zone
            [ '2024-01-22 14:00:00.123456', 'UTC', '2024-01-22 15:00:00.123456',  'Europe/Berlin' ],
            [ '2017-07-28 21:01:58.791319', 'Europe/Berlin', '2017-07-28 19:01:58.791319',  'UTC' ],
            [ '2024-01-22 14:32:04.876209', 'UTC', '2024-01-22 08:32:04.876209',  'America/Costa_Rica' ],
            [ '2024-01-22 16:00:00.664234', 'Europe/Berlin', '2024-01-23 02:00:00.664234',  'Australia/Sydney' ]
            ];

        foreach ($testCases as $testCase) {
            [ $testTimeString, $testTimeZone, $expectedConvertedTimeString, $newTimeZone]  = $testCase;
            $testMsg = "Testing $testTimeString @ $testTimeZone to $newTimeZone";
            $timeString = TimeString::fromString($testTimeString);
            $convertedTimeString = $timeString->toNewTimeZone($newTimeZone, $testTimeZone);
            $this->assertEquals($expectedConvertedTimeString, $convertedTimeString->toString(), $testMsg);
        }
    }

    public function testFormat()
    {
        $timeString1 = TimeString::fromString('2020-03-06');

        $this->assertEquals('2020', $timeString1->format('Y'));
        $this->assertEquals('March', $timeString1->format('F'));
    }

    public function testFromTimestampWithTimezones()
    {
        $systemTimeZone = date_default_timezone_get();

        $timeZones = [
            'Europe/Berlin',
            'UTC',
            'America/Costa_Rica'
            ];
        $nowTimestamp = time();
        $systemTimeString = TimeString::fromTimeStamp($nowTimestamp);
        foreach ($timeZones as $tz) {
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
    public function testToTimeStamp()
    {
        $timeStamp = microtime(true);
        $testTimeStringTimeZones = [
            'America/Argentina/Buenos_Aires',
            'Asia/Tokyo',
            'UTC',
            'Europe/Berlin'
        ];
        foreach ($testTimeStringTimeZones as $tz) {
            $timeString = TimeString::fromTimeStamp($timeStamp, $tz);
            $this->assertEquals($timeStamp, $timeString->toTimeStamp($tz));
        }
    }

    public function testFormatWithTimeZones()
    {

        $testTimeStringTimeZones = [
            'America/Argentina/Buenos_Aires',
            'Asia/Tokyo',
            'UTC',
            'Europe/Berlin'
        ];
        foreach ($testTimeStringTimeZones as $timeStringTimeZone) {
            $nowTimeString = TimeString::now($timeStringTimeZone);
            $hourUTC = intval($nowTimeString->format('H', $timeStringTimeZone, 'UTC'));
            $hourNonUTC = intval($nowTimeString->format('H', $timeStringTimeZone, '-06:00'));
            $hourDiff = $hourUTC > $hourNonUTC ? $hourUTC - $hourNonUTC : $hourUTC - ($hourNonUTC - 24);
            $this->assertNotEquals($hourUTC, $hourNonUTC);
            $this->assertEquals(6, $hourDiff);
        }
    }
}
