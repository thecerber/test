<?php

declare(strict_types=1);

namespace App\Command\SendOrderTelegramNotification;

use App\Entity\Order;
use App\Entity\Shop;
use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendLogStatus;
use App\Service\Telegram\TelegramMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class SendOrderTelegramNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TelegramMessageSenderInterface $messageSender,
    ) {
    }

    public function __invoke(SendOrderTelegramNotificationCommand $command): bool
    {
        $shop = $this->entityManager->getRepository(Shop::class)->find($command->shopId);
        if (!$shop instanceof Shop) {
            throw new NotFoundHttpException('Shop not found.');
        }

        $order = $this->entityManager->getRepository(Order::class)->findOneBy([
            'id' => $command->orderId,
            'shop' => $shop,
        ]);
        if (!$order instanceof Order) {
            throw new NotFoundHttpException('Order not found.');
        }

        $existingLog = $this->entityManager->getRepository(TelegramSendLog::class)->findOneBy([
            'shop' => $shop,
            'order' => $order,
        ]);
        if ($existingLog instanceof TelegramSendLog) {
            return $existingLog->getStatus() === TelegramSendLogStatus::SENT;
        }

        $log = (new TelegramSendLog())
            ->setShop($shop)
            ->setOrder($order)
            ->setMessage(sprintf(
                "Новый заказ %s на сумму %s ₽, клиент\n%s",
                $order->getNumber(),
                $this->formatTotal($order->getTotal()),
                $order->getCustomerName(),
            ));

        $sentSuccessfully = false;
        try {
            $integration = $shop->getTelegramIntegration();
            if ($integration === null) {
                throw new \RuntimeException('Telegram integration not configured.');
            }

            $sentSuccessfully = $this->messageSender->sendMessage(
                botToken: $integration->getBotToken(),
                chatId: $integration->getChatId(),
                message: $log->getMessage(),
            );
        } catch (\Throwable $exception) {
            $log
                ->setStatus(TelegramSendLogStatus::FAILED)
                ->setSentAt(null)
                ->setError($exception->getMessage());

            $this->entityManager->persist($log);
            $this->entityManager->flush();

            return false;
        }

        if ($sentSuccessfully) {
            $log
                ->setStatus(TelegramSendLogStatus::SENT)
                ->setSentAt(new \DateTimeImmutable())
                ->setError(null);
        } else {
            $log
                ->setStatus(TelegramSendLogStatus::FAILED)
                ->setSentAt(null)
                ->setError('Telegram API returned unsuccessful response.');
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $sentSuccessfully;
    }

    private function formatTotal(string $total): string
    {
        return rtrim(rtrim($total, '0'), '.');
    }
}
