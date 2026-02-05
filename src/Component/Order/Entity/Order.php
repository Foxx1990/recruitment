<?php

declare(strict_types=1);

namespace App\Component\Order\Entity;

use App\Component\OrderItem\Entity\OrderItem;
use App\Component\OrderPromotion\Entity\OrderPromotion;
use App\Component\Promotion\Entity\Promotion;
use App\Component\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Order
{
    protected int $id;
    protected User $user;
    protected int $itemsTotal = 0;
    protected int $adjustmentsTotal = 0;
    protected ?int $taxTotal = null;
    protected ?Promotion $orderPromotion = null;
    /**
     * Items total + adjustments total.
     */
    protected int $total = 0;
    /**
     * @var Collection<array-key, OrderItem>
     */
    protected Collection $items;

    /**
     * @var Collection<array-key, OrderPromotion>
     */
    protected Collection $itemPromotions;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->itemPromotions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getItemsTotal(): int
    {
        return $this->itemsTotal;
    }

    public function setItemsTotal(int $itemsTotal): void
    {
        $this->itemsTotal = $itemsTotal;
    }

    public function getAdjustmentsTotal(): int
    {
        return $this->adjustmentsTotal;
    }

    public function getTaxTotal(): ?int
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(?int $taxTotal): void
    {
        $this->taxTotal = $taxTotal;
    }

    public function getOrderPromotion(): ?Promotion
    {
        return $this->orderPromotion;
    }

    public function setOrderPromotion(?Promotion $orderPromotion): void
    {
        $this->orderPromotion = $orderPromotion;
    }

    public function setAdjustmentsTotal(int $adjustmentsTotal): void
    {
        $this->adjustmentsTotal = $adjustmentsTotal;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    /**
     * @return Collection<array-key, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * @return Collection<array-key, OrderPromotion>
     */
    public function getItemPromotions(): Collection
    {
        return $this->itemPromotions;
    }

    public function addItemPromotion(OrderPromotion $orderPromotion): void
    {
        if ($this->itemPromotions->contains($orderPromotion)) {
            return;
        }

        $this->itemPromotions->add($orderPromotion);
        $orderPromotion->setOrder($this);
    }

    public function clearItems(): void
    {
        $this->items->clear();

        $this->recalculateItemsTotal();
    }

    public function addItem(OrderItem $item): void
    {
        if ($this->hasItem($item)) {
            return;
        }

        $this->items->add($item);
        $item->setOrder($this);

        $this->recalculateItemsTotal();
    }

    public function removeItem(OrderItem $item): void
    {
        if (!$this->hasItem($item)) {
            return;
        }

        $this->items->removeElement($item);
        $item->setOrder(null);

        $this->recalculateItemsTotal();
    }

    public function hasItem(OrderItem $item): bool
    {
        return $this->items->contains($item);
    }

    /**
     * Items total + Adjustments total.
     */
    protected function recalculateTotal(): void
    {
        $this->total = $this->itemsTotal + $this->adjustmentsTotal;

        if ($this->total < 0) {
            $this->total = 0;
        }
    }

    protected function recalculateItemsTotal(): void
    {
        $this->itemsTotal = 0;
        foreach ($this->items as $item) {
            $this->itemsTotal += $item->getTotal();
        }

        $this->recalculateTotal();
    }
}
