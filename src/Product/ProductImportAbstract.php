<?php
declare(strict_types=1);

namespace App\Product;

use App\Entity\Product;

abstract class ProductImportAbstract
{
    private int $minStockAmount = 10;
    private int $minCostAmount = 5;
    private int $maxCostAmount = 1000;
    public const DISCONTINUED_YES = 'yes';

    /**
     * @param float $price
     * @param int $stock
     *
     * @return bool
     */
    protected function isImportRulesCorrect(float $price, int $stock): bool
    {
        return ($price >= $this->minCostAmount
                && $stock >= $this->minStockAmount)
            && ($price <= $this->maxCostAmount);
    }

    /**
     * @param string|null $code
     *
     * @return bool
     */
    protected function isNotNullCodeRow(?string $code): bool
    {
        return $code !== null;
    }

    /**
     * @param string|null $discontinued
     *
     * @return bool
     */
    protected function isDiscontinued(?string $discontinued): bool
    {
        return $discontinued === self::DISCONTINUED_YES;
    }

    /**
     * @param int|null $stock
     *
     * @return int
     */
    protected function getStock(?int $stock): int
    {
        return is_numeric($stock)
            ? (int) $stock
            : 0;
    }

    /**
     * @param float|null $price
     *
     * @return float
     */
    protected function getPrice(?float $price): float
    {
        return is_numeric($price)
            ? (float) $price
            : 0;
    }

    /**
     * @param array $record
     * @param \DateTime $nowDateTime
     *
     * @return Product
     */
    abstract protected function setProduct(array $record, \DateTime $nowDateTime): Product;


    /**
     * @param Product $product
     *
     * @return bool
     */
    abstract protected function checkSuccessProductRow(Product $product): bool;

    /**
     * @param \Iterator $records
     *
     * @return array
     */
    abstract public function parse(\Iterator $records): array;
}
