# thomas-institut/timestring

![Latest Stable Version](https://img.shields.io/packagist/v/thomas-institut/timestring?label=Stable)
![GitHub License](https://img.shields.io/github/license/thomas-institut/timestring)

![Dynamic JSON Badge](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fthomas-institut%2Ftimestring%2Frefs%2Fheads%2Fmaster%2Fcomposer.json&query=%24.require.php&label=PHP%20Version)

A TimeString is an immutable object that holds a string with a MySql datetime value with microseconds, 
e.g., `'1999-12-31 23:59:50.123456'`. No timezone information is stored in the class.


## Installation

```` composer require thomas-institut/timestring ````

## Usage

### Constructor
TimeString objects can be constructed from strings, float or integer timestamps, 
or DateTime objects. 

Strings given to the constructor can be partial or complete MySQL datetime values or any
format accepted by PHP's DateTime class.

```php
$ts = new TimeString('1999-12-31 23:59:50.123456');
// inner value is '1999-12-31 23:59:50.123456'

$ts = new TimeString('1999-12-31 23:59:50');
// inner value is '1999-12-31 23:59:50.000000'

$ts = new TimeString('1999-12-31');
// inner value is '1999-12-31 00:00:00.000000'

$ts = new TimeString('December 31, 1999');
// inner value is '1999-12-31 00:00:00.000000'
```

Floats and integers are understood as Unix timestamps. An additional parameter
can be given to specify the timezone:

```php
date_default_timezone_set('UTC');

$ts = new TimeString(1745307389); 
// inner value is '2025-04-22 07:36:29.000000

$ts = new TimeString(1745307389.123456, 'Europe/Berlin');
// inner value is '2025-04-22 09:36:29.123456
```

DateTime objects are also accepted:
```php
$dt = new DateTime('December 31,1999');

$ts = new TimeString($dt);
// inner value is '1999-12-31 00:00:00.000000'
```

### Casting and Formatting

A TimeString object can be cast into a string or formatted using
PHP's DateTime interface formats (see https://www.php.net/manual/en/datetime.format.php)

```php
$timeString = new TimeString('1999-12-31 23:59:50.123456');

$timeString->toString() 
// '1999-12-31 23:59:50.123456'

strval($timeString)  
// '1999-12-31 23:59:50.123456'

$timeString->format('Y-M') 
// '1999-12'
```

TimeString objects can be transformed into other types:
```php
$timeString->toTimestamp(); // a float timestamp
$timeString->toDateTime(); // a DateTime object
$timeString->toDateTime('Asia/Tokyo'); // a DateTime object with a given TZ
```

It is also possible to create a new TimeString object in a different timezone:
```php
$newTimestring = $someTimeString->toNewTimeZone($newTimeZone);
```

TimeString objects can be also created from and converted to
compact strings with only numbers:

```php
$timeString = new TimeString('1999-12-31 23:59:50.123456');

$timeString->toCompactString(); 
// '19991231235950123456'
    
$ts = TimeString::fromCompactString('19991231235950123456');
$ts->toString(); 
// '1999-12-31 23:59:50.123456'
```
### Factory Methods

The following factory methods are also provided: 
```php
$ts = TimeString::now();   // the current time in PHP's default timezone
$ts = TimeString::now('Europe/Berlin'); // the current time in Berlin

$ts = TimeString::fromTimestamp(1736341528); // from any int or float timestamp
$ts = TimeString::fromTimestamp(1736341528, 'America/Costa_Rica'); // with time zone
$ts = TimeString::fromString('today'); // from any string that DateTime can parse
$ts = TimeString::fromDateTime($someDateTimeObject);

// or let TimeString figure it out
$ts = TimeString::fromVariable($someVariable); 
```

### Comparison

The class provides comparison functions:
```php
$timeString = new TimeString('1999-12-31 23:59:50.123456');
$timeStringWithSameValue = clone $timeString;
    
TimeString::equals($timestring, $timeStringWithSameValue); 
// true
TimeString::cmp($timestring, $timeStringWithSameValue); 
// 0

$laterTimeString = new TimeString('2000-12-31 23:59:50.123456');

TimeString::equals($timestring, $laterTimeString); 
// false
TimeString::cmp($timestring, $laterTimeString); 
// 1

$earlierTimeString = new TimeString('1998-12-31 23:59:50.123456');

TimeString::equals($timestring, $laterTimeString); 
// false
TimeString::cmp($timestring, $laterTimeString); 
// -1
```


