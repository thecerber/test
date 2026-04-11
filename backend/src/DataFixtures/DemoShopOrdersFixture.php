<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\Shop;
use App\Entity\TelegramIntegration;
use App\Entity\TelegramSendLog;
use App\Entity\TelegramSendLogStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class DemoShopOrdersFixture extends Fixture
{
    private const ORDER_COUNT = 10;

    /** 70% успешных записей в telegram_send_log */
    private const SUCCESSFUL_SEND_LOG_COUNT = 7;

    public function load(ObjectManager $manager): void
    {
        $shop = (new Shop())->setName('Демо-магазин');

        $telegram = (new TelegramIntegration())
            ->setBotToken('7845123096:AAHqN8vK2pLmR3tYwXzAbCdEfGhIjKlMnOpQr')
            ->setChatId('-1002847391265')
            ->setEnabled(true);
        $shop->setTelegramIntegration($telegram);

        $manager->persist($shop);

        $orders = [];
        for ($i = 1; $i <= self::ORDER_COUNT; $i++) {
            $createdAt = (new \DateTimeImmutable())->modify(sprintf('-%d days', self::ORDER_COUNT - $i));

            $order = (new Order())
                ->setShop($shop)
                ->setNumber(sprintf('ORD-%04d', $i))
                ->setTotal(number_format(100 + $i * 10.5, 4, '.', ''))
                ->setCustomerName(sprintf('Клиент %d', $i))
                ->setCreatedAt($createdAt);

            $manager->persist($order);
            $orders[] = $order;
        }

        foreach ($orders as $index => $order) {
            $log = (new TelegramSendLog())
                ->setShop($shop)
                ->setOrder($order)
                ->setMessage(sprintf('Уведомление Telegram по заказу %s', $order->getNumber()));

            if ($index < self::SUCCESSFUL_SEND_LOG_COUNT) {
                $log->setStatus(TelegramSendLogStatus::SENT);
                $log->setSentAt($order->getCreatedAt()->modify('+1 minute'));
                $log->setError(null);
            } else {
                $log->setStatus(TelegramSendLogStatus::FAILED);
                $log->setSentAt(null);
                $log->setError('Telegram API: Bad Request: chat not found');
            }

            $manager->persist($log);
        }

        $manager->flush();
    }
}
