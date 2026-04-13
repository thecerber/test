<?php

declare(strict_types=1);

namespace App\Controller\CreateOrder;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateOrderBodyRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $number = '',

        #[Assert\NotNull]
        #[Assert\Type(type: 'numeric')]
        #[Assert\GreaterThan(value: 0)]
        public mixed $total = null,

        #[Assert\NotBlank]
        public string $customerName = '',
    ) {
    }

    public function totalAsDecimalString(): string
    {
        return number_format((float) $this->total, 4, '.', '');
    }
}
