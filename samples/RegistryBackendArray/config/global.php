<?php
declare(strict_types=1);

return [
    // class configuration values
    MysqlConnection::class => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'user',
        'password' => 'password',
        'database' => 'db',
    ],
    // injecting values to the constructor of a factory
    Connection::class => [
        'class' => ConnectionFactory::class,
        'args' => [
            'sampleString' => 'string',
            'sampleInt' => 17
        ],
    ],
    // injecting constructor values in the class
    SampleClass::class => [
        'class' => SampleClass::class,
        'args' => [
            'sampleString' => 'string',
            'sampleInt' => 17,
        ]
    ],
];