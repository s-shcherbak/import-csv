<?php
declare(strict_types=1);

namespace App\Product\Csv;

use App\Entity\Product;
use App\Product\ProductImportAbstract;
use App\Product\SerializeProduct;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductImport extends ProductImportAbstract
{
    private array $headerLabel = [];

    /**
     * @var SerializeProduct
     */
    private $serialize;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        SerializeProduct  $serialize,
        ValidatorInterface $validator
    ) {
        $this->serialize = $serialize;
        $this->validator = $validator;
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
     * @param array $record
     * @param \DateTime $nowDateTime
     *
     * @return Product
     */
    protected function setProduct(array $record, \DateTime $nowDateTime): Product
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

    /**
     * @param Product $product
     *
     * @return bool
     */
    protected function checkSuccessProductRow(Product $product): bool
    {
        /** isImportRulesCorrect - function with logic filtering product and not valid product without code **/
        return $this->isImportRulesCorrect($product->getPrice(), $product->getStock())
            && $this->isNotNullCodeRow($product->getCode())
            && $this->validator->validate($product)->count() === 0;
    }

    /**
     * @param \Iterator $records
     *
     * @return array
     */
    public function parse(\Iterator $records): array
    {
        $rowsSuccess = [];
        $rowsCountSuccess = 0;
        $rowsCountError = 0;
        $nowDateTime = new \DateTime("now");

        /** set settings for normalize object to array */
        $this->serialize->setSerializeSettings();

        foreach ($records as $record) {
            $product = $this->setProduct($record, $nowDateTime);

            $productJson = $this->serialize->getNormalizeProduct($product);

            if ($this->checkSuccessProductRow($product)) {
                $rowsSuccess[$product->getCode()] = $productJson;
                $rowsCountSuccess++;
            } else {
                $rowsCountError++;
            }
        }

        return [$rowsSuccess, $rowsCountSuccess, $rowsCountError];
    }
}
