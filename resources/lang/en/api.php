<?php

declare(strict_types=1);

return [
    'title' => 'API',
    'text' => 'Manage your API keys and access tokens for secure API interactions.',
    'configuration' => [
        'heading' => 'API Configuration',
        'description' => 'Manage your API settings and configurations from this section.',
        'fields' => [
            'token' => 'Your API token',
        ],
        'actions' => [
            'generate_token' => 'Generate token',
            'copy' => 'Copy',
            'revoke_token' => 'Revoke token',
        ],
        'messages' => [
            'copied' => 'API Key copied to clipboard!',
            'tokens_deleted' => 'Tokens deleted successfully.',
            'api_key_confidential_warning' => 'Keep your API key confidential. If you believe your key has been compromised, you can generate a new one.',
        ],
    ],
    'logs' => [
        'title' => 'API Logs',
        'description' => 'View your API usage logs to monitor requests and responses.',
        'fields' => [
            'date' => 'Date',
            'email' => 'Email',
            'endpoint' => 'Endpoint',
        ],
        'messages' => [
            'no_logs' => 'No API logs found.',
        ],
    ],
    'docs' => [
        'heading' => 'API Documentation',
        'description' => 'All available endpoints, authentication, and usage examples.',
        'link' => 'Explore the latest documentation here.',
    ],
];
