<?php

declare(strict_types=1);

return [
    'meta_title' => ':status | :app',
    'eyebrow' => 'WEBGUARD / SYSTEM RESPONSE',
    'status_label' => 'HTTP status',
    'return_hint' => 'The monitoring surface is still here. You can continue from the main workspace.',
    'actions' => [
        'dashboard' => 'Open dashboard',
        'login' => 'Go to login',
    ],
    'status' => [
        '400' => [
            'title' => 'The request could not be processed.',
            'message' => 'WebGuard received an invalid request. Please check the address and try again.',
        ],
        '403' => [
            'title' => 'This area is protected.',
            'message' => 'You do not have permission to access this WebGuard resource.',
        ],
        '404' => [
            'title' => 'This signal was not found.',
            'message' => 'The page may have moved, expired, or never existed at this address.',
        ],
        '419' => [
            'title' => 'Your session has expired.',
            'message' => 'For your security, this request is no longer valid. Please start again.',
        ],
        '429' => [
            'title' => 'Too many requests.',
            'message' => 'WebGuard is protecting this workspace. Please wait a moment and try again.',
        ],
        '500' => [
            'title' => 'Something went wrong on our side.',
            'message' => 'WebGuard could not complete this request. Please try again in a moment.',
        ],
        '503' => [
            'title' => 'WebGuard is temporarily unavailable.',
            'message' => 'The service is being prepared or maintained. Please try again shortly.',
        ],
    ],
];
