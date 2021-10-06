<?php

declare(strict_types=1);

namespace App\Service\Utils;

abstract class ProductImportAbstract
{
    private int $minStockAmount = 10;
    private int $minCostAmount = 5;
    private int $maxCostAmount = 1000;

    /**
     * @param array
     *
     * @return boolean
     */
    protected function isImportRulesCorrect(array $rowProduct): bool
    {
        return ($rowProduct['price'] >= $this->minCostAmount
                && $rowProduct['stock'] >= $this->minStockAmount)
            && ($rowProduct['price'] <= $this->maxCostAmount);
    }

    /**
     * @param string $csvPath
     *
     * @return array
     */
    abstract public function execute(string $csvPath): array;

    /**
     * @param void
     *
     * @return int
     */
    abstract public function getRowValidCount(): int;

    /**
     * @param void
     *
     * @return int
     */
    abstract public function getRowErrorCount(): int;

    /**
     * @param void
     *
     * @return array
     */
    abstract public function getRowsError(): array;

    /**
     * @param void
     *
     * @return int
     */
    abstract public function getRowCount(): int;
}