<?php

$config = [
    'cachebuster' => '1',
    'ibkr_accounts' => [
        // Optional: list account IDs here if IBKR's discovery endpoints omit linked accounts.
        // 'U1234567',
    ],
    'database' => [
        'host' => 'host.docker.internal',
        'username' => 'ibkrapp',
        'password' => 'change-me',
        'database' => 'ibkrapp',
    ],
];
