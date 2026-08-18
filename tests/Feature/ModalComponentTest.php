<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ModalComponentTest extends TestCase
{
    public function test_modal_renders_an_accessible_keyboard_scope_contract(): void
    {
        $html = Blade::render('<x-modal name="confirm-delete" focusable><button type="button">Cancel</button></x-modal>');

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
        $this->assertStringContainsString('modal:opened', $html);
        $this->assertStringContainsString('modal:closed', $html);
    }
}
