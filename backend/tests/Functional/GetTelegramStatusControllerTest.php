<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Order;
use App\Entity\Shop;
use App\Entity\TelegramIntegration;
use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendLogStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetTelegramStatusControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();

        $this->client = self::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetSchema();
    }

    public function testReturnsStatusWithMaskedChatIdAndWeeklyStats(): void
    {
        $shop = (new Shop())->setName('Omega');
        $integration = (new TelegramIntegration())
            ->setShop($shop)
            ->setBotToken('123456:ABCDEF_1234567890_ABCDEFGHIJKLMN')
            ->setChatId('-1002847391265')
            ->setEnabled(true);

        $this->entityManager->persist($shop);
        $this->entityManager->persist($integration);

        $this->createLog($shop, 'ORD-1', TelegramSendLogStatus::SENT, new \DateTimeImmutable('-1 day'), new \DateTimeImmutable('-1 day +2 minutes'));
        $this->createLog($shop, 'ORD-2', TelegramSendLogStatus::SENT, new \DateTimeImmutable('-6 days'), new \DateTimeImmutable('-6 days +2 minutes'));
        $this->createLog($shop, 'ORD-3', TelegramSendLogStatus::FAILED, new \DateTimeImmutable('-2 days'), null);
        $this->createLog($shop, 'ORD-4', TelegramSendLogStatus::FAILED, new \DateTimeImmutable('-10 days'), null);

        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/api/shops/%d/telegram/status', $shop->getId()));

        self::assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($response['enabled']);
        self::assertSame('-100******1265', $response['chatId']);
        self::assertNotNull($response['lastSentAt']);
        self::assertSame(2, $response['sentCount']);
        self::assertSame(1, $response['failedCount']);
    }

    public function testReturnsZerosWhenIntegrationMissing(): void
    {
        $shop = (new Shop())->setName('No Integration');
        $this->entityManager->persist($shop);
        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/api/shops/%d/telegram/status', $shop->getId()));

        self::assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($response['enabled']);
        self::assertNull($response['chatId']);
        self::assertNull($response['lastSentAt']);
        self::assertSame(0, $response['sentCount']);
        self::assertSame(0, $response['failedCount']);
    }

    public function testReturnsDisabledWhenIntegrationIsTurnedOff(): void
    {
        $shop = (new Shop())->setName('Disabled Integration');
        $integration = (new TelegramIntegration())
            ->setShop($shop)
            ->setBotToken('123456:ABCDEF_1234567890_ABCDEFGHIJKLMN')
            ->setChatId('44556677')
            ->setEnabled(false);

        $this->entityManager->persist($shop);
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/api/shops/%d/telegram/status', $shop->getId()));

        self::assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($response['enabled']);
        self::assertSame('********', $response['chatId']);
        self::assertNull($response['lastSentAt']);
        self::assertSame(0, $response['sentCount']);
        self::assertSame(0, $response['failedCount']);
    }

    public function testReturns404ForUnknownShop(): void
    {
        $this->client->request('GET', '/api/shops/999999/telegram/status');

        self::assertResponseStatusCodeSame(404);
    }

    private function createLog(
        Shop $shop,
        string $orderNumber,
        TelegramSendLogStatus $status,
        \DateTimeImmutable $orderCreatedAt,
        ?\DateTimeImmutable $sentAt,
    ): void {
        $order = (new Order())
            ->setShop($shop)
            ->setNumber($orderNumber)
            ->setTotal('100.0000')
            ->setCustomerName('Customer')
            ->setCreatedAt($orderCreatedAt);

        $log = (new TelegramSendLog())
            ->setShop($shop)
            ->setOrder($order)
            ->setMessage('Telegram message')
            ->setStatus($status)
            ->setSentAt($sentAt)
            ->setError($status === TelegramSendLogStatus::FAILED ? 'Delivery failed' : null);

        $this->entityManager->persist($order);
        $this->entityManager->persist($log);
    }

    private function resetSchema(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
}
