<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Shop;
use App\Entity\TelegramIntegration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ConnectTelegramIntegrationControllerTest extends WebTestCase
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

    public function testCreateNewTelegramIntegration(): void
    {
        $shopId = $this->createShop('Alpha');
        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/telegram/connect', $shopId), [
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => '987654321',
            'enabled' => true,
        ]);

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsInt($response['id']);
        self::assertSame($shopId, $response['shopId']);
        self::assertSame('123456:ABCDEF_1234567890_ABCDEFGHIJKLMN', $response['botToken']);
        self::assertSame('987654321', $response['chatId']);
        self::assertTrue($response['enabled']);
        self::assertNotEmpty($response['createdAt']);
        self::assertNotEmpty($response['updatedAt']);
    }

    public function testRepeatCallUpdatesExistingIntegration(): void
    {
        $shopId = $this->createShop('Beta');
        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/telegram/connect', $shopId), [
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => '111111111',
            'enabled' => true,
        ]);
        self::assertResponseIsSuccessful();
        $first = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        sleep(1);

        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/telegram/connect', $shopId), [
            'botToken' => '654321:ZYXWVUT_0987654321_ZYXWVUTSRQPON',
            'chatId' => '222222222',
            'enabled' => false,
        ]);
        self::assertResponseIsSuccessful();
        $second = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($first['id'], $second['id']);
        self::assertSame($shopId, $second['shopId']);
        self::assertSame('654321:ZYXWVUT_0987654321_ZYXWVUTSRQPON', $second['botToken']);
        self::assertSame('222222222', $second['chatId']);
        self::assertFalse($second['enabled']);
        self::assertSame($first['createdAt'], $second['createdAt']);
        self::assertNotSame($first['updatedAt'], $second['updatedAt']);

        $integration = $this->entityManager->getRepository(TelegramIntegration::class)->findOneBy(['shop' => $shopId]);
        self::assertInstanceOf(TelegramIntegration::class, $integration);
        self::assertSame('654321:ZYXWVUT_0987654321_ZYXWVUTSRQPON', $integration->getBotToken());
        self::assertSame('222222222', $integration->getChatId());
        self::assertFalse($integration->isEnabled());
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testValidationErrorsForInvalidPayload(array $payload): void
    {
        $shopId = $this->createShop('Gamma');
        $this->client->jsonRequest('POST', sprintf('/api/shops/%d/telegram/connect', $shopId), $payload);

        self::assertResponseStatusCodeSame(422);
    }

    public function testReturns404ForUnknownShop(): void
    {
        $this->client->jsonRequest('POST', '/api/shops/999999/telegram/connect', [
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => '987654321',
            'enabled' => true,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    /** @return iterable<string, array{0: array<string, mixed>}> */
    public static function invalidPayloadProvider(): iterable
    {
        yield 'botToken empty' => [[
            'botToken' => '',
            'chatId' => '987654321',
            'enabled' => true,
        ]];

        yield 'botToken invalid format' => [[
            'botToken' => 'invalid-token',
            'chatId' => '987654321',
            'enabled' => true,
        ]];

        yield 'chatId empty' => [[
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => '',
            'enabled' => true,
        ]];

        yield 'chatId invalid format' => [[
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => 'abc123',
            'enabled' => true,
        ]];

        yield 'enabled empty' => [[
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => '987654321',
            'enabled' => null,
        ]];

        yield 'enabled invalid format' => [[
            'botToken' => '123456:ABCDEF_1234567890_ABCDEFGHIJKLMN',
            'chatId' => '987654321',
            'enabled' => 'true',
        ]];

        yield 'all fields missing' => [[
        ]];
    }

    private function createShop(string $name): int
    {
        $shop = (new Shop())->setName($name);
        $this->entityManager->persist($shop);
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
