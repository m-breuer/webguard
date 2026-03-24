<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;
use RuntimeException;

class LegalController extends Controller
{
    public function imprint(): View
    {
        $imprint = [
            'operator_name' => config('imprint.operator_name'),
            'street' => config('imprint.street'),
            'postal_code' => config('imprint.postal_code'),
            'city' => config('imprint.city'),
            'country' => config('imprint.country'),
            'email' => config('imprint.email'),
            'phone' => config('imprint.phone'),
        ];

        foreach ($imprint as $key => $value) {
            throw_if(blank($value), RuntimeException::class, "Missing required imprint configuration value: {$key}");
        }

        /** @var array{operator_name: string, street: string, postal_code: string, city: string, country: string, email: string, phone: string} $imprint */
        $imprint = array_map(static fn (mixed $value): string => (string) $value, $imprint);

        return view('imprint', [
            'imprint' => [
                ...$imprint,
                'email_payload' => $this->encodeContactPayload($imprint['email']),
                'phone_payload' => $this->encodeContactPayload($imprint['phone']),
            ],
        ]);
    }

    private function encodeContactPayload(string $value): string
    {
        return base64_encode(strrev(str_rot13($value)));
    }
}
