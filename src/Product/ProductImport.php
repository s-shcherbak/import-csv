<?php

namespace App\Product;

use App\Entity\Product;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ProductImport
{
    private array $headerLabel = [];
    private int $minStockAmount = 10;
    private int $minCostAmount = 5;
    private int $maxCostAmount = 1000;
    public const DISCONTINUED_YES = 'yes';

    /**
     * @param array
     *
     * @return boolean
     */
    protected function isImportRulesCorrect(float $price, int $stock): bool
    {
        return ($price >= $this->minCostAmount
                && $stock >= $this->minStockAmount)
            && ($price <= $this->maxCostAmount);
    }

    /**
     * @param array
     *
     * @return bool
     */
    public function setHeaderLabel(array $rowHeader): bool
    {
        if (isset($rowHeader[5])) {
            $this->headerLabel = [
                'code' => $rowHeader[0],
                'name' => $rowHeader[1],
                'description' => $rowHeader[2],
                'stock' => $rowHeader[3],
                'price' => $rowHeader[4],
                'discontinued' => $rowHeader[5]
            ];
            return true;
        }
        return false;
    }

    /**
     * @param array
     *
     * @return boolean
     */
    private function isNotNullCodeRow(?string $code): bool
    {
        return $code !== null;
    }

    private function isDiscontinued(?string $discontinued): bool
    {
        return $discontinued === self::DISCONTINUED_YES;
    }

    private function getStock(?int $stock): int
    {
        return is_numeric($stock)
            ? (int) $stock
            : 0;
    }

    private function getPrice(?float $price): float
    {
        return is_numeric($price)
            ? (float) $price
            : 0;
    }

    private function setProduct(array $record, \DateTime $nowDateTime): Product
    {
        $code = $record[$this->headerLabel['code']];
        $name = $record[$this->headerLabel['name']];
        $description = $record[$this->headerLabel['description']];
        $stock = (int) $record[$this->headerLabel['stock']];
        $price = (float) $record[$this->headerLabel['price']];
        $discontinued = $record[$this->headerLabel['discontinued']];

        $product = new Product();
        $product->setCode($code)
            ->setName($name)
            ->setDescription($description)
            ->setStock($this->getStock($stock))
            ->setPrice($this->getPrice($price))
            ->setDateAdded($nowDateTime)
            ->setTimestamp($nowDateTime);

        if ($this->isDiscontinued($discontinued)) {
            $product->setDateDiscontinued($nowDateTime);
        }
        return $product;
    }

    public function parseCsv(\Iterator $records): array
    {
        $rowsSuccess = [];
        $rowsCountSuccess = 0;
        $rowsCountError = 0;
        $nowDateTime = new \DateTime("now");

        $encoders = [new JsonEncoder()];
        $normalizers = [
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => \DateTime::RFC3339]),
            new ObjectNormalizer()
        ];
        $serializer = new Serializer($normalizers, $encoders);

        foreach ($records as $offset => $record) {
            $product = $this->setProduct($record, $nowDateTime);

            $productJson = $serializer->normalize($product, 'json', [
                ObjectNormalizer::SKIP_NULL_VALUES => true,
            ]);

            /** isImportRulesCorrect - function with logic filtering product and not valid product without code **/
            if ($this->isImportRulesCorrect($product->getPrice(), $product->getStock())
                && $this->isNotNullCodeRow($product->getCode())
            ) {
                $rowsSuccess[$product->getCode()] = $productJson;
                $rowsCountSuccess++;
            } else {
                $rowsCountError++;
            }
        }

        return [$rowsSuccess, $rowsCountSuccess, $rowsCountError];
    }
}
