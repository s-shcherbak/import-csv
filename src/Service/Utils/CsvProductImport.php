<?php
declare(strict_types=1);

namespace App\Service\Utils;

use App\Entity\Product;
use App\Product\SerializeProduct;
use App\Product\WriteDbProduct;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvProductImport extends ProductImportAbstract
{
    protected int $rowsCount = 0;
    protected int $rowsValidCount = 0;
    protected int $rowsErrorCount = 0;
    private int $csvReaderBatch;
    private array $headerLabel = [];

    /**
     * @var SerializeProduct
     */
    private SerializeProduct $serialize;

    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * @var WriteDbProduct
     */
    private WriteDbProduct $writeDbProduct;

    public function __construct(
        ParameterBagInterface $params,
        WriteDbProduct $writeDbProduct,
        SerializeProduct  $serialize,
        ValidatorInterface $validator
    ) {
        $this->csvReaderBatch = $params->get('csv_reader_batch');
        $this->writeDbProduct = $writeDbProduct;
        $this->serialize = $serialize;
        $this->validator = $validator;
    }

    protected function updateRowsValidCount(int $countInsertProduct): void
    {
        $this->rowsValidCount += $countInsertProduct;
    }

    protected function updateRowsErrorCount(int $countErrorProduct): void
    {
        $this->rowsErrorCount += $countErrorProduct;
    }

    private function updateRowCountByDB(int $countErrorProduct): void
    {
        $this->rowsValidCount -= $countErrorProduct;
        $this->rowsErrorCount += $countErrorProduct;
    }

    /**
     * @param array
     *
     * @return bool
     */
    private function setHeaderLabel(array $rowHeader): bool
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
     *
     * @return Product
     */
    protected function setProduct(array $record): Product
    {
        $code = $record[$this->headerLabel['code']];
        $name = $record[$this->headerLabel['name']];
        $description = $record[$this->headerLabel['description']];
        $stock = (int) $record[$this->headerLabel['stock']];
        $price = (float) $record[$this->headerLabel['price']];
        $discontinued = $record[$this->headerLabel['discontinued']];

        return new Product(
            $code,
            $name,
            $description,
            $this->getStock($stock),
            $this->getPrice($price),
            $discontinued
        );
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
     * @param Reader $reader
     *
     * @return void
     * @throws Exception
     */
    private function initializationCsvLib(Reader $reader): void
    {
        $reader->setHeaderOffset(0);
        $reader->includeEmptyRecords();
    }

    /**
     * @param OutputInterface $output
     *
     * @return ProgressBar $progressBar
     */
    private function initializationProgressBar(OutputInterface $output): ProgressBar
    {
        /** progress bar for console **/
        $progressBar = new ProgressBar($output, $this->rowsCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        return $progressBar;
    }

    /**
     * @param \Iterator $records
     *
     * @return array
     */
    protected function parse(\Iterator $records): array
    {
        $rowsSuccess = [];
        $rowsCountSuccess = 0;
        $rowsCountError = 0;

        /** set settings for normalize object to array */
        $this->serialize->setSerializeSettings();

        foreach ($records as $record) {
            $product = $this->setProduct($record);

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

    /**
     * @param string $filePath
     * @param OutputInterface $output
     * @param bool $testMode
     * @return bool
     * @throws Exception
     */
    public function execute(string $filePath, OutputInterface $output, bool $testMode = false): bool
    {
        $reader = Reader::createFromPath($filePath, 'r');
        $this->setRowsCount($reader->count());
        if ($this->rowsCount > 0) {
            $this->initializationCsvLib($reader);

            if (empty($reader->getHeader())) {
                return false;
            }

            if (!$this->setHeaderLabel($reader->getHeader())) {
                return false;
            }

            $progressBar = $this->initializationProgressBar($output);

            $offset = 0;
            $stmt = Statement::create()->offset($offset)->limit($this->csvReaderBatch);
            $notFindRows = false;
            while (!$notFindRows) {
                /* @var $records \Iterator */
                $records = $stmt->process($reader)->getRecords();

                [$rowsSuccess, $rowsCountSuccess, $rowsCountError] = $this->parse($records);

                $this->updateRowsValidCount($rowsCountSuccess);
                $this->updateRowsErrorCount($rowsCountError);

                if (!$testMode) {
                    /** write to DB */
                    $countErrorProduct = $this->writeDbProduct->addProducts($rowsSuccess);
                    $this->updateRowCountByDB($countErrorProduct);
                }

                $offset += $this->csvReaderBatch;
                $stmt = $stmt->offset($offset)->limit($this->csvReaderBatch);

                $progressBar->advance($this->csvReaderBatch);

                if ($rowsCountSuccess + $rowsCountError == 0) {
                    $notFindRows = true;
                }
            }
            $progressBar->finish();
        }

        return true;
    }
}
