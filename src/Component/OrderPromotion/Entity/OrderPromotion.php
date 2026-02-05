<?php

declare(strict_types=1);

namespace App\Component\OrderPromotion\Entity;

use App\Component\Order\Entity\Order;
use App\Component\Promotion\Entity\Promotion;

class OrderPromotion
{
    protected int $id;
    protected ?Order $order;
    protected Promotion $promotion;
    protected int $position;

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getPromotion(): Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(Promotion $promotion): void
    {
        $this->promotion = $promotion;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}
