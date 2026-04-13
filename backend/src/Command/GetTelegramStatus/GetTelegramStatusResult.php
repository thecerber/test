<?php

declare(strict_types=1);

namespace App\Command\GetTelegramStatus;

final readonly class GetTelegramStatusResult
{
    public function __construct(
        public bool $enabled,
        public ?string $chatId,
        public ?\DateTimeImmutable $lastSentAt,
        public int $sentCount,
        public int $failedCount,
    ) {
    }
}
