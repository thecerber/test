<?php

declare(strict_types=1);

namespace App\Controller\GetTelegramStatus;

use App\Command\GetTelegramStatus\GetTelegramStatusCommand;
use App\Command\GetTelegramStatus\GetTelegramStatusHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetTelegramStatusController extends AbstractController
{
    #[Route(
        path: '/api/shops/{shopId}/telegram/status',
        name: 'api_shops_telegram_status',
        requirements: ['shopId' => '\d+'],
        methods: ['GET'],
    )]
    public function __invoke(
        int $shopId,
        GetTelegramStatusHandler $handler,
    ): JsonResponse {
        $status = $handler(new GetTelegramStatusCommand(
            shopId: $shopId,
        ));

        return $this->json([
            'enabled' => $status->enabled,
            'chatId' => $this->maskChatId($status->chatId),
            'lastSentAt' => $status->lastSentAt?->format(\DateTimeInterface::ATOM),
            'sentCount' => $status->sentCount,
            'failedCount' => $status->failedCount,
        ], Response::HTTP_OK);
    }

    private function maskChatId(?string $chatId): ?string
    {
        if ($chatId === null || $chatId === '') {
            return null;
        }

        $chatIdLength = strlen($chatId);
        if ($chatIdLength <= 8) {
            return str_repeat('*', $chatIdLength);
        }

        return sprintf(
            '%s%s%s',
            substr($chatId, 0, 4),
            str_repeat('*', $chatIdLength - 8),
            substr($chatId, -4),
        );
    }
}
