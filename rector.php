<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Exception\Configuration\InvalidConfigurationException;

try {
    return RectorConfig::configure()
        ->withPaths([
            __DIR__ . '/TimeString',
            __DIR__ . '/test',
        ])
        // uncomment to reach your current PHP version
        ->withPhpSets(php83: true)
        ->withAttributesSets(phpunit: true)
        ->withPreparedSets(
            deadCode: true,
            codeQuality: true,
            typeDeclarations: true,
            phpunitCodeQuality: true
        );
} catch (InvalidConfigurationException $e) {
    print "Invalid configuration" . $e->getMessage();
}
