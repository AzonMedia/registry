<?php
declare(strict_types=1);

// this file will override the key 'session' in other global php files
return [
    'session' => [
        'key1' => 'new_value1',
        'key2' => 'new_value2',
    ],
];