# thomas-institut/timestring

A TimeString object holds a string with a MySql datetime value with microseconds.
e.g., `'1999-12-31 23:59:50.123456'`.


## Installation

```` composer require thomas-institut/timestring ````

## Usage

The constructor takes a MySql datetime value with microseconds. No timezone
information is stored in the class. 

A TimeString object can be cast into a string or formatted using
PHP's DateTime interface formats 
(see https://www.php.net/manual/en/datetime.format.php)

    $timeString = new TimeString('1999-12-31 23:59:50.123456');

    $timeString->toString() 
    // '1999-12-31 23:59:50.123456'

    strval($timeString)  
    // '1999-12-31 23:59:50.123456'

    $timeString->format('Y') 
    // '1999'

There are also factory methods to create TimeString object in other ways:

    TimeString::now();   // the currrent time in PHP's default timezone
    TimeString::now('Europe/Berlin'); // the current time in Berlin

    TimeString::fromTimestamp(1736341528); // from any int or float timestamp
    TimeString::fromTimestamp(1736341528, 'America/Costa_Rica'); // with time zone
    TimeString::fromString('today'); // from any string that can be parsed by DateTime
    TimeString::fromDateTime($someDateTimeObject);

    // or let TimeString figure it out
    TimeString::fromVariable($someVariable); 

See https://www.php.net/manual/en/datetime.formats.php for the formats accepted
by `TimeString::fromString`

TimeString object can be transformed into other types:

    $timeString->toTimestamp(); // a float timestamp
    $timeString->toDateTime(); // a DateTime object
    $timeString->toDateTime('Asia/Tokyo'); // a DateTime object with a given TZ

It is also possible to create a TimeString in a different time zone as
some other one:

    $newTimestring = $someTimeString->toNewTimeZone($newTimeZone);

TimeString objects can be also created from and converted to 
compact strings with only numbers: 

    $timeString = new TimeString('1999-12-31 23:59:50.123456');

    $timeString->toCompactString(); 
    // '19991231235950123456'
    
    $ts = TimeString::fromCompactString('19991231235950123456');
    $ts->toString(); 
    // '1999-12-31 23:59:50.123456'

The class provides comparison functions:

    $timeString = new TimeString('1999-12-31 23:59:50.123456');
    $anotherTimeString = clone $timeString;
    
    TimeString::equals($timestring, $anotherTimestring); 
    // true

    TimeString::cmp($timestring, $anotherTimestring); 
    // 0

    $laterTimeString = new TimeString('2000-12-31 23:59:50.123456');

    TimeString::equals($timestring, $laterTimeString); 
    // false

    TimeString::cmp($timestring, $laterTimeString); 
    // 1


