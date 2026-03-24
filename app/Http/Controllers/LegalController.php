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
            'operator_name' => $this->requiredImprintValue('operator_name'),
            'street' => $this->requiredImprintValue('street'),
            'postal_code' => $this->requiredImprintValue('postal_code'),
            'city' => $this->requiredImprintValue('city'),
            'country' => $this->requiredImprintValue('country'),
            'email' => $this->requiredImprintValue('email'),
            'phone' => $this->requiredImprintValue('phone'),
        ];

        return view('imprint', [
            'imprint' => [
                ...$imprint,
                'email_payload' => $this->encodeContactPayload($imprint['email']),
                'phone_payload' => $this->encodeContactPayload($imprint['phone']),
            ],
        ]);
    }

    public function termsOfUse(): View
    {
        return view('terms-of-use', [
            ...$this->contactRevealPayloads(),
        ]);
    }

    public function gdpr(): View
    {
        return view('gdpr', [
            ...$this->contactRevealPayloads(),
        ]);
    }

    /**
     * @return array{email_payload: string, phone_payload: string}
     */
    private function contactRevealPayloads(): array
    {
        return [
            'email_payload' => $this->encodeContactPayload($this->requiredImprintValue('email')),
            'phone_payload' => $this->encodeContactPayload($this->requiredImprintValue('phone')),
        ];
    }

    private function requiredImprintValue(string $key): string
    {
        $value = config("imprint.{$key}");

        throw_if(blank($value), RuntimeException::class, "Missing required imprint configuration value: {$key}");

        return (string) $value;
    }

    private function encodeContactPayload(string $value): string
    {
        return base64_encode(strrev(str_rot13($value)));
    }
}
