<?php

namespace App\Tests\Service\Utils;

use App\Product\ProductImport;
use App\Product\WriteDbProduct;
use App\Service\Utils\CsvProductImport;
use App\Tests\BaseTest;
use League\Csv\Reader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validation;

class CsvProductImportTest extends BaseTest
{
    public $csvProductImport;
    public $productImport;

    public function setUp(): void
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        $csvReaderBatch = 1000;
        $dbWriterBatch = 100;

        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $validator = Validation::createValidator();

        $parameterBagImportInterface = $this->createMock(ParameterBagInterface::class);
        $parameterBagImportInterface->expects($this->once())
            ->method('get')
            ->willReturn($csvReaderBatch);
        $parameterBagDBInterface = $this->createMock(ParameterBagInterface::class);
        $parameterBagDBInterface->expects($this->once())
            ->method('get')
            ->willReturn($dbWriterBatch);
        $writeDbProduct = new WriteDbProduct($entityManager, $validator, $parameterBagDBInterface);
        $this->productImport = new ProductImport();
        $this->csvProductImport = new CsvProductImport($parameterBagImportInterface, $this->productImport, $writeDbProduct);
    }

    public function testValidExecute(): void
    {
        $csvPath = __DIR__. '/../../Fixtures/stockValidOneRow.csv';
        $reader = Reader::createFromPath($csvPath, 'r');
        $this->invokeMethod($this->csvProductImport, 'initializationCsvLib', [$reader]);
        $records = $reader->getRecords();
        $outputInterface = new BufferedOutput();
        $parseToArray = $this->invokeMethod($this->csvProductImport, 'execute', [$csvPath, $outputInterface, true]);
        $dateTime = new \DateTime('now');
        var_dump($dateTime); ;die();
        $assertResult = [
            ['P0001' => [
                'name' => 'TV',
                'description' => '32 Tv',
                'code' => 'P0001',
                'stock' => 10,
                'price' => '399.99'
            ]],
            1,
            0
        ];

        $this->assertSame($assertResult, $this->productImport->parseCsv($records));
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
       // $this->csvProductImport->updateRowCountByDB(1, 2);

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