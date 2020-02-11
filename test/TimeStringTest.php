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
}