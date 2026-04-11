<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'shops')]
class Shop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\OneToOne(mappedBy: 'shop', targetEntity: TelegramIntegration::class, cascade: ['persist', 'remove'])]
    private ?TelegramIntegration $telegramIntegration = null;

    /** @var Collection<int, Order> */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'shop')]
    private Collection $orders;

    /** @var Collection<int, TelegramSendLog> */
    #[ORM\OneToMany(targetEntity: TelegramSendLog::class, mappedBy: 'shop')]
    private Collection $telegramSendLogs;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->telegramSendLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTelegramIntegration(): ?TelegramIntegration
    {
        return $this->telegramIntegration;
    }

    public function setTelegramIntegration(?TelegramIntegration $telegramIntegration): static
    {
        $this->telegramIntegration = $telegramIntegration;
        if ($telegramIntegration !== null && $telegramIntegration->getShop() !== $this) {
            $telegramIntegration->setShop($this);
        }

        return $this;
    }

    /** @return Collection<int, Order> */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    /** @return Collection<int, TelegramSendLog> */
    public function getTelegramSendLogs(): Collection
    {
        return $this->telegramSendLogs;
    }
}
