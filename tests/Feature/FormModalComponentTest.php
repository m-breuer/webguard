<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FormModalComponentTest extends TestCase
{
    public function test_form_modal_renders_accessible_dialog_contract(): void
    {
        $html = Blade::render(
            '<x-form-modal name="monitoring-create" title="Create monitoring" description="Add a service."><form><button type="submit">Save</button></form></x-form-modal>'
        );

        $this->assertStringContainsString('data-form-modal="monitoring-create"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="monitoring-create-title"', $html);
        $this->assertStringContainsString('aria-describedby="monitoring-create-description"', $html);
        $this->assertStringContainsString('open-form-modal', $html);
        $this->assertStringContainsString('close-form-modal', $html);
        $this->assertStringContainsString('max-h-[calc(100vh-3rem)]', $html);
    }

    public function test_form_modal_supports_wider_forms(): void
    {
        $html = Blade::render('<x-form-modal name="monitoring-edit" title="Edit monitoring" max-width="5xl">Form</x-form-modal>');

        $this->assertStringContainsString('sm:max-w-5xl', $html);
    }
}
