<?php

namespace App\Tests\Service\Utils;

use App\Service\Utils\CsvProductImport;
use App\Tests\BaseTest;
use League\Csv\Reader;

class CsvProductImportTest extends BaseTest
{
    public $csvProductImport;

    public function setUp(): void
    {
        $this->csvProductImport = new CsvProductImport();

    }

    public function testValidExecute(): void
    {
        $csvPath = __DIR__. '/../../Fixtures/stockValidOneRow.csv';
        $reader = Reader::createFromPath($csvPath, 'r');
        $this->invokeMethod($this->csvProductImport, 'initializationCsvLib', [$reader]);
        $records = $reader->getRecords();
        $parseToArray = $this->invokeMethod($this->csvProductImport, 'parseToArray', [$records]);

        $assertResult = [
            'P0001' => [
                'line_id' => 1,
                'code' => 'P0001',
                'name' => 'TV',
                'description' => '32 Tv',
                'stock' => 10,
                'price' => 399.99,
                'discontinued' => false
            ]
        ];

        $this->assertSame($assertResult, $this->csvProductImport->execute($csvPath));
        $this->assertSame($assertResult, $parseToArray);

        $assertHeaderKeys = [
            'code',
            'name',
            'description',
            'stock',
            'price',
            'discontinued'
        ];

        $headerKeys = array_keys($this->csvProductImport->getHeaderFile());
        $this->assertSame($assertHeaderKeys, $headerKeys);
    }

    public function testUpdateRowCountAfterDB(): void
    {
        $this->csvProductImport->updateRowCountByDB(1, 2);

        $this->assertSame(1, $this->csvProductImport->getRowValidCount());
        $this->assertSame(2, $this->csvProductImport->getRowErrorCount());
    }

    public function testRulesImportValid(): void
    {
        $productRowValid = [
            'code' => 'P0001',
            'name' => 'TV',
            'description' => '32 Tv',
            'stock' => 10,
            'price' => 399.99,
            'discontinued' => false
        ];

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$productRowValid]);
        $this->assertSame(true, $isImportRules);
    }

    public function testRulesImportInvalidStock(): void
    {
        $productRowValid = [
            'code' => 'P0001',
            'name' => 'TV',
            'description' => '32 Tv',
            'stock' => 1,
            'price' => 399.99,
            'discontinued' => false
        ];

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$productRowValid]);
        $this->assertSame(false, $isImportRules);
    }

    public function testRulesImportInvalidPriceMax(): void
    {
        $productRowValid = [
            'code' => 'P0001',
            'name' => 'TV',
            'description' => '32 Tv',
            'stock' => 50,
            'price' => 1399.99,
            'discontinued' => false
        ];

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$productRowValid]);
        $this->assertSame(false, $isImportRules);
    }

    public function testRulesImportInvalidPriceMin(): void
    {
        $productRowValid = [
            'code' => 'P0001',
            'name' => 'TV',
            'description' => '32 Tv',
            'stock' => 50,
            'price' => 4.99,
            'discontinued' => false
        ];

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$productRowValid]);
        $this->assertSame(false, $isImportRules);
    }
}