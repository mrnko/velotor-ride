<?php

namespace Tests\Unit\Services\Torcoins;

use App\Services\Torcoins\TorcoinCalculator;
use Tests\TestCase;

class TorcoinCalculatorTest extends TestCase
{
    public function test_below_100km_yields_zero_coins(): void
    {
        $this->assertSame(0, TorcoinCalculator::fromDistance(99));
    }

    public function test_exactly_100km_yields_one_coin(): void
    {
        $this->assertSame(1, TorcoinCalculator::fromDistance(100));
    }

    public function test_250km_yields_two_coins(): void
    {
        $this->assertSame(2, TorcoinCalculator::fromDistance(250));
    }

    public function test_999km_yields_nine_coins(): void
    {
        $this->assertSame(9, TorcoinCalculator::fromDistance(999));
    }

    public function test_km_to_next_coin(): void
    {
        $this->assertSame(25.0, TorcoinCalculator::kmToNextCoin(75));
        $this->assertSame(100.0, TorcoinCalculator::kmToNextCoin(100));
    }

    public function test_progress_percent(): void
    {
        $this->assertSame(75.0, TorcoinCalculator::progressPercent(75));
        $this->assertSame(0.0, TorcoinCalculator::progressPercent(100));
    }
}
