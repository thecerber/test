<?php

declare(strict_types=1);

namespace App\Service\Telegram;

final class TelegramBotApiMessageSender implements TelegramMessageSenderInterface
{
    public function sendMessage(string $botToken, string $chatId, string $message): bool
    {
        $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);
        $payload = json_encode([
            'chat_id' => $chatId,
            'text' => $message,
        ], JSON_THROW_ON_ERROR);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            throw new \RuntimeException($error['message'] ?? 'Telegram request failed.');
        }

        /** @var array{ok?: bool} $decoded */
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return (bool) ($decoded['ok'] ?? false);
    }
}
