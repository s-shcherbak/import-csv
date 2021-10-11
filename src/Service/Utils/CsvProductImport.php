<?php
declare(strict_types=1);

namespace App\Service\Utils;

use App\Product\WriteDbProduct;
use App\Product\Csv\ProductImport;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Exception;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class CsvProductImport implements ProductImportInterface
{
    private int $rowsCount = 0;
    private int $rowsValidCount = 0;
    private int $rowsErrorCount = 0;
    private int $csvReaderBatch;
    private $productImport;

    /**
     * @var WriteDbProduct
     */
    private $writeDbProduct;

    public function __construct(
        ParameterBagInterface $params,
        ProductImport $productImport,
        WriteDbProduct $writeDbProduct
    ) {
        $this->csvReaderBatch = $params->get('csv_reader_batch');
        $this->productImport = $productImport;
        $this->writeDbProduct = $writeDbProduct;
    }

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
    public function setRowsCount(int $count): void
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

    private function updateRowsValidCount(int $countInsertProduct): void
    {
        $this->rowsValidCount += $countInsertProduct;
    }

    private function updateRowsErrorCount(int $countErrorProduct): void
    {
        $this->rowsErrorCount += $countErrorProduct;
    }

    private function updateRowCountByDB(int $countErrorProduct): void
    {
        $this->rowsValidCount -= $countErrorProduct;
        $this->rowsErrorCount += $countErrorProduct;
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

            if (!$this->productImport->setHeaderLabel($reader->getHeader())) {
                return false;
            }

            $progressBar = $this->initializationProgressBar($output);

            $offset = 0;
            $stmt = Statement::create()->offset($offset)->limit($this->csvReaderBatch);
            $notFindNewRows = false;
            while (!$notFindNewRows) {
                /* @var $records \Iterator */
                $records = $stmt->process($reader)->getRecords();

                [$rowsSuccess, $rowsCountSuccess, $rowsCountError] = $this->productImport->parse($records);

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
                    $notFindNewRows = true;
                }
            }
            $progressBar->finish();
        }
        return true;
    }
}
