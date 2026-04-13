<?php

declare(strict_types=1);

namespace App\Command\CreateOrder;

final readonly class CreateOrderCommand
{
    public function __construct(
        public int $shopId,
        public string $number,
        public string $total,
        public string $customerName,
    ) {
    }
}
