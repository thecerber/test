<?php

declare(strict_types=1);

namespace App\Controller\ConnectTelegramIntegration;

use App\Command\ConnectTelegramIntegration\ConnectTelegramIntegrationCommand;
use App\Command\ConnectTelegramIntegration\ConnectTelegramIntegrationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class ConnectTelegramIntegrationController extends AbstractController
{
    #[Route(
        path: '/api/shops/{shopId}/telegram/connect',
        name: 'api_shops_telegram_connect',
        requirements: ['shopId' => '\d+'],
        methods: ['POST'],
    )]
    public function __invoke(
        int $shopId,
        #[MapRequestPayload] ConnectTelegramIntegrationBodyRequest $request,
        ConnectTelegramIntegrationHandler $handler,
    ): JsonResponse {
        $integration = $handler(new ConnectTelegramIntegrationCommand(
            shopId: $shopId,
            botToken: $request->botToken,
            chatId: $request->chatId,
            enabled: $request->isEnabled(),
        ));

        return $this->json([
            'id' => $integration->getId(),
            'shopId' => $integration->getShop()?->getId(),
            'botToken' => $integration->getBotToken(),
            'chatId' => $integration->getChatId(),
            'enabled' => $integration->isEnabled(),
            'createdAt' => $integration->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $integration->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_OK);
    }
}
