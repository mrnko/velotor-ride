<?php

namespace Tests\Unit\Support;

use App\Support\DurationFormatter;
use Tests\TestCase;

class DurationFormatterTest extends TestCase
{
    public function test_zero_minutes_reads_as_less_than_a_minute(): void
    {
        $this->assertSame('менше хвилини', DurationFormatter::humanize(0));
    }

    public function test_singular_hour_and_minute_forms(): void
    {
        $this->assertSame('1 година', DurationFormatter::humanize(60));
        $this->assertSame('1 хвилина', DurationFormatter::humanize(1));
    }

    public function test_few_form_for_2_to_4(): void
    {
        $this->assertSame('2 години', DurationFormatter::humanize(120));
        $this->assertSame('3 хвилини', DurationFormatter::humanize(3));
    }

    public function test_many_form_for_5_and_up_including_the_11_to_14_exception(): void
    {
        $this->assertSame('5 годин', DurationFormatter::humanize(300));
        $this->assertSame('12 годин', DurationFormatter::humanize(720));
        $this->assertSame('21 година', DurationFormatter::humanize(1260));
    }

    public function test_combines_hours_and_minutes_under_a_day(): void
    {
        $this->assertSame('1 година 30 хвилин', DurationFormatter::humanize(90));
    }

    public function test_drops_minutes_once_a_full_day_is_spanned(): void
    {
        $this->assertSame('1 день 1 година', DurationFormatter::humanize(1500));
        $this->assertSame('2 дні', DurationFormatter::humanize(2880));
    }
}
