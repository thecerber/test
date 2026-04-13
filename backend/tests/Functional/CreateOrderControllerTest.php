<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Command\SendOrderTelegramNotification\SendOrderTelegramNotificationCommand;
use App\Command\SendOrderTelegramNotification\SendOrderTelegramNotificationHandler;
use App\Entity\Order;
use App\Entity\Shop;
use App\Entity\TelegramIntegration;
use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendLogStatus;
use App\Service\Telegram\TelegramMessageSenderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateOrderControllerTest extends WebTestCase
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

    public function testCreatesOrderAndSkipsTelegramWhenIntegrationMissing(): void
    {
        $shopId = $this->createShop('No Telegram Shop');

        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/orders', $shopId), [
            'number' => 'A-1005',
            'total' => 2490,
            'customerName' => 'Анна',
        ]);

        self::assertResponseStatusCodeSame(201);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('skipped', $response['telegramStatus']);
        self::assertSame('A-1005', $response['number']);
        self::assertSame(2490, $response['total']);
        self::assertSame('Анна', $response['customerName']);
        self::assertCount(1, $this->entityManager->getRepository(Order::class)->findAll());
        self::assertCount(0, $this->entityManager->getRepository(TelegramSendLog::class)->findAll());
    }

    public function testCreatesOrderAndReturnsSentWhenTelegramDelivered(): void
    {
        self::getContainer()->set(TelegramMessageSenderInterface::class, new class () implements TelegramMessageSenderInterface {
            public function sendMessage(string $botToken, string $chatId, string $message): bool
            {
                return true;
            }
        });

        $shopId = $this->createShopWithTelegram('Sent shop', true);

        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/orders', $shopId), [
            'number' => 'A-1006',
            'total' => 1000,
            'customerName' => 'Борис',
        ]);

        self::assertResponseStatusCodeSame(201);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('sent', $response['telegramStatus']);

        /** @var TelegramSendLog $log */
        $log = $this->entityManager->getRepository(TelegramSendLog::class)->findOneBy(['shop' => $shopId]);
        self::assertInstanceOf(TelegramSendLog::class, $log);
        self::assertSame(TelegramSendLogStatus::SENT, $log->getStatus());
        self::assertNotNull($log->getSentAt());
    }

    public function testCreatesOrderAndSkipsTelegramWhenIntegrationDisabled(): void
    {
        $shopId = $this->createShopWithTelegram('Disabled telegram shop', false);

        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/orders', $shopId), [
            'number' => 'A-1008',
            'total' => 1490,
            'customerName' => 'Мария',
        ]);

        self::assertResponseStatusCodeSame(201);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('skipped', $response['telegramStatus']);
        self::assertCount(1, $this->entityManager->getRepository(Order::class)->findAll());
        self::assertCount(0, $this->entityManager->getRepository(TelegramSendLog::class)->findAll());
    }

    public function testTelegramFailureDoesNotBreakOrderCreation(): void
    {
        self::getContainer()->set(TelegramMessageSenderInterface::class, new class () implements TelegramMessageSenderInterface {
            public function sendMessage(string $botToken, string $chatId, string $message): bool
            {
                throw new \RuntimeException('Telegram is unavailable');
            }
        });

        $shopId = $this->createShopWithTelegram('Fail shop', true);

        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/orders', $shopId), [
            'number' => 'A-1007',
            'total' => 500,
            'customerName' => 'Светлана',
        ]);

        self::assertResponseStatusCodeSame(201);
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('failed', $response['telegramStatus']);

        self::assertCount(1, $this->entityManager->getRepository(Order::class)->findAll());
        /** @var TelegramSendLog $log */
        $log = $this->entityManager->getRepository(TelegramSendLog::class)->findOneBy(['shop' => $shopId]);
        self::assertInstanceOf(TelegramSendLog::class, $log);
        self::assertSame(TelegramSendLogStatus::FAILED, $log->getStatus());
        self::assertSame('Telegram is unavailable', $log->getError());
        self::assertNull($log->getSentAt());
    }

    public function testReturns404ForUnknownShop(): void
    {
        $this->client->jsonRequest('POST', '/api/shops/999999/orders', [
            'number' => 'A-1005',
            'total' => 2490,
            'customerName' => 'Анна',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testSendHandlerIsIdempotentByShopAndOrder(): void
    {
        $sender = new class () implements TelegramMessageSenderInterface {
            public int $calls = 0;

            public function sendMessage(string $botToken, string $chatId, string $message): bool
            {
                ++$this->calls;

                return true;
            }
        };
        self::getContainer()->set(TelegramMessageSenderInterface::class, $sender);

        $shop = (new Shop())->setName('Idempotent shop');
        $integration = (new TelegramIntegration())
            ->setShop($shop)
            ->setBotToken('123456:ABCDEF_1234567890_ABCDEFGHIJKLMN')
            ->setChatId('-1001234567890')
            ->setEnabled(true);
        $order = (new Order())
            ->setShop($shop)
            ->setNumber('A-1010')
            ->setTotal('2000.0000')
            ->setCustomerName('Иван');

        $this->entityManager->persist($shop);
        $this->entityManager->persist($integration);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        /** @var SendOrderTelegramNotificationHandler $handler */
        $handler = self::getContainer()->get(SendOrderTelegramNotificationHandler::class);
        $command = new SendOrderTelegramNotificationCommand(
            shopId: (int) $shop->getId(),
            orderId: (int) $order->getId(),
        );

        self::assertTrue($handler($command));
        self::assertTrue($handler($command));
        self::assertSame(1, $sender->calls);
        self::assertCount(1, $this->entityManager->getRepository(TelegramSendLog::class)->findAll());
    }

    private function createShop(string $name): int
    {
        $shop = (new Shop())->setName($name);
        $this->entityManager->persist($shop);
        $this->entityManager->flush();

        return (int) $shop->getId();
    }

    private function createShopWithTelegram(string $name, bool $isEnabled): int
    {
        $shop = (new Shop())->setName($name);
        $integration = (new TelegramIntegration())
            ->setShop($shop)
            ->setBotToken('123456:ABCDEF_1234567890_ABCDEFGHIJKLMN')
            ->setChatId('-1001234567890')
            ->setEnabled($isEnabled);

        $this->entityManager->persist($shop);
        $this->entityManager->persist($integration);
        $this->entityManager->flush();

        return (int) $shop->getId();
    }

    private function resetSchema(): void
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }
}
