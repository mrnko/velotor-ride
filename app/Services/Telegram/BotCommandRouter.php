<?php

namespace App\Services\Telegram;

use App\Services\Telegram\Commands\AllTimeCommand;
use App\Services\Telegram\Commands\HelpCommand;
use App\Services\Telegram\Commands\MeCommand;
use App\Services\Telegram\Commands\StartCommand;
use App\Services\Telegram\Commands\TelegramCommand;
use App\Services\Telegram\Commands\TopCommand;
use App\Services\Telegram\Commands\WeekCommand;
use App\Services\Telegram\Commands\YearCommand;
use Illuminate\Container\Container;

class BotCommandRouter
{
    /** @var array<string, class-string<TelegramCommand>> */
    private array $commands = [
        'start' => StartCommand::class,
        'help' => HelpCommand::class,
        'me' => MeCommand::class,
        'top' => TopCommand::class,
        'week' => WeekCommand::class,
        'year' => YearCommand::class,
        'alltime' => AllTimeCommand::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function has(string $command): bool
    {
        return isset($this->commands[$command]);
    }

    public function resolve(string $command): ?TelegramCommand
    {
        $class = $this->commands[$command] ?? null;

        return $class ? $this->container->make($class) : null;
    }

    /**
     * Extracts the bare command name from a message like "/me" or
     * "/me@ClubBot" (Telegram appends the bot username in group chats).
     */
    public static function commandNameFromText(string $text): ?string
    {
        if (! preg_match('/^\/([a-z0-9_]+)(?:@\w+)?/i', trim($text), $matches)) {
            return null;
        }

        return mb_strtolower($matches[1]);
    }
}
