<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class LocalizedDateTimeDisplayTest extends TestCase
{
    public function test_date_time_component_renders_a_localized_german_timestamp_with_machine_readable_datetime(): void
    {
        app()->setLocale('de');
        $date = Carbon::parse('2027-08-02 16:53:00', 'Europe/Berlin');

        $testResponse = $this->view('components.date-time', ['value' => $date]);

        $testResponse->assertSeeHtml('datetime="2027-08-02T16:53:00+02:00"');
        $testResponse->assertSeeText('02.08.2027 16:53');
    }

    public function test_date_time_component_renders_localized_month_and_year_for_english(): void
    {
        app()->setLocale('en');

        $testResponse = $this->view('components.date-time', [
            'value' => Carbon::parse('2026-01-01 00:00:00', 'Europe/Berlin'),
            'format' => 'month',
        ]);

        $testResponse->assertSeeText('January 2026');
    }
}
