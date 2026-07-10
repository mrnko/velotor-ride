<?php

namespace Tests\Unit\Services\Parsing;

use App\Services\Parsing\ResultParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResultParserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: float}>
     */
    public static function validMessages(): array
    {
        return [
            'результат 10' => ['результат 10', 10.0],
            'результат 10 км' => ['результат 10 км', 10.0],
            'результат 10 километров' => ['результат 10 километров', 10.0],
            'результат 10.5' => ['результат 10.5', 10.5],
            'результат 10,5' => ['результат 10,5', 10.5],
            'result 10' => ['result 10', 10.0],
            '+10 км' => ['+10 км', 10.0],
            'uppercase Результат' => ['Результат 25 км', 25.0],
            'with colon' => ['результат: 12.3 км', 12.3],
            'кілометрів suffix' => ['результат 8 кілометрів', 8.0],
        ];
    }

    #[DataProvider('validMessages')]
    public function test_recognizes_valid_result_formats(string $message, float $expected): void
    {
        $result = (new ResultParser())->parse($message);

        $this->assertTrue($result->matched);
        $this->assertTrue($result->valid);
        $this->assertSame($expected, $result->distanceKm);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function ignoredMessages(): array
    {
        return [
            'normal chat' => ['хто сьогодні їде?'],
            'plain number banter' => ['+1'],
            'emoji only' => ['🚴‍♂️🔥'],
            'question' => ['яка погода буде завтра?'],
            'unrelated command' => ['/somecommand'],
        ];
    }

    #[DataProvider('ignoredMessages')]
    public function test_ignores_unrelated_chat_messages(string $message): void
    {
        $result = (new ResultParser())->parse($message);

        $this->assertFalse($result->matched);
    }

    public function test_rejects_zero_or_negative_distance(): void
    {
        $result = (new ResultParser())->parse('результат 0 км');
        $this->assertTrue($result->matched);
        $this->assertFalse($result->valid);
    }

    public function test_rejects_distance_over_max(): void
    {
        $result = (new ResultParser())->parse('результат 1500 км');
        $this->assertTrue($result->matched);
        $this->assertFalse($result->valid);
    }

    public function test_accepts_distance_at_the_configured_max(): void
    {
        $result = (new ResultParser())->parse('результат 1000 км');
        $this->assertTrue($result->valid);
        $this->assertSame(1000.0, $result->distanceKm);
    }
}
