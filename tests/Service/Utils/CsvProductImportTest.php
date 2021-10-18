<?php
declare(strict_types=1);

namespace App\Tests\Service\Utils;

use App\Product\SerializeProduct;
use App\Product\WriteDbProduct;
use App\Service\Utils\CsvProductImport;
use App\Tests\BaseTest;
use DateTimeInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validation;

class CsvProductImportTest extends BaseTest
{
    public CsvProductImport $csvProductImport;

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
        $serialize = new SerializeProduct();
        $writeDbProduct = new WriteDbProduct($entityManager, $validator, $serialize, $parameterBagDBInterface);

        $this->csvProductImport = new CsvProductImport(
            $parameterBagImportInterface,
            $writeDbProduct,
            $serialize,
            $validator
        );
    }

    public function testValidExecute(): void
    {
        $csvPath = __DIR__. '/../../Fixtures/stockValidOneRow.csv';
        $reader = Reader::createFromPath($csvPath, 'r');
        $this->invokeMethod($this->csvProductImport, 'initializationCsvLib', [$reader]);
        $records = $reader->getRecords();
        $header = $reader->getHeader();
        $outputInterface = new BufferedOutput();
        $parseToArray = $this->invokeMethod($this->csvProductImport, 'execute', [$csvPath, $outputInterface, true]);
        $dateTime = (new \DateTime('now'))->format(DateTimeInterface::RFC3339);
        $assertResult = [
            ['P0001' => [
                'name' => 'TV',
                'description' => '32 Tv',
                'code' => 'P0001',
                'stock' => 10,
                'price' => 399.99,
                'dateAdded' => $dateTime,
                'timestamp' => $dateTime,
                'discontinued' => ''
            ]],
            1,
            0
        ];

        $parseRecords = $this->invokeMethod($this->csvProductImport, 'parse', [$records]);
        $this->assertSame($assertResult, $parseRecords);
        $this->assertSame(true, $parseToArray);
        $this->assertSame(1, $this->csvProductImport->getRowValidCount());
        $this->assertSame(0, $this->csvProductImport->getRowErrorCount());

        $getStock = $this->invokeMethod($this->csvProductImport, 'getStock', ['10']);
        $this->assertSame(10, $getStock);
        $getStock = $this->invokeMethod($this->csvProductImport, 'getStock', ['0']);
        $this->assertSame(0, $getStock);
        $getPrice = $this->invokeMethod($this->csvProductImport, 'getPrice', ['10.30']);
        $this->assertSame(10.3, $getPrice);
        $getPrice = $this->invokeMethod($this->csvProductImport, 'getPrice', ['0']);
        $this->assertSame(0.0, $getPrice);

        $checkSetHeader = $this->invokeMethod($this->csvProductImport, 'setHeaderLabel', [$header]);
        $this->assertSame(true, $checkSetHeader);
    }

    public function testInvalidExecute(): void
    {
        $csvPath = __DIR__. '/../../Fixtures/stockHeaderInvalid.csv';
        $reader = Reader::createFromPath($csvPath, 'r');
        $header = $reader->getHeader();

        $this->assertSame(0, $this->csvProductImport->getRowValidCount());
        $this->assertSame(0, $this->csvProductImport->getRowErrorCount());

        $checkSetHeader = $this->invokeMethod($this->csvProductImport, 'setHeaderLabel', [$header]);
        $this->assertSame(false, $checkSetHeader);
    }

    public function testRulesImportValid(): void
    {
        $price = 399.99;
        $stock = 10;

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$price, $stock]);
        $this->assertSame(true, $isImportRules);
    }

    public function testRulesImportInvalidStockAndPriceMin(): void
    {
        $price = 4.99;
        $stock = 1;

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$price, $stock]);
        $this->assertSame(false, $isImportRules);
    }

    public function testRulesImportInvalidPriceMax(): void
    {
        $price = 1399.99;
        $stock = 50;

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$price, $stock]);
        $this->assertSame(false, $isImportRules);
    }

    public function testRulesImportInvalidPriceMin(): void
    {
        $price = 4.99;
        $stock = 50;

        $isImportRules = $this->invokeMethod($this->csvProductImport, 'isImportRulesCorrect', [$price, $stock]);
        $this->assertSame(true, $isImportRules);
    }
}