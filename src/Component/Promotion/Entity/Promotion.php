<?php

declare(strict_types=1);

namespace App\Component\Promotion\Entity;

class Promotion
{
    public const TYPE_ITEM = 1;
    public const TYPE_ORDER = 2;

    protected int $id;
    protected int $type;
    protected int $percentageDiscount;
    /** @var string[]|null */
    protected ?array $productTypesFilter;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function getPercentageDiscount(): int
    {
        return $this->percentageDiscount;
    }

    public function setPercentageDiscount(int $percentageDiscount): void
    {
        $this->percentageDiscount = $percentageDiscount;
    }

    /**
     * @return string[]|null
     */
    public function getProductTypesFilter(): ?array
    {
        return $this->productTypesFilter;
    }

    /**
     * @param string[]|null $productTypesFilter
     */
    public function setProductTypesFilter(?array $productTypesFilter): void
    {
        $this->productTypesFilter = $productTypesFilter;
    }
}
