<?php

declare(strict_types=1);

namespace App\Command\ConnectTelegramIntegration;

final readonly class ConnectTelegramIntegrationCommand
{
    public function __construct(
        public int $shopId,
        public string $botToken,
        public string $chatId,
        public bool $enabled,
    ) {
    }
}
