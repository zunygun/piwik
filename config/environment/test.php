<?php

return array(

    'Piwik\Tests\Framework\TestEnvironmentOverrides' => DI\object('Piwik\Tests\Framework\TestEnvironmentOverrides'),

    'Piwik\Config' => DI\object('Piwik\Tests\Framework\Mock\Config')
        ->constructorParameter('overrides', DI\link('Piwik\Tests\Framework\TestEnvironmentOverrides')),

    'Piwik\Plugin\Manager' => DI\object('Piwik\Tests\Framework\Mock\Plugin\Manager')
        ->constructorParameter('config', DI\link('Piwik\Config'))
        ->constructorParameter('overrides', DI\link('Piwik\Tests\Framework\TestEnvironmentOverrides')),

    // Disable logging
    'Psr\Log\LoggerInterface' => DI\object('Psr\Log\NullLogger'),

    'Piwik\Cache\Backend' => function () {
        return \Piwik\Cache::buildBackend('file');
    },
    'cache.eager.cache_id' => 'eagercache-test-',

    // Disable loading core translations
    'Piwik\Translation\Translator' => DI\object()
        ->constructorParameter('directories', array()),

);
