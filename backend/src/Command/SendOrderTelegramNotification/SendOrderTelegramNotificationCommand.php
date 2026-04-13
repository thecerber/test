<?php

declare(strict_types=1);

namespace App\Command\SendOrderTelegramNotification;

final readonly class SendOrderTelegramNotificationCommand
{
    public function __construct(
        public int $shopId,
        public int $orderId,
    ) {
    }
}
