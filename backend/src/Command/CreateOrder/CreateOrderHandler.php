<?php

declare(strict_types=1);

namespace App\Command\CreateOrder;

use App\Entity\Order;
use App\Entity\Shop;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CreateOrderHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(CreateOrderCommand $command): Order
    {
        $shop = $this->entityManager->getRepository(Shop::class)->find($command->shopId);
        if (!$shop instanceof Shop) {
            throw new NotFoundHttpException('Shop not found.');
        }

        $order = (new Order())
            ->setShop($shop)
            ->setNumber($command->number)
            ->setTotal($command->total)
            ->setCustomerName($command->customerName);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
