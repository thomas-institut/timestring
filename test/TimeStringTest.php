<?php


namespace ThomasInstitut\TimeString;


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
        } catch (\Exception $e) {
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
}