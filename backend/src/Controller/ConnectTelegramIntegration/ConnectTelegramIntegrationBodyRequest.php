<?php

declare(strict_types=1);

namespace App\Controller\ConnectTelegramIntegration;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ConnectTelegramIntegrationBodyRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+:[A-Za-z0-9_-]{20,}$/')]
        public string $botToken,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^-?\d+$/')]
        public string $chatId,

        #[Assert\NotNull]
        #[Assert\Type(type: 'bool')]
        public bool $enabled,
    ) {
    }
}
