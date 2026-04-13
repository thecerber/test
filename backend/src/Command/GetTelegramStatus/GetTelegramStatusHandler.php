<?php

declare(strict_types=1);

namespace App\Command\GetTelegramStatus;

use App\Entity\Shop;
use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendLogStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetTelegramStatusHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(GetTelegramStatusCommand $command): GetTelegramStatusResult
    {
        $shop = $this->entityManager->getRepository(Shop::class)->find($command->shopId);
        if (!$shop instanceof Shop) {
            throw new NotFoundHttpException('Shop not found.');
        }

        $integration = $shop->getTelegramIntegration();
        if ($integration === null) {
            return new GetTelegramStatusResult(
                enabled: false,
                chatId: null,
                lastSentAt: null,
                sentCount: 0,
                failedCount: 0,
            );
        }

        $sentCountSince = (new \DateTimeImmutable())->modify('-7 days');

        /** @var array{sentCount: string|null, failedCount: string|null} $counts */
        $counts = $this->entityManager->createQueryBuilder()
            ->select('SUM(CASE WHEN log.status = :sentStatus THEN 1 ELSE 0 END) AS sentCount')
            ->addSelect('SUM(CASE WHEN log.status = :failedStatus THEN 1 ELSE 0 END) AS failedCount')
            ->from(TelegramSendLog::class, 'log')
            ->innerJoin('log.order', 'orders')
            ->where('log.shop = :shop')
            ->andWhere('orders.createdAt >= :since')
            ->setParameter('shop', $shop)
            ->setParameter('since', $sentCountSince)
            ->setParameter('sentStatus', TelegramSendLogStatus::SENT)
            ->setParameter('failedStatus', TelegramSendLogStatus::FAILED)
            ->getQuery()
            ->getSingleResult();

        /** @var TelegramSendLog|null $lastSentLog */
        $lastSentLog = $this->entityManager->createQueryBuilder()
            ->select('log')
            ->from(TelegramSendLog::class, 'log')
            ->where('log.shop = :shop')
            ->andWhere('log.status = :sentStatus')
            ->andWhere('log.sentAt IS NOT NULL')
            ->orderBy('log.sentAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('shop', $shop)
            ->setParameter('sentStatus', TelegramSendLogStatus::SENT)
            ->getQuery()
            ->getOneOrNullResult();

        return new GetTelegramStatusResult(
            enabled: $integration->isEnabled(),
            chatId: $integration->getChatId(),
            lastSentAt: $lastSentLog?->getSentAt(),
            sentCount: (int) ($counts['sentCount'] ?? 0),
            failedCount: (int) ($counts['failedCount'] ?? 0),
        );
    }
}
