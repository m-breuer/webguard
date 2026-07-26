<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | External API token abilities
    |--------------------------------------------------------------------------
    |
    | Kept disabled by default so existing personal access tokens retain their
    | current access. Enable only after issuing tokens with external:read and
    | external:write abilities.
    |
    */
    'enforce_token_abilities' => (bool) env('EXTERNAL_API_ENFORCE_TOKEN_ABILITIES', false),
];
