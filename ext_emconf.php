<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Mails to news',
    'description' => 'Creates news from emails',
    'category' => 'plugin',
    'version' => '1.0.1',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'author' => 'Clemens Gogolin',
    'author_email' => 'service@cylancer.net',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.00-13.4.99',
            'news' => '12.3.0-14.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

