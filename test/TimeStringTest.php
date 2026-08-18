<?php

declare(strict_types=1);

namespace ThomasInstitut\TimeString;

use DateTime;
use Exception;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeString::class)]
final class TimeStringTest extends TestCase
{

    #[Test]
    public function testBadArgumentToComposer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TimeString(-1);
    }


    #[Test]
    public function testEncode(): void
    {

        $timeString1 = new TimeString('2019-12-21 13:45:19.123456');
        $compactTimeString1 = '20191221134519123456';
        $this->assertSame($compactTimeString1, $timeString1->toCompactString());
        $this->assertTrue(TimeString::equals($timeString1, TimeString::fromCompactString($compactTimeString1)));
        $nIterations = 10;

        for ($i = 0; $i < $nIterations; $i++) {
            $now = TimeString::now();
            $compactStringNow = $now->toCompactString();
            $this->assertTrue(TimeString::equals($now, TimeString::fromCompactString($compactStringNow)));
        }
    }

    #[Test]
    public function testCloning(): void
    {
        $timeString1 = new TimeString('2019-12-21 13:45:19.123456');
        $timeString2 = clone $timeString1;
        $this->assertTrue(TimeString::equals($timeString1, $timeString2));
    }

    #[Test]
    public function testConstants(): void
    {
        $this->assertGreaterThan(0, TimeString::cmp(TimeString::endOfTimes(), TimeString::postgreSqlEarliestCeTime()));
        $this->assertFalse(TimeString::equals(TimeString::endOfTimes(), TimeString::postgreSqlEarliestCeTime()));

        $this->assertGreaterThan(0, TimeString::cmp(TimeString::endOfTimes(), TimeString::mySqlEarliestTime()));
        $this->assertFalse(TimeString::equals(TimeString::endOfTimes(), TimeString::mySqlEarliestTime()));

        $this->assertGreaterThan(0, TimeString::cmp(TimeString::mySqlEarliestTime(), TimeString::postgreSqlEarliestCeTime()));
        $this->assertFalse(TimeString::equals(TimeString::mySqlEarliestTime(), TimeString::postgreSqlEarliestCeTime()));

    }

    #[Test]
    #[DataProvider('fromVariableProvider')]
    public function testFromVariable(string|float|int $var, string $expected): void
    {
        $msg = "Test from variable: $var";
        $this->assertSame($expected, TimeString::fromVariable($var)->toString(), $msg);
    }

    /**
     * @return Iterator<string, array{(float | int | string), string}>
     */
    public static function fromVariableProvider(): Iterator
    {
        $nowTimeString = TimeString::fromTimeStamp(time());
        yield 'string' => [$nowTimeString->toString(), $nowTimeString->toString()];
        yield 'float' => [$nowTimeString->toTimeStamp(), $nowTimeString->toString()];
        yield 'integer' => [intval($nowTimeString->toTimeStamp()), $nowTimeString->toString()];
    }

    #[Test]
    public function testDateTime(): void
    {
        $timeString1 = TimeString::fromString('2020-03-06');
        $dateTime = $timeString1->toDateTime();
        $this->assertEquals('2020', $dateTime->format('Y'));
    }

    #[Test]
    #[DataProvider('badTimezoneProvider')]
    public function testBadTimezone(string $tz, bool $valid): void
    {
        $now = time();
        $expectedExceptionCaught = !$valid;
        $exceptionCaught = false;
        try {
            TimeString::fromTimeStamp($now, $tz);
        } catch (Exception) {
            $exceptionCaught = true;
        }
        $this->assertSame($expectedExceptionCaught, $exceptionCaught);
    }

    /**
     * @return Iterator<string, array{string, bool}>
     */
    public static function badTimezoneProvider(): Iterator
    {
        yield 'valid timezone' => ['Europe/London', true];
        yield 'invalid timezone' => ['Bad/Timezone', false];
    }

    #[Test]
    #[DataProvider('constructorProvider')]
    public function testConstructor(string $testString, bool $valid, string $expected): void
    {
        $testMsg = "Testing '$testString'";
        $exceptionCaught = false;
        try {
            $timeString = new TimeString($testString);
            if ($valid) {
                $this->assertSame($expected, $timeString->toString(), $testMsg);
            }
        } catch (Exception) {
            $exceptionCaught = true;
        }
        if (!$valid) {
            $this->assertTrue($exceptionCaught, $testMsg);
        }
    }

    /**
     * @return Iterator<string, array{string, bool, string}>
     */
    public static function constructorProvider(): Iterator
    {
        yield 'empty string' => ['', false, ''];
        yield 'date' => ['1971-01-28', true, '1971-01-28 00:00:00.000000'];
        yield 'date and time' => ['1971-01-28 00:00:00', true, '1971-01-28 00:00:00.000000'];
        yield 'invalid day' => ['1971-01-48 00:00:00.000000', false, ''];
        yield 'invalid month' => ['1971-25-28 00:00:00.000000', false, ''];
        yield 'invalid second' => ['1971-01-28 00:00:85.000000', false, ''];
        yield 'invalid minute' => ['1971-01-28 00:85:00.000000', false, ''];
        yield 'invalid hour' => ['1971-01-28 28:00:00.000000', false, ''];
        yield 'named date' => ['Jan 28, 1971', true, '1971-01-28 00:00:00.000000'];
        yield 'named date and time' => ['Jan 28, 1971 3:00pm', true, '1971-01-28 15:00:00.000000'];
        yield 'long named date' => ['28 January 1971', true, '1971-01-28 00:00:00.000000'];
        yield 'invalid words' => ['cats and dogs', false, ''];
        yield 'misspelled month' => ['28 Yan 1971', false, ''];
    }

    #[Test]
    #[DataProvider('fromStringProvider')]
    public function testFromString(string $testString, bool $valid, string $expectedTimeString): void
    {
        date_default_timezone_set('UTC');
        $testMsg = "Testing input string '$testString'";
        $exceptionCaught = null;
        $exceptionMsg = '';
        try {
            $timeString = TimeString::fromString($testString);
            $this->assertSame($expectedTimeString, $timeString->toString(), $testMsg);
        } catch (Exception $e) {
            $exceptionCaught = $e::class;
            $exceptionMsg = $e->getMessage();
        }
        if ($valid) {
            $this->assertNull($exceptionCaught, "Test String '$testString': exception msg '$exceptionMsg'");
        } else {
            $this->assertNotNull($exceptionCaught);
        }
    }

    /**
     * @return Iterator<string, array{string, bool, string}>
     */
    public static function fromStringProvider(): Iterator
    {
        yield 'empty string' => ['', false, ''];
        yield 'date' => ['1971-01-28', true, '1971-01-28 00:00:00.000000'];
        yield 'date and time' => ['1971-01-28 00:00:00', true, '1971-01-28 00:00:00.000000'];
        yield 'named date' => ['Jan 28, 1971', true, '1971-01-28 00:00:00.000000'];
        yield 'named date and time' => ['Jan 28, 1971 3:00pm', true, '1971-01-28 15:00:00.000000'];
        yield 'long named date' => ['28 January 1971', true, '1971-01-28 00:00:00.000000'];
        yield 'invalid words' => ['cats and dogs', false, ''];
        yield 'misspelled month' => ['28 Yan 1971', false, ''];
    }

    #[Test]
    #[DataProvider('convertTimeZonesProvider')]
    public function testConvertTimeZones(
        string $testTimeString,
        string $testTimeZone,
        string $expectedConvertedTimeString,
        string $newTimeZone
    ): void
    {
        $testMsg = "Testing $testTimeString @ $testTimeZone to $newTimeZone";
        $timeString = TimeString::fromString($testTimeString);
        $convertedTimeString = $timeString->toNewTimeZone($newTimeZone, $testTimeZone);
        $this->assertSame($expectedConvertedTimeString, $convertedTimeString->toString(), $testMsg);
    }

    /**
     * @return Iterator<string, array{string, string, string, string}>
     */
    public static function convertTimeZonesProvider(): Iterator
    {
        yield 'UTC to Berlin' => ['2024-01-22 14:00:00.123456', 'UTC', '2024-01-22 15:00:00.123456', 'Europe/Berlin'];
        yield 'Berlin to UTC' => ['2017-07-28 21:01:58.791319', 'Europe/Berlin', '2017-07-28 19:01:58.791319', 'UTC'];
        yield 'UTC to Costa Rica' => ['2024-01-22 14:32:04.876209', 'UTC', '2024-01-22 08:32:04.876209', 'America/Costa_Rica'];
        yield 'Berlin to Sydney' => ['2024-01-22 16:00:00.664234', 'Europe/Berlin', '2024-01-23 02:00:00.664234', 'Australia/Sydney'];
    }

    #[Test]
    public function testFormat(): void
    {
        $timeString1 = TimeString::fromString('2020-03-06');

        $this->assertEquals('2020', $timeString1->format('Y'));
        $this->assertSame('March', $timeString1->format('F'));
    }

    #[Test]
    #[DataProvider('fromTimestampWithTimezonesProvider')]
    public function testFromTimestampWithTimezones(string $tz): void
    {
        $systemTimeZone = date_default_timezone_get();
        $nowTimestamp = time();
        $systemTimeString = TimeString::fromTimeStamp($nowTimestamp);
        $timeString = new TimeString($nowTimestamp, $tz);
        if ($tz === $systemTimeZone) {
            $this->assertSame($systemTimeString->toString(), $timeString->toString());
        } else {
            $this->assertNotSame($systemTimeString->toString(), $timeString->toString());
        }
    }

    /**
     * @return Iterator<string, array{string}>
     */
    public static function fromTimestampWithTimezonesProvider(): Iterator
    {
        yield 'Berlin' => ['Europe/Berlin'];
        yield 'UTC' => ['UTC'];
        yield 'Costa Rica' => ['America/Costa_Rica'];
    }

    #[Test]
    public function testFromDateTime(): void
    {
        $dt = new DateTime();
        $ts1 = TimeString::fromDateTime($dt);
        $ts2 = new TimeString($dt);
        $ts3 = TimeString::fromVariable($dt);
        $this->assertTrue(TimeString::equals($ts1, $ts2));
        $this->assertTrue(TimeString::equals($ts1, $ts3));
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[DataProvider('toTimeStampProvider')]
    public function testToTimeStamp(string $tz): void
    {
        $timeStamp = microtime(true);
        $timeString = TimeString::fromTimeStamp($timeStamp, $tz);
        $this->assertEquals($timeStamp, $timeString->toTimeStamp($tz));
    }

    /**
     * @return Iterator<string, array{string}>
     */
    public static function toTimeStampProvider(): Iterator
    {
        yield 'Buenos Aires' => ['America/Argentina/Buenos_Aires'];
        yield 'Tokyo' => ['Asia/Tokyo'];
        yield 'UTC' => ['UTC'];
        yield 'Berlin' => ['Europe/Berlin'];
    }

    #[Test]
    #[DataProvider('formatWithTimeZonesProvider')]
    public function testFormatWithTimeZones(string $timeStringTimeZone): void
    {
        $nowTimeString = TimeString::now($timeStringTimeZone);
        $hourUTC = intval($nowTimeString->format('H', $timeStringTimeZone, 'UTC'));
        $hourNonUTC = intval($nowTimeString->format('H', $timeStringTimeZone, '-06:00'));
        $hourDiff = $hourUTC > $hourNonUTC ? $hourUTC - $hourNonUTC : $hourUTC - ($hourNonUTC - 24);
        $this->assertNotSame($hourUTC, $hourNonUTC);
        $this->assertSame(6, $hourDiff);
    }

    /**
     * @return Iterator<string, array{string}>
     */
    public static function formatWithTimeZonesProvider(): Iterator
    {
        yield 'Buenos Aires' => ['America/Argentina/Buenos_Aires'];
        yield 'Tokyo' => ['Asia/Tokyo'];
        yield 'UTC' => ['UTC'];
        yield 'Berlin' => ['Europe/Berlin'];
    }
}
