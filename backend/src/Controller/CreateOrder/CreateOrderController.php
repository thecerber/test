<?php

declare(strict_types=1);

namespace App\Controller\CreateOrder;

use App\Command\CreateOrder\CreateOrderCommand;
use App\Command\CreateOrder\CreateOrderHandler;
use App\Command\SendOrderTelegramNotification\SendOrderTelegramNotificationCommand;
use App\Command\SendOrderTelegramNotification\SendOrderTelegramNotificationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateOrderController extends AbstractController
{
    #[Route(
        path: '/api/shops/{shopId}/orders',
        name: 'api_shops_orders_create',
        requirements: ['shopId' => '\d+'],
        methods: ['POST'],
    )]
    public function __invoke(
        int $shopId,
        #[MapRequestPayload] CreateOrderBodyRequest $request,
        CreateOrderHandler $createOrderHandler,
        SendOrderTelegramNotificationHandler $sendOrderTelegramNotificationHandler,
    ): JsonResponse {
        $order = $createOrderHandler(new CreateOrderCommand(
            shopId: $shopId,
            number: $request->number,
            total: $request->totalAsDecimalString(),
            customerName: $request->customerName,
        ));

        $telegramStatus = 'skipped';
        $integration = $order->getShop()?->getTelegramIntegration();
        if ($integration !== null && $integration->isEnabled()) {
            $sent = $sendOrderTelegramNotificationHandler(new SendOrderTelegramNotificationCommand(
                shopId: $shopId,
                orderId: (int) $order->getId(),
            ));
            $telegramStatus = $sent ? 'sent' : 'failed';
        }

        return $this->json([
            'id' => $order->getId(),
            'shopId' => $order->getShop()?->getId(),
            'number' => $order->getNumber(),
            'total' => (float) $order->getTotal(),
            'customerName' => $order->getCustomerName(),
            'createdAt' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'telegramStatus' => $telegramStatus,
        ], Response::HTTP_CREATED);
    }
}
