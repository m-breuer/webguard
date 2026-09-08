<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SupportedLanguage;

final class FrontendTranslationCatalog
{
    /**
     * @return array{locale: string, fallback_locale: string, messages: array<string, string>}
     */
    public function payload(?string $locale): array
    {
        $language = SupportedLanguage::tryFrom((string) $locale) ?? SupportedLanguage::default();
        $fallbackLanguage = SupportedLanguage::default();
        $fallback = $this->load($fallbackLanguage);

        return [
            'locale' => $language->value,
            'fallback_locale' => $fallbackLanguage->value,
            'messages' => array_replace($fallback, $this->load($language)),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function load(SupportedLanguage $language): array
    {
        /** @var mixed $messages */
        $messages = require lang_path($language->value . '/sveltekit.php');

        if (! is_array($messages)) {
            return [];
        }

        $translations = [];

        foreach ($messages as $group) {
            if (! is_array($group)) {
                continue;
            }

            foreach ($group as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $translations[$key] = $value;
                }
            }
        }

        return $translations;
    }
}
