<?php

declare(strict_types=1);

namespace App\Service\Telegram;

interface TelegramMessageSenderInterface
{
    public function sendMessage(string $botToken, string $chatId, string $message): bool;
}
