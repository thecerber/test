<?php

declare(strict_types=1);

namespace App\Command\ConnectTelegramIntegration;

use App\Entity\Shop;
use App\Entity\TelegramIntegration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ConnectTelegramIntegrationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(ConnectTelegramIntegrationCommand $command): TelegramIntegration
    {
        $shop = $this->entityManager->getRepository(Shop::class)->find($command->shopId);
        if (!$shop instanceof Shop) {
            throw new NotFoundHttpException('Shop not found.');
        }

        $telegramIntegration = $shop->getTelegramIntegration();
        if (!$telegramIntegration instanceof TelegramIntegration) {
            $telegramIntegration = (new TelegramIntegration())->setShop($shop);
            $this->entityManager->persist($telegramIntegration);
        }

        $telegramIntegration
            ->setBotToken($command->botToken)
            ->setChatId($command->chatId)
            ->setEnabled($command->enabled)
            ->refreshUpdatedAt();

        $this->entityManager->flush();

        return $telegramIntegration;
    }
}
