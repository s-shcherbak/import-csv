<?php

declare(strict_types=1);

namespace App\Service\Utils;

use App\Entity\Product;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ProductImportAbstract
{
    private const MIN_AMOUNT = 10;
    private const MIN_PRICE = 5;
    private const MAX_PRICE = 1000;
    protected int $rowsCount = 0;
    protected int $rowsValidCount = 0;
    protected int $rowsErrorCount = 0;

    /**
     * @param void
     *
     * @return int
     */
    public function getRowCount(): int
    {
        return $this->rowsCount;
    }

    /**
     * @param int
     *
     * @return void
     */
    protected function setRowsCount(int $count): void
    {
        $this->rowsCount = $count !== 0 ? $count -1 : 0;
    }

    /**
     * @param void
     *
     * @return int
     */
    public function getRowValidCount(): int
    {
        return $this->rowsValidCount;
    }

    /**
     * @param void
     *
     * @return int
     */
    public function getRowErrorCount(): int
    {
        return $this->rowsErrorCount;
    }

    /**
     * @param float $price
     * @param int $stock
     *
     * @return bool
     */
    protected function isImportRulesCorrect(float $price, int $stock): bool
    {
        return ($price >= self::MIN_PRICE
                || $stock >= self::MIN_AMOUNT)
            && ($price <= self::MAX_PRICE);
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
     * @param string $filePath
     * @param OutputInterface $output
     * @param bool $testMode
     * @return bool
     */

    abstract public function execute(string $filePath, OutputInterface $output, bool $testMode): bool;

    /**
     * @param array $record
     *
     * @return Product
     */
    abstract protected function setProduct(array $record): Product;

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
    abstract protected function parse(\Iterator $records): array;
}