<?php

declare(strict_types=1);

namespace App\Command\GetTelegramStatus;

final readonly class GetTelegramStatusCommand
{
    public function __construct(
        public int $shopId,
    ) {
    }
}
