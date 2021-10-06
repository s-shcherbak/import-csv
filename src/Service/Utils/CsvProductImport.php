<?php

declare(strict_types=1);

namespace App\Service\Utils;

use League\Csv\Reader;
use League\Csv\Exception;

class CsvProductImport extends ProductImportAbstract
{

    private int $rowsCount = 0;
    private int $rowsValidCount = 0;
    private int $rowsErrorCount = 0;
    private array $rowsError = [];
    private array $headerFile = [];

    public const DISCONTINUED_YES = 'yes';

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
     * @param void
     *
     * @return int
     */
    public function getRowValidCount(): int
    {
        return $this->rowsValidCount;
    }

    public function updateRowCountByDB(int $countInsertProduct, int $countErrorProduct): bool
    {
        $this->rowsValidCount = $countInsertProduct;
        $this->rowsErrorCount += $countErrorProduct;
        return true;
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
     * @param void
     *
     * @return array
     */
    public function getRowsError(): array
    {
        return $this->rowsError;
    }

    /**
     * @param void
     *
     * @return array
     */
    public function getHeaderFile(): array
    {
        return $this->headerFile;
    }

    /**
     * @param array
     *
     * @return bool
     */
    public function setHeaderFile(array $rowHeader): bool
    {
        if (isset($rowHeader[5])) {
            $this->headerFile = [
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
    private function isNotNullCodeRow(array $rowProduct): bool
    {
        return $rowProduct['code'] !== null;
    }

    private function isDiscontinued(?string $discontinued): bool
    {
        return $discontinued === self::DISCONTINUED_YES;
    }

    /**
     * @param Reader $reader
     *
     * @return void
     */
    private function initializationCsvLib(Reader $reader): void
    {
        $reader->setHeaderOffset(0);
        $reader->includeEmptyRecords();
        $this->setHeaderFile($reader->getHeader());
    }

    /**
     * @param string $csvPath
     *
     * @return array
     */
    public function execute(string $csvPath): array
    {
        $result = [];
        if ($csvPath) {
            $reader = Reader::createFromPath($csvPath, 'r');
            if ($reader->count() > 0) {
                $this->initializationCsvLib($reader);
                if (empty($this->getHeaderFile())) {
                    return [];
                }
                $records = $reader->getRecords();
                $result = $this->parseToArray($records);
            }
        }

        $this->rowsValidCount = count($result);
        $this->rowsErrorCount = $this->rowsCount - count($result);

        return $result;
    }

    /**
     * @param \Iterator $records
     *
     * @return array
     */
    private function parseToArray(\Iterator $records): array
    {
        $result = [];
        foreach ($records as $offset => $record) {
            $this->rowsCount++;
            $rowProduct = [
                'line_id' => $offset,
                'code' => $record[$this->headerFile['code']],
                'name' => $record[$this->headerFile['name']],
                'description' => $record[$this->headerFile['description']],
                'stock' => is_numeric($record[$this->headerFile['stock']])
                    ? (int)$record[$this->headerFile['stock']]
                    : 0,
                'price' => is_numeric($record[$this->headerFile['price']])
                    ? (float)$record[$this->headerFile['price']]
                    : 0,
                'discontinued' => $this->isDiscontinued($record[$this->headerFile['discontinued']]),
            ];

            /** isImportRulesCorrect - function with logic filtering product and not valid product without code **/
            if ($this->isImportRulesCorrect($rowProduct) && $this->isNotNullCodeRow($rowProduct)) {
                $result[$record[$this->headerFile['code']]] = $rowProduct;
            } else {
                $this->rowsError[$record[$this->headerFile['code']]] = $rowProduct;
            }
        }
        return $result;
    }
}